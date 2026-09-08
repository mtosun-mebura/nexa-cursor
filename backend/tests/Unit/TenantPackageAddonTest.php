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

    public function test_nested_form_values_and_legacy_ints_normalize_to_records(): void
    {
        $nested = TenantPackageAddon::normalizeRecords([
            TenantPackageAddon::GPS_TRACKING => [
                'quantity' => 1,
                'starts_at' => '2026-10-01',
            ],
            TenantPackageAddon::EXTRA_CLIENTS => 2,
        ]);

        $this->assertSame(1, $nested[TenantPackageAddon::GPS_TRACKING]['quantity']);
        $this->assertSame('2026-10-01', $nested[TenantPackageAddon::GPS_TRACKING]['starts_at']);
        $this->assertSame(2, $nested[TenantPackageAddon::EXTRA_CLIENTS]['quantity']);

        $legacy = TenantPackageAddon::normalizeSelections([
            TenantPackageAddon::GPS_TRACKING => 1,
            TenantPackageAddon::FLEET => 0,
        ]);
        $this->assertSame(1, $legacy[TenantPackageAddon::GPS_TRACKING]);
        $this->assertSame(0, $legacy[TenantPackageAddon::FLEET]);
    }

    public function test_future_start_is_not_entitled_until_the_start_date(): void
    {
        \Carbon\Carbon::setTestNow('2026-09-07 10:00:00');
        $records = TenantPackageAddon::normalizeRecords([
            TenantPackageAddon::GPS_TRACKING => [
                'quantity' => 1,
                'starts_at' => '2026-10-01',
            ],
        ], []);

        $this->assertSame(0, $records[TenantPackageAddon::GPS_TRACKING]['active_quantity']);
        $this->assertSame(0, TenantPackageAddon::effectiveSelections($records, \Carbon\Carbon::parse('2026-09-15'))[TenantPackageAddon::GPS_TRACKING]);
        $this->assertSame(1, TenantPackageAddon::effectiveSelections($records, \Carbon\Carbon::parse('2026-10-01'))[TenantPackageAddon::GPS_TRACKING]);
        \Carbon\Carbon::setTestNow();
    }

    public function test_mid_month_activation_charges_remaining_days_only(): void
    {
        $asOf = \Carbon\Carbon::parse('2026-09-07');
        $charge = TenantPackageAddon::activationCharge('GPS-trackers', 1, 19, $asOf, $asOf);

        $this->assertNotNull($charge);
        $this->assertFalse($charge['full_month']);
        $this->assertSame(24, $charge['days']);
        $this->assertSame(30, $charge['days_in_month']);
        $this->assertSame(round(19 * (24 / 30), 2), $charge['amount']);
        $this->assertSame('2026-09', $charge['prepaid_through']);
        $this->assertStringContainsString('7 t/m', $charge['description']);
    }

    public function test_first_of_next_month_activation_charges_full_month(): void
    {
        $asOf = \Carbon\Carbon::parse('2026-09-07');
        $start = \Carbon\Carbon::parse('2026-10-01');
        $charge = TenantPackageAddon::activationCharge('GPS-trackers', 1, 19, $start, $asOf);

        $this->assertNotNull($charge);
        $this->assertTrue($charge['full_month']);
        $this->assertSame(19.0, $charge['amount']);
        $this->assertSame('2026-10', $charge['prepaid_through']);
        $this->assertStringContainsString('oktober', mb_strtolower($charge['description']));
    }

    public function test_paid_cancel_stays_entitled_until_first_of_next_month(): void
    {
        \Carbon\Carbon::setTestNow('2026-09-08 10:00:00');
        $previous = TenantPackageAddon::normalizeRecords([
            TenantPackageAddon::GPS_TRACKING => [
                'quantity' => 1,
                'starts_at' => '2026-08-01',
            ],
        ], []);

        $cancelled = TenantPackageAddon::normalizeRecords([
            TenantPackageAddon::GPS_TRACKING => ['quantity' => 0],
        ], $previous, false);

        $gps = $cancelled[TenantPackageAddon::GPS_TRACKING];
        $this->assertSame(0, $gps['quantity']);
        $this->assertSame('2026-10-01', $gps['starts_at']);
        $this->assertSame(1, $gps['active_quantity']);
        $this->assertTrue(TenantPackageAddon::isPendingCancel($gps));
        $this->assertSame(1, TenantPackageAddon::effectiveSelections($cancelled, \Carbon\Carbon::parse('2026-09-08'))[TenantPackageAddon::GPS_TRACKING]);
        $this->assertSame(0, TenantPackageAddon::effectiveSelections($cancelled, \Carbon\Carbon::parse('2026-10-01'))[TenantPackageAddon::GPS_TRACKING]);

        $trialCancel = TenantPackageAddon::normalizeRecords([
            TenantPackageAddon::GPS_TRACKING => ['quantity' => 0],
        ], $previous, true);
        $this->assertSame(0, $trialCancel[TenantPackageAddon::GPS_TRACKING]['quantity']);
        $this->assertNull($trialCancel[TenantPackageAddon::GPS_TRACKING]['starts_at']);
        $this->assertSame(0, TenantPackageAddon::effectiveSelections($trialCancel)[TenantPackageAddon::GPS_TRACKING]);
        \Carbon\Carbon::setTestNow();
    }

    public function test_paid_quantity_decrease_takes_effect_next_month(): void
    {
        \Carbon\Carbon::setTestNow('2026-09-08 10:00:00');
        $previous = TenantPackageAddon::normalizeRecords([
            TenantPackageAddon::EXTRA_CLIENTS => [
                'quantity' => 2,
                'starts_at' => '2026-08-01',
            ],
        ], []);

        $decreased = TenantPackageAddon::normalizeRecords([
            TenantPackageAddon::EXTRA_CLIENTS => ['quantity' => 1],
        ], $previous, false);

        $record = $decreased[TenantPackageAddon::EXTRA_CLIENTS];
        $this->assertSame(1, $record['quantity']);
        $this->assertSame('2026-10-01', $record['starts_at']);
        $this->assertSame(2, $record['active_quantity']);
        $this->assertSame(2, TenantPackageAddon::effectiveSelections($decreased, \Carbon\Carbon::parse('2026-09-20'))[TenantPackageAddon::EXTRA_CLIENTS]);
        $this->assertSame(1, TenantPackageAddon::effectiveSelections($decreased, \Carbon\Carbon::parse('2026-10-01'))[TenantPackageAddon::EXTRA_CLIENTS]);
        $this->assertTrue(TenantPackageAddon::isPendingDecrease($record));
        $this->assertFalse(TenantPackageAddon::isPendingCancel($record));
        \Carbon\Carbon::setTestNow();
    }
}
