<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportCustomerPortalUser;
use App\Modules\NexaTaxi\Services\TaxiContractPortalAccessService;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Services\UserRoleAssignmentService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxiContractPortalAccessServiceTest extends TestCase
{
    private string $conn;

    protected function setUp(): void
    {
        parent::setUp();

        $this->conn = (string) config('database.default');
        app(TaxiContractvervoerSchemaService::class)->ensureTablesExist($this->conn);

        Role::firstOrCreate(['name' => 'contractouder', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'contractant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
    }

    public function test_contractouder_role_without_portal_link_gets_access(): void
    {
        $company = Company::create(['name' => 'Taxi Test', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['contractouder']);

        TransportCustomer::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'OBS Test',
            'active' => true,
        ]);

        $context = app(TaxiContractPortalAccessService::class)->resolveContext($this->conn, $user->fresh());

        $this->assertNotNull($context);
        $this->assertSame('contractouder', $context['portal_role']);
        $this->assertSame((int) $company->id, $context['company_id']);
        $this->assertTrue(
            TransportCustomerPortalUser::on($this->conn)->where('user_id', $user->id)->where('active', true)->exists()
        );
    }

    public function test_chauffeur_without_portal_role_is_denied(): void
    {
        $company = Company::create(['name' => 'Taxi Test', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['chauffeur']);

        TransportCustomer::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'OBS Test',
            'active' => true,
        ]);

        $context = app(TaxiContractPortalAccessService::class)->resolveContext($this->conn, $user->fresh());

        $this->assertNull($context);
    }

    public function test_active_portal_link_grants_access_without_spatie_role(): void
    {
        $company = Company::create(['name' => 'Taxi Test', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $customer = TransportCustomer::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'OBS Test',
            'active' => true,
        ]);

        TransportCustomerPortalUser::on($this->conn)->create([
            'company_id' => $company->id,
            'transport_customer_id' => $customer->id,
            'user_id' => $user->id,
            'portal_role' => TransportCustomerPortalUser::ROLE_CONTRACTANT,
            'active' => true,
        ]);

        $context = app(TaxiContractPortalAccessService::class)->resolveContext($this->conn, $user);

        $this->assertNotNull($context);
        $this->assertSame('contractant', $context['portal_role']);
    }
}
