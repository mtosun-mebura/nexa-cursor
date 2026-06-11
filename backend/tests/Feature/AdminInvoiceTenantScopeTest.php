<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInvoiceTenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    public function test_invoice_index_is_scoped_to_selected_tenant(): void
    {
        $tenantA = Company::create(['name' => 'Taxi A', 'is_active' => true]);
        $tenantB = Company::create(['name' => 'Taxi B', 'is_active' => true]);

        Invoice::query()->create([
            'invoice_number' => 'TA2026-0001',
            'company_id' => $tenantA->id,
            'amount' => 10,
            'tax_amount' => 2.1,
            'total_amount' => 12.1,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'line_items' => [],
        ]);

        Invoice::query()->create([
            'invoice_number' => 'TB2026-0001',
            'company_id' => $tenantB->id,
            'amount' => 20,
            'tax_amount' => 4.2,
            'total_amount' => 24.2,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'line_items' => [],
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)
            ->withSession(['selected_tenant' => $tenantA->id])
            ->get(route('admin.invoices.index'));

        $response->assertOk();
        $response->assertSee('TA2026-0001');
        $response->assertDontSee('TB2026-0001');
    }

    public function test_invoice_show_blocks_other_tenant_when_scoped(): void
    {
        $tenantA = Company::create(['name' => 'Taxi A', 'is_active' => true]);
        $tenantB = Company::create(['name' => 'Taxi B', 'is_active' => true]);

        $foreignInvoice = Invoice::query()->create([
            'invoice_number' => 'TB2026-0099',
            'company_id' => $tenantB->id,
            'amount' => 20,
            'tax_amount' => 4.2,
            'total_amount' => 24.2,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'line_items' => [],
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->withSession(['selected_tenant' => $tenantA->id])
            ->get(route('admin.invoices.show', $foreignInvoice))
            ->assertForbidden();
    }
}
