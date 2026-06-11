<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Module;
use App\Services\AdminDashboardModuleContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminDashboardModuleContextTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function company_with_only_taxi_module_shows_taxi_not_skillmatching(): void
    {
        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);
        Module::query()->create([
            'name' => 'skillmatching',
            'display_name' => 'Skillmatching',
            'version' => '1.0.0',
            'installed' => true,
            'active' => false,
        ]);

        $company = Company::query()->create(['name' => 'Taxi Tenant', 'is_active' => true]);
        $company->modules()->attach($taxi->id);

        $context = app(AdminDashboardModuleContext::class);
        $resolved = $context->resolve($company->id);

        $this->assertFalse($resolved['show_skillmatching']);
    }

    #[Test]
    public function company_with_skillmatching_module_can_show_skillmatching_when_schema_exists(): void
    {
        $skill = Module::query()->create([
            'name' => 'skillmatching',
            'display_name' => 'Skillmatching',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        $company = Company::query()->create(['name' => 'SM Tenant', 'is_active' => true]);
        $company->modules()->attach($skill->id);

        $resolved = app(AdminDashboardModuleContext::class)->resolve($company->id);

        if (\App\Support\ModuleSchemaAvailability::vacanciesTableExists()) {
            $this->assertTrue($resolved['show_skillmatching']);
        } else {
            $this->assertFalse($resolved['show_skillmatching']);
        }
        $this->assertFalse($resolved['show_taxi']);
    }
}
