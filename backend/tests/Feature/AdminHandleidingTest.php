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
            ->assertSee('Dashboard');
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
        $this->assertArrayNotHasKey('betalingen', $pages);
        $this->assertArrayNotHasKey('dispatch', $pages);
        $this->assertArrayNotHasKey('chauffeur-app', $pages);
        $this->assertArrayNotHasKey('contractvervoer', $pages);
        $this->assertArrayNotHasKey('contractportaal', $pages);

        $this->actingAs($user)
            ->get(route('admin.handleiding.index'))
            ->assertOk()
            ->assertSee('Aan de slag')
            ->assertDontSee('Dispatch')
            ->assertDontSee('Chauffeur-app')
            ->assertDontSee('Contractvervoer')
            ->assertDontSee('Betalingen via Mollie');

        $this->actingAs($user)
            ->get(route('admin.handleiding.show', 'dispatch'))
            ->assertNotFound();
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
            ->assertSee('Contractvervoer');
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
