<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\WebsitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePageNextSortOrderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function next_sort_order_is_scoped_per_company(): void
    {
        if (! Schema::hasColumn('website_pages', 'company_id') || ! Schema::hasColumn('website_pages', 'sort_order')) {
            $this->markTestSkipped('website_pages company_id or sort_order not available');
        }

        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );
        $companyA = Company::query()->create(['name' => 'Tenant A', 'is_active' => true]);
        $companyB = Company::query()->create(['name' => 'Tenant B', 'is_active' => true]);

        WebsitePage::create([
            'slug' => 'a-first',
            'title' => 'A first',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'company_id' => $companyA->id,
            'is_active' => true,
            'sort_order' => 3,
        ]);
        WebsitePage::create([
            'slug' => 'b-first',
            'title' => 'B first',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'company_id' => $companyB->id,
            'is_active' => true,
            'sort_order' => 7,
        ]);

        $this->assertSame(4, WebsitePage::nextSortOrderForTenant(null, (int) $companyA->id));
        $this->assertSame(8, WebsitePage::nextSortOrderForTenant(null, (int) $companyB->id));
        $this->assertSame(1, WebsitePage::nextSortOrderForTenant(null, null));
    }
}
