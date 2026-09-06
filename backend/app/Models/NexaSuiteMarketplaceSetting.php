<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NexaSuiteMarketplaceSetting extends Model
{
    public const DEFAULT_PAYMENT_TERMS_TEXT = 'Betaaltermijn: deze factuur dient binnen {dagen} {dagen_label} na factuurdatum te worden betaald.';

    protected $fillable = [
        'fee_percent',
        'auto_generate',
        'auto_send',
        'billing_day',
        'billing_time',
        'tax_rate_percent',
        'payment_terms_days',
        'dunning_first_interval_days',
        'dunning_interval_days',
        'invoice_number_prefix',
        'invoice_number_format',
        'next_invoice_number',
        'current_year',
        'invoice_title',
        'sender_name',
        'sender_email',
        'invoice_footer',
        'invoice_payment_terms_text',
    ];

    protected $casts = [
        'fee_percent' => 'integer',
        'auto_generate' => 'boolean',
        'auto_send' => 'boolean',
        'billing_day' => 'integer',
        'tax_rate_percent' => 'integer',
        'payment_terms_days' => 'integer',
        'dunning_first_interval_days' => 'integer',
        'dunning_interval_days' => 'integer',
        'next_invoice_number' => 'integer',
        'current_year' => 'integer',
    ];

    public static function current(): self
    {
        $defaults = [
            'fee_percent' => (int) config('nexa_suite_marketplace.fee_percent', 10),
            'auto_generate' => (bool) config('nexa_suite_marketplace.auto_generate', true),
            'auto_send' => (bool) config('nexa_suite_marketplace.auto_send', true),
            'billing_day' => (int) config('nexa_suite_marketplace.billing_day', 1),
            'billing_time' => (string) config('nexa_suite_marketplace.billing_time', '06:30'),
            'tax_rate_percent' => (int) config('nexa_suite_marketplace.tax_rate_percent', 21),
            'payment_terms_days' => (int) config('nexa_suite_marketplace.payment_terms_days', 14),
            'dunning_first_interval_days' => (int) config('nexa_suite_marketplace.dunning_first_interval_days', 1),
            'dunning_interval_days' => (int) config('nexa_suite_marketplace.dunning_interval_days', 14),
            'invoice_number_prefix' => (string) config('nexa_suite_marketplace.invoice_number_prefix', 'NSB'),
            'invoice_number_format' => (string) config('nexa_suite_marketplace.invoice_number_format', '{prefix}-{year}-{number}'),
            'next_invoice_number' => 1,
            'current_year' => (int) date('Y'),
            'invoice_title' => (string) config('nexa_suite_marketplace.invoice_title', 'NEXA Suite boekingsfactuur'),
        ];

        return static::query()->firstOrCreate([], $defaults);
    }

    public function shouldRunNow(\DateTimeInterface $now): bool
    {
        $day = max(1, min(28, (int) $this->billing_day));
        if ((int) $now->format('j') !== $day) {
            return false;
        }

        $configured = substr((string) $this->billing_time, 0, 5);
        if (! preg_match('/^\d{2}:\d{2}$/', $configured)) {
            $configured = '06:30';
        }

        return $now->format('H:i') === $configured;
    }

    public function generateInvoiceNumber(): string
    {
        $year = date('Y');
        if ((int) $this->current_year !== (int) $year) {
            $this->current_year = (int) $year;
            $this->next_invoice_number = 1;
            $this->save();
        }

        $number = str_pad((string) $this->next_invoice_number, 4, '0', STR_PAD_LEFT);
        $invoiceNumber = str_replace(
            ['{prefix}', '{year}', '{number}'],
            [$this->invoice_number_prefix ?: 'NSB', $year, $number],
            $this->invoice_number_format ?: '{prefix}-{year}-{number}'
        );

        $this->next_invoice_number++;
        $this->save();

        return $invoiceNumber;
    }

    /**
     * @return array<string, mixed>
     */
    public function issuerDetailsSnapshot(): array
    {
        $platform = PlatformBillingSetting::query()->first();
        $platformIssuer = $platform?->issuerDetailsSnapshot() ?? [];

        $name = trim((string) ($this->sender_name ?: ($platformIssuer['name'] ?? config('app.name', 'Nexa Suite'))));
        $email = trim((string) ($this->sender_email ?: ($platformIssuer['email'] ?? '')));
        $footer = trim((string) ($this->invoice_footer ?: ($platformIssuer['footer_text'] ?? '')));

        return array_filter([
            'name' => $name,
            'address' => $platformIssuer['address'] ?? '',
            'postal_code' => $platformIssuer['postal_code'] ?? '',
            'city' => $platformIssuer['city'] ?? '',
            'country' => $platformIssuer['country'] ?? '',
            'email' => $email,
            'phone' => $platformIssuer['phone'] ?? '',
            'vat_number' => $platformIssuer['vat_number'] ?? '',
            'bank_account' => $platformIssuer['bank_account'] ?? '',
            'invoice_title' => trim((string) ($this->invoice_title ?: config('nexa_suite_marketplace.invoice_title', 'NEXA Suite boekingsfactuur'))),
            'footer_text' => $footer,
            'tax_rate' => (float) $this->tax_rate_percent,
            'payment_terms_days' => (int) $this->payment_terms_days,
        ], fn ($value) => $value !== '' && $value !== null);
    }

    public function paymentTermsTextForInvoice(NexaSuiteBookingInvoice $invoice): string
    {
        if ($invoice->isPaid()) {
            $paidAt = $invoice->paid_at?->format('d-m-Y');

            return $paidAt
                ? 'Deze factuur is volledig betaald op '.$paidAt.'.'
                : 'Deze factuur is volledig betaald. Bedankt voor uw betaling.';
        }

        $template = trim((string) ($this->invoice_payment_terms_text ?? ''));
        if ($template === '') {
            $template = self::DEFAULT_PAYMENT_TERMS_TEXT;
        }

        $days = max(1, (int) ($invoice->payment_terms_days ?: $this->payment_terms_days));
        $daysLabel = $days === 1 ? 'dag' : 'dagen';

        return str_replace(
            ['{dagen}', '{dagen_label}'],
            [(string) $days, $daysLabel],
            $template
        );
    }
}
