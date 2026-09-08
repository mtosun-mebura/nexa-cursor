<?php

namespace App\Services\PlatformBilling;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\PlatformBillingSetting;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\PlatformPaymentMandate;
use App\Services\CompanyEmailLogoService;
use App\Services\EmailTemplateService;
use App\Services\EnvService;
use App\Services\NexaPricingService;
use App\Services\SaasBillingStartEmailTemplateService;
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
            ->orderBy('id')
            ->get();

        $subscriptionInvoice = $existing->first(fn (PlatformInvoice $invoice) => $this->invoiceHasSubscriptionLines($invoice));
        if ($subscriptionInvoice) {
            return $subscriptionInvoice;
        }

        $draft = $existing->first(fn (PlatformInvoice $invoice) => $invoice->status === 'draft' && ! $invoice->isPaid());
        if ($draft) {
            return $this->mergeMonthlyChargesIntoInvoice($draft, $profile, $billingPeriod, $asOf, $settings);
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
     * @param  list<array<string, mixed>>  $charges
     */
    public function issueAddonChargeInvoice(CompanyBillingProfile $profile, array $charges): ?PlatformInvoice
    {
        $charges = array_values(array_filter($charges, fn (array $charge) => round((float) ($charge['amount'] ?? 0), 2) > 0));
        if ($charges === []) {
            return null;
        }

        $settings = PlatformBillingSetting::current();
        $profile->loadMissing('company');
        $company = $profile->company;
        if (! $company) {
            return null;
        }

        $period = (string) ($charges[0]['period'] ?? now()->format('Y-m'));
        $lines = [];
        foreach ($charges as $charge) {
            $quantity = max(1, (int) ($charge['quantity'] ?? 1));
            $total = round((float) ($charge['amount'] ?? 0), 2);
            $lines[] = [
                'description' => (string) ($charge['description'] ?? 'Aanvullende module'),
                'quantity' => $quantity,
                'unit_price' => round($total / $quantity, 2),
                'total' => $total,
                'type' => 'addon',
                'addon_key' => $charge['key'] ?? null,
                'billing_period' => $charge['period'] ?? $period,
                'activation' => true,
            ];
        }

        $draft = PlatformInvoice::query()
            ->where('company_id', $company->id)
            ->where('billing_period', $period)
            ->where('status', 'draft')
            ->orderByDesc('id')
            ->first();

        if ($draft && ! $draft->isPaid()) {
            $existingLines = is_array($draft->line_items) ? $draft->line_items : [];
            $merged = $existingLines;
            foreach ($lines as $line) {
                if (! $this->invoiceHasEquivalentLine($existingLines, $line)) {
                    $merged[] = $line;
                }
            }
            $lineItems = $this->appendDiscountRows($merged, $profile);
            $this->recalculateInvoiceTotals($draft, $profile, $lineItems, $settings);

            return $this->collectAddonInvoice($draft->fresh());
        }

        $lineItems = $this->appendDiscountRows($lines, $profile);
        $taxRate = (float) $settings->tax_rate_percent;
        $totals = $this->calculateInvoiceTotals($profile, $lineItems, $taxRate);
        $paymentTermsDays = max(1, (int) $settings->payment_terms_days);

        $invoice = PlatformInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => $settings->generateInvoiceNumber(),
            'billing_period' => $period,
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

        return $this->collectAddonInvoice($invoice);
    }

    private function collectAddonInvoice(PlatformInvoice $invoice): PlatformInvoice
    {
        if ($invoice->isPaid() || (float) $invoice->total_amount <= 0) {
            return $invoice;
        }

        try {
            return $this->processInvoiceCollection($invoice);
        } catch (\Throwable $e) {
            Log::warning('Incasso aanvullende module mislukt; factuur blijft open', [
                'invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
                'error' => $e->getMessage(),
            ]);

            return $invoice->fresh() ?? $invoice;
        }
    }

    private function mergeMonthlyChargesIntoInvoice(
        PlatformInvoice $invoice,
        CompanyBillingProfile $profile,
        string $billingPeriod,
        Carbon $asOf,
        PlatformBillingSetting $settings,
    ): PlatformInvoice {
        $existing = is_array($invoice->line_items) ? $invoice->line_items : [];
        $monthly = $this->buildChargeLineItems($profile, $billingPeriod, $asOf);
        $merged = $existing;
        foreach ($monthly as $line) {
            if (! $this->invoiceHasEquivalentLine($existing, $line)) {
                $merged[] = $line;
            }
        }
        $lineItems = $this->appendDiscountRows($merged, $profile);
        $this->recalculateInvoiceTotals($invoice, $profile, $lineItems, $settings);
        $this->markExtraLinesAppliedIfNeeded($profile, $lineItems);

        return $invoice->fresh() ?? $invoice;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lineItems
     */
    private function recalculateInvoiceTotals(
        PlatformInvoice $invoice,
        CompanyBillingProfile $profile,
        array $lineItems,
        PlatformBillingSetting $settings,
    ): void {
        $taxRate = (float) $settings->tax_rate_percent;
        $totals = $this->calculateInvoiceTotals($profile, $lineItems, $taxRate);
        $invoice->update([
            'line_items' => $lineItems,
            'amount' => $totals['amount'],
            'tax_amount' => $totals['tax_amount'],
            'total_amount' => $totals['total_amount'],
            'status' => $totals['total_amount'] <= 0 ? 'paid' : $invoice->status,
            'paid_at' => $totals['total_amount'] <= 0 ? ($invoice->paid_at ?? now()) : $invoice->paid_at,
        ]);
    }

    private function invoiceHasSubscriptionLines(PlatformInvoice $invoice): bool
    {
        return collect($invoice->line_items ?? [])->contains(
            fn ($line) => is_array($line) && ($line['type'] ?? '') === 'subscription'
        );
    }

    /**
     * @param  array<int, mixed>  $lines
     * @param  array<string, mixed>  $candidate
     */
    private function invoiceHasEquivalentLine(array $lines, array $candidate): bool
    {
        $candidateKey = $this->invoiceLineIdentity($candidate);
        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }
            if ($this->invoiceLineIdentity($line) === $candidateKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function invoiceLineIdentity(array $line): string
    {
        return implode('|', [
            (string) ($line['type'] ?? ''),
            (string) ($line['addon_key'] ?? ''),
            (string) ($line['billing_period'] ?? ''),
            ! empty($line['activation']) ? 'activation' : 'recurring',
            (string) ($line['platform_billing_line_item_id'] ?? ''),
            (string) ($line['description'] ?? ''),
        ]);
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

        $profile->loadMissing(['package', 'lineItems', 'company']);
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
                $this->appendPackageAddonChargeLines($lines, $profile, $billingPeriod, null);
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
                foreach ($segments as $segment) {
                    $this->appendPackageAddonChargeLines($lines, $profile, $segment['key'], $segment);
                }
            }
        }

        if ($profile->pending_proration_applied_at === null) {
            $proration = round((float) ($profile->pending_proration_amount ?? 0), 2);
            if ($proration > 0) {
                $lines[] = [
                    'description' => trim((string) ($profile->pending_proration_label ?: 'Upgrade (resterende dagen van deze maand)')),
                    'quantity' => 1,
                    'unit_price' => $proration,
                    'total' => $proration,
                    'type' => 'proration',
                    'billing_period' => $billingPeriod,
                ];
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
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array{key?: string, label?: string, fraction?: float}|null  $segment
     */
    private function appendPackageAddonChargeLines(array &$lines, CompanyBillingProfile $profile, string $billingPeriod, ?array $segment): void
    {
        $fraction = $segment === null ? 1.0 : (float) ($segment['fraction'] ?? 1);
        $asOf = isset($segment['key'])
            ? Carbon::createFromFormat('Y-m', $segment['key'])->startOfMonth()
            : Carbon::createFromFormat('Y-m', $billingPeriod)->startOfMonth();
        $skipPrepaid = $segment['key'] ?? $billingPeriod;
        foreach ($profile->packageAddonLines($asOf, false, $skipPrepaid) as $addon) {
            $quantity = max(1, (int) ($addon['quantity'] ?? 1));
            $total = round((float) ($addon['total'] ?? 0) * $fraction, 2);
            if ($total <= 0) {
                continue;
            }
            $unitPrice = round($total / $quantity, 2);
            $name = trim((string) ($addon['name'] ?? 'Aanvullende module'));
            $description = $segment === null
                ? $name
                : $this->subscriptionCalculator->subscriptionLineDescriptionForSegment($name, $segment);

            $lines[] = [
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $total,
                'type' => 'addon',
                'addon_key' => $addon['key'] ?? null,
                'billing_period' => $billingPeriod,
            ];
        }
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

    /**
     * Voorbeeldfactuur op basis van de actuele facturatie-instellingen (geen echte tenant).
     *
     * @return array<string, mixed>
     */
    public function dummyWorkflowInvoicePreview(?Carbon $now = null): array
    {
        $now ??= now();
        $settings = PlatformBillingSetting::current();
        $paymentTermsDays = max(1, (int) $settings->payment_terms_days);
        $billingDay = max(1, min(28, (int) $settings->billing_day));
        $invoiceDate = $now->copy()->startOfMonth()->day($billingDay)->startOfDay();
        if ($invoiceDate->gt($now)) {
            $invoiceDate->subMonthNoOverflow();
        }
        $dueDate = $invoiceDate->copy()->addDays($paymentTermsDays);
        $period = $invoiceDate->format('Y-m');
        $taxRate = (float) $settings->tax_rate_percent;
        $unitPrice = 179.00;
        $taxAmount = round($unitPrice * $taxRate / 100, 2);
        $total = round($unitPrice + $taxAmount, 2);
        $invoiceNumber = str_replace(
            ['{prefix}', '{year}', '{number}'],
            [
                $settings->invoice_number_prefix ?: 'SAAS',
                $invoiceDate->format('Y'),
                'VOORB',
            ],
            $settings->invoice_number_format ?: '{prefix}-{year}-{number}'
        );
        $issuer = $settings->issuerDetailsSnapshot();
        $dummyInvoice = new PlatformInvoice([
            'invoice_number' => $invoiceNumber,
            'billing_period' => $period,
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'payment_terms_days' => $paymentTermsDays,
            'amount' => $unitPrice,
            'tax_amount' => $taxAmount,
            'total_amount' => $total,
            'status' => 'sent',
            'issuer_details' => $issuer,
        ]);

        return [
            'billing_period' => $period,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'payment_terms_days' => $paymentTermsDays,
            'line_items' => [[
                'description' => 'NEXA-abonnement — '.$period,
                'quantity' => 1,
                'unit_price' => $unitPrice,
                'total' => $unitPrice,
                'type' => 'subscription',
                'billing_period' => $period,
            ]],
            'amount' => $unitPrice,
            'tax_amount' => $taxAmount,
            'total_amount' => $total,
            'tax_rate' => $taxRate,
            'issuer' => $issuer,
            'recipient' => [
                'name' => 'Voorbeeld Taxi B.V.',
                'contact_name' => 'Administratie',
                'address' => 'Voorbeeldstraat 12',
                'postal_code' => '7511 AB',
                'city' => 'Enschede',
                'email' => 'facturatie@voorbeeld.taxi',
            ],
            'payment_terms_text' => PlatformBillingSetting::invoicePaymentTermsTextForInvoice($dummyInvoice),
            'company_name' => 'Voorbeeld Taxi B.V.',
            'amount_formatted' => '€'.number_format($total, 2, ',', '.'),
            'due_formatted' => $dueDate->format('d-m-Y'),
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

        try {
            $count += app(TenantSubscriptionService::class)->applyDueChanges($asOf);
        } catch (\Throwable $e) {
            Log::warning('Geplande abonnementswijzigingen toepassen mislukt', ['error' => $e->getMessage()]);
        }

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

        $monthlyAmount = $profile->mollieRecurringAmount();
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

        $hasProration = collect($lineItems)->contains(fn (array $line) => ($line['type'] ?? '') === 'proration');
        if ($hasProration && $profile->pending_proration_applied_at === null) {
            $profile->update(['pending_proration_applied_at' => now()]);
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
        $checkoutUrl = $this->ensurePaymentCheckoutUrl($invoice, $profile);
        $this->sendInvoiceEmail($invoice->fresh(), $profile, $checkoutUrl);

        return $invoice->fresh();
    }

    /**
     * Verstuur (of herverstuur) de NEXA-factuur per e-mail naar de tenant.
     * Werkt altijd: concept, openstaand én betaald. Bij openstaande facturen
     * wordt een betaallink meegestuurd indien beschikbaar of nieuw aangemaakt.
     */
    public function sendInvoiceToTenant(PlatformInvoice $invoice): PlatformInvoice
    {
        $invoice->loadMissing(['company', 'latestPayment']);
        $profile = CompanyBillingProfile::query()
            ->with('company')
            ->where('company_id', $invoice->company_id)
            ->first();

        if (! $profile) {
            $profile = CompanyBillingProfile::query()->firstOrCreate(
                ['company_id' => $invoice->company_id],
                ['billing_mode' => CompanyBillingProfile::MODE_PACKAGE]
            );
            $profile->loadMissing('company');
        }

        $email = $profile->billingEmailForCompany();
        if (! $email) {
            throw new \InvalidArgumentException('Geen facturatie-e-mailadres voor deze tenant.');
        }

        $checkoutUrl = null;
        if (! $invoice->isPaid() && (float) $invoice->total_amount > 0) {
            $checkoutUrl = $this->ensurePaymentCheckoutUrl($invoice, $profile);
        }

        $updates = ['sent_at' => now()];
        if (! $invoice->isPaid() && $invoice->status === 'draft') {
            $updates['status'] = 'sent';
        }
        $invoice->update($updates);

        $this->sendInvoiceEmail($invoice->fresh(['company']), $profile, $checkoutUrl);

        return $invoice->fresh();
    }

    /**
     * Zorg voor een bruikbare Mollie-checkout-URL zonder e-mail te versturen.
     */
    public function ensurePaymentCheckoutUrl(PlatformInvoice $invoice, ?CompanyBillingProfile $profile): ?string
    {
        if ($invoice->isPaid() || (float) $invoice->total_amount <= 0) {
            return null;
        }

        $invoice->loadMissing('latestPayment');
        $existing = $invoice->latestPayment;
        if ($existing && in_array((string) $existing->status, ['pending', 'open'], true) && is_array($existing->mollie_payload)) {
            $existingUrl = $this->mollie->checkoutUrl($existing->mollie_payload);
            if ($existingUrl) {
                return $existingUrl;
            }
        }

        $redirectUrl = route('admin.platform-billing.invoices.show', $invoice);
        $mandate = PlatformPaymentMandate::query()->where('company_id', $invoice->company_id)->first();

        if ($profile?->auto_collect_enabled && ! $mandate?->isActive()) {
            try {
                $this->sendFirstPaymentWithMandateSetup($invoice, $profile, $mandate, $redirectUrl, sendEmail: false);

                return $this->checkoutUrlFromLatestPayment($invoice->fresh(['latestPayment']));
            } catch (\Throwable $e) {
                Log::warning('Eerste-betaling/mandaat mislukt; val terug op eenmalige betaallink', [
                    'invoice_id' => $invoice->id,
                    'company_id' => $invoice->company_id,
                    'error' => $e->getMessage(),
                ]);
            }
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
            'status' => $invoice->isPaid() ? $invoice->status : 'sent',
            'sent_at' => $invoice->sent_at ?? now(),
        ]);

        return $checkoutUrl;
    }

    private function checkoutUrlFromLatestPayment(PlatformInvoice $invoice): ?string
    {
        $payment = $invoice->latestPayment;
        if (! $payment || ! is_array($payment->mollie_payload)) {
            return null;
        }

        return $this->mollie->checkoutUrl($payment->mollie_payload);
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

            app(PlatformDunningService::class)->clearRestrictionIfSettled((int) $paymentRow->company_id);
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

        if ($status === 'paid') {
            app(PlatformDunningService::class)->clearRestrictionIfSettled($companyId);
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
                        if ($invoice && $invoice->status === 'draft') {
                            $this->processInvoiceCollection($invoice);
                            $count++;
                        }
                        app(TenantSubscriptionService::class)->syncMolliePlan(
                            $profile->fresh(['package', 'company']) ?? $profile
                        );
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
        bool $sendEmail = true,
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

        if ($sendEmail) {
            $this->sendInvoiceEmail($invoice->fresh(), $profile, $checkoutUrl);
        }

        return $invoice->fresh();
    }

    private function sendInvoiceEmail(PlatformInvoice $invoice, ?CompanyBillingProfile $profile, ?string $checkoutUrl): void
    {
        $email = $profile?->billingEmailForCompany();
        if (! $email) {
            return;
        }

        $invoice->loadMissing('company');
        $isFirstCollection = $invoice->collection_method === 'first_payment_mandate'
            || PlatformInvoice::query()->where('company_id', $invoice->company_id)->count() <= 1;

        try {
            $pdf = $this->pdf->generateAndStore($invoice);
        } catch (\Throwable $e) {
            Log::warning('Platform factuur-PDF kon niet worden gemaakt', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            $pdf = null;
        }

        if ($isFirstCollection && $profile && $checkoutUrl) {
            try {
                $this->sendBillingStartEmail($invoice, $profile, $checkoutUrl, $email, $pdf);
            } catch (\Throwable $e) {
                Log::warning('Eerste-betalingmail versturen mislukt', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return;
        }

        $statusLine = $invoice->isPaid()
            ? "Hierbij ontvangt u uw NEXA-factuur {$invoice->invoice_number} (periode {$invoice->billing_period})."
            : "Uw NEXA-factuur {$invoice->invoice_number} (periode {$invoice->billing_period}) staat open.";

        $body = "Beste {$invoice->company->name},\n\n".
            $statusLine."\n".
            'Totaalbedrag: €'.number_format((float) $invoice->total_amount, 2, ',', '.')."\n\n";
        if ($checkoutUrl) {
            $body .= "Betaal direct via:\n{$checkoutUrl}\n\n";
            if ($invoice->collection_method === 'first_payment_mandate') {
                $body .= "Met deze betaling geeft u tevens toestemming voor automatische maandelijkse incasso.\n\n";
            }
        }
        $body .= "Met vriendelijke groet,\nNexa Suite";

        Mail::raw($body, function ($message) use ($email, $invoice, $pdf) {
            $message->to($email)->subject('NEXA-factuur '.$invoice->invoice_number);
            if ($pdf && ! empty($pdf['bytes'])) {
                $filename = 'saas-factuur-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->invoice_number).'.pdf';
                $message->attachData($pdf['bytes'], $filename, ['mime' => 'application/pdf']);
            }
        });
    }

    /**
     * @param  array{bytes?: string}|null  $pdf
     */
    private function sendBillingStartEmail(
        PlatformInvoice $invoice,
        CompanyBillingProfile $profile,
        string $checkoutUrl,
        string $email,
        ?array $pdf,
    ): void {
        $billingStart = app(SaasBillingStartEmailTemplateService::class);
        $template = $billingStart->resolveActive();
        $collection = $this->subscriptionCalculator->firstCollectionPresentation(
            $profile,
            $profile->subscription_start_date ?? now()
        );
        $pricing = app(NexaPricingService::class);
        $packageName = $profile->subscriptionLineLabel();
        $firstAmount = (float) $invoice->total_amount > 0
            ? (float) $invoice->total_amount
            : $collection['first_amount_incl'];
        $variables = array_merge(
            [
                'COMPANY_NAME' => $invoice->company?->name ?: 'klant',
                'PACKAGE_NAME' => $packageName,
                'START_DATE' => $collection['start_label'],
                'FIRST_AMOUNT' => $pricing->displayAmount(number_format($firstAmount, 2, '.', '')),
                'MONTHLY_AMOUNT' => $pricing->displayAmount(number_format($collection['monthly_amount'], 2, '.', '')),
                'RECURRING_FROM' => $collection['recurring_from_label'],
                'INVOICE_NUMBER' => (string) $invoice->invoice_number,
                'PAYMENT_URL' => $checkoutUrl,
            ],
            \App\Support\NexaBranding::emailLogoTemplateVariable()
        );

        $mail = app(EmailTemplateService::class);
        $subject = $mail->parseTemplateVariables((string) $template->subject, $variables);
        $html = $mail->parseTemplateVariables((string) $template->html_content, $variables);
        $text = $mail->parseTemplateVariables((string) ($template->text_content ?: strip_tags($html)), $variables);

        $env = app(EnvService::class);
        $env->applyPlatformMailConfigToRuntime();
        $from = $env->resolveMailFromHeaders(null, true);
        $logoService = app(CompanyEmailLogoService::class);
        $toName = $profile->billing_contact_name ?: ($invoice->company?->name ?: $email);

        Mail::send([], [], function ($message) use (
            $email,
            $toName,
            $subject,
            $html,
            $text,
            $from,
            $pdf,
            $invoice,
            $logoService
        ) {
            if (! empty($from['from_address'])) {
                $message->from($from['from_address'], $from['from_name'] ?: SaasBillingStartEmailTemplateService::FROM_NAME);
            }
            $message->to($email, $toName)->subject($subject);
            if ($html) {
                $message->html($logoService->embedInHtml($html, $message, null, 'NEXA Suite'));
            }
            if ($text) {
                $message->text($text);
            }
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

    /**
     * @param  array{
     *     status: string,
     *     payment_terms_days: int,
     *     notes?: string|null,
     *     line_items: array<int, array<string, mixed>>
     * }  $data
     */
    public function updateInvoice(PlatformInvoice $invoice, array $data): PlatformInvoice
    {
        $settings = PlatformBillingSetting::current();
        $issuer = is_array($invoice->issuer_details) && $invoice->issuer_details !== []
            ? $invoice->issuer_details
            : $settings->issuerDetailsSnapshot();
        $taxRate = (float) ($issuer['tax_rate'] ?? $settings->tax_rate_percent);

        $lineItems = [];
        foreach ($data['line_items'] as $row) {
            $quantity = round((float) ($row['quantity'] ?? 1), 2);
            if ($quantity <= 0) {
                $quantity = 1;
            }
            $unitPrice = round((float) ($row['unit_price'] ?? 0), 2);
            $line = [
                'description' => trim((string) ($row['description'] ?? '')),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
                'type' => trim((string) ($row['type'] ?? '')) ?: 'extra',
            ];
            if (! empty($row['billing_period'])) {
                $line['billing_period'] = (string) $row['billing_period'];
            }
            if (! empty($row['platform_billing_line_item_id'])) {
                $line['platform_billing_line_item_id'] = (int) $row['platform_billing_line_item_id'];
            }
            $lineItems[] = $line;
        }

        $totals = $this->calculateTotalsFromLineItems($lineItems, $taxRate);
        $paymentTermsDays = max(1, min(365, (int) $data['payment_terms_days']));
        $invoiceDate = $invoice->invoice_date ?? now();
        $status = (string) $data['status'];

        $paidAt = $invoice->paid_at;
        $sentAt = $invoice->sent_at;
        if ($status === 'paid') {
            $paidAt = $paidAt ?? now();
        } else {
            $paidAt = null;
        }
        if (in_array($status, ['sent', 'paid'], true)) {
            $sentAt = $sentAt ?? now();
        }

        $notes = array_key_exists('notes', $data) ? trim((string) ($data['notes'] ?? '')) : (string) ($invoice->notes ?? '');
        $notes = $notes === '' ? null : $notes;

        $invoice->update([
            'line_items' => $lineItems,
            'amount' => $totals['amount'],
            'tax_amount' => $totals['tax_amount'],
            'total_amount' => $totals['total_amount'],
            'payment_terms_days' => $paymentTermsDays,
            'due_date' => $invoiceDate->copy()->addDays($paymentTermsDays)->toDateString(),
            'status' => $status,
            'paid_at' => $paidAt,
            'sent_at' => $sentAt,
            'notes' => $notes,
            'pdf_path' => null,
        ]);

        return $invoice->fresh();
    }
}
