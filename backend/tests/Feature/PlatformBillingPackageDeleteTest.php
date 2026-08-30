<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PlatformBillingPackage;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformBillingPackageDeleteTest extends TestCase
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
        $company = Company::query()->create(['name' => 'Tenant BV', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company-admin');

        return $user;
    }

    private function package(string $name): PlatformBillingPackage
    {
        return PlatformBillingPackage::query()->create([
            'name' => $name,
            'monthly_amount' => 49.99,
            'currency' => 'EUR',
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    public function test_old_packages_index_redirects_to_nexa_pricing(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.platform-billing.packages.index'))
            ->assertRedirect(route('admin.nexa-pricing.edit'));
    }

    public function test_old_package_mutations_redirect_without_deleting(): void
    {
        $admin = $this->superAdmin();
        $package = $this->package('Te behouden');

        $this->actingAs($admin)
            ->delete(route('admin.platform-billing.packages.destroy', $package))
            ->assertRedirect(route('admin.nexa-pricing.edit'));

        $this->assertDatabaseHas('platform_billing_packages', ['id' => $package->id]);

        $this->actingAs($admin)
            ->delete(route('admin.platform-billing.packages.bulk-destroy'), [
                'package_ids' => [$package->id],
            ])
            ->assertRedirect(route('admin.nexa-pricing.edit'));

        $this->assertDatabaseHas('platform_billing_packages', ['id' => $package->id]);
    }

    public function test_company_admin_cannot_access_old_packages_routes(): void
    {
        $user = $this->companyAdmin();
        $package = $this->package('Beschermd');

        $index = $this->actingAs($user)
            ->get(route('admin.platform-billing.packages.index'));
        $this->assertTrue(
            in_array($index->status(), [403, 302, 303], true),
            'Alleen super-admin mag de oude pakketten-route zien, got: '.$index->status()
        );

        $destroy = $this->actingAs($user)
            ->delete(route('admin.platform-billing.packages.destroy', $package));
        $this->assertTrue(
            in_array($destroy->status(), [403, 302, 303], true),
            'Alleen super-admin mag pakketten wijzigen, got: '.$destroy->status()
        );

        $this->assertDatabaseHas('platform_billing_packages', ['id' => $package->id]);
    }
}
