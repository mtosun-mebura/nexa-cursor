<?php

namespace App\Services;

use App\Support\DutchPhoneNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppBusinessService
{
    public function __construct(
        protected EnvService $env
    ) {}

    public function isConfigured(): bool
    {
        return trim($this->env->get('WHATSAPP_API_TOKEN', '')) !== ''
            && trim($this->env->get('WHATSAPP_PHONE_NUMBER_ID', '')) !== '';
    }

    /**
     * Verstuur een tekstbericht via de WhatsApp Business Cloud API.
     *
     * @return array{ok: bool, error?: string}
     */
    public function sendText(string $recipientE164, string $body): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'WhatsApp Business API is niet geconfigureerd.'];
        }

        $to = $this->normalizeRecipientForApi($recipientE164);
        if ($to === null) {
            return ['ok' => false, 'error' => 'Ongeldig ontvanger-telefoonnummer.'];
        }

        $text = trim($body);
        if ($text === '') {
            return ['ok' => false, 'error' => 'Leeg bericht.'];
        }

        $version = trim($this->env->get('WHATSAPP_API_VERSION', 'v18.0')) ?: 'v18.0';
        $phoneNumberId = trim($this->env->get('WHATSAPP_PHONE_NUMBER_ID', ''));
        $token = trim($this->env->get('WHATSAPP_API_TOKEN', ''));
        $url = 'https://graph.facebook.com/'.rawurlencode($version).'/'.rawurlencode($phoneNumberId).'/messages';

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => mb_substr($text, 0, 4096),
                ],
            ]);

        if ($response->successful()) {
            return ['ok' => true];
        }

        $error = $response->json('error.message')
            ?? $response->json('error.error_user_msg')
            ?? $response->body();

        Log::warning('WhatsApp Business API: verzenden mislukt.', [
            'status' => $response->status(),
            'to' => $to,
            'error' => is_string($error) ? $error : json_encode($error),
        ]);

        return ['ok' => false, 'error' => is_string($error) ? $error : 'Verzenden mislukt.'];
    }

    /**
     * WhatsApp API verwacht landcode + nummer zonder + (bijv. 31612345678).
     */
    public function normalizeRecipientForApi(string $phone): ?string
    {
        $normalized = DutchPhoneNumber::normalizeOptionalNlToInternational(trim($phone));
        if ($normalized === null || $normalized === '') {
            return null;
        }

        return ltrim($normalized, '+');
    }
}
