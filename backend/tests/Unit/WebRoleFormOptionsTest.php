<?php

namespace Tests\Unit;

use App\Support\WebRoleFormOptions;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebRoleFormOptionsTest extends TestCase
{
    #[Test]
    public function test_dedupe_keeps_one_role_per_normalized_name(): void
    {
        $globalKlant = new Role(['name' => 'klant', 'guard_name' => 'web', 'company_id' => null]);
        $globalKlant->id = 1;

        $tenantKlant = new Role(['name' => 'klant', 'guard_name' => 'web', 'company_id' => 5]);
        $tenantKlant->id = 2;

        $capitalizedKlant = new Role(['name' => 'Klant', 'guard_name' => 'web', 'company_id' => 9]);
        $capitalizedKlant->id = 3;

        $chauffeurA = new Role(['name' => 'chauffeur', 'guard_name' => 'web', 'company_id' => null]);
        $chauffeurA->id = 4;

        $chauffeurB = new Role(['name' => 'chauffeur', 'guard_name' => 'web', 'company_id' => 5]);
        $chauffeurB->id = 5;

        $staff = new Role(['name' => 'staff', 'guard_name' => 'web', 'company_id' => null]);
        $staff->id = 6;

        $result = WebRoleFormOptions::dedupe(collect([
            $tenantKlant,
            $capitalizedKlant,
            $chauffeurB,
            $globalKlant,
            $chauffeurA,
            $staff,
        ]));

        $this->assertCount(3, $result);
        $this->assertSame(['chauffeur', 'klant', 'staff'], $result->pluck('name')->all());
        $this->assertSame(1, $result->firstWhere('name', 'klant')?->id);
        $this->assertSame(4, $result->firstWhere('name', 'chauffeur')?->id);
    }
}
