<?php

namespace App\Services\PlatformBilling;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\PlatformPaymentMandate;
use Carbon\Carbon;

class PlatformMollieRequestBuilder
{
    public function __construct(
        private readonly PlatformMollieService $mollie,
        private readonly SubscriptionBillingCalculator $calculator,
        private readonly PlatformBillingService $billing,
    ) {}

    /**
     * @return array<int, array{label: string, method: string, endpoint: string, description: string, payload: array<string, mixed>}>
     */
    public function buildTenantRequestPlan(
        Company $company,
        CompanyBillingProfile $profile,
        ?PlatformPaymentMandate $mandate = null,
        ?Carbon $asOf = null,
    ): array {
        $asOf ??= now();
        $requests = [];
        $customerId = $mandate?->mollie_customer_id;
        $customerName = trim((string) ($profile->billing_contact_name ?: $company->name));
        $customerEmail = $profile->billingEmailForCompany();
        $webhookUrl = $this->mollie->webhookUrl();
        $redirectUrl = route('admin.platform-billing.mandates.return', ['company' => $company->id]);

        if (! $customerId) {
            $payload = ['name' => mb_substr($customerName, 0, 255)];
            if ($customerEmail) {
                $payload['email'] = $customerEmail;
            }
            $requests[] = $this->entry(
                'Klant aanmaken',
                'POST',
                '/v2/customers',
                'Eerste stap: Mollie-klant voor deze tenant.',
                $payload,
            );
            $customerId = 'cst_…';
        }

        $firstPaymentAmount = $this->billing->estimateFirstCollectionAmount($profile, $asOf);
        if ($profile->auto_collect_enabled && $firstPaymentAmount > 0 && $this->calculator->isBillable($profile, $asOf)) {
            $firstPayload = [
                'amount' => [
                    'currency' => 'EUR',
                    'value' => $this->formatAmount($firstPaymentAmount),
                ],
                'customerId' => $customerId,
                'sequenceType' => 'first',
                'description' => mb_substr($this->billing->firstCollectionDescription($profile, $asOf), 0, 255),
                'redirectUrl' => $redirectUrl,
                'metadata' => [
                    'company_id' => $company->id,
                    'type' => 'subscription_first_payment',
                ],
            ];
            if ($webhookUrl) {
                $firstPayload['webhookUrl'] = $webhookUrl;
            }

            $requests[] = $this->entry(
                'Eerste betaling (mandaat + vooruitfacturatie)',
                'POST',
                '/v2/payments',
                'Mandaat via eerste checkout-betaling (iDEAL e.d.; geen method=directdebit). Bedrag = restant startmaand + volledige volgende maand (+ eventuele extra regels).',
                $firstPayload,
            );
        } elseif ($profile->auto_collect_enabled && ! $mandate?->isActive()) {
            $verifyPayload = [
                'amount' => [
                    'currency' => 'EUR',
                    'value' => $this->formatAmount((float) config('platform-billing.mandate_verification_amount', 0.01)),
                ],
                'customerId' => $customerId,
                'sequenceType' => 'first',
                'description' => 'SEPA-mandaat bevestigen (€0,01)',
                'redirectUrl' => $redirectUrl,
                'metadata' => [
                    'company_id' => $company->id,
                    'type' => 'mandate_verification',
                ],
            ];
            if ($webhookUrl) {
                $verifyPayload['webhookUrl'] = $webhookUrl;
            }

            $requests[] = $this->entry(
                'Mandaatverificatie (€0,01)',
                'POST',
                '/v2/payments',
                'Alternatief pad zonder directe vooruitfacturatie: alleen mandaat vastleggen via checkout (niet via method=directdebit).',
                $verifyPayload,
            );
        }

        $subscriptionAmount = $profile->resolveMonthlyAmount();
        $subscriptionStart = $this->calculator->mollieSubscriptionStartDate($profile, $asOf);
        if ($profile->auto_collect_enabled && $subscriptionAmount > 0 && $subscriptionStart && $this->calculator->isBillable($profile, $asOf)) {
            $subscriptionPayload = [
                'amount' => [
                    'currency' => 'EUR',
                    'value' => $this->formatAmount($subscriptionAmount),
                ],
                'interval' => '1 month',
                'startDate' => $subscriptionStart,
                'description' => mb_substr('SaaS-abonnement '.$company->name, 0, 255),
                'method' => 'directdebit',
                'metadata' => [
                    'company_id' => $company->id,
                    'type' => 'platform_subscription',
                ],
            ];

            $times = $this->calculator->mollieSubscriptionTimes($profile, $asOf);
            if ($times !== null && $times > 0) {
                $subscriptionPayload['times'] = $times;
            }

            if ($webhookUrl) {
                $subscriptionPayload['webhookUrl'] = $webhookUrl;
            }

            if ($mandate?->mollie_mandate_id) {
                $subscriptionPayload['mandateId'] = $mandate->mollie_mandate_id;
            }

            $requests[] = $this->entry(
                'Maandelijkse subscription (incasso op de 1e)',
                'POST',
                '/v2/customers/'.$customerId.'/subscriptions',
                'Terugkerende incasso via Mollie Subscriptions API. Start na de vooruitbetaalde periode; elke maand op dag 1.',
                $subscriptionPayload,
            );
        }

        if ($profile->mollie_subscription_id) {
            $requests[] = $this->entry(
                'Subscription opzeggen bij einddatum',
                'DELETE',
                '/v2/customers/'.$customerId.'/subscriptions/'.$profile->mollie_subscription_id,
                'Wordt uitgevoerd zodra de einddatum is bereikt (bijv. einddatum 01-09-2026 → laatste incasso 01-08-2026).',
                [],
            );
        } elseif ($this->calculator->shouldCancelMollieSubscription($profile, $asOf)) {
            $requests[] = $this->entry(
                'Subscription opzeggen bij einddatum',
                'DELETE',
                '/v2/customers/'.$customerId.'/subscriptions/sub_…',
                'Einddatum bereikt: subscription annuleren zodat er geen incasso meer volgt.',
                [],
            );
        }

        return $requests;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{label: string, method: string, endpoint: string, description: string, payload: array<string, mixed>}
     */
    private function entry(string $label, string $method, string $endpoint, string $description, array $payload): array
    {
        return [
            'label' => $label,
            'method' => $method,
            'endpoint' => $endpoint,
            'description' => $description,
            'payload' => $payload,
        ];
    }

    private function formatAmount(float $amount): string
    {
        return number_format(max(0.01, round($amount, 2)), 2, '.', '');
    }
}
