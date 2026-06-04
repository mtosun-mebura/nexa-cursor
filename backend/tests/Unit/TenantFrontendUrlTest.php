<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\CompanyDomain;
use App\Support\Tenancy\TenantFrontendUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantFrontendUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.url' => 'http://localhost:8085',
            'tenancy.dev_effective_host_query_param' => '_tenant_host',
            'tenancy.central_domains' => ['localhost'],
        ]);
    }

    public function test_appends_tenant_host_on_localhost_when_not_on_tenant_domain(): void
    {
        $company = Company::query()->create(['name' => 'Taxi BV', 'is_active' => true]);
        CompanyDomain::query()->create([
            'company_id' => $company->id,
            'host' => 'taxiroyaal.nexasuite.nl',
            'is_primary' => true,
        ]);

        app()->instance('resolved_tenant_id', $company->id);

        $request = Request::create('http://localhost:8085/mijn-taxi', 'GET');
        $this->app->instance('request', $request);

        $url = TenantFrontendUrl::for(route('home'), $company->id, $request);

        $this->assertStringContainsString('_tenant_host=taxiroyaal.nexasuite.nl', $url);
        $this->assertStringStartsWith('http://localhost:8085', $url);
    }

    public function test_keeps_url_unchanged_when_already_on_tenant_host(): void
    {
        $company = Company::query()->create(['name' => 'Taxi BV', 'is_active' => true]);
        CompanyDomain::query()->create([
            'company_id' => $company->id,
            'host' => 'taxiroyaal.nexasuite.nl',
            'is_primary' => true,
        ]);

        $request = Request::create('https://taxiroyaal.nexasuite.nl/', 'GET');
        $this->app->instance('request', $request);

        $home = route('home');
        $url = TenantFrontendUrl::for($home, $company->id, $request);

        $this->assertSame($home, $url);
        $this->assertStringNotContainsString('_tenant_host', $url);
    }
}
