<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run the role seeder
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    public function test_super_admin_can_access_roles_index()
    {
        $user = User::where('email', 'm.tosun@mebura.nl')->first();
        
        $response = $this->actingAs($user)
            ->get('/admin/roles');

        $response->assertStatus(200);
        $response->assertSee('Rollen Beheer');
    }

    public function test_super_admin_can_create_role()
    {
        $user = User::where('email', 'm.tosun@mebura.nl')->first();
        
        $response = $this->actingAs($user)
            ->get('/admin/roles/create');

        $response->assertStatus(200);
        $response->assertSee('Nieuwe Rol Aanmaken');
    }

    public function test_super_admin_can_store_role()
    {
        $user = User::where('email', 'm.tosun@mebura.nl')->first();
        
        $response = $this->actingAs($user)
            ->post('/admin/roles', [
                'name' => 'test-role',
                'description' => 'Test role description',
                'permissions' => ['view-users']
            ]);

        $response->assertRedirect('/admin/roles');
        
        $this->assertDatabaseHas('roles', [
            'name' => 'test-role',
            'description' => 'Test role description'
        ]);
    }

    public function test_non_super_admin_cannot_access_roles()
    {
        // Create a regular user without super-admin role
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $response = $this->actingAs($user)
            ->get('/admin/roles');

        $response->assertStatus(302); // Redirect
    }
}
