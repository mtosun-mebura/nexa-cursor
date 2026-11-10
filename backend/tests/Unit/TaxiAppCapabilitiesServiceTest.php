<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use App\Modules\NexaTaxi\Services\TaxiAppCapabilitiesService;
use App\Services\MarketplaceCompanyRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TaxiAppCapabilitiesServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function marketplace_driver_gets_marketplace_mode_and_driver_screen(): void
    {
        Role::firstOrCreate(['name' => 'marketplace', 'guard_name' => 'web']);
        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);
        $company = Company::query()->create([
            'name' => 'Market Taxi',
            'is_active' => true,
            'package_key' => MarketplaceCompanyRegistrationService::PACKAGE_KEY,
        ]);
        $company->modules()->attach($taxi->id);

        $user = User::factory()->create(['company_id' => $company->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('marketplace');

        $caps = app(TaxiAppCapabilitiesService::class)->forUser($user);

        $this->assertTrue($caps['modes']['chauffeur']);
        $this->assertTrue($caps['modes']['marketplace']);
        $this->assertSame('driver', $caps['default_screen']);
        $this->assertNotEmpty($caps['screens']);
        $this->assertSame('driver', $caps['screens'][0]['key']);
    }
}
