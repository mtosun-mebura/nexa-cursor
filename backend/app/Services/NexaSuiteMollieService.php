<?php

namespace App\Services;

use App\Models\NexaSuiteMarketplaceSetting;
use App\Models\PlatformBillingSetting;
use App\Services\PlatformBilling\PlatformMollieService;
use Illuminate\Support\Facades\URL;
use RuntimeException;

/**
 * Mollie voor Nexa Suite klant-app / marktplaats (platform collect).
 * Geen tenant nodig — sleutel staat op Nexa Suite-instellingen.
 */
class NexaSuiteMollieService
{
    public function isConfigured(): bool
    {
        try {
            $this->apiKey();

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    public function apiKey(): string
    {
        $settings = NexaSuiteMarketplaceSetting::current();
        $key = trim((string) ($settings->decryptedMollieApiKey() ?? ''));

        if ($key !== '' && PaymentProviderService::isValidMollieApiKeyFormat($key)) {
            return $key;
        }

        // Fallback: zelfde NEXA-account als facturatie, of .env.
        try {
            return app(PlatformMollieService::class)->apiKey();
        } catch (RuntimeException) {
            throw new RuntimeException(
                'Mollie API-sleutel voor Nexa Suite ontbreekt. Stel deze in via Configuraties → Nexa Suite (klant-app), of NEXA Suite boekingen → Instellingen.'
            );
        }
    }

    public function webhookUrl(): ?string
    {
        $settings = NexaSuiteMarketplaceSetting::current();
        $configured = trim((string) ($settings->mollie_webhook_url ?? ''));
        if ($configured !== '') {
            return $configured;
        }

        $override = trim((string) config('taxi-dispatch.mollie_webhook_url', ''));
        if ($override !== '') {
            return $override;
        }

        $url = URL::to('/api/taxi/webhooks/mollie');
        if (! app(PaymentProviderService::class)->isMollieReachableWebhookUrl($url)) {
            return null;
        }

        return $url;
    }

    public function keyMode(): ?string
    {
        try {
            $key = $this->apiKey();
        } catch (RuntimeException) {
            return null;
        }

        if (str_starts_with($key, 'live_')) {
            return 'live';
        }
        if (str_starts_with($key, 'test_')) {
            return 'test';
        }

        return null;
    }

    public function usesMarketplaceStoredKey(): bool
    {
        return NexaSuiteMarketplaceSetting::current()->hasStoredMollieApiKey();
    }

    public function usesPlatformBillingFallback(): bool
    {
        if ($this->usesMarketplaceStoredKey()) {
            return false;
        }

        return PlatformBillingSetting::current()->hasStoredMollieApiKey()
            || trim((string) config('platform-billing.mollie_api_key', '')) !== '';
    }
}
