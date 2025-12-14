<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_login_page_is_accessible()
    {
        $response = $this->get('/admin/login');
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.auth.login');
    }

    /** @test */
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
        
        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
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

    /** @test */
    public function invalid_credentials_show_error()
    {
        $response = $this->post('/admin/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrongpassword',
        ]);
        
        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /** @test */
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
        $this->assertGuest();
    }
}







