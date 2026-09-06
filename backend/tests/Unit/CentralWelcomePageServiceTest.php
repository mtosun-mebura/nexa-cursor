<?php

namespace Tests\Unit;

use App\Models\FrontendTheme;
use App\Models\WebsitePage;
use App\Services\CentralWelcomePageService;
use App\Services\FrontendComponentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CentralWelcomePageServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ensure_marketing_pages_creates_home_taxi_contract_and_website(): void
    {
        if (! Schema::hasTable('website_pages')) {
            $this->markTestSkipped('website_pages table required');
        }

        FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );

        $pages = app(CentralWelcomePageService::class)->ensureMarketingPagesExist();

        $this->assertGreaterThanOrEqual(7, $pages->count());
        $this->assertNotNull(WebsitePage::query()->where('slug', WebsitePage::CENTRAL_WELCOME_SLUG)->first());
        $this->assertNotNull(WebsitePage::query()->where('slug', CentralWelcomePageService::TAXI_SLUG)->first());
        $this->assertNotNull(WebsitePage::query()->where('slug', CentralWelcomePageService::BOEK_SLUG)->first());
        $this->assertNotNull(WebsitePage::query()->where('slug', CentralWelcomePageService::CONTRACT_SLUG)->first());
        $this->assertNotNull(WebsitePage::query()->where('slug', CentralWelcomePageService::WEBSITE_SLUG)->first());
        $comparison = WebsitePage::query()->where('slug', CentralWelcomePageService::COMPARISON_SLUG)->first();
        $this->assertNotNull($comparison);
        $this->assertFalse((bool) $comparison->show_in_menu);
        $this->assertSame('Voor & nadelen', $comparison->publicNavLabel());
        $contact = WebsitePage::query()->where('slug', CentralWelcomePageService::CONTACT_SLUG)->first();
        $this->assertNotNull($contact);
        $this->assertSame('contact', $contact->page_type);
        $this->assertTrue((bool) $contact->show_in_menu);
        $home = WebsitePage::query()->where('slug', WebsitePage::CENTRAL_WELCOME_SLUG)->first();
        $this->assertNotNull($home);
        $this->assertTrue((bool) $home->show_in_menu);
        $this->assertSame('Home', $home->publicNavLabel());
        $this->assertNotContains(
            \App\Services\NexaPricingService::PACKAGES_SECTION_KEY,
            $home->getHomeSections()['section_order'] ?? []
        );
    }

    #[Test]
    public function ensure_marketing_pages_creates_central_taxi_even_if_a_tenant_uses_the_same_slug(): void
    {
        if (! Schema::hasTable('website_pages') || ! Schema::hasColumn('website_pages', 'company_id')) {
            $this->markTestSkipped('website_pages.company_id required');
        }

        FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );

        $companyId = 2;
        if (Schema::hasTable('companies')) {
            $companyId = (int) \App\Models\Company::query()->create([
                'name' => 'Tenant Taxi',
                'is_active' => true,
            ])->id;
        }

        WebsitePage::query()->create([
            'slug' => CentralWelcomePageService::TAXI_SLUG,
            'title' => 'Laat klanten 24/7 een taxi boeken. Jij rijdt.',
            'page_type' => 'custom',
            'module_name' => null,
            'company_id' => $companyId,
            'is_active' => true,
            'show_in_menu' => true,
        ]);

        app(CentralWelcomePageService::class)->ensureMarketingPagesExist();

        $central = WebsitePage::query()
            ->where('slug', CentralWelcomePageService::TAXI_SLUG)
            ->whereNull('company_id')
            ->first();
        $this->assertNotNull($central);
        $this->assertSame('Nexa Taxi', $central->publicNavLabel());
        $this->assertTrue((bool) $central->show_in_menu);
        $this->assertTrue((bool) $central->is_active);
    }

    #[Test]
    public function sync_default_content_puts_marketing_copy_and_images_on_home(): void
    {
        if (! Schema::hasTable('website_pages')) {
            $this->markTestSkipped('website_pages table required');
        }

        FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );

        app(CentralWelcomePageService::class)->syncDefaultContent();
        $home = WebsitePage::query()->where('slug', WebsitePage::CENTRAL_WELCOME_SLUG)->first();
        $this->assertNotNull($home);
        $sections = $home->getHomeSections();
        $this->assertSame('Mis je ritten aan de telefoon? Laat klanten zelf boeken.', $sections['hero']['title'] ?? null);
        $this->assertSame('Online boeking, chauffeur-app en contractvervoer in één platform.', $sections['hero']['subtitle'] ?? null);
        $this->assertStringContainsString('hero-nexa-platform.png', (string) ($sections['hero']['background_image_url'] ?? ''));
        $this->assertContains('component:website.screenshot_gallery', $sections['section_order'] ?? []);
        $this->assertContains('component:website.comparison_table', $sections['section_order'] ?? []);
        $this->assertContains('component:vue_material.elevated_cards', $sections['section_order'] ?? []);
        $this->assertContains('component:landwind.stats_strip', $sections['section_order'] ?? []);
        $this->assertContains('component:landwind.feature_checklist', $sections['section_order'] ?? []);
        $this->assertContains('component:landwind.faq', $sections['section_order'] ?? []);
        $this->assertNotContains('why_nexa', $sections['section_order'] ?? []);
        $this->assertSame('Waarom ondernemers voor NEXA kiezen', $sections['component:vue_material.elevated_cards']['title'] ?? null);
        $this->assertSame('24', $sections['component:landwind.stats_strip']['items'][0]['value'] ?? null);
        $this->assertSame('/contact', $sections['cta']['cta_primary_url'] ?? null);
        $this->assertSame('/prijzen', $sections['cta']['cta_secondary_url'] ?? null);
        $gallery = $sections['component:website.screenshot_gallery']['items'] ?? [];
        $this->assertNotEmpty($gallery);
        $this->assertStringContainsString('feature-taxi-booking.png', (string) ($gallery[0]['image_url'] ?? ''));
        $this->assertSame('/taxi', $gallery[0]['url'] ?? null);
        $this->assertSame('Herkenbaar? Dit lost Nexa vandaag op', $sections['component:website.comparison_table']['title'] ?? null);
        $this->assertStringNotContainsString('Mijn Taxi', json_encode($sections, JSON_UNESCAPED_UNICODE));

        $contract = WebsitePage::query()->where('slug', CentralWelcomePageService::CONTRACT_SLUG)->first();
        $this->assertNotNull($contract);
        $contractSections = $contract->getHomeSections();
        $this->assertSame('Vaste ritten zonder Excel.', $contractSections['hero']['title'] ?? null);
        $this->assertContains('featured_services', $contractSections['section_order'] ?? []);
        $typeTitles = array_column($contractSections['featured_services']['items'] ?? [], 'title');
        $this->assertContains('Zorgcontracten', $typeTitles);
        $this->assertContains('Ziekenhuisvervoer', $typeTitles);
        $this->assertContains('Zakelijk vervoer', $typeTitles);
        $this->assertContains('Privévervoer', $typeTitles);

        $comparison = WebsitePage::query()->where('slug', CentralWelcomePageService::COMPARISON_SLUG)->first();
        $this->assertNotNull($comparison);
        $comparisonSections = $comparison->getHomeSections();
        $this->assertSame('Voor- en nadelen van Nexa.', $comparisonSections['hero']['title'] ?? null);
        $this->assertContains('component:website.comparison_table', $comparisonSections['section_order'] ?? []);
        $this->assertContains('component:landwind.faq', $comparisonSections['section_order'] ?? []);
        $this->assertSame(
            'Pijnpunt versus NEXA-antwoord',
            $comparisonSections['component:website.comparison_table']['title'] ?? null
        );

        $contact = WebsitePage::query()->where('slug', CentralWelcomePageService::CONTACT_SLUG)->first();
        $this->assertNotNull($contact);
        $contactSections = $contact->getHomeSections();
        $this->assertSame('Plan een gesprek. We kijken naar jouw ritten.', $contactSections['hero']['title'] ?? null);
        $this->assertContains('email_template', $contactSections['section_order'] ?? []);
        $this->assertNotEmpty($contactSections['email_template']['template_id'] ?? null);
    }

    #[Test]
    public function screenshot_gallery_and_comparison_table_are_available_components(): void
    {
        $service = app(FrontendComponentService::class);
        $this->assertNotNull($service->getById('website.screenshot_gallery'));
        $this->assertNotNull($service->getById('website.comparison_table'));
        $this->assertTrue($service->isAllowedComponentSectionKey('component:website.screenshot_gallery'));
        $this->assertTrue($service->isAllowedComponentSectionKey('component:website.comparison_table'));
    }

    #[Test]
    public function ensure_pricing_on_home_does_not_unhide_packages_component(): void
    {
        if (! Schema::hasTable('website_pages')) {
            $this->markTestSkipped('website_pages table required');
        }

        FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );

        $central = app(CentralWelcomePageService::class);
        $page = $central->ensurePageExists();
        $key = \App\Services\NexaPricingService::PACKAGES_SECTION_KEY;
        $sections = $page->getHomeSections();
        $order = is_array($sections['section_order'] ?? null) ? $sections['section_order'] : [];
        if (! in_array($key, $order, true)) {
            $order[] = $key;
            $sections['section_order'] = $order;
        }
        $sections['visibility'][$key] = false;
        $page->home_sections = $sections;
        $page->save();

        $central->ensurePricingOnHomePage($page->fresh());
        $page->refresh();
        $vis = $page->getHomeSections()['visibility'] ?? [];
        $this->assertFalse((bool) ($vis[$key] ?? true), 'Hiding price packages on the home page must survive ensurePricingOnHomePage');
    }

    #[Test]
    public function ensure_pricing_on_home_does_not_restore_removed_packages_component(): void
    {
        if (! Schema::hasTable('website_pages')) {
            $this->markTestSkipped('website_pages table required');
        }

        FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );

        $central = app(CentralWelcomePageService::class);
        $page = $central->ensurePageExists();
        $key = \App\Services\NexaPricingService::PACKAGES_SECTION_KEY;
        $sections = $page->getHomeSections();
        $sections['section_order'] = array_values(array_filter(
            is_array($sections['section_order'] ?? null) ? $sections['section_order'] : [],
            fn ($item) => $item !== $key
        ));
        unset($sections[$key]);
        $sections['removed_section_keys'] = $key;
        $page->home_sections = $sections;
        $page->save();

        $central->ensurePricingOnHomePage($page->fresh());
        $page->refresh();
        $this->assertNotContains($key, $page->getHomeSections()['section_order'] ?? []);
    }
}
