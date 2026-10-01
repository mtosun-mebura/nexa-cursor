<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\RidePlatformSettlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPaymentFlowsGuideTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');
    }

    #[Test]
    public function super_admin_sees_payment_flows_guide_with_marketplace_and_network(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.payment-flows.guide'))
            ->assertOk()
            ->assertSee('Uitleg betalingen', false)
            ->assertSee('Marketplace', false)
            ->assertSee('Network', false)
            ->assertSee('platform collect', false)
            ->assertSee('Settlement-wachtrij', false);
    }

    #[Test]
    public function company_admin_cannot_open_payment_flows_guide(): void
    {
        $company = Company::query()->create(['name' => 'Taxi', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('company-admin');

        $this->actingAs($admin)
            ->get(route('admin.payment-flows.guide'))
            ->assertStatus(302);
    }

    #[Test]
    public function settlements_queue_lists_ledger_and_allows_manual_process(): void
    {
        $company = Company::query()->create(['name' => 'Claimer', 'is_active' => true]);
        RidePlatformSettlement::query()->create([
            'ride_request_id' => 42,
            'model' => RidePlatformSettlement::MODEL_MARKETPLACE,
            'owner_company_id' => $company->id,
            'fulfiller_company_id' => $company->id,
            'gross_amount' => 100,
            'nexa_fee_percent' => 10,
            'nexa_fee_amount' => 10,
            'net_amount' => 90,
            'owner_share_percent' => 100,
            'owner_share_amount' => 90,
            'fulfiller_share_percent' => 0,
            'fulfiller_share_amount' => 0,
            'currency' => 'EUR',
            'status' => RidePlatformSettlement::STATUS_PENDING_PAYOUT,
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.payment-flows.settlements'))
            ->assertOk()
            ->assertSee('#42', false)
            ->assertSee('marketplace', false)
            ->assertSee('Verwerk wachtrij nu', false);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.payment-flows.settlements.process'))
            ->assertRedirect();
    }
}
