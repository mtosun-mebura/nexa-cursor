<?php

namespace App\Services;

use App\Models\Company;
use App\Support\DutchPhoneNumber;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppBusinessService
{
    public function __construct(
        protected EnvService $env
    ) {}

    /**
     * Platform-brede WhatsApp Business API-credentials (niet per tenant).
     * $companyId blijft in method signatures voor logging/context, maar beïnvloedt credentials niet.
     */
    protected function credential(string $key, ?int $companyId = null, string $default = ''): string
    {
        return trim((string) $this->env->get($key, $default, null));
    }

    public function isConfigured(?int $companyId = null): bool
    {
        return $this->credential('WHATSAPP_API_TOKEN', $companyId) !== ''
            && $this->credential('WHATSAPP_PHONE_NUMBER_ID', $companyId) !== '';
    }

    public function hasApiToken(?int $companyId = null): bool
    {
        return $this->credential('WHATSAPP_API_TOKEN', $companyId) !== '';
    }

    /**
     * Weergavenaam van de tenant voor in de berichttekst (niet de Meta chat-afzender).
     */
    public function tenantDisplayName(?int $companyId): string
    {
        if ($companyId === null || $companyId <= 0) {
            return '';
        }

        try {
            $name = trim((string) (Company::query()->whereKey($companyId)->value('name') ?? ''));

            return $name;
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Zet de tenantnaam bovenaan het bericht. De WhatsApp-profielnaam blijft die van het
     * platform-telefoonnummer in Meta (niet dynamisch per tenant).
     */
    public function prefixMessageWithTenant(string $body, ?int $companyId): string
    {
        $tenant = $this->tenantDisplayName($companyId);
        $text = trim($body);
        if ($tenant === '' || $text === '') {
            return $text;
        }

        if (str_starts_with($text, '*'.$tenant.'*') || str_starts_with($text, $tenant."\n")) {
            return $text;
        }

        return '*'.$tenant."*\n\n".$text;
    }

    /**
     * Controleer of token + Phone Number ID bij Meta geldig zijn.
     *
     * @return array{ok: bool, error?: string|null, meta?: array<string, mixed>}
     */
    public function verifyCredentials(?int $companyId = null): array
    {
        if (! $this->isConfigured($companyId)) {
            return ['ok' => false, 'error' => 'WhatsApp Business API is niet geconfigureerd (token + Phone Number ID).'];
        }

        $version = $this->credential('WHATSAPP_API_VERSION', $companyId, 'v18.0') ?: 'v18.0';
        $phoneNumberId = $this->credential('WHATSAPP_PHONE_NUMBER_ID', $companyId);
        $token = $this->credential('WHATSAPP_API_TOKEN', $companyId);
        $url = 'https://graph.facebook.com/'.rawurlencode($version).'/'.rawurlencode($phoneNumberId)
            .'?fields=id,display_phone_number,verified_name';

        $response = Http::withToken($token)->acceptJson()->get($url);
        if ($response->successful()) {
            return [
                'ok' => true,
                'meta' => [
                    'id' => $response->json('id'),
                    'display_phone_number' => $response->json('display_phone_number'),
                    'verified_name' => $response->json('verified_name'),
                ],
            ];
        }

        return ['ok' => false, 'error' => $this->extractApiError($response)];
    }

    /**
     * Verstuur een tekstbericht via de WhatsApp Business Cloud API.
     *
     * @return array{ok: bool, error?: string, meta?: array<string, mixed>}
     */
    public function sendText(string $recipientE164, string $body, ?int $companyId = null): array
    {
        if (! $this->isConfigured($companyId)) {
            return ['ok' => false, 'error' => 'WhatsApp Business API is niet geconfigureerd.'];
        }

        $to = $this->normalizeRecipientForApi($recipientE164);
        if ($to === null) {
            return ['ok' => false, 'error' => 'Ongeldig ontvanger-telefoonnummer.'];
        }

        $text = $this->prefixMessageWithTenant($body, $companyId);
        if ($text === '') {
            return ['ok' => false, 'error' => 'Leeg bericht.'];
        }

        $version = $this->credential('WHATSAPP_API_VERSION', $companyId, 'v18.0') ?: 'v18.0';
        $phoneNumberId = $this->credential('WHATSAPP_PHONE_NUMBER_ID', $companyId);
        $token = $this->credential('WHATSAPP_API_TOKEN', $companyId);
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
            return ['ok' => true, 'meta' => ['company_id' => $companyId, 'to' => $to]];
        }

        $error = $this->extractApiError($response);

        Log::warning('WhatsApp Business API: verzenden mislukt.', [
            'status' => $response->status(),
            'company_id' => $companyId,
            'to' => $to,
            'error' => $error,
        ]);

        return ['ok' => false, 'error' => $error];
    }

    /**
     * Proactieve boekingsmelding: Meta vereist buiten het 24u-venster een goedgekeurde template.
     * Met template-naam → template; anders free-form tekst (werkt o.a. in test/allowlist).
     *
     * @param  'dispatch'|'customer'  $purpose
     * @param  list<string>|null  $templateParams  Body-variabelen {{1}}…{{n}}; null = legacy 2-param
     * @return array{ok: bool, error?: string, meta?: array<string, mixed>}
     */
    public function sendBookingNotification(
        string $recipientE164,
        string $body,
        ?int $companyId = null,
        string $purpose = 'dispatch',
        ?array $templateParams = null
    ): array {
        $templateKey = $purpose === 'customer'
            ? 'WHATSAPP_BOOKING_CUSTOMER_TEMPLATE'
            : 'WHATSAPP_BOOKING_TEMPLATE';
        $langKey = $purpose === 'customer'
            ? 'WHATSAPP_BOOKING_CUSTOMER_TEMPLATE_LANG'
            : 'WHATSAPP_BOOKING_TEMPLATE_LANG';

        $template = $this->credential($templateKey, $companyId);
        if ($template === '' && $purpose === 'customer') {
            $template = $this->credential('WHATSAPP_BOOKING_TEMPLATE', $companyId);
            $langKey = 'WHATSAPP_BOOKING_TEMPLATE_LANG';
        }

        if ($template !== '') {
            $lang = $this->credential($langKey, $companyId, 'nl') ?: 'nl';

            if (is_array($templateParams) && $templateParams !== []) {
                $params = array_values(array_filter(
                    array_map(
                        fn ($p) => mb_substr(trim((string) $p), 0, 1024),
                        $templateParams
                    ),
                    fn ($p) => $p !== ''
                ));
            } else {
                // Legacy fallback: {{1}} tenant, {{2}} volledige body/samenvatting
                $tenant = $this->tenantDisplayName($companyId);
                $summary = trim($body);
                if ($tenant !== '' && str_starts_with($summary, '*'.$tenant.'*')) {
                    $summary = trim(substr($summary, strlen('*'.$tenant.'*')));
                }
                $params = array_values(array_filter([
                    $tenant !== '' ? $tenant : 'Nexa',
                    mb_substr($summary !== '' ? $summary : $body, 0, 1024),
                ], fn ($p) => is_string($p) && trim($p) !== ''));
            }

            return $this->sendTemplate($recipientE164, $template, $lang, $params, $companyId);
        }

        $result = $this->sendText($recipientE164, $body, $companyId);
        if (! ($result['ok'] ?? false) && $this->isOutsideCustomerCareWindowError((string) ($result['error'] ?? ''))) {
            $result['error'] = trim(
                (string) ($result['error'] ?? '')
                .' Stel een goedgekeurde Meta-template in onder Algemene configuraties → WhatsApp Business API '
                .'(verplicht op productie buiten het 24-uurs klantvenster).'
            );
        }

        return $result;
    }

    public function isOutsideCustomerCareWindowError(string $error): bool
    {
        $haystack = strtolower($error);

        return str_contains($haystack, '131047')
            || str_contains($haystack, 're-engagement')
            || str_contains($haystack, '24 hour')
            || str_contains($haystack, '24-hour')
            || str_contains($haystack, 'customer care window');
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

    /**
     * Goedgekeurd Meta-template (aanbevolen voor proactieve klantberichten).
     *
     * @param  list<string>  $bodyParameters  Volgorde moet overeenkomen met template in Meta Business Manager.
     * @return array{ok: bool, error?: string, meta?: array<string, mixed>}
     */
    public function sendTemplate(
        string $recipientE164,
        string $templateName,
        string $languageCode = 'nl',
        array $bodyParameters = [],
        ?int $companyId = null
    ): array {
        if (! $this->isConfigured($companyId)) {
            return ['ok' => false, 'error' => 'WhatsApp Business API is niet geconfigureerd.'];
        }

        $templateName = trim($templateName);
        if ($templateName === '') {
            return ['ok' => false, 'error' => 'Geen template-naam.'];
        }

        $to = $this->normalizeRecipientForApi($recipientE164);
        if ($to === null) {
            return ['ok' => false, 'error' => 'Ongeldig ontvanger-telefoonnummer.'];
        }

        $version = $this->credential('WHATSAPP_API_VERSION', $companyId, 'v18.0') ?: 'v18.0';
        $phoneNumberId = $this->credential('WHATSAPP_PHONE_NUMBER_ID', $companyId);
        $token = $this->credential('WHATSAPP_API_TOKEN', $companyId);
        $url = 'https://graph.facebook.com/'.rawurlencode($version).'/'.rawurlencode($phoneNumberId).'/messages';

        $template = [
            'name' => $templateName,
            'language' => ['code' => $languageCode ?: 'nl'],
        ];

        $params = array_values(array_filter(array_map(
            fn ($p) => ['type' => 'text', 'text' => mb_substr(trim((string) $p), 0, 1024)],
            $bodyParameters
        ), fn ($p) => $p['text'] !== ''));

        if ($params !== []) {
            $template['components'] = [[
                'type' => 'body',
                'parameters' => $params,
            ]];
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => $template,
            ]);

        if ($response->successful()) {
            return ['ok' => true, 'meta' => ['company_id' => $companyId, 'to' => $to, 'template' => $templateName]];
        }

        $error = $this->extractApiError($response);

        Log::warning('WhatsApp template: verzenden mislukt.', [
            'template' => $templateName,
            'company_id' => $companyId,
            'to' => $to,
            'error' => $error,
        ]);

        return ['ok' => false, 'error' => $error];
    }

    protected function extractApiError(Response $response): string
    {
        $message = $response->json('error.message')
            ?? $response->json('error.error_user_msg')
            ?? null;
        $type = $response->json('error.type');
        $code = $response->json('error.code');

        if (is_string($message) && $message !== '') {
            $parts = [$message];
            if ($code !== null) {
                $parts[] = '(code '.$code.($type ? ', '.$type : '').')';
            }

            return implode(' ', $parts);
        }

        $body = $response->body();

        return is_string($body) && $body !== '' ? $body : 'Verzenden mislukt (HTTP '.$response->status().').';
    }
}
