<?php

namespace Tests\Unit;

use App\Support\TenantPackageAddon;
use Tests\TestCase;

class TenantPackageAddonTest extends TestCase
{
    public function test_normalize_selections_clamps_extra_clients_and_bools(): void
    {
        $this->assertSame([
            TenantPackageAddon::EXTRA_CLIENTS => 0,
            TenantPackageAddon::GPS_TRACKING => 0,
            TenantPackageAddon::FLEET => 0,
        ], TenantPackageAddon::normalizeSelections([]));

        $normalized = TenantPackageAddon::normalizeSelections([
            TenantPackageAddon::EXTRA_CLIENTS => 99,
            TenantPackageAddon::GPS_TRACKING => '1',
            TenantPackageAddon::FLEET => true,
        ]);

        $this->assertSame(50, $normalized[TenantPackageAddon::EXTRA_CLIENTS]);
        $this->assertSame(1, $normalized[TenantPackageAddon::GPS_TRACKING]);
        $this->assertSame(1, $normalized[TenantPackageAddon::FLEET]);
    }

    public function test_catalog_keeps_stable_keys_and_default_prices(): void
    {
        $catalog = TenantPackageAddon::normalizeCatalog([]);
        $keys = array_column($catalog, 'key');

        $this->assertSame([
            TenantPackageAddon::EXTRA_CLIENTS,
            TenantPackageAddon::GPS_TRACKING,
            TenantPackageAddon::FLEET,
        ], $keys);
        $this->assertSame(49, $catalog[0]['price']);
        $this->assertSame(19, $catalog[1]['price']);
        $this->assertSame(249, $catalog[2]['price']);
        $this->assertSame(10, TenantPackageAddon::EXTRA_CLIENTS_PER_PACK);
    }

    public function test_selected_billing_lines_skip_empty_and_price_quantity(): void
    {
        $lines = TenantPackageAddon::selectedBillingLines([
            TenantPackageAddon::EXTRA_CLIENTS => 2,
            TenantPackageAddon::GPS_TRACKING => 1,
            TenantPackageAddon::FLEET => 0,
        ], TenantPackageAddon::normalizeCatalog([]));

        $this->assertCount(2, $lines);
        $this->assertSame(TenantPackageAddon::EXTRA_CLIENTS, $lines[0]['key']);
        $this->assertSame(2, $lines[0]['quantity']);
        $this->assertSame(49.0, $lines[0]['unit_price']);
        $this->assertSame(98.0, $lines[0]['total']);
        $this->assertSame(TenantPackageAddon::GPS_TRACKING, $lines[1]['key']);
        $this->assertSame(19.0, $lines[1]['total']);
    }
}
