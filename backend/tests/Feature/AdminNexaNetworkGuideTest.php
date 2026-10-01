<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNexaNetworkGuideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function super_admin_can_open_nexa_network_guide(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)
            ->get(route('admin.nexa-network.guide'));

        $response->assertOk()
            ->assertSee('Waar doe ik wat?', false)
            ->assertSee('Partners koppelen via invite-code', false)
            ->assertSee('Network-partners / invites', false)
            ->assertSee('Fee-instellingen', false)
            ->assertSee('Drie ritmodellen', false)
            ->assertSee('settlement_eligible', false)
            ->assertSee('Flow in één oogopslag', false);
    }

    #[Test]
    public function company_admin_cannot_open_nexa_network_guide(): void
    {
        $user = User::factory()->create();
        $user->assignRole('company-admin');

        $this->actingAs($user)
            ->get(route('admin.nexa-network.guide'))
            ->assertStatus(302);
    }
}
