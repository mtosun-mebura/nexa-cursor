<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VacanciesAdminGatingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view-companies', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view-vacancies', 'guard_name' => 'web']);
    }

    #[Test]
    public function legacy_vacancies_url_redirects_to_dashboard_when_skillmatching_inactive(): void
    {
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $this->actingAs($super, 'web')
            ->get('/admin/vacancies')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('warning');
    }

    #[Test]
    public function skillmatching_vacancies_url_redirects_to_dashboard_when_module_inactive(): void
    {
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $this->actingAs($super, 'web')
            ->get('/admin/skillmatching/vacancies')
            ->assertRedirect(route('admin.dashboard'));
    }

    #[Test]
    public function dashboard_has_no_vacancies_entry_points_when_skillmatching_is_off(): void
    {
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $html = $this->actingAs($super, 'web')
            ->get(route('admin.companies.index'))
            ->assertOk()
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/href="[^"]*\/admin\/vacancies(\/|"|\?)/', $html);
        $this->assertDoesNotMatchRegularExpression('/href="[^"]*\/admin\/skillmatching\/vacancies/', $html);
    }

    #[Test]
    public function taxi_tenant_cannot_open_vacancies_even_if_skillmatching_is_globally_active(): void
    {
        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
        ]);
        Module::query()->create([
            'name' => 'skillmatching',
            'display_name' => 'Nexa Skillmatching',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-briefcase',
            'installed' => true,
            'active' => true,
        ]);

        $company = Company::query()->create(['name' => 'Taxi Only', 'is_active' => true]);
        $company->modules()->attach($taxi->id);

        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $this->actingAs($super, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->get('/admin/vacancies')
            ->assertRedirect(route('admin.dashboard'));
    }

    #[Test]
    public function standalone_404_renders_a_single_default_nexa_logo(): void
    {
        $html = $this->get('/this-page-does-not-exist-nexa-logo-check')
            ->assertNotFound()
            ->getContent();

        $this->assertStringContainsString('page_404', $html);
        $this->assertStringContainsString('nexa-logo.png', $html);
        $this->assertStringNotContainsString('nexa-logo-dark.png', $html);

        preg_match_all('/<img\b[^>]*>/i', $html, $matches);
        $logoImgs = array_values(array_filter(
            $matches[0],
            fn (string $tag): bool => str_contains($tag, 'nexa-logo')
        ));
        $this->assertCount(1, $logoImgs);
    }

    #[Test]
    public function vacancies_when_skillmatching_off_does_not_render_duplicate_logo_404(): void
    {
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $response = $this->actingAs($super, 'web')->get('/admin/vacancies');
        $response->assertRedirect(route('admin.dashboard'));
        $this->assertStringNotContainsString('page_404', $response->getContent());
        $this->assertStringNotContainsString('nexa-logo-dark.png', $response->getContent());
    }
}
