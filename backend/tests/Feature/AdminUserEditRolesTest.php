<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\UserRoleAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserEditRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view-users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit-users', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function edit_form_checks_chauffeur_when_assigned_role_name_differs_only_by_case(): void
    {
        $capital = Role::query()->firstOrCreate(
            ['name' => 'Chauffeur', 'guard_name' => 'web'],
            ['company_id' => null]
        );

        $company = Company::query()->create(['name' => 'Taxi Roles', 'is_active' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $driver = User::factory()->create(['company_id' => $company->id]);
        $this->attachWebRole($driver, $capital, $company->id);

        $this->assertSame(['Chauffeur'], $driver->webRoleNames());

        $html = $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.users.edit', $driver))
            ->assertOk()
            ->assertSee('Gebruiker Bewerken', false)
            ->getContent();

        $pos = strpos($html, 'roles[]');
        $this->assertTrue(
            (bool) preg_match('/<input[^>]*value="chauffeur"[^>]*>|<input[^>]*value="Chauffeur"[^>]*>/', $html, $match),
            'Checkbox chauffeur ontbreekt. roles[] snippet: '.($pos === false ? 'ONTBREEKT' : substr($html, max(0, $pos - 120), 500))
        );
        $this->assertTrue(
            str_contains($match[0], 'checked'),
            'Rol chauffeur zou aangevinkt moeten zijn, ook als de database "Chauffeur" opslaat. Input: '.$match[0]
        );
    }

    #[Test]
    public function company_admin_sees_own_account_in_users_index(): void
    {
        [$company, $admin] = $this->makeCompanyAdmin();

        $this->actingAs($admin, 'web')
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($admin->email, false)
            ->assertSee('Jij', false)
            ->assertDontSee('mailto:'.$admin->email, false);
    }

    #[Test]
    public function users_index_uses_the_standard_admin_confirm_modal_for_deletes(): void
    {
        [$company, $admin] = $this->makeCompanyAdmin();

        $this->actingAs($admin, 'web')
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('id="admin-confirm-modal"', false)
            ->assertSee('showAdminConfirm', false);
    }

    #[Test]
    public function user_show_does_not_use_mailto_for_email(): void
    {
        [$company, $admin] = $this->makeCompanyAdmin();

        $this->actingAs($admin, 'web')
            ->get(route('admin.users.show', $admin))
            ->assertOk()
            ->assertSee($admin->email, false)
            ->assertDontSee('mailto:'.$admin->email, false);
    }

    #[Test]
    public function users_index_shows_chauffeur_app_offline_status(): void
    {
        [$company, $admin] = $this->makeCompanyAdmin();
        $driver = User::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Dirk',
            'last_name' => 'Rijder',
            'email' => 'dirk.rijder@example.com',
            'is_active' => true,
        ]);
        app(UserRoleAssignmentService::class)->syncWebRoles($driver, ['chauffeur']);

        $this->actingAs($admin, 'web')
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Chauffeur · Offline', false);
    }

    #[Test]
    public function users_index_shows_web_and_api_roles_together(): void
    {
        [$company, $admin] = $this->makeCompanyAdmin();
        $driver = User::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Api',
            'last_name' => 'Chauffeur',
            'email' => 'api.chauffeur@example.com',
            'is_active' => true,
        ]);
        app(UserRoleAssignmentService::class)->syncWebRoles($driver, ['company-admin']);
        $apiRole = Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'api']);
        $this->attachWebRole($driver, $apiRole, $company->id);

        $html = $this->actingAs($admin, 'web')
            ->get(route('admin.users.index'))
            ->assertOk()
            ->getContent();

        $pos = strpos($html, 'api.chauffeur@example.com');
        $this->assertNotFalse($pos);
        $snippet = substr($html, $pos, 8000);
        $this->assertStringContainsString('>Chauffeur<', $snippet);
        $this->assertStringContainsString('Company admin', $snippet);
    }

    #[Test]
    public function company_admin_cannot_edit_own_roles_in_the_form_or_via_post(): void
    {
        [$company, $admin] = $this->makeCompanyAdmin();

        $this->actingAs($admin, 'web')
            ->get(route('admin.users.edit', $admin))
            ->assertOk()
            ->assertSee('Alleen een super-admin kan rollen wijzigen', false)
            ->assertDontSee('data-checkbox-group="roles"', false);

        $this->actingAs($admin, 'web')
            ->put(route('admin.users.update', $admin), [
                'first_name' => $admin->first_name,
                'last_name' => $admin->last_name,
                'email' => $admin->email,
                'roles' => ['chauffeur'],
            ])
            ->assertRedirect(route('admin.users.show', $admin));

        $admin->refresh();
        $this->assertSame(['company-admin'], $admin->webRoleNames());
    }

    #[Test]
    public function company_admin_can_change_another_users_roles(): void
    {
        [$company, $admin] = $this->makeCompanyAdmin();
        $colleague = User::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Karel',
            'last_name' => 'Collega',
            'email' => 'karel.collega@example.com',
        ]);
        app(UserRoleAssignmentService::class)->syncWebRoles($colleague, ['chauffeur']);

        $this->actingAs($admin, 'web')
            ->put(route('admin.users.update', $colleague), [
                'first_name' => $colleague->first_name,
                'last_name' => $colleague->last_name,
                'email' => $colleague->email,
                'roles' => ['company-admin'],
            ])
            ->assertRedirect(route('admin.users.show', $colleague));

        $colleague->refresh();
        $this->assertSame(['company-admin'], $colleague->webRoleNames());
    }

    #[Test]
    public function super_admin_can_change_another_users_roles(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Super Roles', 'is_active' => true]);
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $target = User::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Sara',
            'last_name' => 'Doel',
            'email' => 'sara.doel@example.com',
        ]);
        app(UserRoleAssignmentService::class)->syncWebRoles($target, ['chauffeur']);

        $this->actingAs($super, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->put(route('admin.users.update', $target), [
                'first_name' => $target->first_name,
                'last_name' => $target->last_name,
                'email' => $target->email,
                'company_id' => $company->id,
                'roles' => ['company-admin'],
            ])
            ->assertRedirect(route('admin.users.show', $target));

        $target->refresh();
        $this->assertSame(['company-admin'], $target->webRoleNames());
    }

    /**
     * @return array{0: Company, 1: User}
     */
    private function makeCompanyAdmin(): array
    {
        $company = Company::query()->create(['name' => 'Taxi Eigen Gebruiker', 'is_active' => true]);
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Anna',
            'last_name' => 'Admin',
            'email' => 'anna.admin@example.com',
        ]);
        $role = Role::findByName('company-admin', 'web');
        $role->givePermissionTo(['view-users', 'edit-users']);
        app(UserRoleAssignmentService::class)->syncWebRoles($admin, ['company-admin']);

        return [$company, $admin];
    }

    private function attachWebRole(User $user, Role $role, int $companyId): void
    {
        $pivot = config('permission.table_names.model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key') ?: 'model_id';
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $teamKey = config('permission.column_names.team_foreign_key') ?: 'company_id';

        $row = [
            $rolePivotKey => $role->id,
            $morphKey => $user->id,
            'model_type' => $user->getMorphClass(),
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn($pivot, $teamKey)) {
            $row[$teamKey] = $companyId;
        }

        DB::table($pivot)->insert($row);
    }
}
