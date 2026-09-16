<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\UserRoleAssignmentService;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardAppLaunchBarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'contractant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'contractouder', 'guard_name' => 'web']);
    }

    #[Test]
    public function company_admin_with_chauffeur_role_sees_chauffeur_app_button(): void
    {
        $user = $this->makeCompanyAdmin(['company-admin', 'chauffeur']);
        $this->actingAs($user, 'web');

        $html = view('admin.dashboard.partials.app-launch-bar')->render();

        $this->assertStringContainsString('dashboard-app-launch-bar', $html);
        $this->assertStringContainsString('Chauffeur-app', $html);
        $this->assertStringContainsString('/taxi/chauffeur', $html);
        $this->assertStringContainsString('dashboard-app-launch-btn--chauffeur', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringNotContainsString('Contract-app', $html);
    }

    #[Test]
    public function company_admin_with_contract_role_sees_contract_app_button(): void
    {
        $user = $this->makeCompanyAdmin(['company-admin', 'contractant']);
        $this->actingAs($user, 'web');

        $html = view('admin.dashboard.partials.app-launch-bar')->render();

        $this->assertStringContainsString('dashboard-app-launch-bar', $html);
        $this->assertStringContainsString('Contract-app', $html);
        $this->assertStringContainsString('/taxi/contract', $html);
        $this->assertStringContainsString('dashboard-app-launch-btn--contract', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringNotContainsString('Chauffeur-app', $html);
    }

    #[Test]
    public function company_admin_with_both_roles_sees_both_buttons(): void
    {
        $user = $this->makeCompanyAdmin(['company-admin', 'chauffeur', 'contractouder']);
        $this->actingAs($user, 'web');

        $html = view('admin.dashboard.partials.app-launch-bar')->render();

        $this->assertStringContainsString('Chauffeur-app', $html);
        $this->assertStringContainsString('Contract-app', $html);
        $this->assertStringContainsString('/taxi/chauffeur', $html);
        $this->assertStringContainsString('/taxi/contract', $html);
    }

    #[Test]
    public function company_admin_without_app_roles_sees_no_bar(): void
    {
        $user = $this->makeCompanyAdmin(['company-admin']);
        $this->actingAs($user, 'web');

        $html = view('admin.dashboard.partials.app-launch-bar')->render();

        $this->assertStringNotContainsString('dashboard-app-launch-bar', $html);
        $this->assertStringNotContainsString('Chauffeur-app', $html);
        $this->assertStringNotContainsString('Contract-app', $html);
    }

    #[Test]
    public function company_profile_uses_explicit_two_column_layout(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Layout BV',
            'is_active' => true,
            'email' => 'layout@example.com',
            'website' => 'https://layout.example.com',
            'phone' => '+31123456789',
        ]);
        $this->actingAs($this->makeCompanyAdmin(['company-admin']), 'web');

        $html = view('admin.dashboard.company-profile', [
            'company' => $company,
            'stats' => [],
            'financials' => [],
            'showSkillmatching' => false,
            'showTaxi' => false,
            'taxiStats' => [],
            'recent_rides' => collect(),
            'subscriptionSnapshot' => null,
        ])->render();

        $this->assertStringContainsString('admin-company-profile-layout', $html);
        $this->assertStringContainsString('admin-company-profile-hq', $html);
        $this->assertStringContainsString('grid-template-columns:minmax(22rem,5fr)minmax(0,7fr)', preg_replace('/\s+/', '', $html));
        $this->assertStringNotContainsString('lg:grid-cols-12', $html);
        $this->assertStringNotContainsString('lg:col-span-5', $html);
        $this->assertStringNotContainsString('lg:col-span-7', $html);
        $this->assertStringContainsString('break-words', $html);
    }

    #[Test]
    public function super_admin_always_sees_both_app_buttons(): void
    {
        $user = $this->makeCompanyAdmin(['super-admin']);
        $this->actingAs($user, 'web');

        $html = view('admin.dashboard.partials.app-launch-bar')->render();

        $this->assertStringContainsString('dashboard-app-launch-bar', $html);
        $this->assertStringContainsString('Chauffeur-app', $html);
        $this->assertStringContainsString('Contract-app', $html);
        $this->assertStringContainsString('/taxi/chauffeur', $html);
        $this->assertStringContainsString('/taxi/contract', $html);
    }

    /**
     * @param  list<string>  $roles
     */
    private function makeCompanyAdmin(array $roles): User
    {
        $company = Company::query()->create(['name' => 'Taxi Dashboard Apps', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, $roles);

        return $user->fresh();
    }
}
