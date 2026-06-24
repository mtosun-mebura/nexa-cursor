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
        $this->assertContains('transport_contracts', $required);
        $this->assertContains('transport_passengers', $required);
        $this->assertContains('transport_individual_bookings', $required);
        $this->assertContains('transport_occurrences', $required);
        $this->assertContains('ride_stops', $required);
        $this->assertContains('transport_schedule_exceptions', $required);
    }

    #[Test]
    public function skillmatching_required_tables_excludes_public_branches_table(): void
    {
        $required = config('module_migrations.required_tables.skillmatching', []);

        $this->assertNotContains('branches', $required);
        $this->assertContains('vacancies', $required);
        $this->assertContains('job_configurations', $required);
    }
}
