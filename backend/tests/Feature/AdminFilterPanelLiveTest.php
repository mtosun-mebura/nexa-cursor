<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminFilterPanelLiveTest extends TestCase
{
    #[Test]
    public function admin_responsive_js_filters_in_place_and_keeps_panel_open(): void
    {
        $js = file_get_contents(resource_path('js/admin-responsive.js'));
        $this->assertIsString($js);
        $this->assertStringContainsString('function bindAdminFilterPanelLiveSubmit', $js);
        $this->assertStringContainsString('function submitAdminFilterForm', $js);
        $this->assertStringContainsString('function keepFilterPanelOpen', $js);
        $this->assertStringContainsString("dataset.adminLiveFilter = 'ajax'", $js);
        $this->assertStringContainsString('fetch(url', $js);
        $this->assertStringContainsString("method: 'GET'", $js);
        $this->assertStringContainsString("id === 'filters-form'", $js);
        $this->assertStringContainsString('data-admin-datatable-filter', $js);
        $this->assertStringContainsString('admin-filter-panel-open:', $js);
        $this->assertStringNotContainsString('form.requestSubmit()', $js);
        $this->assertStringNotContainsString('keepPinned', $js);
        $this->assertStringNotContainsString('queueSelectDropdownSync', $js);
        $this->assertStringNotContainsString('observer.observe(document.body', $js);
    }

    #[Test]
    public function companies_index_keeps_filter_form_and_filters_by_status(): void
    {
        Permission::firstOrCreate(['name' => 'view-companies', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $active = Company::query()->create(['name' => 'Livefilter Actief BV', 'is_active' => true]);
        $inactive = Company::query()->create(['name' => 'Livefilter Inactief BV', 'is_active' => false]);

        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.companies.index'))
            ->assertOk()
            ->assertSee('id="filters-form"', false)
            ->assertSee('id="status-filter"', false)
            ->assertSee('id="search-form"', false)
            ->assertSee('data-admin-datatable="true"', false)
            ->assertSee($active->name, false)
            ->assertSee($inactive->name, false);

        $filtered = $this->actingAs($admin)
            ->get(route('admin.companies.index', ['status' => 'active']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="filters-form"', $filtered);
        $this->assertMatchesRegularExpression('/name="status"[\s\S]*value="active"[^>]*\bselected\b/', $filtered);
        $this->assertMatchesRegularExpression('/<tbody>[\s\S]*'.preg_quote($active->name, '/').'[\s\S]*<\/tbody>/', $filtered);

        preg_match('/id="companies_table"[\s\S]*<tbody>([\s\S]*)<\/tbody>/', $filtered, $tbody);
        $this->assertNotEmpty($tbody[1] ?? null);
        $this->assertStringNotContainsString($inactive->name, $tbody[1]);
    }

    #[Test]
    public function users_email_templates_and_notifications_share_the_filters_form_hook(): void
    {
        foreach (['users', 'email-templates', 'notifications'] as $page) {
            $path = resource_path("views/admin/{$page}/index.blade.php");
            $this->assertFileExists($path, $path);
            $contents = file_get_contents($path);
            $this->assertStringContainsString('id="filters-form"', $contents);
            $this->assertStringContainsString('id="status-filter"', $contents);
            $this->assertStringContainsString('filterForm.submit()', $contents);
        }
    }
}
