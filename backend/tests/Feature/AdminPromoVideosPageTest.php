<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPromoVideosPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin', 'web');
    }

    public function test_super_admin_can_open_promo_videos_page_without_tenant(): void
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.promo-videos.index'));

        $response->assertOk();
        $response->assertSee(__('Promo video’s'), false);
        $response->assertSee('NexaProductPromo', false);
        $response->assertSee('NexaAppTeaser', false);
        $response->assertSee('id="promo-video-form"', false);
        $response->assertDontSee(
            'Kies links in de zijbalk een tenant (bedrijf) voordat u deze pagina kunt bekijken',
            false
        );
    }

    public function test_non_super_admin_cannot_open_promo_videos_page(): void
    {
        Role::findOrCreate('company-admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('company-admin');

        $response = $this->actingAs($user)->get(route('admin.promo-videos.index'));

        // Role middleware redirects unauthorized users away from the page.
        $this->assertFalse($response->isSuccessful());
        $response->assertDontSee('NexaProductPromo', false);
    }
}
