<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSetting extends Model
{
    protected $fillable = [
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
        'logo_path',
    ];

    protected $casts = [
        'default_tax_rate' => 'decimal:2',
        'default_amount' => 'decimal:2',
    ];

    public static function getSettings(): self
    {
        return static::firstOrCreate([], [
            'invoice_number_prefix' => 'NX',
            'invoice_number_format' => '{prefix}{year}-{number}',
            'next_invoice_number' => 1,
            'current_year' => date('Y'),
            'default_tax_rate' => 21.00,
            'payment_terms_days' => 30,
        ]);
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
