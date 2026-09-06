<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\CompanySubscriptionChange;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminEmergencyTerminateSubscriptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Carbon::setTestNow('2026-09-06 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function super_admin_can_emergency_terminate_subscription_at_month_end(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $company = $this->companyWithBilling();

        $this->actingAs($admin)
            ->from(route('admin.platform-billing.tenants.edit', $company))
            ->post(route('admin.platform-billing.tenants.emergency-terminate', $company))
            ->assertRedirect(route('admin.platform-billing.tenants.edit', ['company' => $company, 'saved' => 1]))
            ->assertSessionHas('success');

        $profile = CompanyBillingProfile::query()->where('company_id', $company->id)->first();
        $this->assertSame(CompanySubscriptionChange::TYPE_CANCEL, $profile->pending_change_type);
        $this->assertSame('2026-09-30', $profile->subscription_end_date->toDateString());
        $this->assertSame('2026-09-30', $profile->pending_change_effective_on->toDateString());

        $change = CompanySubscriptionChange::query()
            ->where('company_id', $company->id)
            ->where('change_type', CompanySubscriptionChange::TYPE_CANCEL)
            ->where('status', CompanySubscriptionChange::STATUS_SCHEDULED)
            ->first();
        $this->assertNotNull($change);
        $this->assertSame('2026-09-30', $change->effective_on->toDateString());
    }

    #[Test]
    public function edit_page_shows_emergency_terminate_button_for_super_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $company = $this->companyWithBilling();

        $this->actingAs($admin)
            ->get(route('admin.platform-billing.tenants.edit', $company))
            ->assertOk()
            ->assertSee('Noodbeeindiging', false)
            ->assertSee('Abonnement tussentijds beëindigen', false)
            ->assertSee('data-emergency-terminate-open', false)
            ->assertSee('id="tenant-emergency-terminate-modal"', false);
    }

    #[Test]
    public function company_admin_cannot_emergency_terminate(): void
    {
        $company = $this->companyWithBilling();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company-admin');

        $this->actingAs($user)
            ->from(route('admin.dashboard'))
            ->post(route('admin.platform-billing.tenants.emergency-terminate', $company))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');

        $profile = CompanyBillingProfile::query()->where('company_id', $company->id)->first();
        $this->assertNull($profile->pending_change_type);
        $this->assertNull($profile->subscription_end_date);
    }

    private function companyWithBilling(): Company
    {
        $company = Company::query()->create([
            'name' => 'Emergency Terminate Co '.uniqid(),
            'is_active' => true,
            'package_key' => 'pro',
            'email' => 'billing@example.com',
            'created_at' => '2026-03-01 09:00:00',
            'updated_at' => '2026-03-01 09:00:00',
        ]);

        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_PACKAGE,
            'subscription_start_date' => '2026-03-01',
            'auto_collect_enabled' => true,
            'agreed_monthly_amount' => 99,
        ]);

        return $company;
    }
}
