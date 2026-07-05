<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminHandleidingTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdminUser(): User
    {
        Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    public function test_handleiding_index_is_available_for_admin_users(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('admin.handleiding.index'));

        $response->assertOk()
            ->assertSee('Handleiding')
            ->assertSee('Stap-voor-stap uitleg')
            ->assertSee('Aan de slag');
    }

    public function test_handleiding_show_displays_first_article(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->get(route('admin.handleiding.show', 'aan-de-slag'))
            ->assertOk()
            ->assertSee('Aan de slag')
            ->assertSee('Welkom bij Nexa')
            ->assertSee('Navigatie in het menu');
    }

    public function test_unknown_handleiding_slug_returns_not_found(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->get(route('admin.handleiding.show', 'bestaat-niet'))
            ->assertNotFound();
    }
}
