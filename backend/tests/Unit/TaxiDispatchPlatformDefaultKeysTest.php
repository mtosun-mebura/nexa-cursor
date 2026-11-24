<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiDispatchPlatformDefaultKeysTest extends TestCase
{
    #[Test]
    public function marketplace_and_booking_discount_keys_allow_null_company_id(): void
    {
        $this->assertTrue(GeneralSetting::isTaxiDispatchPlatformDefaultKey(
            TaxiDispatchSettingsService::KEY_MARKETPLACE_MAP_RADIUS_KM
        ));
        $this->assertTrue(GeneralSetting::isTaxiDispatchPlatformDefaultKey(
            TaxiDispatchSettingsService::KEY_BOOKING_DISCOUNT_PERCENT
        ));
        $this->assertTrue(GeneralSetting::allowsNullCompanyId(
            TaxiDispatchSettingsService::KEY_MARKETPLACE_MAP_RADIUS_KM
        ));
        $this->assertTrue(GeneralSetting::allowsNullCompanyId(
            TaxiDispatchSettingsService::KEY_BOOKING_DISCOUNT_PERCENT
        ));
    }

    #[Test]
    public function set_marketplace_map_radius_without_tenant_stores_platform_default(): void
    {
        $settings = app(TaxiDispatchSettingsService::class);

        $settings->setMarketplaceMapRadiusKm(42, null);
        $settings->setBookingDiscountPercent(15, null);

        $this->assertSame(42, $settings->marketplaceMapRadiusKm(null));
        $this->assertSame(15, $settings->bookingDiscountPercent(null));

        $this->assertDatabaseHas('general_settings', [
            'key' => TaxiDispatchSettingsService::KEY_MARKETPLACE_MAP_RADIUS_KM,
            'company_id' => null,
            'value' => '42',
        ]);
        $this->assertDatabaseHas('general_settings', [
            'key' => TaxiDispatchSettingsService::KEY_BOOKING_DISCOUNT_PERCENT,
            'company_id' => null,
            'value' => '15',
        ]);
    }

    #[Test]
    public function tenant_can_override_marketplace_map_radius_platform_default(): void
    {
        $company = Company::query()->create([
            'name' => 'Dispatch Radius Co',
            'slug' => 'dispatch-radius-'.uniqid(),
            'is_active' => true,
        ]);
        $settings = app(TaxiDispatchSettingsService::class);

        $settings->setMarketplaceMapRadiusKm(40, null);
        $settings->setMarketplaceMapRadiusKm(12, (int) $company->id);

        $this->assertSame(12, $settings->marketplaceMapRadiusKm((int) $company->id));
        $this->assertSame(40, $settings->marketplaceMapRadiusKm(null));
    }
}
