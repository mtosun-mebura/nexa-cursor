<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Services\UserRoleAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
    }

    public function test_sync_web_roles_assigns_multiple_roles_for_company(): void
    {
        $user = User::factory()->create(['company_id' => null]);

        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin', 'chauffeur']);

        $user->refresh();
        $names = collect($user->webRoleNames())->sort()->values()->all();

        $this->assertSame(['chauffeur', 'company-admin'], $names);
    }

    public function test_sync_web_roles_assigns_company_admin_for_tenant_user(): void
    {
        $company = Company::create(['name' => 'Tenant Test BV', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);

        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        $user->refresh();

        $this->assertSame(['company-admin'], $user->webRoleNames());
    }

    public function test_assigned_role_names_include_web_and_api_without_duplicates(): void
    {
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'api']);
        $company = Company::create(['name' => 'Api Roles BV', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);

        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        $pivot = config('permission.table_names.model_has_roles');
        $apiRole = Role::findByName('chauffeur', 'api');
        \Illuminate\Support\Facades\DB::table($pivot)->insert([
            'role_id' => $apiRole->id,
            'model_id' => $user->id,
            'model_type' => $user->getMorphClass(),
            'company_id' => $company->id,
        ]);

        $this->assertSame(['company-admin'], $user->fresh()->webRoleNames());
        $this->assertSame(['chauffeur', 'company-admin'], $user->fresh()->assignedRoleNames());
    }
}
