<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\MarketplaceCompanyRegistrationService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MarketplaceCompanyRegistrationDuplicateTest extends TestCase
{
    private const VALID_IBAN = 'NL91ABNA0417164300';

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => MarketplaceCompanyRegistrationService::ROLE, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => MarketplaceCompanyRegistrationService::ROLE, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function marketplace_registration_succeeds_for_unique_company(): void
    {
        $result = $this->registration()->register($this->payload([
            'company_name' => 'Unieke Taxi BV',
            'email' => 'uniek@taxi.test',
            'city' => 'Rotterdam',
        ]), '127.0.0.1', false);

        $this->assertSame('Unieke Taxi BV', $result['company']->name);
        $this->assertSame('uniek@taxi.test', $result['company']->email);
        $this->assertSame('Rotterdam', $result['company']->city);
        $this->assertSame(MarketplaceCompanyRegistrationService::PACKAGE_KEY, $result['company']->package_key);
    }

    #[Test]
    public function marketplace_registration_rejects_duplicate_email_on_user(): void
    {
        User::factory()->create(['email' => 'bestaand@taxi.test']);

        try {
            $this->registration()->register($this->payload([
                'email' => 'Bestaand@Taxi.Test',
                'company_name' => 'Ander Bedrijf',
                'city' => 'Utrecht',
            ]), '127.0.0.1', false);
            $this->fail('Dubbele e-mail hoort te falen.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
            $this->assertStringContainsString('al in gebruik', $e->errors()['email'][0]);
        }
    }

    #[Test]
    public function marketplace_registration_rejects_duplicate_email_on_company(): void
    {
        Company::query()->create([
            'name' => 'Bestaand Bedrijf',
            'email' => 'bedrijf@taxi.test',
            'city' => 'Den Haag',
            'is_active' => true,
        ]);

        try {
            $this->registration()->register($this->payload([
                'email' => 'BEDRIJF@taxi.test',
                'company_name' => 'Nieuw Bedrijf',
                'city' => 'Leiden',
            ]), '127.0.0.1', false);
            $this->fail('Dubbele bedrijfs-e-mail hoort te falen.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
            $this->assertStringContainsString('e-mailadres', $e->errors()['email'][0]);
        }
    }

    #[Test]
    public function marketplace_registration_rejects_same_normalized_name_and_city(): void
    {
        Company::query()->create([
            'name' => 'Taxi Dedup B.V.',
            'email' => 'dedup-bestaand@taxi.test',
            'city' => 'Amsterdam',
            'is_active' => true,
            'package_key' => MarketplaceCompanyRegistrationService::PACKAGE_KEY,
        ]);

        try {
            $this->registration()->register($this->payload([
                'company_name' => 'taxi  dedup bv',
                'email' => 'dedup-nieuw@taxi.test',
                'city' => '  Amsterdam  ',
            ]), '127.0.0.1', false);
            $this->fail('Zelfde naam+plaats hoort te falen.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('company_name', $e->errors());
            $this->assertArrayHasKey('city', $e->errors());
            $this->assertStringContainsString('deze naam in deze plaats', $e->errors()['company_name'][0]);
        }

        $this->assertDatabaseMissing('companies', ['email' => 'dedup-nieuw@taxi.test']);
    }

    #[Test]
    public function marketplace_registration_allows_same_name_in_different_city(): void
    {
        Company::query()->create([
            'name' => 'Taxi Stad BV',
            'email' => 'stad-ams@taxi.test',
            'city' => 'Amsterdam',
            'is_active' => true,
            'package_key' => MarketplaceCompanyRegistrationService::PACKAGE_KEY,
        ]);

        $result = $this->registration()->register($this->payload([
            'company_name' => 'Taxi Stad BV',
            'email' => 'stad-utr@taxi.test',
            'city' => 'Utrecht',
        ]), '127.0.0.1', false);

        $this->assertSame('Utrecht', $result['company']->city);
        $this->assertSame('stad-utr@taxi.test', $result['company']->email);
    }

    #[Test]
    public function marketplace_registration_rejects_duplicate_kvk_when_provided(): void
    {
        Company::query()->create([
            'name' => 'KvK Bestaand',
            'email' => 'kvk-bestaand@taxi.test',
            'city' => 'Eindhoven',
            'kvk_number' => '12345678',
            'is_active' => true,
        ]);

        try {
            $this->registration()->register($this->payload([
                'company_name' => 'KvK Nieuw',
                'email' => 'kvk-nieuw@taxi.test',
                'city' => 'Tilburg',
                'kvk_number' => '1234-5678',
            ]), '127.0.0.1', false);
            $this->fail('Dubbel KVK-nummer hoort te falen.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('kvk_number', $e->errors());
            $this->assertStringContainsString('KVK-nummer', $e->errors()['kvk_number'][0]);
        }
    }

    #[Test]
    public function marketplace_registration_stores_valid_kvk_when_provided(): void
    {
        $result = $this->registration()->register($this->payload([
            'company_name' => 'Taxi Met KvK',
            'email' => 'met-kvk@taxi.test',
            'city' => 'Groningen',
            'kvk_number' => '87654321',
        ]), '127.0.0.1', false);

        $this->assertSame('87654321', $result['company']->kvk_number);
    }

    private function registration(): MarketplaceCompanyRegistrationService
    {
        return app(MarketplaceCompanyRegistrationService::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Test Taxi BV',
            'email' => 'test-markt@taxi.test',
            'phone' => '0612345678',
            'city' => 'Amsterdam',
            'iban' => self::VALID_IBAN,
        ], $overrides);
    }
}
