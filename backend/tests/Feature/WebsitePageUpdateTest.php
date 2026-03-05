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
            ['name' => 'Metronic', 'is_active' => true]
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

        $response->assertRedirect(route('admin.website-pages.edit', $page));
        $response->assertSessionHas('success');

        $page->refresh();
        $sections = $page->home_sections;
        $this->assertIsArray($sections);
        $this->assertArrayHasKey('cards_ronde_hoeken', $sections);
        $this->assertSame('/storage/test.jpg', $sections['cards_ronde_hoeken']['items'][0]['image_url'] ?? null);
        $this->assertStringContainsString('Test card text', $sections['cards_ronde_hoeken']['items'][0]['text'] ?? '');
    }

    /**
     * @test
     * @group website-pages
     */
    public function update_persists_component_in_section_order()
    {
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'atom-v2'],
            ['name' => 'Atom v2', 'is_active' => true]
        );
        $page = WebsitePage::create([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => 'Taxi Royaal',
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', 'stats', 'cta'],
                'visibility' => ['hero' => true],
            ],
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $payload = [
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'module_name' => 'Taxi Royaal',
            'frontend_theme_id' => (string) $theme->id,
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'sort_order' => '0',
            '_section_order' => 'hero,stats,component:taxiroyaal.boekingsmodule,cta',
            'home_sections' => [
                'section_order' => 'hero,stats,component:taxiroyaal.boekingsmodule,cta',
                'visibility' => ['hero' => true, 'footer' => true],
                'copyright' => '© Test',
                'footer' => ['tagline' => 'Test tagline'],
            ],
        ];

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect(route('admin.website-pages.edit', $page));
        $page->refresh();
        $sections = $page->home_sections;
        $this->assertIsArray($sections);
        $order = $sections['section_order'] ?? [];
        $this->assertContains('component:taxiroyaal.boekingsmodule', $order, 'Section order must contain the component key');
        $this->assertArrayHasKey('component:taxiroyaal.boekingsmodule', $sections);
    }

    /**
     * @test
     * @group website-pages
     */
    public function update_removes_component_from_section_order_when_not_in_section_order()
    {
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
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
                'section_order' => ['hero', 'stats', 'component:nexa.recente_vacatures', 'cta'],
                'visibility' => ['hero' => true, 'footer' => true],
                'footer' => [],
                'copyright' => '',
            ],
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        // Simuleer: gebruiker heeft "Recente Vacatures" verwijderd; _section_order bevat alleen hero,stats,cta
        $payload = [
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'module_name' => '',
            'frontend_theme_id' => (string) $theme->id,
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'sort_order' => '0',
            '_section_order' => 'hero,stats,cta',
            'home_sections' => [
                'section_order' => 'hero,stats,cta',
                'visibility' => ['hero' => true, 'footer' => true],
                'copyright' => '',
                'footer' => [],
            ],
        ];

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect();
        $page->refresh();
        $sections = $page->home_sections;
        $this->assertIsArray($sections);
        $order = $sections['section_order'] ?? [];
        $this->assertNotContains('component:nexa.recente_vacatures', $order, 'Removed component must not be in section_order after save');
        $this->assertSame(['hero', 'stats', 'cta'], $order);
    }
}
