<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PlatformBillingSetting extends Model
{
    public const DEFAULT_PAYMENT_TERMS_TEXT = 'Betaaltermijn: deze factuur dient binnen {dagen} {dagen_label} na factuurdatum te worden betaald.';

    protected $fillable = [
        'billing_day',
        'billing_time',
        'sender_name',
        'sender_email',
        'tax_rate_percent',
        'payment_terms_days',
        'invoice_footer',
        'invoice_number_prefix',
        'invoice_number_format',
        'next_invoice_number',
        'current_year',
        'invoice_title',
        'company_name',
        'company_address',
        'company_house_number',
        'company_city',
        'company_postal_code',
        'company_country',
        'company_vat_number',
        'company_email',
        'company_phone',
        'bank_account',
        'invoice_payment_terms_text',
        'mollie_api_key',
        'mollie_webhook_url',
    ];

    protected $hidden = [
        'mollie_api_key',
    ];

    protected $casts = [
        'billing_day' => 'integer',
        'tax_rate_percent' => 'decimal:2',
        'payment_terms_days' => 'integer',
        'next_invoice_number' => 'integer',
        'current_year' => 'integer',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'billing_day' => 1,
            'billing_time' => '05:00',
            'tax_rate_percent' => 21,
            'payment_terms_days' => 14,
            'invoice_number_prefix' => config('platform-billing.invoice_number_prefix', 'SAAS'),
            'invoice_number_format' => '{prefix}-{year}-{number}',
            'next_invoice_number' => 1,
            'current_year' => (int) date('Y'),
            'invoice_title' => 'SaaS-factuur',
        ]);
    }

    public function suggestedCurrentYear(): int
    {
        $calendarYear = (int) date('Y');
        $stored = (int) ($this->current_year ?: $calendarYear);

        return max($stored, $calendarYear);
    }

    public function shouldRunNow(\DateTimeInterface $now): bool
    {
        $day = max(1, min(28, (int) $this->billing_day));
        if ((int) $now->format('j') !== $day) {
            return false;
        }

        $configured = substr((string) $this->billing_time, 0, 5);
        if (! preg_match('/^\d{2}:\d{2}$/', $configured)) {
            $configured = '05:00';
        }

        return $now->format('H:i') === $configured;
    }

    /**
     * @return array<string, mixed>
     */
    public function issuerDetailsSnapshot(): array
    {
        $name = trim((string) ($this->company_name ?: $this->sender_name ?: config('app.name', 'Nexa Suite')));

        return array_filter([
            'name' => $name,
            'address' => trim(implode(' ', array_filter([
                trim((string) ($this->company_address ?? '')),
                trim((string) ($this->company_house_number ?? '')),
            ]))),
            'postal_code' => trim((string) ($this->company_postal_code ?? '')),
            'city' => trim((string) ($this->company_city ?? '')),
            'country' => trim((string) ($this->company_country ?? '')),
            'email' => trim((string) ($this->company_email ?: $this->sender_email ?? '')),
            'phone' => trim((string) ($this->company_phone ?? '')),
            'vat_number' => trim((string) ($this->company_vat_number ?? '')),
            'bank_account' => trim((string) ($this->bank_account ?? '')),
            'invoice_title' => trim((string) ($this->invoice_title ?: 'SaaS-factuur')),
            'footer_text' => trim((string) ($this->invoice_footer ?? '')),
            'tax_rate' => (float) $this->tax_rate_percent,
            'payment_terms_days' => (int) $this->payment_terms_days,
        ], fn ($value) => $value !== '' && $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function recipientDetailsSnapshot(Company $company, ?CompanyBillingProfile $profile = null): array
    {
        $profile ??= CompanyBillingProfile::query()->where('company_id', $company->id)->first();

        $address = trim(implode(' ', array_filter([
            $company->street,
            $company->house_number,
            $company->house_number_extension,
        ])));

        return array_filter([
            'name' => $company->name,
            'contact_name' => trim((string) ($profile?->billing_contact_name ?? '')),
            'email' => trim((string) ($profile?->billingEmailForCompany() ?? '')),
            'address' => $address,
            'postal_code' => trim((string) ($company->postal_code ?? '')),
            'city' => trim((string) ($company->city ?? '')),
            'country' => trim((string) ($company->country ?? '')),
            'phone' => trim((string) ($company->phone ?? '')),
            'vat_number' => trim((string) ($company->kvk_number ?? '')),
        ], fn ($value) => $value !== '' && $value !== null);
    }

    public static function paymentTermsDaysForInvoice(PlatformInvoice $invoice): int
    {
        if ((int) ($invoice->payment_terms_days ?? 0) >= 1) {
            return (int) $invoice->payment_terms_days;
        }

        $issuer = is_array($invoice->issuer_details) ? $invoice->issuer_details : [];
        if (! empty($issuer['payment_terms_days']) && (int) $issuer['payment_terms_days'] >= 1) {
            return (int) $issuer['payment_terms_days'];
        }

        $settings = static::current();
        if ((int) $settings->payment_terms_days >= 1) {
            return (int) $settings->payment_terms_days;
        }

        if ($invoice->due_date && $invoice->invoice_date) {
            return max(1, (int) $invoice->invoice_date->startOfDay()->diffInDays($invoice->due_date->startOfDay()));
        }

        return 14;
    }

    public static function invoicePaymentTermsTextForInvoice(PlatformInvoice $invoice): string
    {
        if ($invoice->isPaid()) {
            $paidOn = $invoice->paid_at?->format('d-m-Y');

            return $paidOn
                ? 'Deze factuur is volledig betaald op '.$paidOn.'. Bedankt voor uw betaling.'
                : 'Deze factuur is volledig betaald. Bedankt voor uw betaling.';
        }

        $settings = static::current();
        $template = trim((string) ($settings->invoice_payment_terms_text ?? ''));
        if ($template === '') {
            $template = static::DEFAULT_PAYMENT_TERMS_TEXT;
        }

        $days = static::paymentTermsDaysForInvoice($invoice);
        $daysLabel = $days === 1 ? 'dag' : 'dagen';

        return str_replace(
            ['{dagen}', '{dagen_label}'],
            [(string) $days, $daysLabel],
            $template
        );
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
            [$this->invoice_number_prefix ?: 'SAAS', $year, $number],
            $this->invoice_number_format ?: '{prefix}-{year}-{number}'
        );

        $this->next_invoice_number++;
        $this->save();

        return $invoiceNumber;
    }

    public function previewNextInvoiceNumber(): string
    {
        $year = date('Y');
        $nextNumber = (int) $this->next_invoice_number;

        if ((int) $this->current_year !== (int) $year) {
            $nextNumber = 1;
        }

        $number = str_pad((string) max(1, $nextNumber), 4, '0', STR_PAD_LEFT);

        return str_replace(
            ['{prefix}', '{year}', '{number}'],
            [$this->invoice_number_prefix ?: 'SAAS', $year, $number],
            $this->invoice_number_format ?: '{prefix}-{year}-{number}'
        );
    }

    public function hasStoredMollieApiKey(): bool
    {
        return trim((string) $this->mollie_api_key) !== '';
    }

    public function decryptedMollieApiKey(): ?string
    {
        $stored = trim((string) $this->mollie_api_key);
        if ($stored === '') {
            return null;
        }

        try {
            $key = trim(Crypt::decryptString($stored));
        } catch (\Throwable) {
            // Legacy plain-text values (should not occur after encrypt-on-save).
            $key = $stored;
        }

        return $key !== '' ? $key : null;
    }

    public function maskedMollieApiKey(): ?string
    {
        $key = $this->decryptedMollieApiKey();
        if ($key === null) {
            return null;
        }

        $prefix = str_starts_with($key, 'live_') ? 'live_' : (str_starts_with($key, 'test_') ? 'test_' : '');
        $tail = substr($key, -4);

        return $prefix.'••••••••'.$tail;
    }

    public function setEncryptedMollieApiKey(?string $plainKey): void
    {
        $plainKey = trim((string) $plainKey);
        $this->mollie_api_key = $plainKey !== '' ? Crypt::encryptString($plainKey) : null;
    }

    public function resolvedMollieWebhookUrl(): ?string
    {
        $configured = trim((string) $this->mollie_webhook_url);
        if ($configured !== '') {
            return $configured;
        }

        return null;
    }

    /**
     * Vul lege platformvelden aan vanuit globale factuurinstellingen (admin/invoices/settings).
     */
    public function mergeDefaultsFromGlobalInvoiceSettings(?InvoiceSetting $global = null): self
    {
        $global ??= InvoiceSetting::query()->whereNull('company_id')->first();
        if (! $global) {
            return $this;
        }

        $map = [
            'company_name' => 'company_name',
            'company_address' => 'company_address',
            'company_city' => 'company_city',
            'company_postal_code' => 'company_postal_code',
            'company_country' => 'company_country',
            'company_vat_number' => 'company_vat_number',
            'company_email' => 'company_email',
            'company_phone' => 'company_phone',
            'bank_account' => 'bank_account',
            'invoice_payment_terms_text' => 'invoice_payment_terms_text',
            'payment_terms_days' => 'payment_terms_days',
            'tax_rate_percent' => 'default_tax_rate',
            'invoice_footer' => 'invoice_footer_text',
            'invoice_number_prefix' => 'invoice_number_prefix',
            'invoice_number_format' => 'invoice_number_format',
            'next_invoice_number' => 'next_invoice_number',
            'current_year' => 'current_year',
        ];

        foreach ($map as $platformField => $invoiceField) {
            $current = $this->{$platformField};
            $source = $global->{$invoiceField} ?? null;
            if (($current === null || $current === '') && $source !== null && $source !== '') {
                $this->{$platformField} = $source;
            }
        }

        if (empty($this->sender_name) && ! empty($global->company_name)) {
            $this->sender_name = $global->company_name;
        }
        if (empty($this->sender_email) && ! empty($global->company_email)) {
            $this->sender_email = $global->company_email;
        }

        return $this;
    }
}
