<?php

namespace Tests\Unit;

use App\Models\FrontendTheme;
use App\Models\WebsitePage;
use App\Services\WebsiteBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsiteBuilderMenuPagesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function active_menu_pages_exclude_show_in_menu_false(): void
    {
        if (! Schema::hasColumn('website_pages', 'show_in_menu')) {
            $this->markTestSkipped('show_in_menu column not migrated');
        }

        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );

        WebsitePage::create([
            'slug' => 'menu-visible',
            'title' => 'Zichtbaar',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 0,
        ]);
        WebsitePage::create([
            'slug' => 'menu-hidden',
            'title' => 'Verborgen',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'is_active' => true,
            'show_in_menu' => false,
            'sort_order' => 1,
        ]);

        $menuPages = app(WebsiteBuilderService::class)->getActiveMenuPages();

        $this->assertTrue($menuPages->contains(fn (WebsitePage $p) => $p->slug === 'menu-visible'));
        $this->assertFalse($menuPages->contains(fn (WebsitePage $p) => $p->slug === 'menu-hidden'));
    }
}
