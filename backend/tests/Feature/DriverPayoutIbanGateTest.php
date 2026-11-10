<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PayoutIdentity;
use App\Models\PayoutIdentityAuditLog;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Api\DriverAuthController;
use App\Modules\NexaTaxi\Controllers\Api\DriverAvailabilityController;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Services\MarketplaceCompanyRegistrationService;
use App\Services\ModuleDatabaseService;
use App\Services\Payout\PayoutIdentityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DriverPayoutIbanGateTest extends TestCase
{
    private string $conn = 'module_taxi';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection($this->conn)->create('driver_availability', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_id')->primary();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->boolean('is_online')->default(false);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    #[Test]
    public function driver_cannot_go_online_without_an_iban(): void
    {
        [$company, $driver] = $this->companyAndDriver();

        $response = $this->availability($driver, $company, ['is_online' => true]);

        $this->assertSame(422, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertSame('iban_required', $payload['code'] ?? null);
        $this->assertSame(PayoutIdentityService::DRIVER_IBAN_REQUIRED_MESSAGE, $payload['message'] ?? null);
        $this->assertFalse(
            (bool) DriverAvailability::on($this->conn)->where('driver_id', $driver->id)->value('is_online')
        );
    }

    #[Test]
    public function driver_can_go_offline_without_an_iban_and_online_after_a_valid_iban(): void
    {
        [$company, $driver] = $this->companyAndDriver();

        $offline = $this->availability($driver, $company, ['is_online' => false]);
        $this->assertSame(200, $offline->getStatusCode());
        $this->assertFalse($offline->getData(true)['data']['is_online']);

        app(PayoutIdentityService::class)->setDriverIban($driver, 'NL91 ABNA 0417 1643 00');

        $online = $this->availability($driver, $company, ['is_online' => true]);
        $this->assertSame(200, $online->getStatusCode());
        $this->assertTrue($online->getData(true)['data']['is_online']);
    }

    #[Test]
    public function profile_api_stores_iban_and_returns_only_the_last_four(): void
    {
        [$company, $driver] = $this->companyAndDriver();
        $request = Request::create('/api/taxi/v1/driver/payout-iban', 'PUT', [
            'iban' => 'NL91 ABNA 0417 1643 00',
        ]);
        $request->setUserResolver(fn () => $driver);

        $response = app(DriverAuthController::class)->updatePayoutIban($request, app(PayoutIdentityService::class));
        $payload = $response->getData(true);
        $encoded = json_encode($payload);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('************4300', $payload['data']['masked_iban'] ?? null);
        $this->assertStringNotContainsString('NL91ABNA0417164300', (string) $encoded);
        $this->assertStringNotContainsString('NL91 ABNA', (string) $encoded);

        $identity = PayoutIdentity::query()->where('user_id', $driver->id)->first();
        $this->assertNotNull($identity);
        $this->assertSame('NL91ABNA0417164300', $identity->iban_encrypted);
        $this->assertSame('************4300', $identity->masked_destination);
        $this->assertStringNotContainsString('NL91ABNA0417164300', json_encode($identity->toArray()));

        $raw = (string) DB::table('payout_identities')->where('id', $identity->id)->value('iban_encrypted');
        $this->assertStringNotContainsString('NL91ABNA0417164300', $raw);

        $audit = PayoutIdentityAuditLog::query()->where('action', 'driver_iban_set')->first();
        $this->assertNotNull($audit);
        $this->assertStringNotContainsString('NL91', json_encode($audit->payload));

        $meRequest = Request::create('/api/taxi/v1/driver/me', 'GET');
        $meRequest->setUserResolver(fn () => $driver);
        $meRequest->attributes->set('taxi_company_id', $company->id);
        $me = app(DriverAuthController::class)->me(
            $meRequest,
            app(ModuleDatabaseService::class),
            app(\App\Modules\NexaTaxi\Services\TaxiDriverEarningsAccessService::class)
        );
        $mePayload = $me->getData(true);
        $this->assertSame('************4300', $mePayload['user']['payout_iban_masked'] ?? null);
        $this->assertStringNotContainsString('NL91ABNA0417164300', (string) json_encode($mePayload));
    }

    #[Test]
    public function marketplace_registration_rejects_a_missing_iban(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Dit is nodig voor de uitbetaling van de ritten.', false);

        $this->postJson(route('admin.login.marketplace-register'), [
            'company_name' => 'Taxi Zonder Iban',
            'email' => 'zonder-iban@taxi.test',
            'phone' => '0612345678',
            'city' => 'Amsterdam',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['iban']);

        $this->assertDatabaseMissing('companies', ['email' => 'zonder-iban@taxi.test']);
    }

    #[Test]
    public function marketplace_registration_stores_a_valid_iban_as_a_mask(): void
    {
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => MarketplaceCompanyRegistrationService::ROLE, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => MarketplaceCompanyRegistrationService::ROLE, 'guard_name' => 'api']);

        $result = app(MarketplaceCompanyRegistrationService::class)->register([
            'company_name' => 'Taxi Met Iban',
            'email' => 'met-iban@taxi.test',
            'phone' => '0612345678',
            'city' => 'Amsterdam',
            'iban' => 'NL91 ABNA 0417 1643 00',
        ], '127.0.0.1', false);

        $companyIdentity = PayoutIdentity::query()
            ->where('company_id', $result['company']->id)
            ->where('settlement_party', PayoutIdentity::PARTY_COMPANY)
            ->first();
        $driverIdentity = PayoutIdentity::query()
            ->where('user_id', $result['user']->id)
            ->where('settlement_party', PayoutIdentity::PARTY_INDEPENDENT_DRIVER)
            ->first();

        $this->assertNotNull($companyIdentity);
        $this->assertNotNull($driverIdentity);
        $this->assertSame('************4300', $companyIdentity->masked_destination);
        $this->assertSame('************4300', $driverIdentity->masked_destination);
        $this->assertSame('NL91ABNA0417164300', $companyIdentity->iban_encrypted);
        $this->assertSame('NL91ABNA0417164300', $driverIdentity->iban_encrypted);
        $this->assertFalse($driverIdentity->isEnabled());

        try {
            app(MarketplaceCompanyRegistrationService::class)->register([
                'company_name' => 'Taxi Leeg',
                'email' => 'leeg-iban@taxi.test',
                'phone' => '0612345678',
                'city' => 'Utrecht',
            ], '127.0.0.1', false);
            $this->fail('Registratie zonder IBAN hoort te falen.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('iban', $e->errors());
        }
    }

    /**
     * @return array{0: Company, 1: User}
     */
    private function companyAndDriver(): array
    {
        $company = Company::query()->create(['name' => 'Iban Gate Taxi', 'is_active' => true]);
        $driver = User::factory()->create(['company_id' => $company->id]);

        return [$company, $driver];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function availability(User $driver, Company $company, array $payload): \Illuminate\Http\JsonResponse
    {
        $request = Request::create('/api/taxi/v1/driver/availability', 'PUT', $payload);
        $request->setUserResolver(fn () => $driver);
        $request->attributes->set('taxi_company_id', $company->id);

        return app(DriverAvailabilityController::class)->update($request, app(ModuleDatabaseService::class));
    }
}
