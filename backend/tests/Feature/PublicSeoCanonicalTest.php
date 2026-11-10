<?php

namespace Tests\Feature;

use App\Models\FrontendTheme;
use App\Models\WebsitePage;
use App\Services\CentralWelcomePageService;
use Database\Seeders\InfoRequestFormFieldSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicSeoCanonicalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'http://localhost:8085',
            'tenancy.central_domains' => ['localhost'],
        ]);

        FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        (new InfoRequestFormFieldSeeder)->run();
        app(CentralWelcomePageService::class)->ensureMarketingPagesExist();
    }

    #[Test]
    public function about_without_page_returns_404_not_home_redirect(): void
    {
        WebsitePage::query()->where('page_type', 'about')->delete();

        $this->get('http://localhost:8085/about')
            ->assertNotFound();
    }

    #[Test]
    public function about_redirects_to_canonical_over_ons_when_page_exists(): void
    {
        WebsitePage::query()->where('page_type', 'about')->delete();
        WebsitePage::query()->create([
            'company_id' => null,
            'module_name' => null,
            'slug' => 'over-ons',
            'title' => 'Over ons',
            'page_type' => 'about',
            'is_active' => true,
            'show_in_menu' => false,
            'content' => 'Over NEXA.',
            'meta_description' => 'Over NEXA Suite voor taxibedrijven in Nederland.',
        ]);

        $this->get('http://localhost:8085/about')
            ->assertRedirect('http://localhost:8085/over-ons');
    }

    #[Test]
    public function soft_query_params_are_stripped_with_301(): void
    {
        $this->get('http://localhost:8085/contact?pakket=Business&utm_source=ads')
            ->assertRedirect('http://localhost:8085/contact');
    }
}
