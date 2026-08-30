<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\User;
use App\Services\PlatformBilling\TenantBillingAccessService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantBillingAccessRestrictionTest extends TestCase
{
    public function test_booking_quote_is_denied_when_bookings_are_blocked(): void
    {
        $company = $this->makeTenant(TenantBillingAccessService::BOOKINGS);
        config(['tenancy.dev_host_company_map' => ['localhost' => $company->id]]);

        $response = $this->postJson(route('nexataxi.booking.quote'), [
            'distance_meters' => 5000,
            'duration_seconds' => 600,
            'passengers' => 2,
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => app(TenantBillingAccessService::class)->bookingDeniedMessage(),
        ]);
    }

    public function test_full_block_returns_blocked_page_on_website(): void
    {
        $company = $this->makeTenant(TenantBillingAccessService::FULL);
        config(['tenancy.dev_host_company_map' => ['localhost' => $company->id]]);

        $response = $this->get('/');

        $response->assertStatus(403);
        $response->assertSee('Omgeving tijdelijk geblokkeerd', false);
    }

    public function test_bookings_block_does_not_take_down_the_website(): void
    {
        $company = $this->makeTenant(TenantBillingAccessService::BOOKINGS);
        config(['tenancy.dev_host_company_map' => ['localhost' => $company->id]]);

        $response = $this->get('/');

        $this->assertNotSame(403, $response->status());
    }

    public function test_super_admin_can_save_overdue_block_mode(): void
    {
        Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $company = Company::query()->create([
            'name' => 'Save Block Mode BV',
            'email' => 'save-block@example.test',
            'is_active' => true,
        ]);
        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_FREE,
        ]);

        $response = $this->actingAs($user)->put(route('admin.platform-billing.tenants.update', $company), [
            'billing_mode' => 'free',
            'overdue_block_mode' => 'full',
            'access_restriction' => 'bookings',
            'billing_email' => $company->email,
        ]);

        $response->assertRedirect(route('admin.platform-billing.tenants.edit', $company));
        $profile = CompanyBillingProfile::query()->where('company_id', $company->id)->first();
        $this->assertSame('full', $profile->overdue_block_mode);
        $this->assertSame('bookings', $profile->access_restriction);
        $this->assertSame(TenantBillingAccessService::SOURCE_MANUAL, $profile->access_restriction_source);
    }

    private function makeTenant(string $restriction): Company
    {
        $company = Company::query()->create([
            'name' => 'Blocked Tenant '.$restriction,
            'email' => 'blocked-'.$restriction.'@example.test',
            'is_active' => true,
        ]);
        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_CUSTOM,
            'custom_monthly_amount' => 50,
            'overdue_block_mode' => $restriction,
            'access_restriction' => $restriction,
            'access_restriction_source' => TenantBillingAccessService::SOURCE_DUNNING,
            'access_restricted_at' => now(),
        ]);

        return $company;
    }
}
