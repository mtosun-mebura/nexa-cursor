<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Module;
use App\Models\Notification;
use App\Models\User;
use App\Modules\NexaTaxi\Services\TaxiTenantSetupService;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxiSetupNotificationVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function super_admin_does_not_see_taxi_setup_until_a_tenant_is_selected(): void
    {
        [$company, $admin] = $this->taxiCompanyWithAdmin();
        $super = User::factory()->create(['company_id' => null]);
        $super->assignRole('super-admin');

        Notification::query()->create([
            'user_id' => $admin->id,
            'company_id' => $company->id,
            'type' => TaxiTenantSetupService::NOTIFICATION_TYPE,
            'title' => 'Nexa Taxi inrichten',
            'message' => 'Nog in te richten: Voertuigen.',
            'priority' => 'high',
        ]);
        Notification::query()->create([
            'user_id' => $super->id,
            'company_id' => $company->id,
            'type' => TaxiTenantSetupService::NOTIFICATION_TYPE,
            'title' => 'Nexa Taxi inrichten',
            'message' => 'Oude super-admin kopie.',
            'priority' => 'high',
        ]);
        Notification::query()->create([
            'user_id' => $super->id,
            'type' => 'info',
            'title' => 'Systeem',
            'message' => 'Algemene melding.',
        ]);

        $withoutTenant = $this->actingAs($super)
            ->getJson(route('admin.notifications.list'))
            ->assertOk()
            ->json();
        $this->assertCount(1, $withoutTenant);
        $this->assertSame('Systeem', $withoutTenant[0]['title'] ?? null);

        $withTenant = $this->actingAs($super)
            ->withSession(['selected_tenant' => $company->id])
            ->getJson(route('admin.notifications.list'))
            ->assertOk()
            ->json();
        $titles = collect($withTenant)->pluck('title')->all();
        $this->assertContains('Nexa Taxi inrichten', $titles);
        $this->assertContains('Systeem', $titles);
        $this->assertSame(
            1,
            collect($withTenant)->where('type', TaxiTenantSetupService::NOTIFICATION_TYPE)->count()
        );
    }

    #[Test]
    public function super_admin_with_tenant_sees_that_tenants_other_notifications(): void
    {
        [$company, $admin] = $this->taxiCompanyWithAdmin();
        $super = User::factory()->create(['company_id' => null]);
        $super->assignRole('super-admin');

        Notification::query()->create([
            'user_id' => $admin->id,
            'company_id' => $company->id,
            'type' => 'incident',
            'title' => 'Nieuw incident INC-DEMO-1',
            'message' => 'Er is een incident gemeld.',
        ]);
        $otherCompany = Company::query()->create([
            'name' => 'Andere Taxi '.uniqid(),
            'is_active' => true,
        ]);
        Notification::query()->create([
            'user_id' => $super->id,
            'company_id' => $otherCompany->id,
            'type' => TaxiTenantSetupService::NOTIFICATION_TYPE,
            'title' => 'Nexa Taxi inrichten',
            'message' => 'Andere tenant.',
        ]);

        $json = $this->actingAs($super)
            ->withSession(['selected_tenant' => $company->id])
            ->getJson(route('admin.notifications.list'))
            ->assertOk()
            ->json();

        $titles = collect($json)->pluck('title')->all();
        $this->assertContains('Nieuw incident INC-DEMO-1', $titles);
        $this->assertNotContains('Nexa Taxi inrichten', $titles);
    }

    #[Test]
    public function tenant_admin_sees_company_taxi_setup_once(): void
    {
        [$company, $admin] = $this->taxiCompanyWithAdmin();
        $colleague = User::factory()->create(['company_id' => $company->id]);
        Notification::query()->create([
            'user_id' => $colleague->id,
            'company_id' => $company->id,
            'type' => TaxiTenantSetupService::NOTIFICATION_TYPE,
            'title' => 'Nexa Taxi inrichten',
            'message' => 'Nog in te richten: Voertuigen.',
            'priority' => 'high',
        ]);

        $json = $this->actingAs($admin)
            ->getJson(route('admin.notifications.list'))
            ->assertOk()
            ->json();
        $this->assertCount(1, $json);
        $this->assertSame('Nexa Taxi inrichten', $json[0]['title'] ?? null);
    }

    /**
     * @return array{0: Company, 1: User}
     */
    private function taxiCompanyWithAdmin(): array
    {
        $module = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'is_active' => true,
        ]);
        $company = Company::query()->create([
            'name' => 'Drawer Taxi '.uniqid(),
            'is_active' => true,
        ]);
        $company->modules()->attach($module->id);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company-admin');

        return [$company, $user];
    }
}
