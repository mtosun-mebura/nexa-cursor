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
        $response->assertDontSee('apexcharts', false);
        $response->assertDontSee('core.bundle.js', false);
        $response->assertDontSee('resources/js/app.js', false);
        $response->assertSee('ktui.min.js', false);
        $response->assertSee('ki-filled ki-eye', false);
    }

    #[Test]
    public function forgot_password_page_shows_nexa_logo_and_readable_success_status(): void
    {
        $this->withSession([
            'status' => 'We hebben je een wachtwoord reset link gestuurd!',
        ])->get('/admin/password/reset')
            ->assertOk()
            ->assertViewIs('admin.auth.forgot-password')
            ->assertSee('nexa-brand-lockup', false)
            ->assertSee('auth-status-success', false)
            ->assertSee('We hebben je een wachtwoord reset link gestuurd!', false)
            ->assertDontSee('kt-alert-success', false);
    }

    #[Test]
    public function reset_password_page_shows_nexa_logo_and_full_placeholders(): void
    {
        $this->get('/admin/password/reset/test-token?email=admin@test.com')
            ->assertOk()
            ->assertViewIs('admin.auth.reset-password')
            ->assertSee('nexa-brand-lockup', false)
            ->assertSee('Herhaal wachtwoord', false)
            ->assertDontSee('Voer opnieuw een nieuw wachtwoord in', false);
    }

    #[Test]
    public function admin_pages_without_charts_do_not_load_apexcharts(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('apexcharts.min.js', false)
            ->assertDontSee('apexcharts.css', false);
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

    #[Test]
    public function nearby_taxi_polling_does_not_block_admin_login(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->getJson(route('nexataxi.booking.nearby-taxis', [
                'lat' => 52.22,
                'lng' => 6.89,
                'section_key' => 'component:taxi.boekingsmodule_v2',
            ]))->assertOk();
        }

        $this->post('/admin/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrongpassword',
        ])->assertStatus(302)->assertSessionHasErrors();
    }
}










