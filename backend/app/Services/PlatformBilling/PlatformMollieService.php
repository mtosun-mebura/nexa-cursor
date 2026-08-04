<?php

namespace App\Services\PlatformBilling;

use App\Models\PlatformBillingSetting;
use App\Services\PaymentProviderService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PlatformMollieService
{
    public function apiKey(): string
    {
        $settings = PlatformBillingSetting::current();
        $key = trim((string) ($settings->decryptedMollieApiKey() ?? ''));

        if ($key === '') {
            $key = trim((string) config('platform-billing.mollie_api_key', ''));
        }

        if ($key === '' || ! PaymentProviderService::isValidMollieApiKeyFormat($key)) {
            throw new RuntimeException(
                'Mollie API-sleutel voor SaaS-facturatie ontbreekt. Stel deze in via Admin → SaaS-facturatie → Instellingen.'
            );
        }

        return $key;
    }

    public function webhookUrl(): ?string
    {
        $settings = PlatformBillingSetting::current();
        $configured = trim((string) ($settings->resolvedMollieWebhookUrl() ?? ''));

        if ($configured === '') {
            $configured = trim((string) config('platform-billing.webhook_url', ''));
        }

        if ($configured !== '') {
            return $configured;
        }

        if (app()->environment('local')) {
            return null;
        }

        return url('/api/platform/webhooks/mollie');
    }

    public function isConfigured(): bool
    {
        try {
            $this->apiKey();

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    public function createCustomer(string $name, ?string $email = null): array
    {
        $payload = ['name' => mb_substr($name, 0, 255)];
        if ($email) {
            $payload['email'] = $email;
        }

        return $this->request('POST', 'customers', $payload);
    }

    public function createMandateVerificationPayment(
        string $customerId,
        string $redirectUrl,
        array $metadata = []
    ): array {
        $amount = (float) config('platform-billing.mandate_verification_amount', 0.01);

        return $this->createPayment([
            'amount' => [
                'currency' => 'EUR',
                'value' => $this->formatAmount($amount),
            ],
            'customerId' => $customerId,
            'sequenceType' => 'first',
            'method' => 'directdebit',
            'description' => 'SEPA-mandaat bevestigen (€0,01)',
            'redirectUrl' => $redirectUrl,
            'webhookUrl' => $this->webhookUrl(),
            'metadata' => $metadata,
        ]);
    }

    public function createRecurringPayment(
        string $customerId,
        string $mandateId,
        float $amount,
        string $description,
        array $metadata = []
    ): array {
        return $this->createPayment([
            'amount' => [
                'currency' => 'EUR',
                'value' => $this->formatAmount($amount),
            ],
            'customerId' => $customerId,
            'mandateId' => $mandateId,
            'sequenceType' => 'recurring',
            'description' => mb_substr($description, 0, 255),
            'webhookUrl' => $this->webhookUrl(),
            'metadata' => $metadata,
        ]);
    }

    public function createOneOffPayment(
        float $amount,
        string $description,
        string $redirectUrl,
        array $metadata = []
    ): array {
        return $this->createPayment([
            'amount' => [
                'currency' => 'EUR',
                'value' => $this->formatAmount($amount),
            ],
            'description' => mb_substr($description, 0, 255),
            'redirectUrl' => $redirectUrl,
            'webhookUrl' => $this->webhookUrl(),
            'metadata' => $metadata,
        ]);
    }

    public function createFirstCollectionPayment(
        string $customerId,
        float $amount,
        string $description,
        string $redirectUrl,
        array $metadata = []
    ): array {
        return $this->createPayment([
            'amount' => [
                'currency' => 'EUR',
                'value' => $this->formatAmount($amount),
            ],
            'customerId' => $customerId,
            'sequenceType' => 'first',
            'method' => 'directdebit',
            'description' => mb_substr($description, 0, 255),
            'redirectUrl' => $redirectUrl,
            'webhookUrl' => $this->webhookUrl(),
            'metadata' => $metadata,
        ]);
    }

    public function fetchPayment(string $molliePaymentId): ?array
    {
        try {
            return $this->request('GET', 'payments/'.urlencode($molliePaymentId));
        } catch (\Throwable $e) {
            Log::warning('Platform Mollie payment ophalen mislukt', [
                'mollie_payment_id' => $molliePaymentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function fetchCustomerMandates(string $customerId): array
    {
        $body = $this->request('GET', 'customers/'.urlencode($customerId).'/mandates');

        return is_array($body['_embedded']['mandates'] ?? null) ? $body['_embedded']['mandates'] : [];
    }

    public function createSubscription(string $customerId, array $payload): array
    {
        if (empty($payload['webhookUrl'])) {
            unset($payload['webhookUrl']);
        }

        return $this->request('POST', 'customers/'.urlencode($customerId).'/subscriptions', $payload);
    }

    public function cancelSubscription(string $customerId, string $subscriptionId): array
    {
        return $this->request('DELETE', 'customers/'.urlencode($customerId).'/subscriptions/'.urlencode($subscriptionId));
    }

    public function fetchSubscription(string $customerId, string $subscriptionId): ?array
    {
        try {
            return $this->request('GET', 'customers/'.urlencode($customerId).'/subscriptions/'.urlencode($subscriptionId));
        } catch (\Throwable $e) {
            Log::warning('Platform Mollie subscription ophalen mislukt', [
                'customer_id' => $customerId,
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function checkoutUrl(array $molliePayment): ?string
    {
        return $molliePayment['_links']['checkout']['href'] ?? null;
    }

    public function mapStatus(string $status): string
    {
        return match ($status) {
            'paid' => 'paid',
            'failed', 'expired', 'canceled' => 'failed',
            default => 'pending',
        };
    }

    private function createPayment(array $payload): array
    {
        if (empty($payload['webhookUrl'])) {
            unset($payload['webhookUrl']);
        }

        return $this->request('POST', 'payments', $payload);
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $client = new Client(['timeout' => 20]);
        $options = [
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey(),
                'Content-Type' => 'application/json',
            ],
        ];

        if ($method !== 'GET' && $payload !== []) {
            $options['json'] = $payload;
        }

        try {
            $response = $client->request($method, 'https://api.mollie.com/v2/'.$path, $options);
        } catch (RequestException $e) {
            $message = $e->getResponse()?->getBody()?->getContents() ?: $e->getMessage();
            throw new RuntimeException('Mollie-fout: '.$message, 0, $e);
        }

        if ($method === 'DELETE' && $response->getStatusCode() === 204) {
            return [];
        }

        $body = json_decode((string) $response->getBody(), true);

        return is_array($body) ? $body : [];
    }

    private function formatAmount(float $amount): string
    {
        return number_format(max(0.01, round($amount, 2)), 2, '.', '');
    }
}
