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
}
