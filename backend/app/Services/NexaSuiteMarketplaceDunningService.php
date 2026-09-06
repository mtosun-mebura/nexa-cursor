<?php

namespace App\Services;

use App\Models\NexaSuiteBookingInvoice;
use App\Models\NexaSuiteMarketplaceSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NexaSuiteMarketplaceDunningService
{
    public const ACTION_NONE = 'none';

    public const ACTION_FIRST = 'first';

    public const ACTION_SECOND = 'second';

    public function __construct(
        protected NexaSuiteMarketplaceInvoicePdfService $pdf,
    ) {}

    /**
     * @return array{checked: int, first: int, second: int}
     */
    public function run(?Carbon $now = null): array
    {
        $now ??= now();
        $stats = ['checked' => 0, 'first' => 0, 'second' => 0];
        $settings = NexaSuiteMarketplaceSetting::current();

        NexaSuiteBookingInvoice::query()
            ->with('company.billingProfile')
            ->where('status', 'sent')
            ->whereNull('paid_at')
            ->where('total_amount', '>', 0)
            ->orderBy('id')
            ->chunkById(50, function ($invoices) use ($now, $settings, &$stats) {
                foreach ($invoices as $invoice) {
                    $stats['checked']++;
                    $action = $this->processInvoice($invoice, $now, $settings);
                    if (isset($stats[$action])) {
                        $stats[$action]++;
                    }
                }
            });

        Log::info('NEXA Suite marketplace dunning run completed', $stats);

        return $stats;
    }

    public function processInvoice(
        NexaSuiteBookingInvoice $invoice,
        ?Carbon $now = null,
        ?NexaSuiteMarketplaceSetting $settings = null,
    ): string {
        $now ??= now();
        $settings ??= NexaSuiteMarketplaceSetting::current();
        $invoice->refresh();

        if (! $invoice->isOpen()) {
            return self::ACTION_NONE;
        }

        $due = $invoice->due_date ? Carbon::parse($invoice->due_date)->endOfDay() : null;
        if ($due && $now->lt($due)) {
            return self::ACTION_NONE;
        }

        $firstDays = max(1, (int) $settings->dunning_first_interval_days);
        $secondDays = max(1, (int) $settings->dunning_interval_days);
        $anchor = $due?->copy() ?? ($invoice->sent_at?->copy() ?? $invoice->invoice_date?->copy());
        if (! $anchor) {
            return self::ACTION_NONE;
        }

        if (! $invoice->first_reminder_sent_at) {
            if ($now->gte($anchor->copy()->addDays($firstDays))) {
                $this->sendReminder($invoice, 1);

                return self::ACTION_FIRST;
            }

            return self::ACTION_NONE;
        }

        if (! $invoice->second_reminder_sent_at) {
            $firstSent = $invoice->first_reminder_sent_at->copy();
            if ($now->gte($firstSent->addDays($secondDays))) {
                $this->sendReminder($invoice, 2);

                return self::ACTION_SECOND;
            }
        }

        return self::ACTION_NONE;
    }

    public function sendReminder(NexaSuiteBookingInvoice $invoice, int $level): bool
    {
        $invoice->loadMissing('company.billingProfile');
        $email = $invoice->company?->billingProfile?->billingEmailForCompany()
            ?: trim((string) ($invoice->company?->email ?? ''));
        if ($email === '') {
            return false;
        }

        $mail = $this->composeReminderMail(
            $level,
            (string) ($invoice->company?->name ?: 'relatie'),
            (string) $invoice->invoice_number,
            (string) $invoice->billing_period,
            $invoice->due_date?->format('d-m-Y') ?: '—',
            '€'.number_format((float) $invoice->total_amount, 2, ',', '.'),
        );

        try {
            $pdf = $this->pdf->generateAndStore($invoice);
        } catch (\Throwable) {
            $pdf = null;
        }

        Mail::raw($mail['body'], function ($message) use ($email, $mail, $invoice, $pdf) {
            $message->to($email)->subject($mail['subject']);
            if ($pdf && ! empty($pdf['bytes'])) {
                $filename = 'nexa-suite-boekingen-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->invoice_number).'.pdf';
                $message->attachData($pdf['bytes'], $filename, ['mime' => 'application/pdf']);
            }
        });

        $invoice->update($level === 2
            ? ['second_reminder_sent_at' => now()]
            : ['first_reminder_sent_at' => now()]
        );

        return true;
    }

    /**
     * @return array{subject: string, body: string}
     */
    public function composeReminderMail(
        int $level,
        string $companyName,
        string $invoiceNumber,
        string $period,
        string $dueFormatted,
        string $amountFormatted,
    ): array {
        $label = $level === 2 ? 'Tweede aanmaning' : 'Herinnering';
        $subject = $label.': NEXA Suite boekingsfactuur '.$invoiceNumber;
        $intro = $level === 2
            ? "Dit is een tweede aanmaning. Factuur {$invoiceNumber} is nog niet voldaan."
            : "Factuur {$invoiceNumber} is na de vervaldatum nog open.";

        $body = "Beste {$companyName},\n\n".
            "{$intro}\n".
            "Periode: {$period}\n".
            "Vervaldatum: {$dueFormatted}\n".
            "Openstaand bedrag: {$amountFormatted}\n\n".
            "Wij verzoeken u het bedrag zo spoedig mogelijk te voldoen.\n\n".
            "Met vriendelijke groet,\nNEXA Suite";

        return ['subject' => $subject, 'body' => $body];
    }
}
