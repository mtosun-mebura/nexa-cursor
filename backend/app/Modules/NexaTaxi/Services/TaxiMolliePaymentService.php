<?php

namespace App\Modules\NexaTaxi\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TaxiMolliePaymentService
{
    public function createPayment(
        string $apiKey,
        float $amount,
        string $description,
        string $redirectUrl,
        ?string $webhookUrl = null,
        array $metadata = []
    ): array {
        $client = new Client(['timeout' => 15]);
        $payload = [
            'amount' => [
                'currency' => 'EUR',
                'value' => $this->formatAmount($amount),
            ],
            'description' => mb_substr($description, 0, 255),
            'redirectUrl' => $redirectUrl,
            'metadata' => $metadata,
        ];

        if ($webhookUrl) {
            $payload['webhookUrl'] = $webhookUrl;
        }

        try {
            $response = $client->post('https://api.mollie.com/v2/payments', [
                'headers' => [
                    'Authorization' => 'Bearer '.trim($apiKey),
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
        } catch (RequestException $e) {
            $this->throwFriendlyMollieError($e, 'createPayment');
        }

        $body = json_decode((string) $response->getBody(), true);

        return is_array($body) ? $body : [];
    }

    public function fetchPayment(string $apiKey, string $molliePaymentId): ?array
    {
        try {
            $client = new Client(['timeout' => 15]);
            $response = $client->get('https://api.mollie.com/v2/payments/'.urlencode($molliePaymentId), [
                'headers' => [
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);
            $body = json_decode((string) $response->getBody(), true);

            return is_array($body) ? $body : null;
        } catch (\Throwable $e) {
            Log::warning('Mollie payment ophalen mislukt', [
                'mollie_payment_id' => $molliePaymentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function checkoutUrl(array $molliePayment): ?string
    {
        return $molliePayment['_links']['checkout']['href'] ?? null;
    }

    public function mapMollieStatus(string $status): string
    {
        return match ($status) {
            'paid' => 'paid',
            'failed' => 'failed',
            'canceled', 'cancelled' => 'canceled',
            'expired' => 'expired',
            default => 'open',
        };
    }

    public function formatAmount(float $amount): string
    {
        return number_format(max(0.01, round($amount, 2)), 2, '.', '');
    }

    protected function throwFriendlyMollieError(RequestException $e, string $context): void
    {
        $status = $e->getResponse()?->getStatusCode();
        $body = $e->getResponse() ? (string) $e->getResponse()->getBody() : '';

        Log::warning('Mollie API-aanroep mislukt', [
            'context' => $context,
            'status' => $status,
            'body' => mb_substr($body, 0, 500),
            'error' => $e->getMessage(),
        ]);

        if ($status === 401 || ($status === 400 && str_contains($body, 'Authorization'))) {
            throw new RuntimeException(
                'De Mollie API-sleutel is ongeldig. Controleer onder Admin → Betalingsproviders of je een geldige test_- of live_-sleutel hebt ingevuld.'
            );
        }

        throw new RuntimeException('Betaling starten bij Mollie is mislukt. Probeer het later opnieuw.');
    }
}
