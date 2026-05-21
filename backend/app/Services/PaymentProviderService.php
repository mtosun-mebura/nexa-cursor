<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\PaymentProvider;
use App\Support\Tenancy\CentralDomains;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class PaymentProviderService
{
    public function resolveCompanyId(?int $companyId = null): ?int
    {
        if ($companyId !== null && $companyId > 0) {
            return $companyId;
        }

        $scoped = GeneralSetting::resolveScopeCompanyId();
        if ($scoped !== null && $scoped > 0) {
            return $scoped;
        }

        $userCompanyId = auth()->user()?->company_id;

        return $userCompanyId ? (int) $userCompanyId : null;
    }

    public function getMollieForCompany(?int $companyId = null, bool $activeOnly = true): ?PaymentProvider
    {
        $cid = $this->resolveCompanyId($companyId);
        if ($cid === null) {
            return null;
        }

        $query = PaymentProvider::query()
            ->where('company_id', $cid)
            ->where('provider_type', 'mollie');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $provider = $query->first();
        if ($provider !== null) {
            return $provider;
        }

        if (! $activeOnly || ! $this->allowMollieTestProviders()) {
            return null;
        }

        return PaymentProvider::query()
            ->where('company_id', $cid)
            ->where('provider_type', 'mollie')
            ->orderByDesc('updated_at')
            ->get()
            ->first(fn (PaymentProvider $candidate) => $this->isMollieTestProvider($candidate)
                && $this->getDecryptedApiKey($candidate) !== null);
    }

    public function allowMollieTestProviders(): bool
    {
        return (bool) config('taxi-dispatch.allow_mollie_test_providers', false);
    }

    public function isMollieTestProvider(PaymentProvider $provider): bool
    {
        if ($provider->provider_type !== 'mollie') {
            return false;
        }

        if ((bool) $provider->getConfigValue('test_mode')) {
            return true;
        }

        $apiKey = $this->getDecryptedApiKey($provider);

        return is_string($apiKey) && str_starts_with($apiKey, 'test_');
    }

    public function getDecryptedApiKey(PaymentProvider $provider): ?string
    {
        $encrypted = $provider->getConfigValue('api_key');
        if (! is_string($encrypted) || trim($encrypted) === '') {
            return null;
        }

        try {
            $key = Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }

        $key = trim($key);

        return $key !== '' ? $key : null;
    }

    public function mollieApiKeyForCompany(?int $companyId = null): ?string
    {
        $provider = $this->getMollieForCompany($companyId);
        if (! $provider) {
            return null;
        }

        return $this->getDecryptedApiKey($provider);
    }

    /**
     * Webhook-URL voor weergave in admin (mag localhost zijn).
     */
    public function mollieWebhookUrlForCompany(?int $companyId = null): ?string
    {
        return $this->resolveMollieWebhookUrlRaw($companyId);
    }

    /**
     * Webhook-URL die naar de Mollie API gaat. Null als Mollie de URL niet kan bereiken
     * (localhost / LAN-IP). Betalingen werken dan via redirect + polling in de chauffeur-app.
     */
    public function mollieWebhookUrlForPayment(?int $companyId = null): ?string
    {
        $override = trim((string) config('taxi-dispatch.mollie_webhook_url', ''));
        if ($override !== '') {
            return $override;
        }

        $url = $this->resolveMollieWebhookUrlRaw($companyId);
        if ($url === null || ! $this->isMollieReachableWebhookUrl($url)) {
            Log::info('Mollie webhook overgeslagen: URL niet bereikbaar vanaf internet.', [
                'url' => $url,
                'hint' => 'Voor automatische webhook-updates: zet TAXI_MOLLIE_WEBHOOK_URL op een publieke tunnel (ngrok).',
            ]);

            return null;
        }

        return $url;
    }

    protected function resolveMollieWebhookUrlRaw(?int $companyId): ?string
    {
        $provider = $this->getMollieForCompany($companyId, false)
            ?? $this->getMollieForCompany($companyId, true);

        if (! $provider) {
            return URL::to('/api/taxi/webhooks/mollie');
        }

        $configured = trim((string) $provider->getConfigValue('webhook_url'));
        if ($configured !== '') {
            return $configured;
        }

        return URL::to('/api/taxi/webhooks/mollie');
    }

    public function isMollieReachableWebhookUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        return ! CentralDomains::isLocalDevEntryHost($host);
    }

    public function isMollieConfiguredForCompany(?int $companyId = null): bool
    {
        return $this->mollieApiKeyForCompany($companyId) !== null;
    }

    /**
     * @return array{configured: bool, provider: ?PaymentProvider, api_key_preview: ?string, webhook_url: ?string, test_mode: bool}
     */
    public function mollieSummaryForCompany(?int $companyId = null): array
    {
        $provider = $this->getMollieForCompany($companyId, false);
        $apiKey = $provider ? $this->getDecryptedApiKey($provider) : null;

        return [
            'configured' => $apiKey !== null && $provider !== null,
            'provider' => $provider,
            'api_key_preview' => $apiKey ? $this->maskApiKey($apiKey) : null,
            'webhook_url' => $provider
                ? ($provider->getConfigValue('webhook_url') ?: URL::to('/api/taxi/webhooks/mollie'))
                : null,
            'test_mode' => (bool) $provider?->getConfigValue('test_mode'),
            'is_active' => (bool) $provider?->is_active,
        ];
    }

    public function maskApiKey(string $apiKey): string
    {
        $apiKey = trim($apiKey);
        if (strlen($apiKey) <= 12) {
            return str_repeat('•', max(8, strlen($apiKey)));
        }

        return substr($apiKey, 0, 8).str_repeat('•', 8).substr($apiKey, -4);
    }

    public function getActiveProviders(?int $companyId = null)
    {
        $cid = $this->resolveCompanyId($companyId);
        $query = PaymentProvider::where('is_active', true);
        if ($cid !== null) {
            $query->where('company_id', $cid);
        }

        return $query->get();
    }

    public function getProviderByType(string $type, ?int $companyId = null)
    {
        $cid = $this->resolveCompanyId($companyId);
        $query = PaymentProvider::where('provider_type', $type)->where('is_active', true);
        if ($cid !== null) {
            $query->where('company_id', $cid);
        }

        return $query->first();
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
