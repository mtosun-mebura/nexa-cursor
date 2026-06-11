<?php

namespace Tests\Feature;

use App\Http\Middleware\ApplyDevSimulatedTenantHost;
use App\Models\Company;
use App\Models\CompanyDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FrontendLogoutTest extends TestCase
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

    #[Test]
    public function logout_redirects_to_home_with_tenant_host_on_dev(): void
    {
        $company = Company::query()->create(['name' => 'Taxi BV', 'is_active' => true]);
        CompanyDomain::query()->create([
            'company_id' => $company->id,
            'host' => 'taxiroyaal.nexasuite.nl',
            'is_primary' => true,
        ]);

        $user = User::factory()->create(['company_id' => $company->id]);

        app()->instance('resolved_tenant_id', $company->id);

        $response = $this->withSession([
            ApplyDevSimulatedTenantHost::SESSION_DEV_EFFECTIVE_HOST => 'taxiroyaal.nexasuite.nl',
        ])
            ->actingAs($user)
            ->post('http://localhost:8085/logout');

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('_tenant_host=taxiroyaal.nexasuite.nl', $location);
        $this->assertStringContainsString('localhost:8085', $location);
    }
}
