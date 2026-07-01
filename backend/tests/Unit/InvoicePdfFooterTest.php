<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfFooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_footer_uses_live_invoice_settings_not_snapshot(): void
    {
        $company = Company::create(['name' => 'Taxi Royaal', 'is_active' => true]);

        InvoiceSetting::query()->create([
            'company_id' => $company->id,
            'invoice_number_prefix' => 'TR',
            'invoice_number_format' => '{prefix}{year}-{number}',
            'next_invoice_number' => 1,
            'current_year' => 2026,
            'default_tax_rate' => 21,
            'payment_terms_days' => 14,
            'invoice_footer_text' => null,
        ]);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'TR2026-0009',
            'company_id' => $company->id,
            'amount' => 5.74,
            'tax_amount' => 1.21,
            'total_amount' => 6.95,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'company_details' => [
                'name' => 'Taxi Royaal',
                'footer_text' => 'Hier komt een footer tekst.',
            ],
            'line_items' => [],
        ]);

        $details = app(InvoicePdfService::class)->resolvePdfDetails($invoice);

        $this->assertArrayNotHasKey('footer_text', $details);
    }

    public function test_pdf_footer_shows_when_invoice_settings_field_is_filled(): void
    {
        $company = Company::create(['name' => 'Taxi Royaal', 'is_active' => true]);

        InvoiceSetting::query()->create([
            'company_id' => $company->id,
            'invoice_number_prefix' => 'TR',
            'invoice_number_format' => '{prefix}{year}-{number}',
            'next_invoice_number' => 1,
            'current_year' => 2026,
            'default_tax_rate' => 21,
            'payment_terms_days' => 14,
            'invoice_footer_text' => 'Betaal binnen 14 dagen op NL00BANK0123456789.',
        ]);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'TR2026-0010',
            'company_id' => $company->id,
            'amount' => 5.74,
            'tax_amount' => 1.21,
            'total_amount' => 6.95,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'company_details' => [
                'name' => 'Taxi Royaal',
                'footer_text' => 'Oude snapshot footer',
            ],
            'line_items' => [],
        ]);

        $details = app(InvoicePdfService::class)->resolvePdfDetails($invoice);

        $this->assertSame('Betaal binnen 14 dagen op NL00BANK0123456789.', $details['footer_text']);
    }

    public function test_payment_terms_text_uses_custom_template_with_placeholders(): void
    {
        $company = Company::create(['name' => 'Taxi Royaal', 'is_active' => true]);

        InvoiceSetting::query()->create([
            'company_id' => $company->id,
            'invoice_number_prefix' => 'TR',
            'invoice_number_format' => '{prefix}{year}-{number}',
            'next_invoice_number' => 1,
            'current_year' => 2026,
            'default_tax_rate' => 21,
            'payment_terms_days' => 14,
            'invoice_payment_terms_text' => 'Gelieve te betalen binnen {dagen} {dagen_label}.',
        ]);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'TR2026-0011',
            'company_id' => $company->id,
            'amount' => 5.74,
            'tax_amount' => 1.21,
            'total_amount' => 6.95,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'company_details' => ['name' => 'Taxi Royaal'],
            'line_items' => [],
        ]);

        $this->assertSame(
            'Gelieve te betalen binnen 14 dagen.',
            InvoiceSetting::invoicePaymentTermsTextForInvoice($invoice)
        );
    }

    public function test_footer_does_not_inherit_global_placeholder_for_company_without_own_text(): void
    {
        $company = Company::create(['name' => 'Taxi Royaal', 'is_active' => true]);

        InvoiceSetting::query()->create([
            'company_id' => null,
            'invoice_number_prefix' => 'NX',
            'invoice_number_format' => '{prefix}{year}-{number}',
            'next_invoice_number' => 1,
            'current_year' => 2026,
            'default_tax_rate' => 21,
            'payment_terms_days' => 30,
            'invoice_footer_text' => 'Hier komt een footer tekst.',
        ]);

        InvoiceSetting::query()->create([
            'company_id' => $company->id,
            'invoice_number_prefix' => 'TR',
            'invoice_number_format' => '{prefix}{year}-{number}',
            'next_invoice_number' => 1,
            'current_year' => 2026,
            'default_tax_rate' => 21,
            'payment_terms_days' => 14,
            'invoice_footer_text' => null,
        ]);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'TR2026-0013',
            'company_id' => $company->id,
            'amount' => 5.74,
            'tax_amount' => 1.21,
            'total_amount' => 6.95,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'company_details' => [
                'name' => 'Taxi Royaal',
                'footer_text' => 'Hier komt een footer tekst.',
            ],
            'line_items' => [],
        ]);

        $this->assertNull(InvoiceSetting::invoiceFooterTextForCompany($company->id));
        $details = app(InvoicePdfService::class)->resolvePdfDetails($invoice);
        $this->assertArrayNotHasKey('footer_text', $details);
    }

    public function test_payment_terms_text_falls_back_to_default_when_empty(): void
    {
        $company = Company::create(['name' => 'Taxi Royaal', 'is_active' => true]);

        InvoiceSetting::query()->create([
            'company_id' => $company->id,
            'invoice_number_prefix' => 'TR',
            'invoice_number_format' => '{prefix}{year}-{number}',
            'next_invoice_number' => 1,
            'current_year' => 2026,
            'default_tax_rate' => 21,
            'payment_terms_days' => 1,
        ]);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'TR2026-0012',
            'company_id' => $company->id,
            'amount' => 5.74,
            'tax_amount' => 1.21,
            'total_amount' => 6.95,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => now(),
            'due_date' => now()->addDay(),
            'company_details' => ['name' => 'Taxi Royaal'],
            'line_items' => [],
        ]);

        $this->assertSame(
            'Betaaltermijn: deze factuur dient binnen 1 dag na factuurdatum te worden betaald.',
            InvoiceSetting::invoicePaymentTermsTextForInvoice($invoice)
        );
    }

    public function test_paid_invoice_uses_paid_payment_terms_text(): void
    {
        $company = Company::create(['name' => 'Taxi Royaal', 'is_active' => true]);

        InvoiceSetting::query()->create([
            'company_id' => $company->id,
            'invoice_number_prefix' => 'TR',
            'invoice_number_format' => '{prefix}{year}-{number}',
            'next_invoice_number' => 1,
            'current_year' => 2026,
            'default_tax_rate' => 21,
            'payment_terms_days' => 14,
        ]);

        $paidDate = now()->startOfDay();
        $invoice = Invoice::query()->create([
            'invoice_number' => 'TR2026-0099',
            'company_id' => $company->id,
            'amount' => 28.31,
            'tax_amount' => 5.95,
            'total_amount' => 34.26,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => $paidDate,
            'due_date' => $paidDate->copy()->addDays(14),
            'paid_date' => $paidDate,
            'company_details' => ['name' => 'Taxi Royaal', 'tax_rate' => 21],
            'line_items' => [],
        ]);

        $this->assertTrue($invoice->isPaid());
        $this->assertStringContainsString(
            'volledig betaald op '.$paidDate->format('d-m-Y'),
            InvoiceSetting::invoicePaymentTermsTextForInvoice($invoice)
        );
    }

    public function test_paid_invoice_pdf_template_shows_settled_banner(): void
    {
        $company = Company::create(['name' => 'Taxi Royaal', 'is_active' => true]);
        $paidDate = now()->startOfDay();

        $invoice = Invoice::query()->create([
            'invoice_number' => 'TR2026-0100',
            'company_id' => $company->id,
            'amount' => 28.31,
            'tax_amount' => 5.95,
            'total_amount' => 34.26,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => $paidDate,
            'due_date' => $paidDate->copy()->addDays(14),
            'paid_date' => $paidDate,
            'company_details' => ['name' => 'Taxi Royaal', 'tax_rate' => 21],
            'line_items' => [
                ['description' => 'Taxirit', 'quantity' => 1, 'unit_price' => 28.31, 'total' => 28.31],
            ],
        ]);

        $html = view('invoices.pdf.document', [
            'invoice' => $invoice,
            'company' => $company,
            'details' => app(InvoicePdfService::class)->resolvePdfDetails($invoice),
            'logoDataUri' => null,
            'lineItems' => $invoice->line_items,
            'paymentTermsText' => InvoiceSetting::invoicePaymentTermsTextForInvoice($invoice),
        ])->render();

        $this->assertStringContainsString('BETALING VOLDAAN', $html);
        $this->assertStringContainsString('Openstaand bedrag', $html);
        $this->assertStringContainsString('volledig voldaan', $html);
    }
}
