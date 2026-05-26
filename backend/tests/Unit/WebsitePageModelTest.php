<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use App\Models\FrontendTheme;
use App\Models\WebsitePage;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebsitePageModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!Schema::hasTable('frontend_themes')) {
            $this->markTestSkipped('frontend_themes table required (run migrations)');
        }
    }

    #[Test]
    public function scope_active_filters_only_active_pages(): void
    {
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        WebsitePage::create([
            'slug' => 'active-page',
            'title' => 'Active',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        WebsitePage::create([
            'slug' => 'inactive-page',
            'title' => 'Inactive',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $active = WebsitePage::active()->get();
        $this->assertCount(1, $active);
        $this->assertSame('active-page', $active->first()->slug);
    }

    #[Test]
    public function scope_show_in_menu_filters_when_column_exists(): void
    {
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $conn = $theme->getConnectionName() ?? config('database.default');
        $hasColumn = WebsitePage::tableHasColumnOnConnection($conn, 'show_in_menu');

        WebsitePage::create([
            'slug' => 'in-menu',
            'title' => 'In Menu',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 0,
        ]);
        WebsitePage::create([
            'slug' => 'not-in-menu',
            'title' => 'Not In Menu',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'is_active' => true,
            'show_in_menu' => false,
            'sort_order' => 1,
        ]);

        $inMenu = WebsitePage::active()->showInMenu()->get();
        if ($hasColumn) {
            $this->assertCount(1, $inMenu);
            $this->assertSame('in-menu', $inMenu->first()->slug);
        } else {
            $this->assertGreaterThanOrEqual(1, $inMenu->count());
        }
    }

    #[Test]
    public function model_has_expected_fillable_attributes(): void
    {
        $expected = [
            'slug', 'title', 'content', 'meta_description', 'home_sections',
            'page_type', 'module_name', 'frontend_theme_id', 'is_active',
            'show_in_menu', 'sort_order',
        ];
        $page = new WebsitePage;
        $this->assertEquals($expected, $page->getFillable());
    }

    #[Test]
    public function is_active_and_show_in_menu_are_cast_to_boolean(): void
    {
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $page = WebsitePage::create([
            'slug' => 'cast-test',
            'title' => 'Cast',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $this->assertIsBool($page->is_active);
        if (Schema::hasColumn('website_pages', 'show_in_menu')) {
            $page->refresh();
            $this->assertTrue($page->show_in_menu === true || $page->show_in_menu === false);
        }
    }
}
