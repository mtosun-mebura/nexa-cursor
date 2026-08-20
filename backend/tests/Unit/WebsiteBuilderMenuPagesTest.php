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

    #[Test]
    public function central_menu_includes_welcome_page_as_home(): void
    {
        if (! Schema::hasColumn('website_pages', 'show_in_menu')) {
            $this->markTestSkipped('show_in_menu column not migrated');
        }

        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );

        $welcome = WebsitePage::create([
            'slug' => WebsitePage::CENTRAL_WELCOME_SLUG,
            'title' => 'NEXA Suite',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'is_active' => true,
            'show_in_menu' => false,
            'sort_order' => 0,
        ]);
        WebsitePage::create([
            'slug' => 'taxi',
            'title' => 'Nexa Taxi',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 1,
        ]);

        $menuPages = app(WebsiteBuilderService::class)->getActiveMenuPages();

        $this->assertTrue($welcome->isPublicHomeNavItem());
        $this->assertSame('Home', $welcome->publicNavLabel());
        $this->assertSame(WebsitePage::CENTRAL_WELCOME_SLUG, $menuPages->first()?->slug);
        $this->assertTrue($menuPages->contains(fn (WebsitePage $p) => $p->slug === 'taxi'));
    }

    #[Test]
    public function public_nav_label_uses_menu_title_instead_of_seo_title(): void
    {
        if (! Schema::hasColumn('website_pages', 'menu_title')) {
            $this->markTestSkipped('website_pages.menu_title column required');
        }

        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );

        $page = WebsitePage::create([
            'slug' => 'taxi-seo-menu',
            'title' => 'Taxi software voor vervoerders in de regio',
            'menu_title' => 'Taxi',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 2,
        ]);

        $this->assertSame('Taxi', $page->publicNavLabel());
        $this->assertSame('Home', WebsitePage::defaultMenuTitleFromPage('NEXA Suite', 'custom', WebsitePage::CENTRAL_WELCOME_SLUG));
    }
}
