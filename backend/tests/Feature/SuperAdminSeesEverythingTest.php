<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Super-admin moet overal bij kunnen: alle tenants in de switcher, volledig sidebar-menu en geen 403
 * op de belangrijkste admin-pagina's — ook als het account een company_id heeft (Spatie team-scope)
 * of er een tenant gekozen is.
 */
class SuperAdminSeesEverythingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function superAdmin(?Company $ownCompany = null): User
    {
        $user = User::factory()->create(['company_id' => $ownCompany?->id]);
        $user->assignRole('super-admin');

        return $user;
    }

    /** @return array<string, array{0: string}> */
    public static function adminRoutes(): array
    {
        // admin.permissions.index declareert een globale functie in de view (niet 2x per proces te renderen).
        // Dashboard en nexa-suite-bookings gebruiken Postgres-only SQL (EXTRACT(...)::integer) en draaien niet op sqlite.
        $names = [
            'admin.website-pages.index',
            'admin.companies.index',
            'admin.users.index',
            'admin.roles.index',
            'admin.modules.index',
            'admin.settings.index',
            'admin.settings.general.index',
            'admin.settings.upgrade.index',
            'admin.monitoring.index',
            'admin.platform-billing.tenants.index',
            'admin.platform-billing.packages.index',
            'admin.payment-providers.index',
            'admin.email-templates.index',
            'admin.newsletters.index',
            'admin.taxi.ride_requests.index',
            'admin.taxi.vehicles.index',
            'admin.taxi.dispatch_settings.edit',
            'admin.taxi.gps_tracking.index',
        ];

        return array_combine($names, array_map(fn (string $n) => [$n], $names));
    }

    #[Test]
    public function tenant_switcher_lists_every_company_for_super_admin_even_when_scoped_to_one_tenant(): void
    {
        $a = Company::query()->create(['name' => 'Zeta Taxi', 'is_active' => true]);
        $b = Company::query()->create(['name' => 'Alfa Taxi', 'is_active' => true]);
        $c = Company::query()->create(['name' => 'Midden Taxi', 'is_active' => false]);
        $super = $this->superAdmin($a);

        $html = $this->actingAs($super)
            ->withSession(['selected_tenant' => $a->id])
            ->get(route('admin.website-pages.index'))
            ->assertOk()
            ->getContent();

        $menu = substr($html, (int) strpos($html, 'class="kt-dropdown-menu tenant-switcher-menu'));
        $menu = substr($menu, 0, (int) strpos($menu, '</div>'.PHP_EOL.'        </div>'));

        foreach (['Alle Tenants', 'Zeta Taxi', 'Alfa Taxi', 'Midden Taxi', '(inactief)'] as $label) {
            $this->assertStringContainsString($label, $menu);
        }
        $this->assertLessThan(strpos($menu, 'Midden Taxi'), strpos($menu, 'Alfa Taxi'), 'Tenants horen op naam gesorteerd te zijn');
        $this->assertLessThan(strpos($menu, 'Zeta Taxi'), strpos($menu, 'Midden Taxi'));
    }

    #[Test]
    public function tenant_switcher_is_not_cached_across_new_companies(): void
    {
        $super = $this->superAdmin();
        Company::query()->create(['name' => 'Eerste Taxi', 'is_active' => true]);

        $this->actingAs($super)->get(route('admin.website-pages.index'))->assertOk()->assertSee('Eerste Taxi');

        Company::query()->create(['name' => 'Tweede Taxi', 'is_active' => true]);

        $this->actingAs($super)->get(route('admin.website-pages.index'))->assertOk()->assertSee('Tweede Taxi');
    }

    #[Test]
    public function company_admin_gets_no_tenant_switcher(): void
    {
        $own = Company::query()->create(['name' => 'Eigen Taxi', 'is_active' => true]);
        Company::query()->create(['name' => 'Vreemde Taxi', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $own->id]);
        $admin->assignRole('company-admin');

        $this->actingAs($admin)
            ->get(route('admin.handleiding.index'))
            ->assertOk()
            ->assertDontSee('Vreemde Taxi')
            ->assertDontSee('class="tenant-switcher px-3', false);
    }

    #[Test]
    public function super_admin_with_own_company_still_gets_full_sidebar_menu(): void
    {
        $company = Company::query()->create(['name' => 'Eigen Bedrijf', 'is_active' => true]);
        $super = $this->superAdmin($company);

        $html = $this->actingAs($super)->get(route('admin.website-pages.index'))->assertOk()->getContent();

        foreach ([
            'admin.companies.index',
            'admin.users.index',
            'admin.roles.index',
            'admin.modules.index',
            'admin.settings.index',
            'admin.monitoring.index',
            'admin.platform-billing.tenants.index',
        ] as $routeName) {
            $this->assertStringContainsString(
                route($routeName, absolute: false),
                $html,
                "Sidebar mist {$routeName} voor super-admin met eigen company_id"
            );
        }
    }

    #[Test]
    #[DataProvider('adminRoutes')]
    public function super_admin_is_not_blocked_on_admin_route(string $routeName): void
    {
        if (! Route::has($routeName)) {
            $this->markTestSkipped("Route {$routeName} bestaat niet.");
        }

        $tenant = Company::query()->create(['name' => 'Audit Taxi', 'is_active' => true]);

        // Zonder tenant, met tenant gekozen, en met een super-admin die zelf een company_id heeft.
        $scenarios = [
            'alle tenants' => [$this->superAdmin(), []],
            'tenant gekozen' => [$this->superAdmin(), ['selected_tenant' => $tenant->id]],
            'eigen company_id' => [$this->superAdmin($tenant), ['selected_tenant' => $tenant->id]],
        ];

        foreach ($scenarios as $label => [$user, $session]) {
            $response = $this->actingAs($user)->withSession($session)->get(route($routeName));

            $this->assertNotSame(403, $response->status(), "{$routeName} geeft 403 ({$label})");
            $this->assertLessThan(500, $response->status(), "{$routeName} geeft serverfout ({$label})");
            if ($response->isRedirect()) {
                $this->assertStringNotContainsString('login', (string) $response->headers->get('Location'), "{$routeName} stuurt naar login ({$label})");
                $response->assertSessionMissing('error');
            }
        }
    }
}
