<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyEntitlementService;
use App\Services\UserRoleAssignmentService;
use App\Support\TenantPackageCapability;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyEntitlementServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
    }

    public function test_no_package_means_no_extra_limits(): void
    {
        $company = Company::query()->create(['name' => 'Vrij Taxi', 'is_active' => true]);
        $service = app(CompanyEntitlementService::class);

        $this->assertNull($service->maxDrivers($company));
        $this->assertTrue($service->allows($company, TenantPackageCapability::MOLLIE_PAYMENTS));
        $this->assertTrue($service->allows($company, TenantPackageCapability::INVOICE_PDF));
    }

    public function test_start_package_limits_drivers_and_blocks_mollie(): void
    {
        $company = Company::query()->create([
            'name' => 'Start Taxi',
            'is_active' => true,
            'package_key' => 'start',
        ]);
        $service = app(CompanyEntitlementService::class);

        $this->assertSame(3, $service->maxDrivers($company));
        $this->assertFalse($service->allows($company, TenantPackageCapability::MOLLIE_PAYMENTS));
        $this->assertTrue($service->allows($company, TenantPackageCapability::INVOICE_PDF));
        $this->assertFalse($service->allows($company, TenantPackageCapability::DISPATCH));
    }

    public function test_pro_package_has_unlimited_drivers_and_mollie(): void
    {
        $company = Company::query()->create([
            'name' => 'Pro Taxi',
            'is_active' => true,
            'package_key' => 'pro',
        ]);
        $service = app(CompanyEntitlementService::class);

        $this->assertNull($service->maxDrivers($company));
        $this->assertTrue($service->allows($company, TenantPackageCapability::MOLLIE_PAYMENTS));
        $this->assertTrue($service->allows($company, TenantPackageCapability::DRIVER_APP));
    }

    public function test_assert_can_assign_chauffeur_roles_allows_fourth_driver_without_package(): void
    {
        $company = Company::query()->create([
            'name' => 'Onbeperkt Taxi',
            'is_active' => true,
        ]);
        $roles = app(UserRoleAssignmentService::class);
        for ($i = 0; $i < 3; $i++) {
            $driver = User::factory()->create(['company_id' => $company->id]);
            $roles->syncWebRoles($driver, ['chauffeur']);
        }

        app(CompanyEntitlementService::class)->assertCanAssignChauffeurRoles($company, ['chauffeur']);
        $this->assertSame(3, app(CompanyEntitlementService::class)->chauffeurCount($company));
    }

    public function test_assert_can_assign_chauffeur_roles_blocks_fourth_driver_on_start(): void
    {
        $company = Company::query()->create([
            'name' => 'Start Limiet',
            'is_active' => true,
            'package_key' => 'start',
        ]);
        $roles = app(UserRoleAssignmentService::class);
        for ($i = 0; $i < 3; $i++) {
            $driver = User::factory()->create(['company_id' => $company->id]);
            $roles->syncWebRoles($driver, ['chauffeur']);
        }

        $service = app(CompanyEntitlementService::class);
        $this->assertSame(3, $service->chauffeurCount($company));

        try {
            $service->assertCanAssignChauffeurRoles($company, ['chauffeur']);
            $this->fail('Vierde chauffeur had geblokkeerd moeten worden.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('maximaal 3 chauffeurs', $e->errors()['roles'][0] ?? '');
        }
    }

    public function test_business_has_10_contract_clients_and_addons_raise_the_limit(): void
    {
        $company = Company::query()->create([
            'name' => 'Business Klanten',
            'is_active' => true,
            'package_key' => 'business',
        ]);
        $service = app(CompanyEntitlementService::class);

        $this->assertSame(10, $service->maxContractClients($company));
        $this->assertTrue($service->allows($company, TenantPackageCapability::CONTRACT_TRANSPORT));
        $this->assertFalse($service->allows($company, TenantPackageCapability::GPS_TRACKING));

        $company->package_addons = [
            \App\Support\TenantPackageAddon::EXTRA_CLIENTS => 1,
            \App\Support\TenantPackageAddon::GPS_TRACKING => 1,
        ];
        $this->assertSame(20, $service->maxContractClients($company));
        $this->assertTrue($service->allows($company, TenantPackageCapability::GPS_TRACKING));

        $company->package_addons = [
            \App\Support\TenantPackageAddon::FLEET => 1,
        ];
        $this->assertNull($service->maxContractClients($company));
    }

    public function test_pro_has_no_contract_transport(): void
    {
        $company = Company::query()->create([
            'name' => 'Pro Geen Contract',
            'is_active' => true,
            'package_key' => 'pro',
        ]);
        $service = app(CompanyEntitlementService::class);

        $this->assertSame(0, $service->maxContractClients($company));
        $this->assertFalse($service->allows($company, TenantPackageCapability::CONTRACT_TRANSPORT));

        try {
            $service->assertCanCreateContractCustomer($company);
            $this->fail('Pro had geen contractklant mogen aanmaken.');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
    }

    public function test_no_package_means_unlimited_contract_clients(): void
    {
        $company = Company::query()->create(['name' => 'Oude Tenant', 'is_active' => true]);
        $service = app(CompanyEntitlementService::class);

        $this->assertNull($service->maxContractClients($company));
        $this->assertTrue($service->allows($company, TenantPackageCapability::CONTRACT_TRANSPORT));
        $this->assertTrue($service->allows($company, TenantPackageCapability::GPS_TRACKING));
    }

    public function test_package_addons_persist_and_raise_business_client_limit(): void
    {
        $company = Company::query()->create([
            'name' => 'Business Addons',
            'is_active' => true,
            'package_key' => 'business',
            'package_addons' => \App\Support\TenantPackageAddon::normalizeSelections([
                \App\Support\TenantPackageAddon::EXTRA_CLIENTS => 1,
                \App\Support\TenantPackageAddon::GPS_TRACKING => 1,
            ]),
        ]);
        $company->refresh();
        $service = app(CompanyEntitlementService::class);

        $this->assertSame(20, $service->maxContractClients($company));
        $this->assertTrue($service->allows($company, TenantPackageCapability::GPS_TRACKING));
        $this->assertSame(1, $service->addonQuantity($company, \App\Support\TenantPackageAddon::EXTRA_CLIENTS));
    }

    public function test_scheduled_addon_does_not_grant_access_before_start_date(): void
    {
        $company = Company::query()->create([
            'name' => 'Business Later GPS',
            'is_active' => true,
            'package_key' => 'business',
            'package_addons' => [
                \App\Support\TenantPackageAddon::GPS_TRACKING => [
                    'quantity' => 1,
                    'starts_at' => now()->addMonthNoOverflow()->startOfMonth()->toDateString(),
                    'active_quantity' => 0,
                ],
            ],
        ]);
        $service = app(CompanyEntitlementService::class);

        $this->assertFalse($service->allows($company, TenantPackageCapability::GPS_TRACKING));
        $this->assertSame(0, $service->addonQuantity($company, \App\Support\TenantPackageAddon::GPS_TRACKING));
    }

    public function test_start_pro_and_business_capability_matrix(): void
    {
        $start = Company::query()->create(['name' => 'Matrix Start', 'is_active' => true, 'package_key' => 'start']);
        $pro = Company::query()->create(['name' => 'Matrix Pro', 'is_active' => true, 'package_key' => 'pro']);
        $business = Company::query()->create(['name' => 'Matrix Business', 'is_active' => true, 'package_key' => 'business']);
        $service = app(CompanyEntitlementService::class);

        $expected = [
            TenantPackageCapability::WEBSITE_BOOKING => [true, true, true],
            TenantPackageCapability::INVOICE_PDF => [true, true, true],
            TenantPackageCapability::MOLLIE_PAYMENTS => [false, true, true],
            TenantPackageCapability::DISPATCH => [false, true, true],
            TenantPackageCapability::DRIVER_APP => [false, true, true],
            TenantPackageCapability::CONTRACT_TRANSPORT => [false, false, true],
            TenantPackageCapability::CONTRACT_PORTAL => [false, false, true],
            TenantPackageCapability::MULTIPLE_ADMINS => [false, true, true],
            TenantPackageCapability::MONTHLY_INVOICE_SEPA => [false, false, true],
            TenantPackageCapability::GPS_TRACKING => [false, false, false],
        ];

        foreach ($expected as $capability => [$startAllowed, $proAllowed, $businessAllowed]) {
            $this->assertSame($startAllowed, $service->allows($start, $capability), 'start.'.$capability);
            $this->assertSame($proAllowed, $service->allows($pro, $capability), 'pro.'.$capability);
            $this->assertSame($businessAllowed, $service->allows($business, $capability), 'business.'.$capability);
        }

        $this->assertSame(3, $service->maxDrivers($start));
        $this->assertNull($service->maxDrivers($pro));
        $this->assertNull($service->maxDrivers($business));
        $this->assertSame(0, $service->maxContractClients($start));
        $this->assertSame(0, $service->maxContractClients($pro));
        $this->assertSame(10, $service->maxContractClients($business));
        $this->assertSame('Geen (geen contractvervoer)', $service->contractClientLimitLabel($start));
        $this->assertSame('10', $service->contractClientLimitLabel($business));
    }

    public function test_start_blocks_second_company_admin_pro_allows_it(): void
    {
        $start = Company::query()->create(['name' => 'Start Admin Limiet', 'is_active' => true, 'package_key' => 'start']);
        $pro = Company::query()->create(['name' => 'Pro Admin Open', 'is_active' => true, 'package_key' => 'pro']);
        $roles = app(UserRoleAssignmentService::class);

        $startAdmin = User::factory()->create(['company_id' => $start->id]);
        $roles->syncWebRoles($startAdmin, ['company-admin']);
        $proAdmin = User::factory()->create(['company_id' => $pro->id]);
        $roles->syncWebRoles($proAdmin, ['company-admin']);

        $service = app(CompanyEntitlementService::class);
        $this->assertSame(1, $service->companyAdminCount($start));
        $this->assertSame(1, $service->companyAdminCount($pro));

        $service->assertCanAssignCompanyAdminRoles($pro, ['company-admin']);

        try {
            $service->assertCanAssignCompanyAdminRoles($start, ['company-admin']);
            $this->fail('Tweede beheerder op Start had geblokkeerd moeten worden.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('maximaal één beheerder', $e->errors()['roles'][0] ?? '');
        }

        $service->assertCanAssignCompanyAdminRoles($start, ['company-admin'], $startAdmin);
    }

    public function test_denied_messages_name_the_package_and_upgrade_path(): void
    {
        $start = Company::query()->create(['name' => 'Start Copy', 'is_active' => true, 'package_key' => 'start']);
        $service = app(CompanyEntitlementService::class);

        $this->assertStringContainsString('Start-pakket', $service->deniedMessage(TenantPackageCapability::MOLLIE_PAYMENTS, $start));
        $this->assertStringContainsString('Pro of Business', $service->deniedMessage(TenantPackageCapability::MOLLIE_PAYMENTS, $start));
        $this->assertStringContainsString('Upgrade naar Business', $service->deniedMessage(TenantPackageCapability::CONTRACT_TRANSPORT, $start));
        $this->assertStringContainsString('GPS-trackers', $service->deniedMessage(TenantPackageCapability::GPS_TRACKING, $start));
    }

    public function test_trial_unlocks_all_extra_modules_without_saved_addons(): void
    {
        $company = Company::query()->create([
            'name' => 'Trial GPS',
            'is_active' => true,
            'package_key' => 'business',
        ]);
        app(\App\Services\PlatformBilling\TenantSubscriptionService::class)->ensureProfile($company);
        $company->billingProfile->update([
            'trial_started_at' => now()->toDateString(),
            'trial_ends_at' => now()->addMonth()->toDateString(),
            'subscription_start_date' => now()->addMonth()->toDateString(),
        ]);
        $company->unsetRelation('billingProfile');
        $service = app(CompanyEntitlementService::class);

        $this->assertTrue($service->allows($company, TenantPackageCapability::GPS_TRACKING));
        $this->assertTrue($service->hasAddon($company, \App\Support\TenantPackageAddon::GPS_TRACKING));
        $this->assertTrue($service->hasAddon($company, \App\Support\TenantPackageAddon::FLEET));
        $this->assertNull($service->maxContractClients($company));

        $company->billingProfile->update([
            'trial_started_at' => now()->subMonths(2)->toDateString(),
            'trial_ends_at' => now()->subDay()->toDateString(),
            'subscription_start_date' => now()->subDay()->toDateString(),
        ]);
        $company->unsetRelation('billingProfile');

        $this->assertFalse($service->allows($company, TenantPackageCapability::GPS_TRACKING));
        $this->assertFalse($service->hasAddon($company, \App\Support\TenantPackageAddon::GPS_TRACKING));
        $this->assertSame(10, $service->maxContractClients($company));
    }

    public function test_assert_allows_throws_for_start_dispatch_and_sepa(): void
    {
        $start = Company::query()->create(['name' => 'Start Assert', 'is_active' => true, 'package_key' => 'start']);
        $service = app(CompanyEntitlementService::class);

        try {
            $service->assertAllows($start, TenantPackageCapability::DISPATCH);
            $this->fail('Dispatch op Start had geblokkeerd moeten worden.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Dispatch', $e->errors()['package'][0] ?? '');
        }

        try {
            $service->assertAllows($start, TenantPackageCapability::MONTHLY_INVOICE_SEPA);
            $this->fail('SEPA op Start had geblokkeerd moeten worden.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('SEPA', $e->errors()['package'][0] ?? '');
        }

        $service->assertAllows($start, TenantPackageCapability::WEBSITE_BOOKING);
        $service->assertAllows($start, TenantPackageCapability::INVOICE_PDF);
    }
}
