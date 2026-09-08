<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostcodeLookupTest extends TestCase
{
    #[Test]
    public function lookup_uses_pdok_when_openpostcode_has_no_street(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        Http::fake([
            'openpostcode.nl/*' => Http::response(['woonplaats' => 'Enschede'], 200),
            'api.pdok.nl/*' => Http::response([
                'response' => [
                    'docs' => [[
                        'straatnaam' => 'Deurningerstraat',
                        'huisnummer' => 240,
                        'postcode' => '7522CA',
                        'woonplaatsnaam' => 'Enschede',
                        'centroide_ll' => 'POINT(6.8958 52.2205)',
                    ]],
                ],
            ], 200),
            'maps.googleapis.com/*' => Http::response(['status' => 'ZERO_RESULTS'], 200),
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.postcode.lookup'), [
                'postcode' => '7522CA',
                'huisnummer' => '240',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'street' => 'Deurningerstraat',
                'city' => 'Enschede',
                'source' => 'pdok',
            ]);
    }
}
