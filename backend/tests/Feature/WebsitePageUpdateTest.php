<?php

namespace Tests\Feature;

use App\Models\FrontendTheme;
use App\Models\User;
use App\Models\WebsitePage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebsitePageUpdateTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    /**
     * @test
     * @group website-pages
     */
    public function update_persists_cards_ronde_hoeken_home_sections()
    {
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $page = WebsitePage::create([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', 'cards_ronde_hoeken', 'cta'],
                'visibility' => ['hero' => true, 'cards_ronde_hoeken' => true],
            ],
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $payload = [
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'module_name' => '',
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'sort_order' => '0',
            'home_sections' => [
                'section_order' => 'hero,stats,cards_ronde_hoeken,cta',
                'visibility' => [
                    'hero' => '1',
                    'cards_ronde_hoeken' => '1',
                    'cards_ronde_hoeken_item_0' => '1',
                ],
                'copyright' => '',
                'cards_ronde_hoeken' => [
                    'items' => [
                        0 => [
                            'image_url' => '/storage/test.jpg',
                            'text' => '<p>Test card text</p>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect(route('admin.website-pages.index'));
        $response->assertSessionHas('success');

        $page->refresh();
        $sections = $page->home_sections;
        $this->assertIsArray($sections);
        $this->assertArrayHasKey('cards_ronde_hoeken', $sections);
        $this->assertSame('/storage/test.jpg', $sections['cards_ronde_hoeken']['items'][0]['image_url'] ?? null);
        $this->assertStringContainsString('Test card text', $sections['cards_ronde_hoeken']['items'][0]['text'] ?? '');
    }
}
