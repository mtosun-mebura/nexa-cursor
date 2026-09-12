<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxiDriverEligibilityServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'contractant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'contractouder', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function contract_ride_filter_is_only_for_users_with_both_chauffeur_and_contract_roles(): void
    {
        $company = Company::query()->create(['name' => 'Filter Co', 'is_active' => true]);
        $eligibility = app(TaxiDriverEligibilityService::class);

        $chauffeurOnly = User::factory()->create(['company_id' => $company->id]);
        $chauffeurOnly->assignRole('chauffeur');
        $this->assertFalse($eligibility->canUseContractRideFilter($chauffeurOnly));

        $contractantOnly = User::factory()->create(['company_id' => $company->id]);
        $contractantOnly->assignRole('contractant');
        $this->assertFalse($eligibility->canUseContractRideFilter($contractantOnly));

        $both = User::factory()->create(['company_id' => $company->id]);
        $both->assignRole(['chauffeur', 'contractant']);
        $this->assertTrue($eligibility->canUseContractRideFilter($both));

        $ouderBoth = User::factory()->create(['company_id' => $company->id]);
        $ouderBoth->assignRole(['chauffeur', 'contractouder']);
        $this->assertTrue($eligibility->canUseContractRideFilter($ouderBoth));
    }
}
