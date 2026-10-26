<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use Illuminate\Validation\ValidationException;
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

    #[Test]
    public function contract_assignment_requires_the_contractant_role(): void
    {
        $company = Company::query()->create(['name' => 'Assign Co', 'is_active' => true]);
        $eligibility = app(TaxiDriverEligibilityService::class);

        $chauffeur = User::factory()->create(['company_id' => $company->id]);
        $chauffeur->assignRole('chauffeur');
        $contractant = User::factory()->create(['company_id' => $company->id]);
        $contractant->assignRole(['chauffeur', 'contractant']);
        $ouder = User::factory()->create(['company_id' => $company->id]);
        $ouder->assignRole(['chauffeur', 'contractouder']);

        $this->assertFalse($eligibility->hasContractantRole($chauffeur));
        $this->assertFalse($eligibility->hasContractantRole($ouder));
        $this->assertTrue($eligibility->hasContractantRole($contractant));

        $eligibility->assertContractantForContractAssignment((int) $contractant->id);

        try {
            $eligibility->assertContractantForContractAssignment((int) $chauffeur->id);
            $this->fail('Chauffeur zonder contractant-rol hoort geweigerd te worden.');
        } catch (ValidationException $e) {
            $this->assertSame(
                TaxiDriverEligibilityService::CONTRACT_ASSIGNMENT_DENIED,
                $e->errors()['driver_id'][0]
            );
        }
    }
}
