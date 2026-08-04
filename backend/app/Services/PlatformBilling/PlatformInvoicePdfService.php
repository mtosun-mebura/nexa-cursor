<?php

namespace App\Services\PlatformBilling;

use App\Models\Company;
use App\Models\PlatformBillingSetting;
use App\Models\PlatformInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PlatformInvoicePdfService
{
    /**
     * @return array{bytes: string, path: string|null}
     */
    public function generateAndStore(PlatformInvoice $invoice): array
    {
        $bytes = $this->renderPdfBytes($invoice);
        $path = 'platform-invoices/'.$invoice->company_id.'/'.$invoice->invoice_number.'.pdf';
        Storage::disk('local')->put($path, $bytes);
        $invoice->update(['pdf_path' => $path]);

        return ['bytes' => $bytes, 'path' => $path];
    }

    public function renderPdfBytes(PlatformInvoice $invoice): string
    {
        $invoice->loadMissing('company');
        $settings = PlatformBillingSetting::current();
        $issuer = is_array($invoice->issuer_details) && $invoice->issuer_details !== []
            ? $invoice->issuer_details
            : $settings->issuerDetailsSnapshot();
        $recipient = is_array($invoice->recipient_details) && $invoice->recipient_details !== []
            ? $invoice->recipient_details
            : $settings->recipientDetailsSnapshot($invoice->company);

        $taxRate = (float) ($issuer['tax_rate'] ?? $settings->tax_rate_percent);

        $presentation = app(PlatformBillingService::class)->resolveStoredInvoicePresentation($invoice, $taxRate);

        return $this->renderDocumentPdf(
            $this->invoiceViewData($invoice, $issuer, $recipient, $presentation, $taxRate)
        );
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    public function renderPreviewPdfBytes(Company $company, array $preview): string
    {
        $invoice = new PlatformInvoice([
            'company_id' => $company->id,
            'invoice_number' => (string) ($preview['invoice_number'] ?? 'VOORBEELD'),
            'billing_period' => (string) ($preview['billing_period'] ?? ''),
            'amount' => $preview['amount'] ?? 0,
            'tax_amount' => $preview['tax_amount'] ?? 0,
            'total_amount' => $preview['total_amount'] ?? 0,
            'invoice_date' => $preview['invoice_date'] ?? now()->toDateString(),
            'due_date' => $preview['due_date'] ?? now()->toDateString(),
            'payment_terms_days' => (int) ($preview['payment_terms_days'] ?? 14),
            'line_items' => $preview['line_items'] ?? [],
            'status' => 'draft',
        ]);
        $invoice->setRelation('company', $company);

        $issuer = $preview['issuer'] ?? PlatformBillingSetting::current()->issuerDetailsSnapshot();
        $recipient = $preview['recipient'] ?? PlatformBillingSetting::current()->recipientDetailsSnapshot($company);
        $taxRate = (float) ($preview['tax_rate'] ?? ($issuer['tax_rate'] ?? 21));

        $presentation = [
            'line_items' => $preview['line_items'] ?? [],
            'gross_amount' => (float) ($preview['amount'] ?? 0),
            'discount_percent' => 0,
            'discount_amount' => 0.0,
            'amount' => (float) ($preview['amount'] ?? 0),
            'tax_amount' => (float) ($preview['tax_amount'] ?? 0),
            'total_amount' => (float) ($preview['total_amount'] ?? 0),
        ];

        $viewData = $this->invoiceViewData($invoice, $issuer, $recipient, $presentation, $taxRate);
        $viewData['paymentTermsText'] = (string) ($preview['payment_terms_text'] ?? '');

        return $this->renderDocumentPdf($viewData);
    }

    /**
     * @param  array<string, mixed>  $presentation
     * @return array<string, mixed>
     */
    private function invoiceViewData(
        PlatformInvoice $invoice,
        array $issuer,
        array $recipient,
        array $presentation,
        float $taxRate,
    ): array {
        return [
            'invoice' => $invoice,
            'issuer' => $issuer,
            'recipient' => $recipient,
            'lineItems' => $presentation['line_items'],
            'grossAmount' => $presentation['amount'],
            'netAmount' => $presentation['amount'],
            'discountAmount' => 0.0,
            'discountPercent' => 0,
            'taxRate' => $taxRate,
            'paymentTermsText' => PlatformBillingSetting::invoicePaymentTermsTextForInvoice($invoice),
            'logoDataUri' => \App\Support\AdminLogo::invoicePdfDataUri(),
        ];
    }

    /**
     * @param  array<string, mixed>  $viewData
     */
    private function renderDocumentPdf(array $viewData): string
    {
        return Pdf::loadView('platform-billing.pdf.document', $viewData)->setPaper('a4')->output();
    }
}
