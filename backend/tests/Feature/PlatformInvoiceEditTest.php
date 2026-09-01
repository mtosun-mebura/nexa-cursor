<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PlatformBillingLineItem;
use App\Models\PlatformBillingSetting;
use App\Models\PlatformInvoice;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformInvoiceEditTest extends TestCase
{
    private function superAdmin(): User
    {
        Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function companyAdmin(): User
    {
        Role::query()->firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $company = Company::query()->create(['name' => 'Andere Tenant BV', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company-admin');

        return $user;
    }

    private function invoice(array $overrides = []): PlatformInvoice
    {
        $company = Company::query()->create(['name' => 'Edit Taxi BV', 'is_active' => true]);
        PlatformBillingSetting::current()->update(['tax_rate_percent' => 21]);

        return PlatformInvoice::query()->create(array_merge([
            'company_id' => $company->id,
            'invoice_number' => 'NEXA-EDIT-0001',
            'billing_period' => '2026-09',
            'amount' => 100,
            'tax_amount' => 21,
            'total_amount' => 121,
            'currency' => 'EUR',
            'status' => 'draft',
            'invoice_date' => '2026-09-01',
            'due_date' => '2026-09-15',
            'payment_terms_days' => 14,
            'line_items' => [
                [
                    'description' => 'Business — september 2026',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'total' => 100,
                    'type' => 'subscription',
                    'billing_period' => '2026-09',
                ],
            ],
        ], $overrides));
    }

    public function test_super_admin_can_open_invoice_edit_form(): void
    {
        $invoice = $this->invoice();

        $this->actingAs($this->superAdmin())
            ->get(route('admin.platform-billing.invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('Factuur bewerken', false)
            ->assertSee('Business — september 2026', false)
            ->assertSee('Lege regel', false);
    }

    public function test_super_admin_can_update_invoice_lines_and_totals(): void
    {
        $invoice = $this->invoice();

        $this->actingAs($this->superAdmin())
            ->put(route('admin.platform-billing.invoices.update', $invoice), [
                'status' => 'sent',
                'payment_terms_days' => 21,
                'notes' => 'Aangepast voor controle',
                'line_items' => [
                    [
                        'description' => 'Business — september 2026',
                        'quantity' => 1,
                        'unit_price' => 179,
                        'type' => 'subscription',
                        'billing_period' => '2026-09',
                    ],
                    [
                        'description' => 'Ontwikkeling website 5 pagina\'s',
                        'quantity' => 1,
                        'unit_price' => 1250,
                        'type' => 'extra',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.platform-billing.invoices.show', $invoice));

        $invoice->refresh();
        $this->assertSame('sent', $invoice->status);
        $this->assertSame(21, $invoice->payment_terms_days);
        $this->assertSame('Aangepast voor controle', $invoice->notes);
        $this->assertEquals(1429.00, (float) $invoice->amount);
        $this->assertEquals(300.09, (float) $invoice->tax_amount);
        $this->assertEquals(1729.09, (float) $invoice->total_amount);
        $this->assertCount(2, $invoice->line_items);
        $this->assertSame('Ontwikkeling website 5 pagina\'s', $invoice->line_items[1]['description']);
        $this->assertSame('2026-09-22', $invoice->due_date?->format('Y-m-d'));
        $this->assertNotNull($invoice->sent_at);
        $this->assertNull($invoice->pdf_path);
    }

    public function test_edit_form_lists_catalog_line_items(): void
    {
        $invoice = $this->invoice();
        PlatformBillingLineItem::query()->create([
            'name' => 'Website ontwikkeling',
            'description' => 'Home, Informatie, Diensten, Over ons en Contact',
            'unit_price' => 1250,
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.platform-billing.invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('Website ontwikkeling', false)
            ->assertSee('Bestaande regel kiezen', false)
            ->assertSee('Zelf invullen', false);
    }

    public function test_saving_catalog_line_keeps_catalog_id(): void
    {
        $invoice = $this->invoice();
        $catalog = PlatformBillingLineItem::query()->create([
            'name' => 'Startkosten',
            'description' => 'Eenmalige inrichting',
            'unit_price' => 250,
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin())
            ->put(route('admin.platform-billing.invoices.update', $invoice), [
                'status' => 'draft',
                'payment_terms_days' => 14,
                'line_items' => [
                    [
                        'description' => $catalog->invoiceLineDescription(),
                        'quantity' => 1,
                        'unit_price' => 250,
                        'type' => 'extra',
                        'platform_billing_line_item_id' => $catalog->id,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.platform-billing.invoices.show', $invoice));

        $invoice->refresh();
        $this->assertSame($catalog->id, (int) ($invoice->line_items[0]['platform_billing_line_item_id'] ?? 0));
        $this->assertSame('Startkosten — Eenmalige inrichting', $invoice->line_items[0]['description']);
        $this->assertEquals(250.00, (float) $invoice->amount);
    }

    public function test_paid_invoice_can_be_edited(): void
    {
        $invoice = $this->invoice([
            'status' => 'paid',
            'paid_at' => '2026-09-06 00:00:00',
        ]);

        $this->actingAs($this->superAdmin())
            ->put(route('admin.platform-billing.invoices.update', $invoice), [
                'status' => 'paid',
                'payment_terms_days' => 14,
                'line_items' => [
                    [
                        'description' => 'Aangepaste regel',
                        'quantity' => 1,
                        'unit_price' => 50,
                        'type' => 'extra',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.platform-billing.invoices.show', $invoice));

        $invoice->refresh();
        $this->assertSame('Aangepaste regel', $invoice->line_items[0]['description']);
        $this->assertEquals(50.00, (float) $invoice->amount);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_company_admin_cannot_edit_saas_invoice(): void
    {
        $invoice = $this->invoice();

        $response = $this->actingAs($this->companyAdmin())
            ->get(route('admin.platform-billing.invoices.edit', $invoice));

        $this->assertTrue(
            in_array($response->status(), [403, 302, 303], true),
            'Alleen super-admin mag NEXA-facturen bewerken, got: '.$response->status()
        );
    }
}
