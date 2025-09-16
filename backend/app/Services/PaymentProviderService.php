<?php

namespace App\Services;

use App\Models\PaymentProvider;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class PaymentProviderService
{
    public function getActiveProviders()
    {
        return PaymentProvider::where('is_active', true)->get();
    }

    public function getProviderByType($type)
    {
        return PaymentProvider::where('provider_type', $type)
            ->where('is_active', true)
            ->first();
    }

    public function testConnection(PaymentProvider $provider)
    {
        try {
            $apiKey = Crypt::decryptString($provider->getConfigValue('api_key'));
            
            switch ($provider->provider_type) {
                case 'mollie':
                    return $this->testMollieConnection($apiKey);
                case 'stripe':
                    return $this->testStripeConnection($apiKey);
                case 'paypal':
                    return $this->testPayPalConnection($apiKey, $provider->getConfigValue('api_secret'));
                case 'adyen':
                    return $this->testAdyenConnection($apiKey);
                default:
                    return ['success' => false, 'message' => 'Provider type niet ondersteund'];
            }
        } catch (\Exception $e) {
            Log::error('Payment provider connection test failed', [
                'provider_id' => $provider->id,
                'provider_type' => $provider->provider_type,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'message' => 'Fout bij testen van verbinding: ' . $e->getMessage()];
        }
    }

    private function testMollieConnection($apiKey)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://api.mollie.com/v2/methods', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json'
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                return ['success' => true, 'message' => 'Mollie verbinding succesvol getest'];
            } else {
                return ['success' => false, 'message' => 'Mollie API reageerde met status: ' . $response->getStatusCode()];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Mollie verbinding mislukt: ' . $e->getMessage()];
        }
    }

    private function testStripeConnection($apiKey)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://api.stripe.com/v1/account', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                return ['success' => true, 'message' => 'Stripe verbinding succesvol getest'];
            } else {
                return ['success' => false, 'message' => 'Stripe API reageerde met status: ' . $response->getStatusCode()];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Stripe verbinding mislukt: ' . $e->getMessage()];
        }
    }

    private function testPayPalConnection($clientId, $clientSecret)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://api-m.sandbox.paypal.com/v1/oauth2/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'grant_type' => 'client_credentials'
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                return ['success' => true, 'message' => 'PayPal verbinding succesvol getest'];
            } else {
                return ['success' => false, 'message' => 'PayPal API reageerde met status: ' . $response->getStatusCode()];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'PayPal verbinding mislukt: ' . $e->getMessage()];
        }
    }

    private function testAdyenConnection($apiKey)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://checkout-test.adyen.com/v70/account', [
                'headers' => [
                    'X-API-Key' => $apiKey,
                    'Content-Type' => 'application/json'
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                return ['success' => true, 'message' => 'Adyen verbinding succesvol getest'];
            } else {
                return ['success' => false, 'message' => 'Adyen API reageerde met status: ' . $response->getStatusCode()];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Adyen verbinding mislukt: ' . $e->getMessage()];
        }
    }

    public function createPayment($provider, $amount, $currency = 'EUR', $description = '', $metadata = [])
    {
        try {
            $apiKey = Crypt::decryptString($provider->getConfigValue('api_key'));
            
            switch ($provider->provider_type) {
                case 'mollie':
                    return $this->createMolliePayment($apiKey, $amount, $currency, $description, $metadata);
                case 'stripe':
                    return $this->createStripePayment($apiKey, $amount, $currency, $description, $metadata);
                default:
                    throw new \Exception('Provider type niet ondersteund voor betalingen');
            }
        } catch (\Exception $e) {
            Log::error('Payment creation failed', [
                'provider_id' => $provider->id,
                'provider_type' => $provider->provider_type,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    private function createMolliePayment($apiKey, $amount, $currency, $description, $metadata)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->post('https://api.mollie.com/v2/payments', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'amount' => [
                    'currency' => $currency,
                    'value' => number_format($amount, 2, '.', '')
                ],
                'description' => $description,
                'redirectUrl' => $metadata['redirect_url'] ?? 'https://example.com/return',
                'webhookUrl' => $metadata['webhook_url'] ?? null,
                'metadata' => $metadata
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    private function createStripePayment($apiKey, $amount, $currency, $description, $metadata)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->post('https://api.stripe.com/v1/payment_intents', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'amount' => (int)($amount * 100), // Stripe expects cents
                'currency' => strtolower($currency),
                'description' => $description,
                'metadata' => json_encode($metadata)
            ]
        ]);

        return json_decode($response->getBody(), true);
    }
}
