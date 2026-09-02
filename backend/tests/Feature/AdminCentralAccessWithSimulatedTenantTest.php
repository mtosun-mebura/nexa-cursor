<?php

namespace Tests\Feature;

use App\Http\Middleware\ApplyDevSimulatedTenantHost;
use App\Models\Company;
use App\Models\CompanyDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCentralAccessWithSimulatedTenantTest extends TestCase
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

        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'demo', 'guard_name' => 'web']);
    }

    #[Test]
    public function company_admin_can_open_central_admin_with_simulated_tenant_session(): void
    {
        $other = Company::query()->create(['name' => 'Andere Taxi', 'is_active' => true]);
        CompanyDomain::query()->create([
            'company_id' => $other->id,
            'host' => 'taxiroyaal.nexasuite.nl',
            'is_primary' => true,
        ]);

        $own = Company::query()->create(['name' => 'Demo Taxi', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $own->id]);
        $user->assignRole('company-admin');

        $this->actingAs($user)
            ->withSession([
                ApplyDevSimulatedTenantHost::SESSION_DEV_EFFECTIVE_HOST => 'taxiroyaal.nexasuite.nl',
            ])
            ->get('http://localhost:8085/admin')
            ->assertOk();
    }

    #[Test]
    public function company_admin_is_blocked_on_another_tenants_real_host(): void
    {
        $other = Company::query()->create(['name' => 'Andere Taxi', 'is_active' => true]);
        CompanyDomain::query()->create([
            'company_id' => $other->id,
            'host' => 'taxiroyaal.nexasuite.nl',
            'is_primary' => true,
        ]);

        $own = Company::query()->create(['name' => 'Demo Taxi', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $own->id]);
        $user->assignRole('company-admin');

        $this->actingAs($user)
            ->get('http://taxiroyaal.nexasuite.nl/admin')
            ->assertForbidden();
    }

    #[Test]
    public function company_admin_can_open_central_home_with_simulated_tenant_session(): void
    {
        $other = Company::query()->create(['name' => 'Andere Taxi', 'is_active' => true]);
        CompanyDomain::query()->create([
            'company_id' => $other->id,
            'host' => 'taxiroyaal.nexasuite.nl',
            'is_primary' => true,
        ]);

        $own = Company::query()->create(['name' => 'Demo Taxi', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $own->id]);
        $user->assignRole('company-admin');

        $this->actingAs($user)
            ->withSession([
                ApplyDevSimulatedTenantHost::SESSION_DEV_EFFECTIVE_HOST => 'taxiroyaal.nexasuite.nl',
            ])
            ->get('http://localhost:8085/')
            ->assertOk();
    }

    #[Test]
    public function empty_tenant_host_query_clears_simulated_tenant_session(): void
    {
        $other = Company::query()->create(['name' => 'Andere Taxi', 'is_active' => true]);
        CompanyDomain::query()->create([
            'company_id' => $other->id,
            'host' => 'taxiroyaal.nexasuite.nl',
            'is_primary' => true,
        ]);

        $this->withSession([
            ApplyDevSimulatedTenantHost::SESSION_DEV_EFFECTIVE_HOST => 'taxiroyaal.nexasuite.nl',
        ])
            ->get('http://localhost:8085/?nexa_admin_preview=1&_tenant_host=')
            ->assertOk()
            ->assertSessionMissing(ApplyDevSimulatedTenantHost::SESSION_DEV_EFFECTIVE_HOST);
    }
}
