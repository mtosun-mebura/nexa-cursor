<?php

namespace App\Services\Payout;

use App\Models\PayoutIdentity;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mollie Connect balance transfer: POST /v2/connect/balance-transfers.
 *
 * Requires an OAuth or advanced access token with balance-transfers.write.
 * A test_/live_ API key cannot create this transfer. Missing token, source
 * organization, or driver organization returns a failed result — never a stub id.
 */
class MollieConnectBalanceTransferClient implements PlatformSettlementTransferClient
{
    public function transfer(
        PayoutIdentity $identity,
        float $amount,
        string $currency,
        string $reference,
    ): array {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Bedrag is 0; er is geen overboeking gedaan.'];
        }

        $token = trim((string) config('nexa_payout.connect_access_token'));
        $sourceOrg = $this->organizationId((string) config('nexa_payout.connect_source_organization_id'));
        $destinationOrg = $this->destinationOrganizationId($identity);

        if ($destinationOrg === null) {
            return [
                'ok' => false,
                'error' => 'Chauffeur heeft geen Mollie-organisatie (org_…) op de uitbetalingsrekening.',
            ];
        }

        if ($token === '' || $sourceOrg === null) {
            return [
                'ok' => false,
                'error' => 'Mollie Connect-token of NEXA-organisatie ontbreekt. Er is geen overboeking gedaan.',
            ];
        }

        $description = mb_substr($reference, 0, 255);
        $payload = [
            'amount' => [
                'currency' => strtoupper($currency) !== '' ? strtoupper($currency) : 'EUR',
                'value' => number_format(round($amount, 2), 2, '.', ''),
            ],
            'description' => $description,
            'source' => [
                'type' => 'organization',
                'id' => $sourceOrg,
                'description' => $description,
            ],
            'destination' => [
                'type' => 'organization',
                'id' => $destinationOrg,
                'description' => $description,
            ],
            'category' => 'purchase',
            'metadata' => [
                'reference' => $reference,
            ],
            'testmode' => (bool) config('nexa_payout.connect_testmode', false),
        ];

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->withHeaders([
                    'Idempotency-Key' => mb_substr('driver-payout-'.$reference, 0, 255),
                ])
                ->timeout(15)
                ->post('https://api.mollie.com/v2/connect/balance-transfers', $payload);
        } catch (\Throwable $e) {
            Log::warning('Mollie Connect balance transfer request failed', [
                'reference' => $reference,
                'error' => $this->safeError($e->getMessage(), $token),
            ]);

            return [
                'ok' => false,
                'error' => 'Mollie-overboeking is niet bereikbaar. Er is geen geld overgemaakt.',
            ];
        }

        $body = $response->json();
        $body = is_array($body) ? $body : [];

        if ($response->failed()) {
            $detail = (string) ($body['detail'] ?? $body['title'] ?? 'Mollie-overboeking geweigerd.');
            Log::warning('Mollie Connect balance transfer rejected', [
                'reference' => $reference,
                'status' => $response->status(),
                'detail' => $this->safeError($detail, $token),
            ]);

            return [
                'ok' => false,
                'error' => 'Mollie-overboeking geweigerd: '.$this->safeError($detail, $token),
            ];
        }

        $transferId = trim((string) ($body['id'] ?? ''));
        $status = (string) ($body['status'] ?? '');
        if ($transferId === '' || $status === 'failed') {
            $reason = (string) (data_get($body, 'statusReason.message') ?: 'Mollie gaf geen transfer-id terug.');

            return [
                'ok' => false,
                'error' => $this->safeError($reason, $token),
            ];
        }

        return [
            'ok' => true,
            'transfer_id' => $transferId,
        ];
    }

    private function destinationOrganizationId(PayoutIdentity $identity): ?string
    {
        foreach ([$identity->provider_organization_id, $identity->provider_account_id] as $candidate) {
            $org = $this->organizationId((string) $candidate);
            if ($org !== null) {
                return $org;
            }
        }

        return null;
    }

    private function organizationId(string $value): ?string
    {
        $value = trim($value);

        return preg_match('/^org_[A-Za-z0-9]+$/', $value) === 1 ? $value : null;
    }

    private function safeError(string $message, string $token): string
    {
        if ($token !== '') {
            $message = str_replace($token, '[token]', $message);
        }

        return mb_substr(trim($message), 0, 300);
    }
}
