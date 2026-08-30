<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserCreateFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-staff', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function create_form_has_password_generate_control_and_hides_function_without_skillmatching(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Tenant', 'is_active' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $html = $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.users.create', [
                'from_wizard' => 1,
                'wizard_company' => $company->id,
                'wizard_step' => 5,
                'company_id' => $company->id,
            ]))
            ->assertOk()
            ->assertSee('id="user-create-password-generate"', false)
            ->assertSee('Genereer een tijdelijk wachtwoord', false)
            ->getContent();

        $this->assertFunctionRowHidden($html, true);
    }

    #[Test]
    public function create_form_password_is_optional_when_adding_a_user_to_an_existing_tenant(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Bestaand', 'is_active' => true]);
        User::factory()->create(['company_id' => $company->id]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $html = $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.users.create', [
                'company_id' => $company->id,
            ]))
            ->assertOk()
            ->assertSee('Optioneel bij chauffeur, contractant en contractouder', false)
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/id="user-create-password"[^>]*>/',
            $html
        );
        preg_match('/<input[^>]*id="user-create-password"[^>]*>/', $html, $match);
        $this->assertNotEmpty($match);
        $this->assertStringNotContainsString('required', $match[0]);
    }

    #[Test]
    public function create_form_password_is_optional_when_chauffeur_role_is_selected(): void
    {
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        $company = Company::query()->create(['name' => 'Taxi Chauffeur', 'is_active' => true]);
        User::factory()->create(['company_id' => $company->id]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $html = $this->actingAs($admin, 'web')
            ->withSession([
                'selected_tenant' => $company->id,
                '_old_input' => ['roles' => ['chauffeur']],
            ])
            ->get(route('admin.users.create', ['company_id' => $company->id]))
            ->assertOk()
            ->assertSee('Niet nodig. Chauffeur, contractant en contractouder', false)
            ->getContent();

        preg_match('/<input[^>]*id="user-create-password"[^>]*>/', $html, $match);
        $this->assertNotEmpty($match);
        $this->assertStringNotContainsString('required', $match[0]);
    }

    #[Test]
    public function create_form_shows_function_for_skillmatching_tenant(): void
    {
        $skill = Module::query()->create([
            'name' => 'skillmatching',
            'display_name' => 'Nexa Skillmatching',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);
        $company = Company::query()->create(['name' => 'Skill Tenant', 'is_active' => true]);
        $company->modules()->attach($skill->id);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $html = $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.users.create', [
                'from_wizard' => 1,
                'wizard_company' => $company->id,
                'wizard_step' => 5,
                'company_id' => $company->id,
            ]))
            ->assertOk()
            ->getContent();

        $this->assertFunctionRowHidden($html, false);
    }

    #[Test]
    public function storing_a_user_sets_must_change_password_and_ignores_function_without_skillmatching(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Store', 'is_active' => true]);
        User::factory()->create(['company_id' => $company->id]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->post(route('admin.users.store'), [
                'first_name' => 'Nieuwe',
                'last_name' => 'Gebruiker',
                'email' => 'nieuwe.gebruiker@example.com',
                'password' => 'Password1',
                'function' => 'Chauffeur planner',
                'company_id' => $company->id,
                'roles' => ['company-staff'],
            ])
            ->assertRedirect();

        $created = User::query()->where('email', 'nieuwe.gebruiker@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->must_change_password);
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'function')) {
            $this->assertNull($created->function);
        }
    }

    #[Test]
    public function storing_a_user_keeps_function_for_skillmatching_tenant(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('users', 'function')) {
            $this->markTestSkipped('Kolom users.function ontbreekt in deze testdatabase.');
        }
        $company->modules()->attach($skill->id);
        User::factory()->create(['company_id' => $company->id]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->post(route('admin.users.store'), [
                'first_name' => 'Recruiter',
                'last_name' => 'Test',
                'email' => 'recruiter.test@example.com',
                'password' => 'Password1',
                'function' => 'Recruiter',
                'company_id' => $company->id,
                'roles' => ['company-staff'],
            ])
            ->assertRedirect();

        $created = User::query()->where('email', 'recruiter.test@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->must_change_password);
        $this->assertSame('Recruiter', $created->function);
    }

    private function assertFunctionRowHidden(string $html, bool $hidden): void
    {
        $this->assertTrue(
            (bool) preg_match('/<tr id="user-function-row"[^>]*>/', $html, $match),
            'Functie-rij ontbreekt in het formulier.'
        );
        $hasHidden = str_contains($match[0], 'hidden');
        if ($hidden) {
            $this->assertTrue($hasHidden, 'Functie-rij zou verborgen moeten zijn zonder Skillmatching.');
        } else {
            $this->assertFalse($hasHidden, 'Functie-rij zou zichtbaar moeten zijn bij Skillmatching.');
        }
    }
}
