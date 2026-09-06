<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\NexaSuiteBookingInvoice;
use App\Models\NexaSuiteBookingInvoiceRide;
use App\Models\NexaSuiteMarketplaceSetting;
use App\Models\PlatformBillingSetting;
use App\Modules\NexaTaxi\Models\RideRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NexaSuiteMarketplaceBillingService
{
    public function __construct(
        protected ModuleDatabaseService $moduleDb,
        protected NexaSuiteMarketplaceInvoicePdfService $pdf,
    ) {}

    /**
     * @return array{generated: int, sent: int, skipped: int}
     */
    public function runMonthlyBilling(bool $force = false, ?Carbon $now = null): array
    {
        $now ??= now();
        $settings = NexaSuiteMarketplaceSetting::current();
        $stats = ['generated' => 0, 'sent' => 0, 'skipped' => 0];

        if (! $settings->auto_generate && ! $force) {
            return $stats;
        }
        if (! $force && ! $settings->shouldRunNow($now)) {
            return $stats;
        }

        $period = $now->copy()->subMonthNoOverflow()->format('Y-m');

        return $this->generateForPeriod($period, $settings->auto_send || $force);
    }

    /**
     * @return array{generated: int, sent: int, skipped: int}
     */
    public function generateForPeriod(string $period, bool $send = false, ?int $companyId = null): array
    {
        $stats = ['generated' => 0, 'sent' => 0, 'skipped' => 0];
        $settings = NexaSuiteMarketplaceSetting::current();

        $companyIds = $companyId
            ? [$companyId]
            : $this->companyIdsWithUnbilledCompletedRides($period);

        foreach ($companyIds as $id) {
            $company = Company::query()->find((int) $id);
            if (! $company) {
                $stats['skipped']++;
                continue;
            }

            $invoice = $this->generateInvoiceForCompany($company, $period, $settings);
            if (! $invoice) {
                $stats['skipped']++;
                continue;
            }

            $stats['generated']++;
            if ($send && $invoice->status === 'draft' && (float) $invoice->total_amount > 0) {
                $this->sendInvoice($invoice);
                $stats['sent']++;
            } elseif ((float) $invoice->total_amount <= 0 && $invoice->status === 'draft') {
                $invoice->update(['status' => 'paid', 'paid_at' => now()]);
            }
        }

        Log::info('NEXA Suite marketplace billing run', ['period' => $period] + $stats);

        return $stats;
    }

    public function generateInvoiceForCompany(
        Company $company,
        string $period,
        ?NexaSuiteMarketplaceSetting $settings = null,
    ): ?NexaSuiteBookingInvoice {
        $settings ??= NexaSuiteMarketplaceSetting::current();
        $existing = NexaSuiteBookingInvoice::query()
            ->where('company_id', $company->id)
            ->where('billing_period', $period)
            ->first();
        if ($existing && $existing->status !== 'cancelled') {
            return $existing;
        }
        if ($existing) {
            $existing->rides()->delete();
            $existing->delete();
        }

        $rides = $this->unbilledCompletedRidesForCompany((int) $company->id, $period);
        if ($rides === []) {
            return null;
        }

        $feePercent = (float) $settings->fee_percent;
        $ridesSubtotal = 0.0;
        $rideRows = [];
        foreach ($rides as $ride) {
            $price = $this->rideBillableAmount($ride);
            $ridesSubtotal += $price;
            $rideRows[] = [
                'ride_request_id' => (int) $ride->id,
                'ride_price' => round($price, 2),
                'fee_amount' => round($price * ($feePercent / 100), 2),
            ];
        }

        $rideCount = count($rideRows);
        $amount = round($ridesSubtotal * ($feePercent / 100), 2);
        $taxRate = (float) $settings->tax_rate_percent;
        $taxAmount = round($amount * ($taxRate / 100), 2);
        $total = round($amount + $taxAmount, 2);
        $invoiceDate = now()->startOfDay();
        $dueDate = $invoiceDate->copy()->addDays(max(1, (int) $settings->payment_terms_days));

        $lineItems = [[
            'type' => 'nexa_suite_fee',
            'description' => $this->lineDescription($rideCount, $feePercent, $ridesSubtotal),
            'quantity' => 1,
            'unit_price' => $amount,
            'total' => $amount,
        ]];

        $issuer = $settings->issuerDetailsSnapshot();
        $recipient = PlatformBillingSetting::current()->recipientDetailsSnapshot(
            $company,
            CompanyBillingProfile::query()->where('company_id', $company->id)->first()
        );

        return DB::transaction(function () use (
            $company,
            $period,
            $settings,
            $rideCount,
            $ridesSubtotal,
            $feePercent,
            $amount,
            $taxAmount,
            $total,
            $invoiceDate,
            $dueDate,
            $lineItems,
            $issuer,
            $recipient,
            $rideRows,
        ) {
            $invoice = NexaSuiteBookingInvoice::query()->create([
                'company_id' => $company->id,
                'invoice_number' => $settings->generateInvoiceNumber(),
                'billing_period' => $period,
                'ride_count' => $rideCount,
                'rides_subtotal' => round($ridesSubtotal, 2),
                'fee_percent' => $feePercent,
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
                'currency' => 'EUR',
                'status' => $total <= 0 ? 'paid' : 'draft',
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'payment_terms_days' => (int) $settings->payment_terms_days,
                'paid_at' => $total <= 0 ? now() : null,
                'line_items' => $lineItems,
                'issuer_details' => $issuer,
                'recipient_details' => $recipient,
            ]);

            foreach ($rideRows as $row) {
                NexaSuiteBookingInvoiceRide::query()->create([
                    'nexa_suite_booking_invoice_id' => $invoice->id,
                    'ride_request_id' => $row['ride_request_id'],
                    'ride_price' => $row['ride_price'],
                    'fee_amount' => $row['fee_amount'],
                ]);
            }

            return $invoice;
        });
    }

    public function sendInvoice(NexaSuiteBookingInvoice $invoice): bool
    {
        $invoice->loadMissing('company.billingProfile');
        $email = $invoice->company?->billingProfile?->billingEmailForCompany()
            ?: trim((string) ($invoice->company?->email ?? ''));
        if ($email === '') {
            Log::warning('NEXA Suite boekingsfactuur niet verzonden: geen facturatie-e-mail', [
                'invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
            ]);

            return false;
        }

        try {
            $pdf = $this->pdf->generateAndStore($invoice);
        } catch (\Throwable $e) {
            Log::warning('NEXA Suite boekingsfactuur-PDF kon niet worden gemaakt', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            $pdf = null;
        }

        $companyName = $invoice->company?->name ?: 'relatie';
        $body = "Beste {$companyName},\n\n".
            "Hierbij ontvangt u factuur {$invoice->invoice_number} voor gereden ritten vanuit NEXA Suite ".
            "(periode {$invoice->billing_period}).\n".
            $invoice->ride_count.' rit(ten), provisie '.(int) $invoice->fee_percent."%.\n".
            'Totaalbedrag: €'.number_format((float) $invoice->total_amount, 2, ',', '.').".\n\n".
            "Met vriendelijke groet,\nNEXA Suite";

        Mail::raw($body, function ($message) use ($email, $invoice, $pdf) {
            $message->to($email)->subject('NEXA Suite boekingsfactuur '.$invoice->invoice_number);
            if ($pdf && ! empty($pdf['bytes'])) {
                $filename = 'nexa-suite-boekingen-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->invoice_number).'.pdf';
                $message->attachData($pdf['bytes'], $filename, ['mime' => 'application/pdf']);
            }
        });

        $invoice->update([
            'status' => $invoice->isPaid() ? 'paid' : 'sent',
            'sent_at' => $invoice->sent_at ?: now(),
        ]);

        return true;
    }

    public function markPaid(NexaSuiteBookingInvoice $invoice): void
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => $invoice->paid_at ?: now(),
        ]);
    }

    public function updateStatus(NexaSuiteBookingInvoice $invoice, string $status): void
    {
        $payload = ['status' => $status];
        if ($status === 'paid') {
            $payload['paid_at'] = $invoice->paid_at ?: now();
        }
        if ($status === 'draft') {
            $payload['paid_at'] = null;
        }
        if ($status === 'cancelled') {
            $payload['paid_at'] = null;
        }
        $invoice->update($payload);
    }

    /**
     * @return list<int>
     */
    public function companyIdsWithUnbilledCompletedRides(string $period): array
    {
        $rides = $this->completedMarketplaceRidesInPeriod($period);
        $billed = NexaSuiteBookingInvoiceRide::query()->pluck('ride_request_id')->all();
        $billedLookup = array_fill_keys(array_map('intval', $billed), true);
        $ids = [];
        foreach ($rides as $ride) {
            if (isset($billedLookup[(int) $ride->id])) {
                continue;
            }
            $companyId = (int) $ride->company_id;
            if ($companyId > 0) {
                $ids[$companyId] = $companyId;
            }
        }

        return array_values($ids);
    }

    /**
     * @return list<RideRequest>
     */
    public function unbilledCompletedRidesForCompany(int $companyId, string $period): array
    {
        $billed = NexaSuiteBookingInvoiceRide::query()->pluck('ride_request_id')->all();
        $billedLookup = array_fill_keys(array_map('intval', $billed), true);

        return array_values(array_filter(
            $this->completedMarketplaceRidesInPeriod($period, $companyId),
            fn (RideRequest $ride) => ! isset($billedLookup[(int) $ride->id])
        ));
    }

    /**
     * @return list<RideRequest>
     */
    public function completedMarketplaceRidesInPeriod(string $period, ?int $companyId = null): array
    {
        [$from, $to] = $this->periodBounds($period);
        $this->moduleDb->ensureModuleStorageReady('taxi');
        $conn = $this->moduleDb->getModuleConnectionName('taxi');

        $query = RideRequest::on($conn)
            ->where('source', RideRequest::SOURCE_NEXA_SUITE)
            ->where('status', RideRequest::STATUS_COMPLETED)
            ->whereBetween('pickup_at', [$from, $to]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->orderBy('pickup_at')->get()->all();
    }

    public function rideBillableAmount(RideRequest $ride): float
    {
        $price = $ride->final_price ?? $ride->quoted_price;

        return round(max(0, (float) $price), 2);
    }

    public function lineDescription(int $rideCount, float $feePercent, float $ridesSubtotal): string
    {
        $countLabel = $rideCount === 1 ? '1 gereden rit' : $rideCount.' gereden ritten';
        $percent = (string) (int) round($feePercent);

        return $countLabel.' vanuit NEXA Suite (provisie '.$percent.'% over €'.number_format($ridesSubtotal, 2, ',', '.').' ritomzet)';
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodBounds(string $period): array
    {
        $start = Carbon::createFromFormat('Y-m', $period, 'Europe/Amsterdam')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [$start, $end];
    }
}
