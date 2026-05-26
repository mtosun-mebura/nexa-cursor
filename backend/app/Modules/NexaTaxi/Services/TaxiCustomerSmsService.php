<?php

namespace App\Modules\NexaTaxi\Services;

use App\Support\DutchPhoneNumber;
use Illuminate\Support\Facades\Log;
use Vonage\Client;
use Vonage\SMS\Message\SMS;

/**
 * SMS naar klanten (rit geaccepteerd). Geen gratis productie-SMS: demo = log, vonage = betaalde API.
 */
class TaxiCustomerSmsService
{
    public function isVonageConfigured(): bool
    {
        return trim((string) config('services.vonage.api_key')) !== ''
            && trim((string) config('services.vonage.api_secret')) !== ''
            && trim((string) config('services.vonage.from')) !== '';
    }

    /**
     * @return array{ok: bool, error?: string, demo?: bool}
     */
    public function send(string $provider, string $phone, string $message): array
    {
        $message = trim($message);
        if ($message === '') {
            return ['ok' => false, 'error' => 'Leeg SMS-bericht.'];
        }

        $normalized = DutchPhoneNumber::normalizeOptionalNlToInternational(trim($phone));
        if ($normalized === null || $normalized === '') {
            return ['ok' => false, 'error' => 'Ongeldig telefoonnummer.'];
        }

        if ($provider === TaxiDispatchSettingsService::SMS_PROVIDER_DEMO) {
            Log::info('Demo SMS (klant rit geaccepteerd)', [
                'to' => $normalized,
                'message' => $message,
            ]);

            return ['ok' => true, 'demo' => true];
        }

        if ($provider === TaxiDispatchSettingsService::SMS_PROVIDER_VONAGE) {
            if (! $this->isVonageConfigured()) {
                return ['ok' => false, 'error' => 'Vonage is niet geconfigureerd (VONAGE_API_KEY, VONAGE_API_SECRET, VONAGE_FROM_NUMBER in .env).'];
            }

            try {
                $client = new Client(
                    new \Vonage\Client\Credentials\Basic(
                        config('services.vonage.api_key'),
                        config('services.vonage.api_secret')
                    )
                );
                $client->sms()->send(new SMS(
                    $normalized,
                    config('services.vonage.from'),
                    mb_substr($message, 0, 1600)
                ));

                return ['ok' => true];
            } catch (\Throwable $e) {
                Log::warning('Vonage SMS mislukt.', ['to' => $normalized, 'error' => $e->getMessage()]);

                return ['ok' => false, 'error' => $e->getMessage()];
            }
        }

        return ['ok' => false, 'error' => 'SMS-provider staat uit.'];
    }
}
