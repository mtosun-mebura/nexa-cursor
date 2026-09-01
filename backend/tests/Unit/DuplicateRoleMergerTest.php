<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Support\DuplicateRoleMerger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DuplicateRoleMergerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function merge_moves_assignments_from_capitalized_role_to_canonical_slug(): void
    {
        $canonical = Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        $capital = Role::query()->firstOrCreate(
            ['name' => 'Chauffeur', 'guard_name' => 'web'],
            ['company_id' => null]
        );

        $company = Company::query()->create(['name' => 'Merge Taxi', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $pivot = config('permission.table_names.model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key') ?: 'model_id';
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $teamKey = config('permission.column_names.team_foreign_key') ?: 'company_id';

        $row = [
            $rolePivotKey => $capital->id,
            $morphKey => $user->id,
            'model_type' => $user->getMorphClass(),
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn($pivot, $teamKey)) {
            $row[$teamKey] = $company->id;
        }
        DB::table($pivot)->insert($row);

        DuplicateRoleMerger::mergeCaseDuplicates();

        $this->assertSame(['chauffeur'], $user->fresh()->webRoleNames());
        $this->assertNull(Role::query()->where('name', 'Chauffeur')->where('guard_name', 'web')->first());
        $this->assertNotNull(Role::query()->where('name', 'chauffeur')->where('guard_name', 'web')->first());
        $this->assertSame($canonical->id, Role::query()->where('name', 'chauffeur')->where('guard_name', 'web')->value('id'));
    }
}
