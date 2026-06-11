<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    /**
     * @return array{bytes: string, path: string|null}
     */
    public function generateAndStore(Invoice $invoice): array
    {
        $bytes = $this->renderPdfBytes($invoice);
        $path = 'invoices/'.$invoice->company_id.'/'.$invoice->invoice_number.'.pdf';
        Storage::disk('local')->put($path, $bytes);
        $invoice->update(['pdf_path' => $path]);

        return ['bytes' => $bytes, 'path' => $path];
    }

    public function renderPdfBytes(Invoice $invoice): string
    {
        $company = $invoice->company ?? Company::find($invoice->company_id);
        $details = $this->resolvePdfDetails($invoice);

        return Pdf::loadView('invoices.pdf.document', [
            'invoice' => $invoice,
            'company' => $company,
            'details' => $details,
            'logoDataUri' => $this->companyLogoDataUri($company),
            'lineItems' => $invoice->line_items ?? [],
            'paymentTermsText' => InvoiceSetting::invoicePaymentTermsTextForInvoice($invoice),
        ])->setPaper('a4')->output();
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvePdfDetails(Invoice $invoice): array
    {
        $details = is_array($invoice->company_details) ? $invoice->company_details : [];
        unset($details['footer_text']);

        $footerText = InvoiceSetting::invoiceFooterTextForCompany(
            (int) $invoice->company_id > 0 ? (int) $invoice->company_id : null
        );
        if ($footerText !== null) {
            $details['footer_text'] = $footerText;
        }

        return $details;
    }

    protected function companyLogoDataUri(?Company $company): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        if (! $company || ! $company->logo_blob || ! $company->logo_mime_type) {
            return null;
        }

        $binary = base64_decode($company->logo_blob, true);
        if ($binary === false) {
            return null;
        }

        return 'data:'.$company->logo_mime_type.';base64,'.base64_encode($binary);
    }
}
