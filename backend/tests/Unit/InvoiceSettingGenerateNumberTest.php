<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSettingGenerateNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_invoice_number_skips_numbers_already_in_use(): void
    {
        $year = date('Y');
        $company = Company::query()->create(['name' => 'Taxi Test', 'is_active' => true]);
        $other = Company::query()->create(['name' => 'Andere Taxi', 'is_active' => true]);

        $settings = InvoiceSetting::query()->create([
            'company_id' => $company->id,
            'invoice_number_prefix' => 'NX',
            'invoice_number_format' => '{prefix}{year}-{number}',
            'next_invoice_number' => 2,
            'current_year' => (int) $year,
            'default_tax_rate' => 21,
            'payment_terms_days' => 30,
        ]);

        Invoice::query()->create([
            'invoice_number' => 'NX'.$year.'-0002',
            'company_id' => $other->id,
            'module' => Invoice::MODULE_TAXI,
            'module_reference_id' => 12,
            'customer_name' => 'Bestaande',
            'amount' => 10,
            'tax_amount' => 0,
            'total_amount' => 10,
            'currency' => 'EUR',
            'status' => 'paid',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
        ]);

        $number = $settings->fresh()->generateInvoiceNumber();

        $this->assertSame('NX'.$year.'-0003', $number);
        $this->assertSame(4, $settings->fresh()->next_invoice_number);
    }
}
