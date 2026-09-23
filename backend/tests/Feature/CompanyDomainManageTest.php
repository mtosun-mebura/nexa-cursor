<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyDomain;
use App\Models\User;
use App\Services\UserRoleAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyDomainManageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function wizard_step3_shows_manageable_domain_list(): void
    {
        $company = $this->companyWithDomains();

        $this->actingAs($this->superAdmin())
            ->get(route('admin.companies.wizard.step', [$company, 3]))
            ->assertOk()
            ->assertSee('royaaltaxi.nl', false)
            ->assertSee('taxiroyaal.nexasuite.nl', false)
            ->assertSee('js-domain-primary-switch', false)
            ->assertSee('js-domain-edit-toggle', false)
            ->assertSee('Domein toevoegen', false)
            ->assertDontSee('Bestaande domeinen:', false);
    }

    #[Test]
    public function domain_host_can_be_updated(): void
    {
        $company = $this->companyWithDomains();
        $domain = $company->domains->firstWhere('host', 'royaaltaxi.nl');

        $this->actingAs($this->superAdmin())
            ->putJson(route('admin.companies.domains.update', [$company, $domain]), [
                'host' => 'nieuw-taxi.nl',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Domein bijgewerkt.')
            ->assertJsonPath('has_domains', true);

        $this->assertSame('nieuw-taxi.nl', $domain->fresh()->host);
    }

    #[Test]
    public function primary_can_be_switched_and_cleared_to_another_domain(): void
    {
        $company = $this->companyWithDomains();
        $primary = $company->domains->firstWhere('is_primary', true);
        $other = $company->domains->firstWhere('is_primary', false);
        $this->assertNotNull($primary);
        $this->assertNotNull($other);

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.companies.domains.primary', [$company, $other]))
            ->assertOk();

        $this->assertTrue($other->fresh()->is_primary);
        $this->assertFalse($primary->fresh()->is_primary);

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.companies.domains.primary', [$company, $other]), [
                'clear' => '1',
            ])
            ->assertOk();

        $this->assertFalse($other->fresh()->is_primary);
        $this->assertTrue($primary->fresh()->is_primary);
    }

    #[Test]
    public function deleting_primary_promotes_another_domain(): void
    {
        $company = $this->companyWithDomains();
        $primary = $company->domains->firstWhere('is_primary', true);
        $other = $company->domains->firstWhere('is_primary', false);

        $this->actingAs($this->superAdmin())
            ->deleteJson(route('admin.companies.domains.destroy', [$company, $primary]))
            ->assertOk();

        $this->assertDatabaseMissing('company_domains', ['id' => $primary->id]);
        $this->assertTrue($other->fresh()->is_primary);
    }

    private function companyWithDomains(): Company
    {
        $company = Company::query()->create([
            'name' => 'Domein Test Taxi',
            'email' => 'domein-test@example.com',
            'phone' => '0612345678',
            'street' => 'Teststraat',
            'house_number' => '1',
            'postal_code' => '1234AB',
            'city' => 'Amsterdam',
            'is_active' => true,
        ]);

        CompanyDomain::query()->create([
            'company_id' => $company->id,
            'host' => 'royaaltaxi.nl',
            'is_primary' => true,
        ]);
        CompanyDomain::query()->create([
            'company_id' => $company->id,
            'host' => 'taxiroyaal.nexasuite.nl',
            'is_primary' => false,
        ]);

        return $company->fresh(['domains']);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['company_id' => null]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['super-admin']);

        return $user->fresh();
    }
}
