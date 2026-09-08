<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\WebsitePage;
use App\Modules\NexaTaxi\Services\TaxiTosunBookingGpsDemoService;
use App\Support\TenantPackageAddon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiTosunBookingGpsDemoServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function enable_turns_on_gps_addon_and_booking_demo_flags(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Tosun',
            'slug' => 'taxitosun',
            'package_key' => 'pro',
            'package_addons' => [],
        ]);
        $page = WebsitePage::query()->create([
            'company_id' => $company->id,
            'title' => 'Home',
            'slug' => 'home',
            'page_type' => 'home',
            'is_active' => true,
            'home_sections' => [
                'component:taxi.boekingsmodule_v2' => [
                    'title' => 'Boek een rit',
                ],
            ],
        ]);

        $result = app(TaxiTosunBookingGpsDemoService::class)->enable();

        $this->assertSame($company->id, $result['company']->id);
        $this->assertTrue(app(\App\Services\CompanyEntitlementService::class)->hasAddon(
            $result['company'],
            TenantPackageAddon::GPS_TRACKING
        ));
        $this->assertSame(1, $result['pages']);

        $page->refresh();
        $logic = $page->home_sections['component:taxi.boekingsmodule_v2']['logic'] ?? [];
        $this->assertTrue((bool) ($logic['show_live_fleet'] ?? false));
        $this->assertTrue((bool) ($logic['show_live_fleet_demo'] ?? false));
    }
}
