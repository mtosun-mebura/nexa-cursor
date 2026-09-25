<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\NexaBranding;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminChromeBrandingTest extends TestCase
{
    #[Test]
    public function admin_chrome_uses_nexa_suite_logo_and_favicon_mark_even_with_tenant_selected(): void
    {
        Permission::firstOrCreate(['name' => 'view-companies', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $company = Company::query()->create([
            'name' => 'Logo Tenant',
            'is_active' => true,
            'logo_blob' => base64_encode('light-bytes'),
            'logo_mime_type' => 'image/png',
            'logo_dark_blob' => base64_encode('dark-bytes'),
            'logo_dark_mime_type' => 'image/png',
        ]);

        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $html = $this->actingAs($super)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.companies.index'))
            ->assertOk()
            ->assertSee('admin-mobile-menu-toggle', false)
            ->assertSee('admin-mobile-header-lockup', false)
            ->assertSee('admin-mobile-header-brand', false)
            ->assertSee('images/nexa-x-logo.png', false)
            ->assertSee('object-contain bg-transparent', false)
            ->assertDontSee('object-contain bg-black', false)
            ->assertSee('default-logo', false)
            ->assertSee('small-logo', false)
            ->getContent();

        $this->assertStringContainsString('nexa-brand-lockup', $html);
        $this->assertStringContainsString(NexaBranding::defaultLogoUrl(), $html);
        $this->assertStringContainsString(NexaBranding::defaultLogoDarkUrl(), $html);
        $this->assertStringContainsString('alt="NEXA Suite"', $html);
        $this->assertStringContainsString('admin-mobile-menu-icon', $html);
        $this->assertStringNotContainsString('kt-btn-icon kt-btn-ghost admin-mobile-menu-toggle', $html);
        $this->assertStringNotContainsString('admin-mobile-header-mark', $html);
        $this->assertStringNotContainsString(route('admin.companies.logo', $company), $html);
        $this->assertStringNotContainsString(route('admin.companies.logo.dark', $company), $html);

        preg_match_all('/<img\b[^>]*class="[^"]*logo-dark[^"]*"[^>]*>/i', $html, $darkLogos);
        $this->assertNotEmpty($darkLogos[0]);
        foreach ($darkLogos[0] as $tag) {
            $this->assertStringContainsString('hidden', $tag);
        }
    }

    #[Test]
    public function admin_responsive_css_keeps_open_select_menus_under_their_trigger(): void
    {
        $css = file_get_contents(resource_path('css/admin-responsive.css'));
        $this->assertIsString($css);
        $this->assertStringContainsString('.kt-select-wrapper .kt-select-dropdown.open', $css);
        $this->assertStringContainsString('top: calc(100% + 4px)', $css);
        // Dropdown groeit mee met optietekst (niet geknipt op smalle triggerbreedte).
        $this->assertStringContainsString('width: max-content !important', $css);
        $this->assertStringContainsString('white-space: nowrap !important', $css);
        $this->assertStringContainsString('admin-mobile-menu-icon', $css);
        $this->assertStringContainsString('pointer-events: none', $css);
        $this->assertStringContainsString('#sidebar#sidebar:not(.open)', $css);
        $this->assertStringContainsString('translate: -100% 0 !important', $css);
        $this->assertStringContainsString('body:not(:has(#sidebar.open)) .kt-drawer-backdrop', $css);
        $this->assertStringContainsString('.kt-select-dropdown:not(.open):not(.show)', $css);
        $this->assertStringNotContainsString('[data-kt-select-wrapper]:has(.open)', $css);
        $this->assertStringContainsString('width: 2.5rem', $css);
        $this->assertStringNotContainsString('isolation: isolate', $css);
        $this->assertStringContainsString('.admin-mobile-header-lockup', $css);
        $this->assertStringContainsString('grid-template-columns: repeat(3, minmax(0, 1fr))', $css);
        $this->assertStringContainsString('padding-block: 0.5rem !important', $css);
        $this->assertStringContainsString('#content .admin-company-profile-layout', $css);
        $this->assertStringContainsString('minmax(22rem, 5fr) minmax(0, 7fr)', $css);

        $js = file_get_contents(resource_path('js/admin-responsive.js'));
        $this->assertIsString($js);
        $this->assertStringContainsString('function syncSelectDropdownOpenState', $js);
        $this->assertStringContainsString('function keepFilterPanelOpen', $js);
        $this->assertStringContainsString('function submitAdminFilterForm', $js);
        $this->assertStringContainsString('function closeAdminMobileNavDrawer', $js);
        $this->assertStringContainsString("dataset.adminLiveFilter = 'ajax'", $js);
        $this->assertStringContainsString("method: 'GET'", $js);
        $this->assertStringContainsString('data-admin-datatable-filter', $js);
        $this->assertStringNotContainsString('keepPinned', $js);
        $this->assertStringNotContainsString('queueSelectDropdownSync', $js);
        $this->assertStringNotContainsString("observer.observe(document.body", $js);
        $this->assertStringContainsString('skipViewAction', $js);
        $this->assertStringContainsString('isPrimaryViewActionLabel', $js);
        $this->assertStringContainsString('admin-list-card__avatar', $js);
        $this->assertStringContainsString('admin-list-card__subtitle', $js);
        $this->assertStringContainsString('.admin-list-card__avatar', $css);
        $this->assertStringContainsString('buildIconButtonFromMenuLink', $js);
        $this->assertStringContainsString('isKeyValueDetailTable', $js);
        $this->assertStringContainsString('admin-kv-table', $js);
        $this->assertStringContainsString('.kt-table.admin-kv-table.kt-table-border-dashed:not(.admin-keep-table-layout) td:first-child', $css);
        $this->assertStringContainsString('.admin-list-card__field--value-only', $css);
        $this->assertDoesNotMatchRegularExpression(
            '/function isListContextTable\([^)]*\) \{[\s\S]*?if \(!table\.querySelector\(\'thead\'\)\)/',
            $js
        );
        $this->assertStringContainsString('admin-card-action-form--danger', $js);
        $this->assertStringContainsString('ki-cross-circle', $js);
        $this->assertStringNotContainsString('Veld ${index + 1}', $js);
        $this->assertDoesNotMatchRegularExpression(
            '/admin-card-action-form--labeled:has\(\.text-danger\)[\s\S]{0,80}grid-column:\s*1\s*\/\s*-1/',
            $css
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\.admin-list-card__action-buttons\s*>\s*a\.text-danger[\s\S]{0,80}grid-column:\s*1\s*\/\s*-1/',
            $css
        );
        $this->assertStringContainsString('iconActionBtnClass', $js);
        $this->assertStringContainsString('fillIconActionButton', $js);
        $this->assertStringContainsString('admin-list-card__action-caption', $js);
        $this->assertStringContainsString('.admin-list-card__action-caption', $css);
        $this->assertStringContainsString('configureren: \'ki-setting-2\'', $js);
        $this->assertStringContainsString("'migraties opnieuw': 'ki-tablet'", $js);
        $this->assertStringContainsString("'database dummydata': 'ki-cube-2'", $js);
        $this->assertStringContainsString('getMenuActionIconNode', $js);
        $this->assertStringContainsString('grid-template-columns: repeat(3, minmax(0, 1fr))', $css);
        $this->assertStringNotContainsString('form.requestSubmit(submitBtn)', $js);
    }

    #[Test]
    public function mobile_hamburger_has_no_button_chrome_and_icon_ignores_pointer_events(): void
    {
        Permission::firstOrCreate(['name' => 'view-companies', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $html = $this->actingAs($super)
            ->get(route('admin.companies.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/class="admin-mobile-menu-toggle[^"]*"[\s\S]*?data-kt-drawer-toggle="#sidebar"/',
            $html
        );
        $this->assertStringContainsString('class="admin-mobile-menu-icon" viewBox="0 0 24 24" width="24"', $html);
        $this->assertStringNotContainsString('kt-btn-icon kt-btn-ghost admin-mobile-menu-toggle', $html);
        $this->assertMatchesRegularExpression(
            '/id="sidebar"[^>]*\bhidden lg:flex\b|\bhidden lg:flex\b[^>]*id="sidebar"/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/id="sidebar"[^>]*\bz-20 flex flex-col\b|\bz-20 flex flex-col\b[^>]*id="sidebar"/',
            $html
        );
        $this->assertStringContainsString('@media (max-width: 1023px)', $html);
        $this->assertStringContainsString('#sidebar:not(.open)', $html);
        $this->assertStringContainsString('translate: -100% 0 !important', $html);
    }
}
