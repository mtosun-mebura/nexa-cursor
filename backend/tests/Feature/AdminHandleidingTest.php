<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\UserRoleAssignmentService;
use App\Support\AdminHandleiding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminHandleidingTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdminUser(): User
    {
        Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    protected function createCompanyAdmin(string $packageKey): User
    {
        Role::query()->firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);

        $company = Company::query()->create([
            'name' => 'Handleiding '.ucfirst($packageKey),
            'is_active' => true,
            'package_key' => $packageKey,
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        return $user->fresh();
    }

    public function test_handleiding_index_is_available_for_admin_users(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('admin.handleiding.index'));

        $response->assertOk()
            ->assertSee('Handleiding')
            ->assertSee('Stap-voor-stap uitleg')
            ->assertSee('Aan de slag')
            ->assertSee('Dashboard')
            ->assertSee('Zoek op onderwerp of trefwoord', false)
            ->assertSee('data-handleiding-search', false)
            ->assertSee('data-handleiding-card', false);
    }

    public function test_handleiding_show_displays_first_article(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->get(route('admin.handleiding.show', 'aan-de-slag'))
            ->assertOk()
            ->assertSee('Aan de slag')
            ->assertSee('Welkom bij NEXA')
            ->assertSee('Tijdelijk wachtwoord');
    }

    public function test_unknown_handleiding_slug_returns_not_found(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->get(route('admin.handleiding.show', 'bestaat-niet'))
            ->assertNotFound();
    }

    #[Test]
    public function start_package_hides_handleiding_pages_outside_the_package(): void
    {
        $user = $this->createCompanyAdmin('start');
        $pages = AdminHandleiding::pagesForUser($user);

        $this->assertArrayHasKey('aan-de-slag', $pages);
        $this->assertArrayHasKey('dashboard', $pages);
        $this->assertArrayHasKey('ritten', $pages);
        $this->assertArrayHasKey('website-en-boekingen', $pages);
        $this->assertArrayHasKey('facturen', $pages);
        $this->assertArrayHasKey('incidenten', $pages);
        $this->assertArrayNotHasKey('gps-tracker', $pages);
        $this->assertArrayNotHasKey('betalingen', $pages);
        $this->assertArrayNotHasKey('dispatch', $pages);
        $this->assertArrayNotHasKey('chauffeur-app', $pages);
        $this->assertArrayNotHasKey('contractvervoer', $pages);
        $this->assertArrayNotHasKey('contractportaal', $pages);
        $this->assertArrayNotHasKey('nexa-suite-ritten', $pages);
        $this->assertArrayNotHasKey('frontend-en-ai', $pages);
        $this->assertArrayNotHasKey('nexa-facturatie', $pages);
        $this->assertArrayNotHasKey('paketten', $pages);
        $this->assertArrayNotHasKey('bedrijven', $pages);

        $this->actingAs($user)
            ->get(route('admin.handleiding.index'))
            ->assertOk()
            ->assertSee('Aan de slag')
            ->assertSee('Incidenten')
            ->assertDontSee('Dispatch')
            ->assertDontSee('Chauffeur-app')
            ->assertDontSee('Contractvervoer')
            ->assertDontSee('Betalingen via Mollie')
            ->assertDontSee('Alleen super-admin')
            ->assertDontSee('NEXA Suite ritten')
            ->assertDontSee('Front-end en AI-website');

        $this->actingAs($user)
            ->get(route('admin.handleiding.show', 'dispatch'))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('admin.handleiding.show', 'nexa-suite-ritten'))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('admin.handleiding.show', 'incidenten'))
            ->assertOk()
            ->assertSee('Incidenten')
            ->assertDontSee('Voor super-admins');
    }

    #[Test]
    public function pro_package_shows_dispatch_but_not_contract_pages(): void
    {
        $user = $this->createCompanyAdmin('pro');
        $pages = AdminHandleiding::pagesForUser($user);

        $this->assertArrayHasKey('betalingen', $pages);
        $this->assertArrayHasKey('dispatch', $pages);
        $this->assertArrayHasKey('chauffeur-app', $pages);
        $this->assertArrayNotHasKey('contractvervoer', $pages);
        $this->assertArrayNotHasKey('contractportaal', $pages);

        $this->actingAs($user)
            ->get(route('admin.handleiding.show', 'dispatch'))
            ->assertOk()
            ->assertSee('Dispatch');

        $this->actingAs($user)
            ->get(route('admin.handleiding.show', 'contractvervoer'))
            ->assertNotFound();
    }

    #[Test]
    public function business_package_shows_contract_pages(): void
    {
        $user = $this->createCompanyAdmin('business');
        $pages = AdminHandleiding::pagesForUser($user);

        $this->assertArrayHasKey('contractvervoer', $pages);
        $this->assertArrayHasKey('contractportaal', $pages);
    }

    #[Test]
    public function super_admin_with_selected_tenant_sees_only_that_package(): void
    {
        $super = $this->createAdminUser();
        $startAdmin = $this->createCompanyAdmin('start');

        $this->actingAs($super)
            ->withSession(['selected_tenant' => $startAdmin->company_id])
            ->get(route('admin.handleiding.index'))
            ->assertOk()
            ->assertSee('Aan de slag')
            ->assertSee('NEXA Suite ritten')
            ->assertSee('Front-end en AI-website')
            ->assertSee('Alleen super-admin')
            ->assertDontSee('Dispatch')
            ->assertDontSee('Contractvervoer');
    }

    #[Test]
    public function super_admin_without_tenant_still_sees_all_pages(): void
    {
        $super = $this->createAdminUser();

        $this->actingAs($super)
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.handleiding.index'))
            ->assertOk()
            ->assertSee('Dispatch')
            ->assertSee('Contractvervoer')
            ->assertSee('NEXA Suite ritten')
            ->assertSee('GPS-tracker')
            ->assertSee('Paketten');

        $this->actingAs($super)
            ->get(route('admin.handleiding.show', 'nexa-suite-ritten'))
            ->assertOk()
            ->assertSee('Betalingen → NEXA Suite ritten');

        $this->actingAs($super)
            ->get(route('admin.handleiding.show', 'incidenten'))
            ->assertOk()
            ->assertSee('Voor super-admins');
    }

    #[Test]
    public function gps_handleiding_is_visible_when_the_addon_is_enabled(): void
    {
        $user = $this->createCompanyAdmin('pro');
        $user->company->forceFill([
            'package_addons' => [\App\Support\TenantPackageAddon::GPS_TRACKING => 1],
        ])->save();

        $pages = AdminHandleiding::pagesForUser($user->fresh());
        $this->assertArrayHasKey('gps-tracker', $pages);

        $this->actingAs($user->fresh())
            ->get(route('admin.handleiding.show', 'gps-tracker'))
            ->assertOk()
            ->assertSee('GPS-tracker')
            ->assertSee('images/handleiding/gps-tracker.jpg', false);
    }

    #[Test]
    public function first_login_redirects_from_dashboard_to_handleiding(): void
    {
        $user = $this->createCompanyAdmin('start');
        $user->forceFill(['welcome_handleiding_pending' => true])->save();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.handleiding.index'));
    }

    #[Test]
    public function visiting_handleiding_clears_the_first_login_redirect(): void
    {
        $user = $this->createCompanyAdmin('start');
        $user->forceFill(['welcome_handleiding_pending' => true])->save();

        $this->actingAs($user)
            ->get(route('admin.handleiding.index'))
            ->assertOk()
            ->assertSee('Handleiding');

        $user->refresh();
        $this->assertFalse($user->welcome_handleiding_pending);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }
}
