<?php

namespace Tests\Feature;

use App\Http\Middleware\ApplyDevSimulatedTenantHost;
use App\Models\Company;
use App\Models\CompanyDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketingPreviewTest extends TestCase
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
    public function marketing_hub_is_available_on_localhost(): void
    {
        $this->get('http://localhost:8085/marketing')
            ->assertOk()
            ->assertSee('NEXA Suite', false);
    }

    #[Test]
    public function marketing_prijzen_page_is_available(): void
    {
        $this->get('http://localhost:8085/marketing/prijzen')
            ->assertOk()
            ->assertSee('€ 49', false)
            ->assertSee('€ 750', false)
            ->assertSee('Pro — € 99', false);
    }

    #[Test]
    public function marketing_hub_stays_available_with_simulated_tenant_session(): void
    {
        $company = Company::query()->create(['name' => 'Taxi BV', 'is_active' => true]);
        CompanyDomain::query()->create([
            'company_id' => $company->id,
            'host' => 'taxiroyaal.nexasuite.nl',
            'is_primary' => true,
        ]);

        $this->withSession([
            ApplyDevSimulatedTenantHost::SESSION_DEV_EFFECTIVE_HOST => 'taxiroyaal.nexasuite.nl',
        ])
            ->get('http://localhost:8085/marketing')
            ->assertOk()
            ->assertSee('NEXA Suite', false);
    }

    #[Test]
    public function marketing_hub_stays_available_with_tenant_host_query(): void
    {
        $company = Company::query()->create(['name' => 'Taxi BV', 'is_active' => true]);
        CompanyDomain::query()->create([
            'company_id' => $company->id,
            'host' => 'taxiroyaal.nexasuite.nl',
            'is_primary' => true,
        ]);

        $this->get('http://localhost:8085/marketing?_tenant_host=taxiroyaal.nexasuite.nl')
            ->assertOk()
            ->assertSee('NEXA Suite', false);
    }

    #[Test]
    public function marketing_hub_is_hidden_on_real_tenant_host(): void
    {
        $company = Company::query()->create(['name' => 'Taxi BV', 'is_active' => true]);
        CompanyDomain::query()->create([
            'company_id' => $company->id,
            'host' => 'taxiroyaal.nexasuite.nl',
            'is_primary' => true,
        ]);

        $this->call('GET', 'http://taxiroyaal.nexasuite.nl/marketing')
            ->assertNotFound();
    }
}
