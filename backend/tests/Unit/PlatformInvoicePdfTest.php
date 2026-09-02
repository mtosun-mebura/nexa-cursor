<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\PlatformBillingSetting;
use App\Models\PlatformInvoice;
use App\Services\SaasBillingStartEmailTemplateService;
use Carbon\Carbon;
use Tests\TestCase;

class PlatformInvoicePdfTest extends TestCase
{
    public function test_recipient_snapshot_uses_company_address(): void
    {
        $company = Company::query()->create([
            'name' => 'Horizon Taxi',
            'email' => 'facturatie@horizontaxi.nl',
            'street' => 'Stationsweg',
            'house_number' => '8',
            'postal_code' => '7511 AB',
            'city' => 'Enschede',
            'country' => 'Nederland',
            'is_active' => true,
        ]);

        $recipient = PlatformBillingSetting::current()->recipientDetailsSnapshot($company);

        $this->assertSame('Horizon Taxi', $recipient['name']);
        $this->assertSame('Stationsweg 8', $recipient['address']);
        $this->assertSame('7511 AB', $recipient['postal_code']);
        $this->assertSame('Enschede', $recipient['city']);
        $this->assertSame('facturatie@horizontaxi.nl', $recipient['email']);
    }

    public function test_recipient_snapshot_falls_back_to_main_location(): void
    {
        $company = Company::query()->create([
            'name' => 'Locatie Taxi',
            'email' => 'info@locatie.taxi',
            'is_active' => true,
        ]);
        CompanyLocation::query()->create([
            'company_id' => $company->id,
            'name' => 'Hoofdkantoor',
            'street' => 'Kerkstraat',
            'house_number' => '3',
            'postal_code' => '7411 AB',
            'city' => 'Deventer',
            'country' => 'Nederland',
            'is_main' => true,
            'is_active' => true,
        ]);

        $recipient = PlatformBillingSetting::current()->recipientDetailsSnapshot($company->fresh());

        $this->assertSame('Kerkstraat 3', $recipient['address']);
        $this->assertSame('7411 AB', $recipient['postal_code']);
        $this->assertSame('Deventer', $recipient['city']);
    }

    public function test_invoice_pdf_html_includes_customer_address_and_payment_terms(): void
    {
        $company = new Company([
            'name' => 'Horizon Taxi',
            'email' => 'facturatie@horizontaxi.nl',
            'street' => 'Voorbeeldstraat',
            'house_number' => '12',
            'postal_code' => '7511 AB',
            'city' => 'Enschede',
            'country' => 'Nederland',
        ]);
        $invoice = new PlatformInvoice([
            'invoice_number' => 'NEXA-2026-0001',
            'billing_period' => '2026-09',
            'amount' => 283.10,
            'tax_amount' => 59.45,
            'total_amount' => 342.55,
            'invoice_date' => '2026-09-04',
            'due_date' => '2026-09-18',
            'payment_terms_days' => 14,
            'status' => 'sent',
        ]);
        $html = view('platform-billing.pdf.document', [
            'invoice' => $invoice,
            'issuer' => ['name' => 'Nexa Suite', 'invoice_title' => 'SaaS-factuur'],
            'recipient' => PlatformBillingSetting::current()->recipientDetailsSnapshot($company),
            'lineItems' => [],
            'grossAmount' => 283.10,
            'netAmount' => 283.10,
            'discountAmount' => 0,
            'discountPercent' => 0,
            'taxRate' => 21,
            'paymentTermsText' => PlatformBillingSetting::invoicePaymentTermsTextForInvoice($invoice),
            'logoDataUri' => null,
        ])->render();

        $this->assertStringContainsString('Voorbeeldstraat 12', $html);
        $this->assertStringContainsString('7511 AB Enschede', $html);
        $this->assertStringContainsString('Nederland', $html);
        $this->assertStringContainsString('Betaaltermijn', $html);
        $this->assertStringContainsString('14 dagen', $html);
        $this->assertStringContainsString('pdf-page-footer', $html);
        $this->assertSame(1, substr_count($html, 'Betaaltermijn'));
    }

    public function test_sample_first_payment_pdf_is_generated(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');
        $attachment = app(SaasBillingStartEmailTemplateService::class)->sampleInvoiceAttachment();
        Carbon::setTestNow();

        $this->assertNotNull($attachment);
        $this->assertStringStartsWith('%PDF', $attachment['bytes']);
        $this->assertStringContainsString('saas-factuur', $attachment['filename']);
        $this->assertGreaterThan(1000, strlen($attachment['bytes']));
    }
}
