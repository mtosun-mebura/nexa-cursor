<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\User;
use App\Models\WebsitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebsitePageStructuredDataFrontendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function preview_includes_json_ld_structured_data(): void
    {
        $company = Company::create([
            'name' => 'Structured Data BV',
            'email' => 'info@structured.test',
            'is_active' => true,
        ]);

        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );

        $page = WebsitePage::create([
            'title' => 'Over ons',
            'slug' => 'over-ons-test-'.uniqid(),
            'meta_description' => 'Wij zijn Structured Data BV.',
            'page_type' => 'about',
            'frontend_theme_id' => $theme->id,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 1,
            'home_sections' => WebsitePage::defaultHomeSectionsForTheme('modern'),
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->get(route('admin.website-pages.preview', $page));

        $response->assertOk();
        $response->assertSee('application/ld+json', false);
        $response->assertSee('Structured Data BV', false);
        $response->assertSee('"@type":"WebPage"', false);
        $response->assertSee('Wij zijn Structured Data BV.', false);
    }
}
