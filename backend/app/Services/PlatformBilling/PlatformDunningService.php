<?php

namespace App\Services\PlatformBilling;

use App\Models\CompanyBillingProfile;
use App\Models\PlatformBillingSetting;
use App\Models\PlatformInvoice;
use App\Models\PlatformPaymentMandate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PlatformDunningService
{
    public const ACTION_NONE = 'none';

    public const ACTION_PAID = 'paid';

    public const ACTION_FIRST = 'first';

    public const ACTION_SECOND = 'second';

    public const ACTION_BLOCK = 'block';

    public function __construct(
        private readonly PlatformMollieService $mollie,
        private readonly TenantBillingAccessService $access,
        private readonly PlatformInvoicePdfService $pdf,
    ) {}

    /**
     * Controleer openstaande SaaS-facturen: Mollie-sync, 1e/2e aanmaning, daarna tenant-blokkade.
     *
     * @return array{checked: int, paid: int, first: int, second: int, blocked: int}
     */
    public function run(?Carbon $now = null): array
    {
        $now ??= now();
        $stats = ['checked' => 0, 'paid' => 0, 'first' => 0, 'second' => 0, 'blocked' => 0];

        PlatformInvoice::query()
            ->with(['company.billingProfile'])
            ->whereNotIn('status', ['paid', 'draft'])
            ->whereNull('paid_at')
            ->where('total_amount', '>', 0)
            ->orderBy('id')
            ->chunkById(50, function ($invoices) use ($now, &$stats) {
                foreach ($invoices as $invoice) {
                    $stats['checked']++;
                    $action = $this->processInvoice($invoice, $now);
                    if (isset($stats[$action])) {
                        $stats[$action]++;
                    }
                }
            });

        Log::info('Platform dunning run completed', $stats);

        return $stats;
    }

    public function processInvoice(PlatformInvoice $invoice, ?Carbon $now = null): string
    {
        $now ??= now();
        $invoice->refresh();

        if ($invoice->isPaid() || (float) $invoice->total_amount <= 0 || $invoice->status === 'draft') {
            return self::ACTION_NONE;
        }

        $this->refreshPaymentStatusFromMollie($invoice);
        $invoice->refresh();

        if ($invoice->isPaid()) {
            $this->clearRestrictionIfSettled((int) $invoice->company_id);

            return self::ACTION_PAID;
        }

        $firstIntervalDays = PlatformBillingSetting::dunningFirstIntervalDays();
        $secondIntervalDays = PlatformBillingSetting::dunningSecondIntervalDays();
        $dueDate = $this->dueDate($invoice);

        if ($invoice->first_reminder_sent_at === null) {
            $firstEligible = $dueDate->copy()->startOfDay()->addDays($firstIntervalDays);
            if ($now->copy()->startOfDay()->gte($firstEligible)) {
                $this->sendReminder($invoice, 1);

                return self::ACTION_FIRST;
            }

            return self::ACTION_NONE;
        }

        if ($invoice->second_reminder_sent_at === null) {
            $secondEligible = $invoice->first_reminder_sent_at->copy()->startOfDay()->addDays($secondIntervalDays);
            if ($now->copy()->startOfDay()->gte($secondEligible)) {
                $this->sendReminder($invoice, 2);

                return self::ACTION_SECOND;
            }

            return self::ACTION_NONE;
        }

        if ($invoice->block_waived_at !== null) {
            return self::ACTION_NONE;
        }

        $blockEligible = $invoice->second_reminder_sent_at->copy()->startOfDay()->addDays($secondIntervalDays);
        if ($now->copy()->startOfDay()->lt($blockEligible)) {
            return self::ACTION_NONE;
        }

        $this->blockTenantForInvoice($invoice);

        return self::ACTION_BLOCK;
    }

    public function clearRestrictionIfSettled(int $companyId): void
    {
        if ($companyId <= 0) {
            return;
        }

        $profile = CompanyBillingProfile::query()->where('company_id', $companyId)->first();
        if (! $profile) {
            return;
        }

        if (($profile->access_restriction_source ?? null) === TenantBillingAccessService::SOURCE_MANUAL) {
            return;
        }

        if (! in_array($profile->access_restriction, [TenantBillingAccessService::BOOKINGS, TenantBillingAccessService::FULL], true)) {
            return;
        }

        $stillBlocking = PlatformInvoice::query()
            ->where('company_id', $companyId)
            ->whereNotIn('status', ['paid', 'draft'])
            ->whereNull('paid_at')
            ->where('total_amount', '>', 0)
            ->whereNotNull('second_reminder_sent_at')
            ->whereNull('block_waived_at')
            ->get()
            ->contains(function (PlatformInvoice $invoice) {
                $secondIntervalDays = PlatformBillingSetting::dunningSecondIntervalDays();
                $blockEligible = $invoice->second_reminder_sent_at->copy()->startOfDay()->addDays($secondIntervalDays);

                return now()->copy()->startOfDay()->gte($blockEligible);
            });

        if (! $stillBlocking) {
            $this->access->clearRestriction($profile);
        }
    }

    public function dueDate(PlatformInvoice $invoice): Carbon
    {
        if ($invoice->due_date) {
            return $invoice->due_date->copy()->startOfDay();
        }

        $termsDays = PlatformBillingSetting::paymentTermsDaysForInvoice($invoice);
        $invoiceDate = $invoice->invoice_date?->copy() ?? now();

        return $invoiceDate->startOfDay()->addDays($termsDays);
    }

    private function refreshPaymentStatusFromMollie(PlatformInvoice $invoice): void
    {
        if (! $this->mollie->isConfigured()) {
            return;
        }

        try {
            if ($invoice->mollie_payment_id) {
                app(PlatformBillingService::class)->syncPaymentFromMollie((string) $invoice->mollie_payment_id);
                $invoice->refresh();
                if ($invoice->isPaid()) {
                    return;
                }
            }

            $this->syncMatchingListedPayments($invoice);
        } catch (\Throwable $e) {
            Log::warning('Platform dunning: Mollie-sync mislukt', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function syncMatchingListedPayments(PlatformInvoice $invoice): void
    {
        $mandate = PlatformPaymentMandate::query()->where('company_id', $invoice->company_id)->first();
        $customerId = trim((string) ($mandate?->mollie_customer_id ?? ''));
        $profile = $invoice->company?->billingProfile
            ?? CompanyBillingProfile::query()->where('company_id', $invoice->company_id)->first();
        $subscriptionId = trim((string) ($profile?->mollie_subscription_id ?? ''));

        $payments = [];
        if ($customerId !== '') {
            $payments = array_merge($payments, $this->mollie->listCustomerPayments($customerId));
        }
        if ($customerId !== '' && $subscriptionId !== '') {
            $payments = array_merge($payments, $this->mollie->listSubscriptionPayments($customerId, $subscriptionId));
        }

        $seen = [];
        foreach ($payments as $remote) {
            if (! is_array($remote)) {
                continue;
            }
            $id = (string) ($remote['id'] ?? '');
            if ($id === '' || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            if (! $this->remotePaymentMatchesInvoice($invoice, $remote)) {
                continue;
            }
            app(PlatformBillingService::class)->syncPaymentFromMollie($id);
            $invoice->refresh();
            if ($invoice->isPaid()) {
                return;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    private function remotePaymentMatchesInvoice(PlatformInvoice $invoice, array $remote): bool
    {
        if (($remote['status'] ?? '') !== 'paid') {
            return false;
        }

        $meta = is_array($remote['metadata'] ?? null) ? $remote['metadata'] : [];
        if ((int) ($meta['platform_invoice_id'] ?? 0) === (int) $invoice->id) {
            return true;
        }
        if (trim((string) ($meta['billing_period'] ?? '')) === (string) $invoice->billing_period) {
            return true;
        }
        if ((string) ($remote['id'] ?? '') === (string) ($invoice->mollie_payment_id ?? '')) {
            return true;
        }

        $paidAt = (string) ($remote['paidAt'] ?? '');
        $amount = (float) ($remote['amount']['value'] ?? 0);
        if ($paidAt !== '' && substr($paidAt, 0, 7) === (string) $invoice->billing_period
            && abs($amount - (float) $invoice->total_amount) < 0.05) {
            return true;
        }

        return false;
    }

    private function sendReminder(PlatformInvoice $invoice, int $step): void
    {
        $invoice->loadMissing('company.billingProfile');
        $profile = $invoice->company?->billingProfile
            ?? CompanyBillingProfile::query()->where('company_id', $invoice->company_id)->first();
        $email = $profile?->billingEmailForCompany();
        if (! $email) {
            Log::warning('Platform aanmaning niet verstuurd: geen facturatie-e-mail', [
                'invoice_id' => $invoice->id,
                'step' => $step,
            ]);

            return;
        }

        $copy = $this->composeReminderMail(
            $step,
            (string) ($invoice->company?->name ?? 'tenant'),
            (string) $invoice->invoice_number,
            (string) $invoice->billing_period,
            $this->dueDate($invoice)->format('d-m-Y'),
            '€'.number_format((float) $invoice->total_amount, 2, ',', '.'),
        );

        if ($step === 1) {
            $invoice->first_reminder_sent_at = now();
        } else {
            $invoice->second_reminder_sent_at = now();
        }

        $this->sendMail($email, $copy['subject'], $copy['body'], $invoice);
        $invoice->save();
    }

    /**
     * Tekst van de 1e of 2e aanmaning, met de actuele termijn uit facturatie-instellingen.
     *
     * @return array{subject: string, body: string}
     */
    public function composeReminderMail(
        int $step,
        string $companyName,
        string $invoiceNumber,
        string $billingPeriod,
        string $dueFormatted,
        string $amountFormatted,
    ): array {
        $payWithinDays = PlatformBillingSetting::dunningSecondIntervalDays();

        if ($step === 2) {
            $subject = 'Tweede aanmaning NEXA-factuur '.$invoiceNumber;
            $intro = 'Ondanks onze eerdere aanmaning is uw NEXA-factuur nog niet voldaan.';
        } else {
            $subject = 'Aanmaning NEXA-factuur '.$invoiceNumber;
            $intro = 'Uw NEXA-factuur is na de betaaltermijn nog niet voldaan.';
        }

        $body = "Beste {$companyName},\n\n".
            "{$intro}\n\n".
            "Factuurnummer: {$invoiceNumber}\n".
            "Periode: {$billingPeriod}\n".
            "Vervaldatum: {$dueFormatted}\n".
            "Openstaand bedrag: {$amountFormatted}\n\n".
            "Gelieve binnen {$payWithinDays} dagen te betalen. Blijft betaling uit, dan kunnen wij de boekingsmodule of de volledige omgeving blokkeren.\n\n".
            "Met vriendelijke groet,\nNexa Suite";

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    private function blockTenantForInvoice(PlatformInvoice $invoice): void
    {
        $invoice->loadMissing('company.billingProfile');
        $profile = CompanyBillingProfile::query()->firstOrCreate(
            ['company_id' => $invoice->company_id],
            ['billing_mode' => CompanyBillingProfile::MODE_PACKAGE]
        );

        if ($invoice->block_waived_at) {
            return;
        }

        $mode = $this->access->overdueBlockMode($profile);
        $this->access->applyRestriction($profile, $mode, TenantBillingAccessService::SOURCE_DUNNING, $invoice);

        if ($invoice->blocked_at === null) {
            $invoice->blocked_at = now();
            $invoice->save();
            $this->sendBlockNotice($invoice, $profile, $mode);
        }
    }

    private function sendBlockNotice(PlatformInvoice $invoice, CompanyBillingProfile $profile, string $mode): void
    {
        $email = $profile->billingEmailForCompany();
        if (! $email) {
            return;
        }

        $companyName = $invoice->company?->name ?? 'tenant';
        $consequence = $mode === TenantBillingAccessService::FULL
            ? 'Uw omgeving is volledig geblokkeerd tot de factuur is voldaan.'
            : 'De boekingsmodule op uw website is uitgeschakeld tot de factuur is voldaan.';

        $body = "Beste {$companyName},\n\n".
            "Uw NEXA-factuur {$invoice->invoice_number} is na twee aanmaningen nog niet betaald.\n".
            "{$consequence}\n\n".
            "Zodra de betaling binnen is, wordt de blokkade automatisch opgeheven.\n\n".
            "Met vriendelijke groet,\nNexa Suite";

        $this->sendMail($email, 'Account geblokkeerd — NEXA-factuur '.$invoice->invoice_number, $body, $invoice);
    }

    private function sendMail(string $email, string $subject, string $body, PlatformInvoice $invoice): void
    {
        try {
            $pdf = $this->pdf->generateAndStore($invoice);
        } catch (\Throwable $e) {
            Log::warning('Platform aanmaning-PDF kon niet worden gemaakt', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            $pdf = null;
        }

        Mail::raw($body, function ($message) use ($email, $subject, $invoice, $pdf) {
            $message->to($email)->subject($subject);
            if ($pdf && ! empty($pdf['bytes'])) {
                $filename = 'saas-factuur-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->invoice_number).'.pdf';
                $message->attachData($pdf['bytes'], $filename, ['mime' => 'application/pdf']);
            }
        });
    }
}
