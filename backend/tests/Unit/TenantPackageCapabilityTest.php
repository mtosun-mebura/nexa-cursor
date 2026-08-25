<?php

namespace Tests\Unit;

use App\Support\TenantPackageCapability;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TenantPackageCapabilityTest extends TestCase
{
    public function test_definitions_cover_all_capability_constants(): void
    {
        $keys = array_column(TenantPackageCapability::definitions(), 'key');

        $this->assertContains(TenantPackageCapability::MAX_DRIVERS, $keys);
        $this->assertContains(TenantPackageCapability::WEBSITE_BOOKING, $keys);
        $this->assertContains(TenantPackageCapability::MOLLIE_PAYMENTS, $keys);
        $this->assertContains(TenantPackageCapability::INVOICE_PDF, $keys);
        $this->assertContains(TenantPackageCapability::DISPATCH, $keys);
        $this->assertContains(TenantPackageCapability::DRIVER_APP, $keys);
        $this->assertContains(TenantPackageCapability::CONTRACT_TRANSPORT, $keys);
        $this->assertContains(TenantPackageCapability::CONTRACT_PORTAL, $keys);
        $this->assertContains(TenantPackageCapability::MULTIPLE_ADMINS, $keys);
        $this->assertContains(TenantPackageCapability::MONTHLY_INVOICE_SEPA, $keys);
        $this->assertContains(TenantPackageCapability::MAX_CONTRACT_CLIENTS, $keys);
        $this->assertCount(11, $keys);
    }

    #[DataProvider('packageDefaultProvider')]
    public function test_defaults_for_catalog_packages(string $key, array $expected): void
    {
        $defaults = TenantPackageCapability::defaultsForKey($key);

        foreach ($expected as $capability => $value) {
            $this->assertSame($value, $defaults[$capability], $key.'.'.$capability);
        }
    }

    /**
     * @return array<string, array{0: string, 1: array<string, int|bool>}>
     */
    public static function packageDefaultProvider(): array
    {
        return [
            'start' => ['start', [
                TenantPackageCapability::MAX_DRIVERS => 3,
                TenantPackageCapability::WEBSITE_BOOKING => true,
                TenantPackageCapability::MOLLIE_PAYMENTS => false,
                TenantPackageCapability::INVOICE_PDF => true,
                TenantPackageCapability::DISPATCH => false,
                TenantPackageCapability::DRIVER_APP => false,
                TenantPackageCapability::CONTRACT_TRANSPORT => false,
                TenantPackageCapability::CONTRACT_PORTAL => false,
                TenantPackageCapability::MULTIPLE_ADMINS => false,
                TenantPackageCapability::MONTHLY_INVOICE_SEPA => false,
                TenantPackageCapability::MAX_CONTRACT_CLIENTS => 0,
            ]],
            'pro' => ['pro', [
                TenantPackageCapability::MAX_DRIVERS => 0,
                TenantPackageCapability::MOLLIE_PAYMENTS => true,
                TenantPackageCapability::DISPATCH => true,
                TenantPackageCapability::DRIVER_APP => true,
                TenantPackageCapability::MULTIPLE_ADMINS => true,
                TenantPackageCapability::CONTRACT_TRANSPORT => false,
                TenantPackageCapability::MAX_CONTRACT_CLIENTS => 0,
            ]],
            'business' => ['business', [
                TenantPackageCapability::MAX_DRIVERS => 0,
                TenantPackageCapability::MOLLIE_PAYMENTS => true,
                TenantPackageCapability::CONTRACT_TRANSPORT => true,
                TenantPackageCapability::CONTRACT_PORTAL => true,
                TenantPackageCapability::MONTHLY_INVOICE_SEPA => true,
                TenantPackageCapability::MAX_CONTRACT_CLIENTS => 10,
            ]],
        ];
    }

    public function test_normalize_keeps_unlimited_drivers_when_flag_is_set(): void
    {
        $normalized = TenantPackageCapability::normalize([
            'max_drivers' => 8,
            'max_drivers_unlimited' => '1',
            'mollie_payments' => '0',
        ], 'pro');

        $this->assertSame(0, $normalized[TenantPackageCapability::MAX_DRIVERS]);
        $this->assertFalse($normalized[TenantPackageCapability::MOLLIE_PAYMENTS]);
    }

    public function test_normalize_fills_missing_bools_from_package_defaults(): void
    {
        $normalized = TenantPackageCapability::normalize([], 'start');

        $this->assertFalse($normalized[TenantPackageCapability::DISPATCH]);
        $this->assertTrue($normalized[TenantPackageCapability::WEBSITE_BOOKING]);
        $this->assertSame(3, $normalized[TenantPackageCapability::MAX_DRIVERS]);
    }

    public function test_slug_from_name_falls_back_when_empty(): void
    {
        $this->assertSame('start', TenantPackageCapability::slugFromName('Start'));
        $this->assertSame('pakket', TenantPackageCapability::slugFromName('***'));
    }
}
