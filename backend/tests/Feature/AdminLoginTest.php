<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_login_page_is_accessible()
    {
        $response = $this->get('/admin/login');
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.auth.login');
    }

    #[Test]
    public function super_admin_can_login()
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('super-admin');
        
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);
        
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertTrue(
            str_ends_with($location, '/admin') || str_ends_with($location, '/admin/dashboard'),
            "Expected redirect to /admin or /admin/dashboard, got: {$location}"
        );
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function regular_user_cannot_login_to_admin()
    {
        $user = User::factory()->create([
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
        ]);
        
        $response = $this->post('/admin/login', [
            'email' => 'user@test.com',
            'password' => 'password',
        ]);
        
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    #[Test]
    public function invalid_credentials_show_error()
    {
        $response = $this->post('/admin/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrongpassword',
        ]);
        
        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    #[Test]
    public function authenticated_admin_can_logout()
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('super-admin');
        
        $this->actingAs($user);
        
        $response = $this->post('/admin/logout');
        
        $response->assertRedirect('/admin/login');
    }

    #[Test]
    public function login_with_login_page_as_intended_redirects_to_dashboard(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('super-admin');

        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
            'intended' => 'http://localhost/admin/login',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function login_after_session_expired_redirects_to_intended_admin_page(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('super-admin');

        $this->get('/admin/login?intended='.urlencode('http://localhost/admin/settings'));

        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
            'intended' => 'http://localhost/admin/settings',
        ]);

        $response->assertRedirect('/admin/settings');
        $this->assertAuthenticatedAs($user);
    }
}










