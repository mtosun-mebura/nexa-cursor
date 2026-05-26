<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AdminLoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test admin login page displays correctly
     */
    public function test_admin_login_page_displays()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->assertSee('Admin Inloggen')
                    ->assertSee('E-mailadres')
                    ->assertSee('Wachtwoord')
                    ->assertSee('Beveiligde Toegang')
                    ->assertPresent('input[name="email"]')
                    ->assertPresent('input[name="password"]')
                    ->assertPresent('button[type="submit"]');
        });
    }

    /**
     * Test super admin can login
     */
    public function test_super_admin_can_login()
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole($role);
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/admin/login')
                    ->type('email', 'admin@test.com')
                    ->type('password', 'password')
                    ->press('Inloggen')
                    ->assertPathIs('/admin')
                    ->assertSee('Dashboard');
        });
    }

    /**
     * Test invalid credentials show error
     */
    public function test_invalid_credentials_show_error()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->type('email', 'wrong@test.com')
                    ->type('password', 'wrongpassword')
                    ->press('Inloggen')
                    ->assertPathIs('/admin/login')
                    ->assertSee('Ongeldige inloggegevens');
        });
    }

    /**
     * Test password visibility toggle works
     */
    public function test_password_visibility_toggle_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->type('password', 'testpassword')
                    ->assertInputValue('password', 'testpassword')
                    ->click('@password-toggle')
                    ->assertAttribute('input[name="password"]', 'type', 'text')
                    ->click('@password-toggle')
                    ->assertAttribute('input[name="password"]', 'type', 'password');
        });
    }
}










