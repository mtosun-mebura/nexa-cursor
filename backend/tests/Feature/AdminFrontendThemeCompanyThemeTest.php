<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminFrontendThemeCompanyThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_index_shows_theme_per_tenant_not_per_module(): void
    {
        Company::query()->create(['name' => 'Taxi Royaal', 'is_active' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->get(route('admin.frontend-themes.index'))
            ->assertOk()
            ->assertSee('Thema per tenant', false)
            ->assertSee('Taxi Royaal', false)
            ->assertDontSee('Thema per module', false);
    }

    public function test_super_admin_can_assign_theme_to_company(): void
    {
        $theme = FrontendTheme::query()->create([
            'slug' => 'landwind-test',
            'name' => 'Landwind',
            'is_active' => true,
        ]);
        $company = Company::query()->create(['name' => 'Taxi Royaal', 'is_active' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->post(route('admin.frontend-themes.update-company-theme'), [
                'company_id' => $company->id,
                'frontend_theme_id' => $theme->id,
            ])
            ->assertRedirect(route('admin.frontend-themes.index'));

        $this->assertSame($theme->id, (int) $company->fresh()->frontend_theme_id);
    }
}
