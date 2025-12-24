<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

/**
 * Tests voor dropdown/select veld validatie
 * 
 * Test dat:
 * - Verplichte dropdowns worden gevalideerd
 * - Optionele dropdowns geen validatie nodig hebben
 * - Lege waarden worden correct afgehandeld
 */
class DropdownValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    /** @test */
    public function required_dropdown_must_have_value()
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $this->actingAs($user);

        $response = $this->post('/admin/users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'role' => '', // Lege waarde voor verplicht veld
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    /** @test */
    public function optional_dropdown_can_be_empty()
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $this->actingAs($user);

        $company = Company::factory()->create();

        $response = $this->post('/admin/users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'role' => 'admin',
            'company_id' => '', // Lege waarde voor optioneel veld
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'company_id' => null,
        ]);
    }

    /** @test */
    public function required_dropdown_with_value_is_valid()
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $this->actingAs($user);

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $response = $this->post('/admin/users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'role' => 'admin', // Geldige waarde
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function optional_dropdown_with_value_is_valid()
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $this->actingAs($user);

        $company = Company::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $response = $this->post('/admin/users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'role' => 'admin',
            'company_id' => $company->id, // Optionele waarde ingevuld
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'company_id' => $company->id,
        ]);
    }
}





