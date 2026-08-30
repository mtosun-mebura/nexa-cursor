<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserWizardCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_wizard_user_create_selects_tenant_from_url_without_sidebar_choice(): void
    {
        $company = Company::query()->create(['name' => 'Memmo Taxi', 'is_active' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.users.create', [
                'from_wizard' => 1,
                'wizard_company' => $company->id,
                'wizard_step' => 5,
                'company_id' => $company->id,
            ]))
            ->assertOk()
            ->assertSee('Nieuwe Gebruiker', false)
            ->assertSee('Memmo Taxi', false)
            ->assertSee('name="company_id"', false)
            ->assertSee('value="'.$company->id.'"', false)
            ->assertDontSee('Kies links in de zijbalk een tenant', false);

        $this->assertSame($company->id, (int) session('selected_tenant'));
    }

    public function test_user_create_without_tenant_hides_form_for_super_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Kies links in de zijbalk een tenant', false)
            ->assertDontSee('name="first_name"', false);
    }
}
