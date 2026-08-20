<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use App\Services\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MenuServiceCompanyModuleFilterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function company_admin_only_sees_menu_items_for_attached_modules(): void
    {
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        foreach (['vehicles.view', 'skillmatching.vacancies.view'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);
        Module::query()->create([
            'name' => 'skillmatching',
            'display_name' => 'Skillmatching',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        $company = Company::query()->create(['name' => 'Taxi Demo Co', 'is_active' => true]);
        $company->modules()->attach(Module::query()->where('name', 'taxi')->value('id'));

        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo(['vehicles.view', 'skillmatching.vacancies.view']);
        app(\App\Services\UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        $this->actingAs($user);
        $items = app(MenuService::class)->getModuleMenuItems();
        $modules = array_values(array_unique(array_column($items, 'module')));

        $this->assertContains('taxi', $modules);
        $this->assertNotContains('skillmatching', $modules);
    }
}
