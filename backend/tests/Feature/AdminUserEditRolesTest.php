<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserEditRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function edit_form_checks_chauffeur_when_assigned_role_name_differs_only_by_case(): void
    {
        $capital = Role::query()->firstOrCreate(
            ['name' => 'Chauffeur', 'guard_name' => 'web'],
            ['company_id' => null]
        );

        $company = Company::query()->create(['name' => 'Taxi Roles', 'is_active' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $driver = User::factory()->create(['company_id' => $company->id]);
        $this->attachWebRole($driver, $capital, $company->id);

        $this->assertSame(['Chauffeur'], $driver->webRoleNames());

        $html = $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.users.edit', $driver))
            ->assertOk()
            ->assertSee('Gebruiker Bewerken', false)
            ->getContent();

        $pos = strpos($html, 'roles[]');
        $this->assertTrue(
            (bool) preg_match('/<input[^>]*value="chauffeur"[^>]*>|<input[^>]*value="Chauffeur"[^>]*>/', $html, $match),
            'Checkbox chauffeur ontbreekt. roles[] snippet: '.($pos === false ? 'ONTBREEKT' : substr($html, max(0, $pos - 120), 500))
        );
        $this->assertTrue(
            str_contains($match[0], 'checked'),
            'Rol chauffeur zou aangevinkt moeten zijn, ook als de database "Chauffeur" opslaat. Input: '.$match[0]
        );
    }

    private function attachWebRole(User $user, Role $role, int $companyId): void
    {
        $pivot = config('permission.table_names.model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key') ?: 'model_id';
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $teamKey = config('permission.column_names.team_foreign_key') ?: 'company_id';

        $row = [
            $rolePivotKey => $role->id,
            $morphKey => $user->id,
            'model_type' => $user->getMorphClass(),
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn($pivot, $teamKey)) {
            $row[$teamKey] = $companyId;
        }

        DB::table($pivot)->insert($row);
    }
}
