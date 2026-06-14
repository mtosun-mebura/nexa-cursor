<?php

namespace Database\Seeders;

use App\Models\PaymentProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

class PaymentProviderSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'name' => 'Mollie Test',
                'provider_type' => 'mollie',
                'is_active' => true,
                'config' => [
                    'api_key' => Crypt::encryptString('test_1234567890abcdef'),
                    'api_secret' => null,
                    'webhook_url' => 'https://example.com/webhooks/mollie',
                    'test_mode' => true,
                    'description' => 'Mollie betalingsprovider voor test doeleinden',
                ],
            ],
            [
                'name' => 'Stripe Test',
                'provider_type' => 'stripe',
                'is_active' => false,
                'config' => [
                    'api_key' => Crypt::encryptString('sk_test_1234567890abcdef'),
                    'api_secret' => null,
                    'webhook_url' => 'https://example.com/webhooks/stripe',
                    'test_mode' => true,
                    'description' => 'Stripe betalingsprovider voor test doeleinden',
                ],
            ],
            [
                'name' => 'PayPal Test',
                'provider_type' => 'paypal',
                'is_active' => false,
                'config' => [
                    'api_key' => Crypt::encryptString('test_client_id_123456'),
                    'api_secret' => Crypt::encryptString('test_client_secret_123456'),
                    'webhook_url' => 'https://example.com/webhooks/paypal',
                    'test_mode' => true,
                    'description' => 'PayPal betalingsprovider voor test doeleinden',
                ],
            ],
            [
                'name' => 'Adyen Test',
                'provider_type' => 'adyen',
                'is_active' => false,
                'config' => [
                    'api_key' => Crypt::encryptString('test_adyen_api_key_123456'),
                    'api_secret' => null,
                    'webhook_url' => 'https://example.com/webhooks/adyen',
                    'test_mode' => true,
                    'description' => 'Adyen betalingsprovider voor test doeleinden',
                ],
            ],
        ];

        foreach ($defaults as $row) {
            $type = $row['provider_type'];
            // Alleen defaults aanmaken als er nog geen globale provider (company_id null) bestaat.
            // updateOrCreate overschrijft bij elke deploy de in admin ingestelde API-keys — dat mag niet.
            PaymentProvider::firstOrCreate(
                ['provider_type' => $type, 'company_id' => null],
                [
                    'name' => $row['name'],
                    'is_active' => $row['is_active'],
                    'config' => $row['config'],
                ]
            );
        }
    }
}
