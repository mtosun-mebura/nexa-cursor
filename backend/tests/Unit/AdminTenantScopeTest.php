<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\Admin\AdminTenantScope;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTenantScopeTest extends TestCase
{
    protected function superAdminWithoutTenant(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');
        session()->forget('selected_tenant');

        return $user;
    }

    protected function bindRoute(string $uri, string $name): void
    {
        $request = Request::create($uri, 'GET');
        $request->setRouteResolver(function () use ($uri, $name) {
            return (new Route('GET', $uri, function () {
                return null;
            }))->name($name);
        });
        $this->app->instance('request', $request);
    }

    public function test_super_admin_without_tenant_can_manage_central_website_pages(): void
    {
        $user = $this->superAdminWithoutTenant();
        $this->actingAs($user);
        $this->bindRoute('/admin/website-pages', 'admin.website-pages.index');

        $scope = app(AdminTenantScope::class);

        $this->assertTrue($scope->isSuperAdminWithoutTenant());
        $this->assertFalse($scope->isTenantScopedActive());
        $this->assertFalse($scope->routeRequiresTenant());
        $this->assertFalse($scope->shouldShowTenantNotice());
        $this->assertFalse($scope->shouldHideContent());
    }

    public function test_exempt_dashboard_does_not_require_tenant(): void
    {
        $user = $this->superAdminWithoutTenant();
        $this->actingAs($user);
        $this->bindRoute('/admin', 'admin.dashboard');

        $scope = app(AdminTenantScope::class);

        $this->assertFalse($scope->routeRequiresTenant());
        $this->assertFalse($scope->shouldShowTenantNotice());
        $this->assertFalse($scope->shouldHideContent());
    }

    public function test_settings_index_shows_notice_but_keeps_content(): void
    {
        $user = $this->superAdminWithoutTenant();
        $this->actingAs($user);
        $this->bindRoute('/admin/settings', 'admin.settings.index');

        $scope = app(AdminTenantScope::class);

        $this->assertTrue($scope->shouldShowTenantNotice());
        $this->assertFalse($scope->shouldHideContent());
        $this->assertSame('settings', $scope->noticeVariant());
    }

    public function test_upgrade_is_platform_wide_without_tenant(): void
    {
        $user = $this->superAdminWithoutTenant();
        $this->actingAs($user);
        $this->bindRoute('/admin/settings/upgrade', 'admin.settings.upgrade.index');

        $scope = app(AdminTenantScope::class);

        $this->assertFalse($scope->routeRequiresTenant());
        $this->assertFalse($scope->shouldShowTenantNotice());
        $this->assertFalse($scope->shouldHideContent());
    }

    public function test_nexa_release_version_is_platform_key(): void
    {
        $this->assertTrue(\App\Models\GeneralSetting::isGlobalPlatformKey('nexa_release_version'));
    }

    public function test_super_admin_without_tenant_can_manage_frontend_themes(): void
    {
        $user = $this->superAdminWithoutTenant();
        $this->actingAs($user);
        $this->bindRoute('/admin/frontend-themes', 'admin.frontend-themes.index');

        $scope = app(AdminTenantScope::class);

        $this->assertFalse($scope->routeRequiresTenant());
        $this->assertFalse($scope->shouldShowTenantNotice());
        $this->assertFalse($scope->shouldHideContent());
    }

    public function test_super_admin_without_tenant_can_view_frontend_components(): void
    {
        $user = $this->superAdminWithoutTenant();
        $this->actingAs($user);
        $this->bindRoute('/admin/frontend-components', 'admin.frontend-components.index');

        $scope = app(AdminTenantScope::class);

        $this->assertFalse($scope->routeRequiresTenant());
        $this->assertFalse($scope->shouldShowTenantNotice());
        $this->assertFalse($scope->shouldHideContent());
    }

    public function test_super_admin_without_tenant_can_manage_newsletters(): void
    {
        $user = $this->superAdminWithoutTenant();
        $this->actingAs($user);
        $this->bindRoute('/admin/newsletters', 'admin.newsletters.index');

        $scope = app(AdminTenantScope::class);

        $this->assertFalse($scope->routeRequiresTenant());
        $this->assertFalse($scope->shouldShowTenantNotice());
        $this->assertFalse($scope->shouldHideContent());
    }

    public function test_super_admin_without_tenant_can_manage_email_templates(): void
    {
        $user = $this->superAdminWithoutTenant();
        $this->actingAs($user);
        $this->bindRoute('/admin/email-templates', 'admin.email-templates.index');

        $scope = app(AdminTenantScope::class);

        $this->assertFalse($scope->routeRequiresTenant());
        $this->assertFalse($scope->shouldShowTenantNotice());
        $this->assertFalse($scope->shouldHideContent());
    }

    public function test_super_admin_without_tenant_can_manage_saas_platform_billing(): void
    {
        $user = $this->superAdminWithoutTenant();
        $this->actingAs($user);
        $this->bindRoute('/admin/platform-billing/invoices', 'admin.platform-billing.invoices.index');

        $scope = app(AdminTenantScope::class);

        $this->assertFalse($scope->routeRequiresTenant());
        $this->assertFalse($scope->shouldShowTenantNotice());
        $this->assertFalse($scope->shouldHideContent());
    }

    public function test_optional_filter_prefers_query_over_sidebar_tenant(): void
    {
        $user = $this->superAdminWithoutTenant();
        $this->actingAs($user);
        session(['selected_tenant' => 9]);

        $request = Request::create('/admin/platform-billing/invoices', 'GET', ['company_id' => '4']);
        $this->assertSame(4, app(AdminTenantScope::class)->optionalFilterTenantId($request));

        $cleared = Request::create('/admin/platform-billing/invoices', 'GET', ['company_id' => '']);
        $this->assertNull(app(AdminTenantScope::class)->optionalFilterTenantId($cleared));

        $fromSidebar = Request::create('/admin/platform-billing/invoices', 'GET');
        $this->assertSame(9, app(AdminTenantScope::class)->optionalFilterTenantId($fromSidebar));
    }
}
