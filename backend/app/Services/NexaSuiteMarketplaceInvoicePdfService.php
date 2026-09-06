<?php

namespace App\Services;

use App\Models\NexaSuiteBookingInvoice;
use App\Models\NexaSuiteMarketplaceSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class NexaSuiteMarketplaceInvoicePdfService
{
    /**
     * @return array{bytes: string, path: string|null}
     */
    public function generateAndStore(NexaSuiteBookingInvoice $invoice): array
    {
        $bytes = $this->renderPdfBytes($invoice);
        $path = 'nexa-suite-booking-invoices/'.$invoice->company_id.'/'.$invoice->invoice_number.'.pdf';
        Storage::disk('local')->put($path, $bytes);
        $invoice->update(['pdf_path' => $path]);

        return ['bytes' => $bytes, 'path' => $path];
    }

    public function renderPdfBytes(NexaSuiteBookingInvoice $invoice): string
    {
        $invoice->loadMissing('company');
        $settings = NexaSuiteMarketplaceSetting::current();
        $issuer = is_array($invoice->issuer_details) && $invoice->issuer_details !== []
            ? $invoice->issuer_details
            : $settings->issuerDetailsSnapshot();
        $recipient = is_array($invoice->recipient_details) && $invoice->recipient_details !== []
            ? $invoice->recipient_details
            : [];

        $taxRate = (float) ($issuer['tax_rate'] ?? $settings->tax_rate_percent);
        $lineItems = is_array($invoice->line_items) ? $invoice->line_items : [];

        return Pdf::loadView('platform-billing.pdf.document', [
            'invoice' => $invoice,
            'issuer' => $issuer,
            'recipient' => $recipient,
            'lineItems' => $lineItems,
            'grossAmount' => (float) $invoice->amount,
            'netAmount' => (float) $invoice->amount,
            'discountAmount' => 0.0,
            'discountPercent' => 0,
            'taxRate' => $taxRate,
            'taxRateLabel' => 'BTW ('.rtrim(rtrim(number_format($taxRate, 2, ',', '.'), '0'), ',').'%)',
            'isPaid' => $invoice->isPaid(),
            'paymentTermsText' => $settings->paymentTermsTextForInvoice($invoice),
            'logoDataUri' => \App\Support\AdminLogo::invoicePdfDataUri(),
            'fmt' => fn (float $n) => number_format($n, 2, ',', '.'),
            'fmtSigned' => function (float $n): string {
                $formatted = number_format(abs($n), 2, ',', '.');

                return ($n < 0 ? '- ' : '').'€ '.$formatted;
            },
        ])->setPaper('a4')->output();
    }
}
