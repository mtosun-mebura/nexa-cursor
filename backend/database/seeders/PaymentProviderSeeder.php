<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentProvider;
use Illuminate\Support\Facades\Crypt;

class PaymentProviderSeeder extends Seeder
{
    public function run(): void
    {
        // Mollie Provider
        PaymentProvider::create([
            'name' => 'Mollie Test',
            'provider_type' => 'mollie',
            'is_active' => true,
            'config' => [
                'api_key' => Crypt::encryptString('test_1234567890abcdef'),
                'api_secret' => null,
                'webhook_url' => 'https://example.com/webhooks/mollie',
                'test_mode' => true,
                'description' => 'Mollie betalingsprovider voor test doeleinden'
            ]
        ]);

        // Stripe Provider
        PaymentProvider::create([
            'name' => 'Stripe Test',
            'provider_type' => 'stripe',
            'is_active' => false,
            'config' => [
                'api_key' => Crypt::encryptString('sk_test_1234567890abcdef'),
                'api_secret' => null,
                'webhook_url' => 'https://example.com/webhooks/stripe',
                'test_mode' => true,
                'description' => 'Stripe betalingsprovider voor test doeleinden'
            ]
        ]);

        // PayPal Provider
        PaymentProvider::create([
            'name' => 'PayPal Test',
            'provider_type' => 'paypal',
            'is_active' => false,
            'config' => [
                'api_key' => Crypt::encryptString('test_client_id_123456'),
                'api_secret' => Crypt::encryptString('test_client_secret_123456'),
                'webhook_url' => 'https://example.com/webhooks/paypal',
                'test_mode' => true,
                'description' => 'PayPal betalingsprovider voor test doeleinden'
            ]
        ]);

        // Adyen Provider
        PaymentProvider::create([
            'name' => 'Adyen Test',
            'provider_type' => 'adyen',
            'is_active' => false,
            'config' => [
                'api_key' => Crypt::encryptString('test_adyen_api_key_123456'),
                'api_secret' => null,
                'webhook_url' => 'https://example.com/webhooks/adyen',
                'test_mode' => true,
                'description' => 'Adyen betalingsprovider voor test doeleinden'
            ]
        ]);
    }
}
