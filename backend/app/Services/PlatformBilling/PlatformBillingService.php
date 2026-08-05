<?php

namespace App\Services\PlatformBilling;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\PlatformBillingSetting;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\PlatformPaymentMandate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PlatformBillingService
{
    public function __construct(
        private readonly PlatformMollieService $mollie,
        private readonly PlatformInvoicePdfService $pdf,
        private readonly SubscriptionBillingCalculator $subscriptionCalculator,
    ) {}

    public function billingPeriodForRun(Carbon $runDate): string
    {
        return $runDate->copy()->format('Y-m');
    }

    public function generateInvoiceForCompany(Company $company, string $billingPeriod, ?PlatformBillingSetting $settings = null, ?Carbon $asOf = null): ?PlatformInvoice
    {
        $settings ??= PlatformBillingSetting::current();
        $profile = CompanyBillingProfile::query()->where('company_id', $company->id)->with(['package', 'lineItems'])->first();
        if (! $profile) {
            return null;
        }

        $asOf ??= Carbon::createFromFormat('Y-m', $billingPeriod)->startOfMonth();

        if (! $this->subscriptionCalculator->isBillable($profile, $asOf)) {
            return null;
        }

        if ($profile->hasActiveMollieSubscription() && $profile->auto_collect_enabled) {
            return null;
        }

        $existing = PlatformInvoice::query()
            ->where('company_id', $company->id)
            ->where('billing_period', $billingPeriod)
            ->first();
        if ($existing) {
            return $existing;
        }

        $lineItems = $this->buildInvoiceLineItems($profile, $billingPeriod, $asOf);
        $taxRate = (float) $settings->tax_rate_percent;
        $totals = $this->calculateInvoiceTotals($profile, $lineItems, $taxRate);

        $paymentTermsDays = max(1, (int) $settings->payment_terms_days);

        $invoice = PlatformInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => $settings->generateInvoiceNumber(),
            'billing_period' => $billingPeriod,
            'amount' => $totals['amount'],
            'tax_amount' => $totals['tax_amount'],
            'total_amount' => $totals['total_amount'],
            'currency' => 'EUR',
            'status' => $totals['total_amount'] <= 0 ? 'paid' : 'draft',
            'invoice_date' => now()->toDateString(),
            'payment_terms_days' => $paymentTermsDays,
            'due_date' => now()->addDays($paymentTermsDays)->toDateString(),
            'paid_at' => $totals['total_amount'] <= 0 ? now() : null,
            'line_items' => $lineItems,
            'issuer_details' => $settings->issuerDetailsSnapshot(),
            'recipient_details' => $settings->recipientDetailsSnapshot($company, $profile),
            'collection_method' => $totals['total_amount'] <= 0 ? 'free' : null,
        ]);

        $this->markExtraLinesAppliedIfNeeded($profile, $lineItems);

        return $invoice;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildInvoiceLineItems(CompanyBillingProfile $profile, string $billingPeriod, ?Carbon $asOf = null): array
    {
        return $this->appendDiscountRows(
            $this->buildChargeLineItems($profile, $billingPeriod, $asOf),
            $profile
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildChargeLineItems(CompanyBillingProfile $profile, string $billingPeriod, ?Carbon $asOf = null): array
    {
        if ($profile->billing_mode !== CompanyBillingProfile::MODE_PACKAGE) {
            $profile->setRelation('package', null);
        }

        $profile->loadMissing(['package', 'lineItems']);
        $asOf ??= now();
        $lines = [];

        if ($this->subscriptionCalculator->isBillable($profile, $asOf)) {
            $segments = $this->subscriptionCalculator->advanceCoverageSegments($profile, $asOf);
            if ($segments === []) {
                $baseAmount = $profile->subscriptionBaseAmount();
                $lines[] = [
                    'description' => $profile->subscriptionLineLabel().' — '.$billingPeriod,
                    'quantity' => 1,
                    'unit_price' => $baseAmount,
                    'total' => $baseAmount,
                    'type' => 'subscription',
                    'billing_period' => $billingPeriod,
                ];
            } else {
                foreach ($segments as $segment) {
                    $baseAmount = round($profile->subscriptionBaseAmount() * $segment['fraction'], 2);
                    $lines[] = [
                        'description' => $this->subscriptionCalculator->subscriptionLineDescriptionForSegment(
                            $profile->subscriptionLineLabel(),
                            $segment
                        ),
                        'quantity' => 1,
                        'unit_price' => $baseAmount,
                        'total' => $baseAmount,
                        'type' => 'subscription',
                        'billing_period' => $segment['key'],
                    ];
                }
            }
        }

        if ($profile->shouldIncludeExtraLines()) {
            foreach ($profile->lineItems as $item) {
                if (! $item->is_active) {
                    continue;
                }
                $price = round((float) $item->unit_price, 2);
                $lines[] = [
                    'description' => $item->invoiceLineDescription(),
                    'quantity' => 1,
                    'unit_price' => $price,
                    'total' => $price,
                    'type' => 'extra',
                    'platform_billing_line_item_id' => $item->id,
                ];
            }
        }

        return $lines;
    }

    /**
     * @param  array<int, array<string, mixed>>  $chargeLines
     * @return array<int, array<string, mixed>>
     */
    public function appendDiscountRows(array $chargeLines, CompanyBillingProfile $profile): array
    {
        $result = [];
        $hasExtraLines = false;
        $subscriptionGross = round(array_sum(array_map(
            fn (array $line) => ($line['type'] ?? '') === 'subscription' ? (float) ($line['total'] ?? 0) : 0.0,
            $chargeLines
        )), 2);
        $subscriptionDiscountPercent = $profile->discountPercent();
        $subscriptionDiscountAmount = $subscriptionDiscountPercent > 0
            ? round($subscriptionGross * $subscriptionDiscountPercent / 100, 2)
            : 0.0;
        $lastSubscriptionIndex = -1;
        foreach ($chargeLines as $index => $line) {
            if (($line['type'] ?? '') === 'subscription') {
                $lastSubscriptionIndex = $index;
            }
        }

        foreach ($chargeLines as $index => $line) {
            $result[] = $line;

            if ($index === $lastSubscriptionIndex && $subscriptionDiscountAmount > 0 && $subscriptionDiscountPercent > 0) {
                $result[] = $this->discountLine('subscription', $subscriptionDiscountPercent, $subscriptionDiscountAmount);
            }

            if (($line['type'] ?? '') === 'extra') {
                $hasExtraLines = true;
            }
        }

        if ($hasExtraLines) {
            $discountAmount = $profile->extraLinesDiscountAmount();
            $discountPercent = $profile->extraLinesDiscountPercent();
            if ($discountAmount > 0 && $discountPercent > 0) {
                $result[] = $this->discountLine('extra_lines', $discountPercent, $discountAmount);
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function discountLine(string $scope, int $percent, float $amount): array
    {
        $negative = round(-abs($amount), 2);

        return [
            'type' => 'discount',
            'discount_scope' => $scope,
            'description' => "Korting ({$percent}%)",
            'quantity' => 1,
            'unit_price' => $negative,
            'total' => $negative,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lineItems
     * @return array{
     *     gross_amount: float,
     *     discount_percent: int,
     *     discount_amount: float,
     *     subscription_discount_amount: float,
     *     extra_lines_discount_percent: int,
     *     extra_lines_discount_amount: float,
     *     amount: float,
     *     tax_amount: float,
     *     total_amount: float
     * }
     */
    public function calculateInvoiceTotals(CompanyBillingProfile $profile, array $lineItems, float $taxRate): array
    {
        $chargeLines = array_values(array_filter(
            $lineItems,
            fn (array $line) => ($line['type'] ?? '') !== 'discount'
        ));

        $grossAmount = round(array_sum(array_map(
            fn (array $line) => (float) ($line['total'] ?? 0),
            $chargeLines
        )), 2);

        $subscriptionDiscountAmount = round(array_sum(array_map(
            fn (array $line) => ($line['type'] ?? '') === 'subscription'
                ? round((float) ($line['total'] ?? 0) * $profile->discountPercent() / 100, 2)
                : 0.0,
            $chargeLines
        )), 2);
        $extraLinesDiscountPercent = $profile->extraLinesDiscountPercent();
        $extraLinesDiscountAmount = $profile->extraLinesDiscountAmount();
        $discountAmount = round($subscriptionDiscountAmount + $extraLinesDiscountAmount, 2);

        $netAmount = round(max(0, $grossAmount - $discountAmount), 2);
        $taxAmount = round($netAmount * ($taxRate / 100), 2);

        return [
            'gross_amount' => $grossAmount,
            'discount_percent' => $profile->discountPercent(),
            'discount_amount' => $discountAmount,
            'subscription_discount_amount' => $subscriptionDiscountAmount,
            'extra_lines_discount_percent' => $extraLinesDiscountPercent,
            'extra_lines_discount_amount' => $extraLinesDiscountAmount,
            'amount' => $netAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => round($netAmount + $taxAmount, 2),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lineItems
     * @return array{amount: float, tax_amount: float, total_amount: float}
     */
    public function calculateTotalsFromLineItems(array $lineItems, float $taxRate): array
    {
        $netAmount = round(array_sum(array_map(
            fn (array $line) => (float) ($line['total'] ?? 0),
            $lineItems
        )), 2);
        $taxAmount = round($netAmount * ($taxRate / 100), 2);

        return [
            'amount' => $netAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => round($netAmount + $taxAmount, 2),
        ];
    }

    /**
     * @return array{
     *     line_items: array<int, array<string, mixed>>,
     *     gross_amount: float,
     *     discount_percent: int,
     *     discount_amount: float,
     *     amount: float,
     *     tax_amount: float,
     *     total_amount: float
     * }
     */
    public function resolveStoredInvoicePresentation(PlatformInvoice $invoice, float $taxRate): array
    {
        $items = $invoice->line_items ?? [];
        $hasInlineDiscounts = collect($items)->contains(fn (array $line) => ($line['type'] ?? '') === 'discount');
        $lineItems = $hasInlineDiscounts
            ? $items
            : $this->legacyLineItemsWithDiscountRows($items, $invoice);

        $chargeLines = array_values(array_filter(
            $lineItems,
            fn (array $line) => ($line['type'] ?? '') !== 'discount'
        ));

        $grossAmount = round(array_sum(array_map(
            fn (array $line) => (float) ($line['total'] ?? 0),
            $chargeLines
        )), 2);

        $netAmount = round((float) $invoice->amount, 2);
        $taxAmount = round((float) $invoice->tax_amount, 2);
        $totalAmount = round((float) $invoice->total_amount, 2);

        return [
            'line_items' => $lineItems,
            'gross_amount' => $grossAmount,
            'discount_percent' => 0,
            'discount_amount' => 0.0,
            'amount' => $netAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function legacyLineItemsWithDiscountRows(array $items, PlatformInvoice $invoice): array
    {
        $grossAmount = round(array_sum(array_map(
            fn (array $line) => (float) ($line['total'] ?? 0),
            $items
        )), 2);
        $netAmount = round((float) $invoice->amount, 2);
        $discountAmount = round(max(0, $grossAmount - $netAmount), 2);

        if ($discountAmount <= 0) {
            return $items;
        }

        $subscriptionLine = collect($items)->first(fn (array $line) => ($line['type'] ?? '') === 'subscription');
        $subscriptionTotal = $subscriptionLine ? (float) ($subscriptionLine['total'] ?? 0) : 0.0;
        $discountPercent = $subscriptionTotal > 0
            ? (int) round(($discountAmount / $subscriptionTotal) * 100)
            : 0;

        $result = [];
        $discountInjected = false;
        foreach ($items as $line) {
            $result[] = $line;
            if (! $discountInjected && ($line['type'] ?? '') === 'subscription') {
                $result[] = $this->discountLine('subscription', $discountPercent, $discountAmount);
                $discountInjected = true;
            }
        }

        if (! $discountInjected) {
            $result[] = $this->discountLine('subscription', $discountPercent, $discountAmount);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function previewInvoiceData(Company $company, CompanyBillingProfile $profile, ?string $billingPeriod = null): array
    {
        $settings = PlatformBillingSetting::current();
        $asOf = now();
        $period = $billingPeriod ?? $this->billingPeriodForRun($asOf);
        $lineItems = $this->buildInvoiceLineItems($profile, $period, $asOf);
        $taxRate = (float) $settings->tax_rate_percent;
        $totals = $this->calculateInvoiceTotals($profile, $lineItems, $taxRate);
        $paymentTermsDays = max(1, (int) $settings->payment_terms_days);

        return [
            'billing_period' => $period,
            'invoice_number' => $settings->previewNextInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays($paymentTermsDays)->toDateString(),
            'payment_terms_days' => $paymentTermsDays,
            'line_items' => $lineItems,
            'gross_amount' => $totals['gross_amount'],
            'discount_percent' => $totals['discount_percent'],
            'discount_amount' => $totals['discount_amount'],
            'subscription_discount_amount' => $totals['subscription_discount_amount'],
            'extra_lines_discount_percent' => $totals['extra_lines_discount_percent'],
            'extra_lines_discount_amount' => $totals['extra_lines_discount_amount'],
            'amount' => $totals['amount'],
            'tax_amount' => $totals['tax_amount'],
            'total_amount' => $totals['total_amount'],
            'tax_rate' => $taxRate,
            'issuer' => $settings->issuerDetailsSnapshot(),
            'recipient' => $settings->recipientDetailsSnapshot($company, $profile),
            'payment_terms_text' => PlatformBillingSetting::invoicePaymentTermsTextForInvoice(
                new PlatformInvoice([
                    'payment_terms_days' => $paymentTermsDays,
                    'invoice_date' => now(),
                    'due_date' => now()->addDays($paymentTermsDays),
                    'status' => 'draft',
                ])
            ),
            'extra_lines_one_time' => (bool) $profile->extra_lines_one_time,
            'extra_lines_applied_at' => $profile->extra_lines_applied_at,
            'includes_extra_lines' => collect($lineItems)->contains(fn (array $line) => ($line['type'] ?? '') === 'extra'),
            'notes' => trim((string) ($profile->notes ?? '')),
            'subscription_start_date' => $profile->subscription_start_date?->format('Y-m-d'),
            'subscription_end_date' => $profile->subscription_end_date?->format('Y-m-d'),
            'subscription_coverage_segments' => $this->subscriptionCalculator->advanceCoverageSegments($profile, $asOf),
            'mollie_subscription_start_date' => $this->subscriptionCalculator->mollieSubscriptionStartDate($profile, $asOf),
        ];
    }

    public function estimateFirstCollectionAmount(CompanyBillingProfile $profile, ?Carbon $asOf = null): float
    {
        $asOf ??= now();
        $period = $this->billingPeriodForRun($asOf);
        $lineItems = $this->buildInvoiceLineItems($profile, $period, $asOf);
        $taxRate = (float) PlatformBillingSetting::current()->tax_rate_percent;
        $totals = $this->calculateInvoiceTotals($profile, $lineItems, $taxRate);

        return (float) $totals['total_amount'];
    }

    public function firstCollectionDescription(CompanyBillingProfile $profile, ?Carbon $asOf = null): string
    {
        $asOf ??= now();
        $segments = $this->subscriptionCalculator->advanceCoverageSegments($profile, $asOf);
        if ($segments === []) {
            return 'SaaS-abonnement — vooruitbetaling';
        }

        $labels = array_map(fn (array $segment) => $segment['label'], $segments);

        return 'SaaS-abonnement — vooruitbetaling ('.implode(' + ', $labels).')';
    }

    public function syncSubscriptionLifecycle(?Carbon $asOf = null): int
    {
        $asOf ??= now();
        $count = 0;

        CompanyBillingProfile::query()
            ->with('company')
            ->whereNotNull('mollie_subscription_id')
            ->chunkById(50, function ($profiles) use ($asOf, &$count) {
                foreach ($profiles as $profile) {
                    if ($this->cancelMollieSubscriptionIfNeeded($profile, $asOf)) {
                        $count++;
                    }
                }
            });

        CompanyBillingProfile::query()
            ->with('company')
            ->where('auto_collect_enabled', true)
            ->whereNull('mollie_subscription_id')
            ->chunkById(50, function ($profiles) use (&$count) {
                foreach ($profiles as $profile) {
                    $mandate = PlatformPaymentMandate::query()->where('company_id', $profile->company_id)->first();
                    if ($mandate?->isActive() && $this->ensureMollieSubscription($profile, $mandate)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function ensureMollieSubscription(CompanyBillingProfile $profile, ?PlatformPaymentMandate $mandate = null): bool
    {
        if (! $profile->auto_collect_enabled || $profile->hasActiveMollieSubscription()) {
            return false;
        }

        if (! $this->subscriptionCalculator->isBillable($profile)) {
            return false;
        }

        $mandate ??= PlatformPaymentMandate::query()->where('company_id', $profile->company_id)->first();
        if (! $mandate?->isActive() || ! $mandate->mollie_customer_id) {
            return false;
        }

        $monthlyAmount = $profile->resolveMonthlyAmount();
        if ($monthlyAmount <= 0) {
            return false;
        }

        $startDate = $this->subscriptionCalculator->mollieSubscriptionStartDate($profile);
        if (! $startDate) {
            return false;
        }

        $companyName = $profile->company?->name ?? 'Tenant';
        $payload = [
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format(max(0.01, round($monthlyAmount, 2)), 2, '.', ''),
            ],
            'interval' => '1 month',
            'startDate' => $startDate,
            'description' => mb_substr('SaaS-abonnement '.$companyName, 0, 255),
            'method' => 'directdebit',
            'mandateId' => $mandate->mollie_mandate_id,
            'metadata' => [
                'company_id' => $profile->company_id,
                'type' => 'platform_subscription',
            ],
            'webhookUrl' => $this->mollie->webhookUrl(),
        ];

        $times = $this->subscriptionCalculator->mollieSubscriptionTimes($profile);
        if ($times !== null && $times > 0) {
            $payload['times'] = $times;
        }

        if (empty($payload['webhookUrl'])) {
            unset($payload['webhookUrl']);
        }

        $remote = $this->mollie->createSubscription((string) $mandate->mollie_customer_id, $payload);
        $profile->update([
            'mollie_subscription_id' => (string) ($remote['id'] ?? ''),
            'mollie_subscription_status' => (string) ($remote['status'] ?? 'pending'),
            'mollie_subscription_synced_at' => now(),
        ]);

        return true;
    }

    public function cancelMollieSubscriptionIfNeeded(CompanyBillingProfile $profile, ?Carbon $asOf = null): bool
    {
        if (! $this->subscriptionCalculator->shouldCancelMollieSubscription($profile, $asOf)) {
            return false;
        }

        $mandate = PlatformPaymentMandate::query()->where('company_id', $profile->company_id)->first();
        if (! $profile->mollie_subscription_id || ! $mandate?->mollie_customer_id) {
            return false;
        }

        $this->mollie->cancelSubscription(
            (string) $mandate->mollie_customer_id,
            (string) $profile->mollie_subscription_id
        );

        $profile->update([
            'mollie_subscription_status' => 'canceled',
            'mollie_subscription_synced_at' => now(),
        ]);

        return true;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lineItems
     */
    private function markExtraLinesAppliedIfNeeded(CompanyBillingProfile $profile, array $lineItems): void
    {
        $hasExtras = collect($lineItems)->contains(fn (array $line) => ($line['type'] ?? '') === 'extra');
        if ($hasExtras && $profile->extra_lines_one_time && $profile->extra_lines_applied_at === null) {
            $profile->update(['extra_lines_applied_at' => now()]);
        }
    }

    public function processInvoiceCollection(PlatformInvoice $invoice): PlatformInvoice
    {
        if ($invoice->isPaid() || (float) $invoice->total_amount <= 0) {
            return $invoice;
        }

        $profile = CompanyBillingProfile::query()->where('company_id', $invoice->company_id)->first();
        $mandate = PlatformPaymentMandate::query()->where('company_id', $invoice->company_id)->first();

        if ($profile?->auto_collect_enabled && $mandate?->isActive()) {
            return $this->collectViaMandate($invoice, $mandate);
        }

        return $this->sendPaymentLink($invoice, $profile);
    }

    public function collectViaMandate(PlatformInvoice $invoice, PlatformPaymentMandate $mandate): PlatformInvoice
    {
        $payment = $this->mollie->createRecurringPayment(
            (string) $mandate->mollie_customer_id,
            (string) $mandate->mollie_mandate_id,
            (float) $invoice->total_amount,
            'SaaS factuur '.$invoice->invoice_number,
            [
                'platform_invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
                'type' => PlatformPayment::TYPE_INVOICE,
            ]
        );

        $mollieId = (string) ($payment['id'] ?? '');
        PlatformPayment::query()->create([
            'company_id' => $invoice->company_id,
            'platform_invoice_id' => $invoice->id,
            'type' => PlatformPayment::TYPE_INVOICE,
            'mollie_payment_id' => $mollieId,
            'amount' => $invoice->total_amount,
            'currency' => 'EUR',
            'status' => $this->mollie->mapStatus((string) ($payment['status'] ?? 'open')),
            'mollie_payload' => $payment,
        ]);

        $invoice->update([
            'mollie_payment_id' => $mollieId,
            'collection_method' => 'mandate',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return $invoice->fresh();
    }

    public function sendPaymentLink(PlatformInvoice $invoice, ?CompanyBillingProfile $profile): PlatformInvoice
    {
        $redirectUrl = route('admin.platform-billing.invoices.show', $invoice);
        $mandate = PlatformPaymentMandate::query()->where('company_id', $invoice->company_id)->first();

        if ($profile?->auto_collect_enabled && ! $mandate?->isActive()) {
            return $this->sendFirstPaymentWithMandateSetup($invoice, $profile, $mandate, $redirectUrl);
        }

        $payment = $this->mollie->createOneOffPayment(
            (float) $invoice->total_amount,
            'SaaS factuur '.$invoice->invoice_number,
            $redirectUrl,
            [
                'platform_invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
                'type' => PlatformPayment::TYPE_INVOICE,
            ]
        );

        $mollieId = (string) ($payment['id'] ?? '');
        $checkoutUrl = $this->mollie->checkoutUrl($payment);

        PlatformPayment::query()->create([
            'company_id' => $invoice->company_id,
            'platform_invoice_id' => $invoice->id,
            'type' => PlatformPayment::TYPE_INVOICE,
            'mollie_payment_id' => $mollieId,
            'amount' => $invoice->total_amount,
            'currency' => 'EUR',
            'status' => 'pending',
            'mollie_payload' => $payment,
        ]);

        $invoice->update([
            'mollie_payment_id' => $mollieId,
            'collection_method' => 'payment_link',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->sendInvoiceEmail($invoice, $profile, $checkoutUrl);

        return $invoice->fresh();
    }

    public function requestMandate(Company $company, ?string $recipientEmail = null): array
    {
        $profile = CompanyBillingProfile::query()->firstOrCreate(
            ['company_id' => $company->id],
            ['billing_mode' => CompanyBillingProfile::MODE_PACKAGE]
        );

        $email = trim((string) ($recipientEmail ?: $profile->billingEmailForCompany()));
        if ($email === '') {
            throw new \InvalidArgumentException('Geen facturatie-e-mailadres voor deze tenant.');
        }

        $mandate = PlatformPaymentMandate::query()->firstOrCreate(
            ['company_id' => $company->id],
            ['status' => 'pending']
        );

        if (! $mandate->mollie_customer_id) {
            $customer = $this->mollie->createCustomer(
                $profile->billing_contact_name ?: $company->name,
                $email
            );
            $mandate->update(['mollie_customer_id' => (string) ($customer['id'] ?? '')]);
            $mandate->refresh();
        }

        $redirectUrl = route('admin.platform-billing.mandates.return', ['company' => $company->id]);
        $firstAmount = $this->estimateFirstCollectionAmount($profile);
        $verificationAmount = (float) config('platform-billing.mandate_verification_amount', 0.01);
        $useFirstCollection = $this->subscriptionCalculator->isBillable($profile)
            && $firstAmount > $verificationAmount;

        if ($useFirstCollection) {
            $payment = $this->mollie->createFirstCollectionPayment(
                (string) $mandate->mollie_customer_id,
                $firstAmount,
                $this->firstCollectionDescription($profile),
                $redirectUrl,
                [
                    'company_id' => $company->id,
                    'type' => 'subscription_first_payment',
                ]
            );
            $paymentType = PlatformPayment::TYPE_INVOICE;
            $paymentAmount = $firstAmount;
        } else {
            $payment = $this->mollie->createMandateVerificationPayment(
                (string) $mandate->mollie_customer_id,
                $redirectUrl,
                [
                    'company_id' => $company->id,
                    'type' => PlatformPayment::TYPE_MANDATE_VERIFICATION,
                ]
            );
            $paymentType = PlatformPayment::TYPE_MANDATE_VERIFICATION;
            $paymentAmount = $verificationAmount;
        }

        $mollieId = (string) ($payment['id'] ?? '');
        $checkoutUrl = $this->mollie->checkoutUrl($payment);

        PlatformPayment::query()->updateOrCreate(
            ['mollie_payment_id' => $mollieId],
            [
                'company_id' => $company->id,
                'platform_invoice_id' => null,
                'type' => $paymentType,
                'amount' => $paymentAmount,
                'currency' => 'EUR',
                'status' => 'pending',
                'mollie_payload' => $payment,
            ]
        );

        $mandate->update([
            'verification_mollie_payment_id' => $mollieId,
            'status' => 'pending',
            'last_requested_at' => now(),
        ]);

        $paymentDescription = $useFirstCollection
            ? 'Via onderstaande link geeft u een SEPA-mandaat af en betaalt u de eerste vooruitfacturatie (€'.number_format($firstAmount, 2, ',', '.').'):'
            : 'Via onderstaande link kunt u een SEPA-mandaat afgeven voor automatische maandelijkse incasso (verificatiebetaling €0,01):';

        Mail::raw(
            "Beste {$company->name},\n\n".
            $paymentDescription."\n\n".
            ($checkoutUrl ?? '')."\n\n".
            "Met vriendelijke groet,\nNexa Suite",
            function ($message) use ($email, $company) {
                $message->to($email)->subject('SEPA-mandaat aanvragen — '.$company->name);
            }
        );

        return [
            'checkout_url' => $checkoutUrl,
            'mollie_payment_id' => $mollieId,
        ];
    }

    public function syncPaymentFromMollie(string $molliePaymentId): void
    {
        $remote = $this->mollie->fetchPayment($molliePaymentId);
        if (! $remote) {
            return;
        }

        $paymentRow = PlatformPayment::query()->where('mollie_payment_id', $molliePaymentId)->first();
        if (! $paymentRow) {
            $this->syncOrphanMolliePayment($remote);

            return;
        }

        $status = $this->mollie->mapStatus((string) ($remote['status'] ?? ''));
        $paymentRow->update([
            'status' => $status,
            'mollie_payload' => $remote,
        ]);

        if ($paymentRow->type === PlatformPayment::TYPE_MANDATE_VERIFICATION && $status === 'paid') {
            $this->activateMandateFromVerification($paymentRow->company_id, (string) ($remote['customerId'] ?? ''));
        }

        if ($paymentRow->type === PlatformPayment::TYPE_INVOICE && $status === 'paid') {
            if ($paymentRow->platform_invoice_id) {
                PlatformInvoice::query()->whereKey($paymentRow->platform_invoice_id)->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            $customerId = (string) ($remote['customerId'] ?? '');
            if ($customerId !== '') {
                $this->activateMandateFromVerification($paymentRow->company_id, $customerId);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    private function syncOrphanMolliePayment(array $remote): void
    {
        $subscriptionId = trim((string) ($remote['subscriptionId'] ?? ''));
        if ($subscriptionId === '') {
            return;
        }

        $metadata = is_array($remote['metadata'] ?? null) ? $remote['metadata'] : [];
        $companyId = (int) ($metadata['company_id'] ?? 0);
        if ($companyId <= 0) {
            $profile = CompanyBillingProfile::query()->where('mollie_subscription_id', $subscriptionId)->first();
            $companyId = (int) ($profile?->company_id ?? 0);
        }
        if ($companyId <= 0) {
            return;
        }

        $molliePaymentId = (string) ($remote['id'] ?? '');
        if ($molliePaymentId === '') {
            return;
        }

        if (PlatformPayment::query()->where('mollie_payment_id', $molliePaymentId)->exists()) {
            return;
        }

        $status = $this->mollie->mapStatus((string) ($remote['status'] ?? ''));
        $amount = (float) ($remote['amount']['value'] ?? 0);
        $company = Company::query()->find($companyId);
        if (! $company) {
            return;
        }

        $period = now()->format('Y-m');
        $invoice = PlatformInvoice::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'billing_period' => $period,
                'mollie_payment_id' => $molliePaymentId,
            ],
            [
                'invoice_number' => PlatformBillingSetting::current()->generateInvoiceNumber(),
                'amount' => round($amount / (1 + ((float) PlatformBillingSetting::current()->tax_rate_percent / 100)), 2),
                'tax_amount' => 0,
                'total_amount' => $amount,
                'currency' => 'EUR',
                'status' => $status === 'paid' ? 'paid' : 'sent',
                'invoice_date' => now()->toDateString(),
                'payment_terms_days' => max(1, (int) PlatformBillingSetting::current()->payment_terms_days),
                'due_date' => now()->toDateString(),
                'paid_at' => $status === 'paid' ? now() : null,
                'line_items' => [],
                'collection_method' => 'subscription',
                'sent_at' => now(),
            ]
        );

        if ($status === 'paid' && ! $invoice->paid_at) {
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        }

        PlatformPayment::query()->create([
            'company_id' => $companyId,
            'platform_invoice_id' => $invoice->id,
            'type' => PlatformPayment::TYPE_INVOICE,
            'mollie_payment_id' => $molliePaymentId,
            'amount' => $amount,
            'currency' => 'EUR',
            'status' => $status,
            'mollie_payload' => $remote,
        ]);
    }

    public function runMonthlyBilling(?Carbon $now = null, bool $force = false): int
    {
        $now ??= now();
        $settings = PlatformBillingSetting::current();
        if (! $force && ! $settings->shouldRunNow($now)) {
            return 0;
        }

        $period = $this->billingPeriodForRun($now);
        $count = 0;

        $this->syncSubscriptionLifecycle($now);

        CompanyBillingProfile::query()
            ->with(['company', 'package'])
            ->chunkById(50, function ($profiles) use ($period, $now, &$count) {
                foreach ($profiles as $profile) {
                    if (! $profile->company) {
                        continue;
                    }
                    DB::transaction(function () use ($profile, $period, $now, &$count) {
                        $invoice = $this->generateInvoiceForCompany($profile->company, $period, null, $now);
                        if (! $invoice || $invoice->status !== 'draft') {
                            return;
                        }
                        $this->processInvoiceCollection($invoice);
                        $count++;
                    });
                }
            });

        Log::info('Platform billing run completed', ['period' => $period, 'invoices' => $count]);

        return $count;
    }

    private function activateMandateFromVerification(int $companyId, string $customerId): void
    {
        $mandates = $customerId !== '' ? $this->mollie->fetchCustomerMandates($customerId) : [];
        $valid = collect($mandates)->first(fn ($m) => ($m['status'] ?? '') === 'valid');

        PlatformPaymentMandate::query()->updateOrCreate(
            ['company_id' => $companyId],
            [
                'mollie_customer_id' => $customerId ?: null,
                'mollie_mandate_id' => $valid['id'] ?? null,
                'status' => $valid ? 'active' : 'pending',
                'signed_at' => $valid ? now() : null,
            ]
        );

        if ($valid) {
            $profile = CompanyBillingProfile::query()->where('company_id', $companyId)->first();
            if ($profile) {
                $this->ensureMollieSubscription($profile->fresh());
            }
        }
    }

    private function sendFirstPaymentWithMandateSetup(
        PlatformInvoice $invoice,
        CompanyBillingProfile $profile,
        ?PlatformPaymentMandate $mandate,
        string $redirectUrl,
    ): PlatformInvoice {
        $email = $profile->billingEmailForCompany();
        if (! $email) {
            throw new \InvalidArgumentException('Geen facturatie-e-mailadres voor deze tenant.');
        }

        $mandate ??= PlatformPaymentMandate::query()->firstOrCreate(
            ['company_id' => $invoice->company_id],
            ['status' => 'pending']
        );

        if (! $mandate->mollie_customer_id) {
            $customer = $this->mollie->createCustomer(
                $profile->billing_contact_name ?: ($invoice->company?->name ?? 'Tenant'),
                $email
            );
            $mandate->update(['mollie_customer_id' => (string) ($customer['id'] ?? '')]);
            $mandate->refresh();
        }

        $payment = $this->mollie->createFirstCollectionPayment(
            (string) $mandate->mollie_customer_id,
            (float) $invoice->total_amount,
            'SaaS factuur '.$invoice->invoice_number.' (incl. mandaat)',
            $redirectUrl,
            [
                'platform_invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
                'type' => PlatformPayment::TYPE_INVOICE,
            ]
        );

        $mollieId = (string) ($payment['id'] ?? '');
        $checkoutUrl = $this->mollie->checkoutUrl($payment);

        PlatformPayment::query()->create([
            'company_id' => $invoice->company_id,
            'platform_invoice_id' => $invoice->id,
            'type' => PlatformPayment::TYPE_INVOICE,
            'mollie_payment_id' => $mollieId,
            'amount' => $invoice->total_amount,
            'currency' => 'EUR',
            'status' => 'pending',
            'mollie_payload' => $payment,
        ]);

        $invoice->update([
            'mollie_payment_id' => $mollieId,
            'collection_method' => 'first_payment_mandate',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->sendInvoiceEmail($invoice->fresh(), $profile, $checkoutUrl);

        return $invoice->fresh();
    }

    private function sendInvoiceEmail(PlatformInvoice $invoice, ?CompanyBillingProfile $profile, ?string $checkoutUrl): void
    {
        $email = $profile?->billingEmailForCompany();
        if (! $email) {
            return;
        }

        $body = "Beste {$invoice->company->name},\n\n".
            "Uw SaaS-factuur {$invoice->invoice_number} (periode {$invoice->billing_period}) staat open.\n".
            'Totaalbedrag: €'.number_format((float) $invoice->total_amount, 2, ',', '.')."\n\n";
        if ($checkoutUrl) {
            $body .= "Betaal direct via:\n{$checkoutUrl}\n\n";
            if ($invoice->collection_method === 'first_payment_mandate') {
                $body .= "Met deze betaling geeft u tevens toestemming voor automatische maandelijkse incasso.\n\n";
            }
        }
        $body .= "Met vriendelijke groet,\nNexa Suite";

        try {
            $pdf = $this->pdf->generateAndStore($invoice);
        } catch (\Throwable $e) {
            Log::warning('Platform factuur-PDF kon niet worden gemaakt', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            $pdf = null;
        }

        Mail::raw($body, function ($message) use ($email, $invoice, $pdf) {
            $message->to($email)->subject('SaaS-factuur '.$invoice->invoice_number);
            if ($pdf && ! empty($pdf['bytes'])) {
                $filename = 'saas-factuur-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->invoice_number).'.pdf';
                $message->attachData($pdf['bytes'], $filename, ['mime' => 'application/pdf']);
            }
        });
    }

    public function updateInvoicePaymentTerms(PlatformInvoice $invoice, int $paymentTermsDays): PlatformInvoice
    {
        $paymentTermsDays = max(1, min(365, $paymentTermsDays));
        $invoiceDate = $invoice->invoice_date ?? now();

        $invoice->update([
            'payment_terms_days' => $paymentTermsDays,
            'due_date' => $invoiceDate->copy()->addDays($paymentTermsDays)->toDateString(),
        ]);

        return $invoice->fresh();
    }
}
