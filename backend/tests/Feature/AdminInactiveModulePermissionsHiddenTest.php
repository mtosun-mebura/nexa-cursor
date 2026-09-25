<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\User;
use App\Services\UserRoleAssignmentService;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInactiveModulePermissionsHiddenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function roles_and_permissions_hide_skillmatching_when_module_is_off(): void
    {
        $super = $this->superAdmin();
        $role = Role::findByName('company-admin', 'web');

        $edit = $this->actingAs($super)
            ->get(route('admin.roles.edit', $role))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('view-vacancies', $edit);
        $this->assertStringNotContainsString('view-matches', $edit);
        $this->assertStringNotContainsString('view-interviews', $edit);
        $this->assertStringNotContainsString('skillmatching.vacancies.view', $edit);
        $this->assertStringNotContainsString('>Vacatures<', $edit);
        $this->assertStringContainsString('view-users', $edit);
        $this->assertStringContainsString('view-mailserver', $edit);

        $create = $this->actingAs($super)
            ->get(route('admin.roles.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('view-vacancies', $create);
        $this->assertStringNotContainsString('>Vacatures<', $create);
        $this->assertStringContainsString('view-users', $create);

        $show = $this->actingAs($super)
            ->get(route('admin.roles.show', $role))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('>Vacatures<', $show);
        $this->assertStringNotContainsString('>Matches<', $show);
        $this->assertStringNotContainsString('>Interviews<', $show);
        $this->assertMatchesRegularExpression('/Toegewezen Rechten \((\d+)\)/', $show);
        preg_match('/Toegewezen Rechten \((\d+)\)/', $show, $headingCount);
        preg_match('/Aantal Rechten.*?kt-badge-info[^>]*>\s*(\d+)/s', $show, $badgeCount);
        $this->assertNotEmpty($badgeCount[1] ?? null);
        $this->assertSame($headingCount[1], $badgeCount[1]);

        $permissions = $this->actingAs($super)
            ->get(route('admin.permissions.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('view-vacancies', $permissions);
        $this->assertStringNotContainsString('skillmatching.vacancies.view', $permissions);
        $this->assertStringContainsString('view-users', $permissions);
        $this->assertStringContainsString('view-mailserver', $permissions);
    }

    #[Test]
    public function updating_a_role_keeps_hidden_skillmatching_permissions(): void
    {
        $super = $this->superAdmin();
        $role = Role::create([
            'name' => 'hidden-module-role',
            'guard_name' => 'web',
            'description' => 'Test',
        ]);
        $role->syncPermissions(['view-users', 'view-vacancies', 'view-mailserver']);

        $this->actingAs($super)
            ->put(route('admin.roles.update', $role), [
                'name' => 'hidden-module-role',
                'description' => 'Test',
                'permissions' => ['view-users', 'view-mailserver'],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('view-users'));
        $this->assertTrue($role->hasPermissionTo('view-mailserver'));
        $this->assertTrue($role->hasPermissionTo('view-vacancies'));
    }

    #[Test]
    public function skillmatching_permissions_reappear_when_the_module_is_available(): void
    {
        if (! Schema::hasTable('vacancies')) {
            Schema::create('vacancies', function ($table) {
                $table->id();
            });
        }
        Module::query()->create([
            'name' => 'skillmatching',
            'display_name' => 'Nexa Skillmatching',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-briefcase',
            'installed' => true,
            'active' => true,
        ]);

        $super = $this->superAdmin();
        $role = Role::findByName('company-admin', 'web');

        $this->actingAs($super)
            ->get(route('admin.roles.edit', $role))
            ->assertOk()
            ->assertSee('view-vacancies', false)
            ->assertSee('Vacatures', false);
    }

    #[Test]
    public function roles_show_uses_the_same_permission_matrix_as_edit(): void
    {
        if (! Schema::hasTable('ride_requests')) {
            Schema::create('ride_requests', function ($table) {
                $table->id();
            });
        }
        Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
        ]);
        foreach (['ai_chatbot.view', 'ai_chatbot.create', 'view-users'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::create([
            'name' => 'matrix-show-role',
            'guard_name' => 'web',
            'description' => 'Test',
        ]);
        $role->syncPermissions(['ai_chatbot.view', 'ai_chatbot.create', 'view-users']);

        $html = $this->actingAs($this->superAdmin())
            ->get(route('admin.roles.show', $role))
            ->assertOk()
            ->assertSee('Module / Resource', false)
            ->assertSee('AI-chatbot', false)
            ->assertSee('Gebruikers', false)
            ->assertDontSee('Ai_chatbot.create', false)
            ->assertSee('Toegewezen Rechten (3)', false)
            ->getContent();

        preg_match('/Aantal Rechten.*?kt-badge-info[^>]*>\s*(\d+)/s', $html, $badgeCount);
        $this->assertSame('3', $badgeCount[1] ?? null);

        $this->assertStringContainsString('text-blue-500', $html);
        $this->assertStringContainsString('data-permission-action="View"', $html);
        $this->assertStringContainsString('data-permission-action="Create"', $html);
        $this->assertStringNotContainsString('name="permissions[]"', $html);
    }

    private function superAdmin(): User
    {
        $super = User::factory()->create();
        app(UserRoleAssignmentService::class)->syncWebRoles($super, ['super-admin']);

        return $super->fresh();
    }
}
