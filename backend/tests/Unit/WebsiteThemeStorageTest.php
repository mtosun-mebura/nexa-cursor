<?php

namespace Tests\Unit;

use App\Http\Middleware\ApplyDevSimulatedTenantHost;
use App\Support\Tenancy\WebsiteThemeStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class WebsiteThemeStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('tenancy.dev_effective_host_query_param', '_tenant_host');
        Config::set('tenancy.central_domains', ['localhost', '127.0.0.1']);
    }

    public function test_storage_key_uses_tenant_host_on_dev_with_query_param(): void
    {
        $request = Request::create('http://localhost:8085/?_tenant_host=taxitosun.nexasuite.nl', 'GET');

        $this->assertSame('website-theme:taxitosun.nexasuite.nl', WebsiteThemeStorage::storageKey($request));
    }

    public function test_storage_key_differs_per_tenant_host(): void
    {
        $taxitosun = Request::create('http://localhost:8085/?_tenant_host=taxitosun.nexasuite.nl', 'GET');
        $taxiroyaal = Request::create('http://localhost:8085/?_tenant_host=taxiroyaal.nexasuite.nl', 'GET');

        $this->assertNotSame(
            WebsiteThemeStorage::storageKey($taxitosun),
            WebsiteThemeStorage::storageKey($taxiroyaal)
        );
    }

    public function test_storage_key_uses_request_host_on_real_tenant_domain(): void
    {
        $request = Request::create('https://taxitosun.nexasuite.nl/', 'GET');

        $this->assertSame('website-theme:taxitosun.nexasuite.nl', WebsiteThemeStorage::storageKey($request));
    }

    public function test_storage_key_uses_session_simulated_host_when_query_missing(): void
    {
        $request = Request::create('http://localhost:8085/', 'GET');
        $request->setLaravelSession($this->app['session.store']);
        $request->session()->put(ApplyDevSimulatedTenantHost::SESSION_DEV_EFFECTIVE_HOST, 'taxiroyaal.nexasuite.nl');

        $this->assertSame('website-theme:taxiroyaal.nexasuite.nl', WebsiteThemeStorage::storageKey($request));
    }
}
