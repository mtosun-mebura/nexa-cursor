<?php

namespace Tests\Unit;

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
}
