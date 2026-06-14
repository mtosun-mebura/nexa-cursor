<?php

namespace Tests\Unit;

use App\Models\PaymentProvider;
use App\Services\PaymentProviderService;
use Database\Seeders\PaymentProviderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PaymentProviderSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_does_not_overwrite_existing_mollie_api_key_on_rerun(): void
    {
        $customKey = 'live_dHarccVRALvMa0OgWgqkPfPJ5zqGv';

        PaymentProvider::query()->create([
            'name' => 'Mollie productie',
            'provider_type' => 'mollie',
            'company_id' => null,
            'is_active' => true,
            'config' => [
                'api_key' => Crypt::encryptString($customKey),
                'test_mode' => false,
            ],
        ]);

        $this->seed(PaymentProviderSeeder::class);

        $provider = PaymentProvider::query()
            ->where('provider_type', 'mollie')
            ->whereNull('company_id')
            ->first();

        $this->assertNotNull($provider);
        $this->assertSame($customKey, app(PaymentProviderService::class)->getDecryptedApiKey($provider));
        $this->assertSame('Mollie productie', $provider->name);
    }

    public function test_seeder_does_not_overwrite_tenant_scoped_mollie_provider(): void
    {
        $tenantKey = 'live_tenantSpecificKey1234567890ab';

        PaymentProvider::query()->create([
            'name' => 'Mollie tenant',
            'provider_type' => 'mollie',
            'company_id' => 42,
            'is_active' => true,
            'config' => [
                'api_key' => Crypt::encryptString($tenantKey),
                'test_mode' => false,
            ],
        ]);

        $this->seed(PaymentProviderSeeder::class);

        $tenantProvider = PaymentProvider::query()
            ->where('provider_type', 'mollie')
            ->where('company_id', 42)
            ->first();

        $this->assertNotNull($tenantProvider);
        $this->assertSame($tenantKey, app(PaymentProviderService::class)->getDecryptedApiKey($tenantProvider));
    }

    public function test_seeder_creates_default_mollie_when_missing(): void
    {
        $this->seed(PaymentProviderSeeder::class);

        $provider = PaymentProvider::query()
            ->where('provider_type', 'mollie')
            ->whereNull('company_id')
            ->first();

        $this->assertNotNull($provider);
        $this->assertSame('test_1234567890abcdef', app(PaymentProviderService::class)->getDecryptedApiKey($provider));
    }
}
