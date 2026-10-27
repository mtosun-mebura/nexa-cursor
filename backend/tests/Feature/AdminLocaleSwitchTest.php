<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleSwitchTest extends TestCase
{
    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    private function tenantSession(array $extra = []): array
    {
        $tenant = Company::query()->create(['name' => 'Locale Taxi', 'is_active' => true]);

        return ['selected_tenant' => $tenant->id] + $extra;
    }

    #[Test]
    public function default_admin_is_dutch_and_header_offers_english_flag(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->withSession($this->tenantSession())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame('nl', app()->getLocale());
        $this->assertStringContainsString('id="admin-locale-switch"', $html);
        $this->assertStringContainsString('data-locale-target="en"', $html);
        $this->assertStringContainsString('title="English"', $html);
        $this->assertStringContainsString(route('admin.locale.switch', 'en'), $html);
        $this->assertStringContainsString('Gebruikers', $html);
        $this->assertStringContainsString('Uitloggen', $html);
    }

    #[Test]
    public function switching_to_english_stores_session_redirects_back_and_translates_menu_and_header(): void
    {
        $user = $this->superAdmin();
        $back = route('admin.users.index');

        $this->actingAs($user)
            ->from($back)
            ->get(route('admin.locale.switch', 'en'))
            ->assertRedirect($back)
            ->assertSessionHas('locale', 'en');

        $html = $this->actingAs($user)
            ->withSession($this->tenantSession(['locale' => 'en']))
            ->get($back)
            ->assertOk()
            ->getContent();

        $this->assertSame('en', app()->getLocale());
        // Toggle toont nu de NL-vlag.
        $this->assertStringContainsString('data-locale-target="nl"', $html);
        $this->assertStringContainsString('title="Nederlands"', $html);
        $this->assertStringNotContainsString('data-locale-target="en"', $html);
        // Menu + header + lijstscherm in het Engels.
        $this->assertStringContainsString('Roles and permissions', $html);
        $this->assertStringContainsString('Log out', $html);
        $this->assertStringContainsString('My profile', $html);
        $this->assertStringContainsString('User management', $html);
        $this->assertStringNotContainsString('>Uitloggen<', $html);
    }

    #[Test]
    public function module_admin_pages_use_the_session_locale_too(): void
    {
        if (! Route::has('admin.taxi.vehicles.index')) {
            $this->markTestSkipped('NexaTaxi-routes zijn niet geregistreerd in deze testomgeving.');
        }

        $html = $this->actingAs($this->superAdmin())
            ->withSession($this->tenantSession(['locale' => 'en']))
            ->get(route('admin.taxi.vehicles.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-locale-target="nl"', $html);
        $this->assertStringContainsString('New vehicle', $html);
    }

    #[Test]
    public function invalid_locale_is_rejected_and_session_unchanged(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/locale/fr')
            ->assertNotFound();

        $this->assertNull(session('locale'));
    }
}
