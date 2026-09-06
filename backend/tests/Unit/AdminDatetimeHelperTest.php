<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;

class AdminDatetimeHelperTest extends TestCase
{
    public function test_parses_and_displays_admin_datetime_picker_values(): void
    {
        $this->assertSame('2026-08-30 14:30:00', parse_admin_datetime('30-08-2026 14:30'));
        $this->assertSame('2026-08-30 14:30:00', parse_admin_datetime('2026-08-30T14:30'));
        $this->assertSame('2026-08-30 00:00:00', parse_admin_datetime('30-08-2026'));
        $this->assertSame('30-08-2026 14:30', admin_datetime_picker_display('30-08-2026 14:30'));
        $this->assertSame('30-08-2026 14:30', admin_datetime_picker_display('2026-08-30T14:30'));
        $this->assertSame('30-08-2026 09:05', admin_datetime_picker_display(Carbon::parse('2026-08-30 09:05:00')));
        $this->assertNull(parse_admin_datetime(''));
        $this->assertSame('', admin_datetime_picker_display(null));
    }

    public function test_parses_and_displays_admin_month_picker_values(): void
    {
        $this->assertSame('2026-08', parse_admin_month('08-2026'));
        $this->assertSame('2026-08', parse_admin_month('2026-08'));
        $this->assertSame('2026-08', parse_admin_month('2026-08-01'));
        $this->assertSame('2026-08', parse_admin_month(Carbon::parse('2026-08-15')));
        $this->assertSame('08-2026', admin_month_picker_display('2026-08'));
        $this->assertSame('08-2026', admin_month_picker_display('08-2026'));
        $this->assertNull(parse_admin_month(''));
        $this->assertSame('', admin_month_picker_display(null));
    }
}
