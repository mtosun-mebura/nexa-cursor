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

    public function test_super_admin_without_tenant_requires_scope_on_tenant_pages(): void
    {
        $user = $this->superAdminWithoutTenant();
        $this->actingAs($user);
        $this->bindRoute('/admin/website-pages', 'admin.website-pages.index');

        $scope = app(AdminTenantScope::class);

        $this->assertTrue($scope->isSuperAdminWithoutTenant());
        $this->assertFalse($scope->isTenantScopedActive());
        $this->assertTrue($scope->shouldShowTenantNotice());
        $this->assertTrue($scope->shouldHideContent());
        $this->assertSame('website-pages', $scope->noticeVariant());
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
}
