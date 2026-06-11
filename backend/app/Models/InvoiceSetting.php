<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSetting extends Model
{
    public const DEFAULT_PAYMENT_TERMS_TEXT = 'Betaaltermijn: deze factuur dient binnen {dagen} {dagen_label} na factuurdatum te worden betaald.';

    protected $fillable = [
        'company_id',
        'location_id',
        'invoice_number_prefix',
        'invoice_number_format',
        'next_invoice_number',
        'current_year',
        'company_name',
        'company_address',
        'company_city',
        'company_postal_code',
        'company_country',
        'company_vat_number',
        'company_email',
        'company_phone',
        'bank_account',
        'default_tax_rate',
        'default_amount',
        'payment_terms_days',
        'invoice_footer_text',
        'invoice_payment_terms_text',
        'logo_path',
    ];

    protected $casts = [
        'default_tax_rate' => 'decimal:2',
        'default_amount' => 'decimal:2',
    ];

    public static function getSettings(): self
    {
        return static::getSettingsForCompany(null);
    }

    /**
     * Waarde voor het formulier: minstens het lopende kalenderjaar, hoger indien handmatig gezet.
     */
    public function suggestedCurrentYear(): int
    {
        $calendarYear = (int) date('Y');
        $stored = (int) ($this->current_year ?: $calendarYear);

        return max($stored, $calendarYear);
    }

    public static function invoiceFooterTextForCompany(?int $companyId): ?string
    {
        $companyId = $companyId && $companyId > 0 ? $companyId : null;
        $row = static::query()
            ->when(
                $companyId !== null,
                fn ($q) => $q->where('company_id', $companyId),
                fn ($q) => $q->whereNull('company_id')
            )
            ->first();

        if ($row === null) {
            return null;
        }

        $text = trim((string) ($row->invoice_footer_text ?? ''));

        return $text !== '' ? $text : null;
    }

    public static function invoicePaymentTermsTextForInvoice(Invoice $invoice): string
    {
        $companyId = (int) ($invoice->company_id ?? 0);
        $settings = static::getSettingsForCompany($companyId > 0 ? $companyId : null);
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

    public static function getSettingsForCompany(?int $companyId): self
    {
        $companyId = $companyId && $companyId > 0 ? $companyId : null;

        $existing = static::query()
            ->when(
                $companyId !== null,
                fn ($q) => $q->where('company_id', $companyId),
                fn ($q) => $q->whereNull('company_id')
            )
            ->first();

        if ($existing) {
            return $existing;
        }

        $global = static::query()->whereNull('company_id')->first();

        $defaults = [
            'company_id' => $companyId,
            'invoice_number_prefix' => 'NX',
            'invoice_number_format' => '{prefix}{year}-{number}',
            'next_invoice_number' => 1,
            'current_year' => (int) date('Y'),
            'default_tax_rate' => 21.00,
            'payment_terms_days' => 30,
        ];

        if ($global) {
            foreach ([
                'invoice_number_prefix', 'invoice_number_format', 'next_invoice_number', 'current_year',
                'default_tax_rate', 'payment_terms_days', 'company_name', 'company_address', 'company_city',
                'company_postal_code', 'company_country', 'company_vat_number', 'company_email',
                'company_phone', 'bank_account', 'invoice_payment_terms_text',
            ] as $field) {
                if ($global->{$field} !== null) {
                    $defaults[$field] = $global->{$field};
                }
            }
        }

        return static::create($defaults);
    }

    /**
     * Betaaltermijn voor PDF/e-mail: snapshot → tenant → globaal (admin #payment_terms_days).
     */
    public static function paymentTermsDaysForInvoice(Invoice $invoice): int
    {
        $details = is_array($invoice->company_details) ? $invoice->company_details : [];
        if (! empty($details['payment_terms_days']) && (int) $details['payment_terms_days'] >= 1) {
            return (int) $details['payment_terms_days'];
        }

        $companyId = (int) ($invoice->company_id ?? 0);
        $global = static::query()->whereNull('company_id')->first();
        $tenant = $companyId > 0
            ? static::query()->where('company_id', $companyId)->first()
            : null;

        $globalDays = $global && (int) $global->payment_terms_days >= 1
            ? (int) $global->payment_terms_days
            : null;
        $tenantDays = $tenant && (int) $tenant->payment_terms_days >= 1
            ? (int) $tenant->payment_terms_days
            : null;

        if ($tenantDays !== null && $globalDays !== null && $tenantDays === 30 && $globalDays !== 30) {
            return $globalDays;
        }
        if ($tenantDays !== null) {
            return $tenantDays;
        }
        if ($globalDays !== null) {
            return $globalDays;
        }

        if ($invoice->due_date && $invoice->invoice_date) {
            return max(1, (int) $invoice->invoice_date->startOfDay()->diffInDays($invoice->due_date->startOfDay()));
        }

        return 30;
    }

    public function generateInvoiceNumber(bool $isPartial = false, ?string $parentInvoiceNumber = null, ?int $partialNumber = null): string
    {
        $year = date('Y');
        
        // Reset counter if year changed
        if ($this->current_year != $year) {
            $this->current_year = $year;
            $this->next_invoice_number = 1;
            $this->save();
        }

        $number = str_pad($this->next_invoice_number, 4, '0', STR_PAD_LEFT);
        
        if ($isPartial && $parentInvoiceNumber && $partialNumber) {
            // Partial invoice: NX2025-0001-1
            $invoiceNumber = $parentInvoiceNumber . '-' . $partialNumber;
        } else {
            // Regular invoice: NX2025-0001
            $invoiceNumber = str_replace(
                ['{prefix}', '{year}', '{number}'],
                [$this->invoice_number_prefix, $year, $number],
                $this->invoice_number_format
            );
            
            // Increment for next invoice
            $this->next_invoice_number++;
            $this->save();
        }

        return $invoiceNumber;
    }
}
