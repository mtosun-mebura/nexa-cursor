<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\CompanySubscriptionChange;
use App\Models\User;
use App\Services\PlatformBilling\SubscriptionBillingCalculator;
use App\Services\PlatformBilling\TenantSubscriptionService;
use App\Services\UserRoleAssignmentService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCompanySubscriptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Carbon::setTestNow('2026-03-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function company_admin_sees_abonnementen_menu_and_current_contract(): void
    {
        [$user] = $this->companyAdmin('start');

        $this->actingAs($user)
            ->get(route('admin.subscriptions.show'))
            ->assertOk()
            ->assertSee('Abonnementen', false)
            ->assertSee('Huidig abonnement', false)
            ->assertSee('Start', false)
            ->assertSee('Nu upgraden', false)
            ->assertSee('Opzeggen per 15-03-2027', false)
            ->assertSee('data-upgrade-open', false)
            ->assertSee('id="subscription-upgrade-modal"', false)
            ->assertSee('Upgraden bevestigen', false)
            ->assertSee('data-cancel-open', false)
            ->assertSee('id="subscription-cancel-modal"', false)
            ->assertSee('Opzeggen bevestigen', false)
            ->assertSee('Direct opzeggen kan alleen tijdens de', false)
            ->assertSee('proefperiode', false)
            ->assertSee('einde van het jaarcontract', false)
            ->assertDontSee('wordt meegenomen in de SEPA-incasso. Doorgaan?', false)
            ->assertDontSee("onclick=\"return confirm('Opzeggen per", false);
    }

    #[Test]
    public function super_admin_does_not_see_abonnementen_menu_and_cannot_open_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.subscriptions.show'))
            ->assertForbidden();
    }

    #[Test]
    public function staff_cannot_open_abonnementen(): void
    {
        $company = $this->company('start');
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('staff');

        $this->actingAs($user)
            ->get(route('admin.subscriptions.show'))
            ->assertForbidden();
    }

    #[Test]
    public function upgrade_applies_immediately_and_prorates_the_new_price(): void
    {
        [$user, $company] = $this->companyAdmin('start');

        $this->actingAs($user)
            ->from(route('admin.subscriptions.show'))
            ->post(route('admin.subscriptions.upgrade'), ['package_key' => 'pro'])
            ->assertRedirect(route('admin.subscriptions.show', ['saved' => 1]));

        $company->refresh();
        $this->assertSame('pro', $company->package_key);

        $profile = CompanyBillingProfile::query()->where('company_id', $company->id)->first();
        $this->assertNotNull($profile);
        $this->assertSame('pro', $profile->package?->package_key);
        $this->assertEquals(99.0, (float) $profile->package->monthly_amount);
        $this->assertGreaterThan(0, (float) $profile->pending_proration_amount);

        $this->assertDatabaseHas('company_subscription_changes', [
            'company_id' => $company->id,
            'change_type' => CompanySubscriptionChange::TYPE_UPGRADE,
            'status' => CompanySubscriptionChange::STATUS_APPLIED,
            'to_package_key' => 'pro',
        ]);
    }

    #[Test]
    public function downgrade_during_first_year_waits_until_contract_end(): void
    {
        [$user, $company] = $this->companyAdmin('pro');

        $this->actingAs($user)
            ->post(route('admin.subscriptions.downgrade'), ['package_key' => 'start'])
            ->assertRedirect(route('admin.subscriptions.show', ['saved' => 1]));

        $company->refresh();
        $this->assertSame('pro', $company->package_key);

        $profile = CompanyBillingProfile::query()->where('company_id', $company->id)->first();
        $this->assertSame('downgrade', $profile->pending_change_type);
        $this->assertSame('start', $profile->pending_package_key);
        $this->assertSame('2027-03-15', $profile->pending_change_effective_on->toDateString());
        $this->assertEquals(99.0, (float) $profile->package->monthly_amount);
    }

    #[Test]
    public function applying_a_scheduled_downgrade_sets_the_new_price(): void
    {
        [$user, $company] = $this->companyAdmin('pro');
        $this->actingAs($user)
            ->post(route('admin.subscriptions.downgrade'), ['package_key' => 'start']);

        Carbon::setTestNow('2027-03-15 09:00:00');
        $applied = app(TenantSubscriptionService::class)->applyDueChanges(now());
        $this->assertSame(1, $applied);

        $company->refresh();
        $this->assertSame('start', $company->package_key);
        $profile = CompanyBillingProfile::query()->where('company_id', $company->id)->with('package')->first();
        $this->assertNull($profile->pending_change_type);
        $this->assertEquals(49.0, (float) $profile->package->monthly_amount);
    }

    #[Test]
    public function cancel_during_first_year_stops_sepa_at_contract_end(): void
    {
        [$user, $company] = $this->companyAdmin('start');

        $this->actingAs($user)
            ->post(route('admin.subscriptions.cancel'))
            ->assertRedirect(route('admin.subscriptions.show', ['saved' => 1]));

        $profile = CompanyBillingProfile::query()->where('company_id', $company->id)->first();
        $this->assertSame('cancel', $profile->pending_change_type);
        $this->assertSame('2027-03-15', $profile->subscription_end_date->toDateString());
        $profile->mollie_subscription_id = 'sub_test';
        $profile->save();

        $calculator = app(SubscriptionBillingCalculator::class);
        $this->assertFalse($calculator->shouldCancelMollieSubscription($profile, Carbon::parse('2027-03-14')));
        $this->assertTrue($calculator->shouldCancelMollieSubscription($profile, Carbon::parse('2027-03-15')));
        $this->assertNotNull($calculator->mollieSubscriptionTimes($profile));
    }

    #[Test]
    public function after_first_year_cancel_is_allowed_at_month_end(): void
    {
        [$user, $company] = $this->companyAdmin('start');
        Carbon::setTestNow('2027-04-10 10:00:00');

        $this->actingAs($user)
            ->post(route('admin.subscriptions.cancel'))
            ->assertRedirect(route('admin.subscriptions.show', ['saved' => 1]));

        $profile = CompanyBillingProfile::query()->where('company_id', $company->id)->first();
        $this->assertSame('2027-04-30', $profile->subscription_end_date->toDateString());
    }

    #[Test]
    public function pending_cancel_can_be_withdrawn(): void
    {
        [$user, $company] = $this->companyAdmin('start');
        $this->actingAs($user)->post(route('admin.subscriptions.cancel'));

        $this->actingAs($user)
            ->post(route('admin.subscriptions.withdraw'))
            ->assertRedirect(route('admin.subscriptions.show', ['saved' => 1]));

        $profile = CompanyBillingProfile::query()->where('company_id', $company->id)->first();
        $this->assertNull($profile->pending_change_type);
        $this->assertNull($profile->subscription_end_date);
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function companyAdmin(string $packageKey): array
    {
        $company = $this->company($packageKey);
        $user = User::factory()->create(['company_id' => $company->id]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        return [$user, $company];
    }

    private function company(string $packageKey): Company
    {
        return Company::query()->create([
            'name' => 'Abonnement Test '.uniqid(),
            'is_active' => true,
            'package_key' => $packageKey,
            'created_at' => '2026-03-15 09:00:00',
            'updated_at' => '2026-03-15 09:00:00',
        ]);
    }
}
