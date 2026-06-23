<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModuleMigrationsConfigTest extends TestCase
{
    #[Test]
    public function taxi_required_tables_includes_contractvervoer(): void
    {
        $required = config('module_migrations.required_tables.taxi', []);

        $this->assertContains('transport_customers', $required);
        $this->assertContains('transport_individual_bookings', $required);
        $this->assertContains('transport_occurrences', $required);
        $this->assertContains('ride_stops', $required);
        $this->assertContains('transport_schedule_exceptions', $required);
    }
}
