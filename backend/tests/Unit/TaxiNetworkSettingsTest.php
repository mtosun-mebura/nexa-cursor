<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiNetworkSettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function network_defaults_to_off(): void
    {
        $settings = app(TaxiDispatchSettingsService::class);

        $this->assertFalse($settings->networkEnabled(1));
        $this->assertSame(TaxiDispatchSettingsService::NETWORK_MODE_OFF, $settings->networkMode(1));
        $this->assertSame([], $settings->networkPartnerCompanyIds(1));
    }

    #[Test]
    public function network_partners_can_be_stored_and_read(): void
    {
        $company = Company::query()->create(['name' => 'Network Owner', 'is_active' => true]);
        $settings = app(TaxiDispatchSettingsService::class);
        $settings->setNetworkMode(TaxiDispatchSettingsService::NETWORK_MODE_AUTO, $company->id);
        $settings->setNetworkPartnerCompanyIds('2, 5, 2, 0', $company->id);
        $settings->setNetworkFallbackSeconds(90, $company->id);
        $settings->setNetworkMaxRadiusKm(40, $company->id);

        $this->assertTrue($settings->networkEnabled($company->id));
        $this->assertSame(TaxiDispatchSettingsService::NETWORK_MODE_AUTO, $settings->networkMode($company->id));
        $this->assertSame([2, 5], $settings->networkPartnerCompanyIds($company->id));
        $this->assertSame(90, $settings->networkFallbackSeconds($company->id));
        $this->assertSame(40, $settings->networkMaxRadiusKm($company->id));
    }
}
