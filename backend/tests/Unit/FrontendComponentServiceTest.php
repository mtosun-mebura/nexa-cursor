<?php

namespace Tests\Unit;

use App\Services\FrontendComponentService;
use Tests\TestCase;

class FrontendComponentServiceTest extends TestCase
{
    public function test_features_card_is_not_available_as_page_component(): void
    {
        $service = app(FrontendComponentService::class);

        $this->assertNull($service->getById('website.features_card'));
        $this->assertFalse($service->isAllowedComponentSectionKey('component:website.features_card'));
        $this->assertFalse($service->isPersistableComponentSectionKey('component:website.features_card'));
        $this->assertContains(
            'component:website.features_card',
            FrontendComponentService::removedComponentSectionKeys()
        );

        $ids = $service->availableForPage(null)->pluck('id')->map(fn ($id) => strtolower((string) $id));
        $this->assertNotContains('website.features_card', $ids);
    }

    public function test_text_block_section_is_not_available_as_page_component(): void
    {
        $service = app(FrontendComponentService::class);

        $this->assertNull($service->getById('website.text_block_section'));
        $this->assertFalse($service->isAllowedComponentSectionKey('component:website.text_block_section'));
        $this->assertContains(
            'component:website.text_block_section',
            FrontendComponentService::removedComponentSectionKeys()
        );

        $ids = $service->availableForPage(null)->pluck('id')->map(fn ($id) => strtolower((string) $id));
        $this->assertNotContains('website.text_block_section', $ids);
    }

    public function test_legacy_google_reviews_key_is_persistable_and_normalizes(): void
    {
        $service = app(FrontendComponentService::class);

        $this->assertTrue($service->isPersistableComponentSectionKey('component:nexa.google_reviews'));
        $this->assertSame(
            'component:website.google_reviews',
            FrontendComponentService::normalizeComponentSectionKey('component:nexa.google_reviews')
        );
    }

    public function test_screenshot_gallery_and_comparison_table_are_page_components(): void
    {
        $service = app(FrontendComponentService::class);

        $this->assertNotNull($service->getById('website.screenshot_gallery'));
        $this->assertNotNull($service->getById('website.comparison_table'));
        $this->assertNotNull($service->getById('website.pricing_packages'));
        $ids = $service->availableForPage(null)->pluck('id')->map(fn ($id) => strtolower((string) $id));
        $this->assertContains('website.screenshot_gallery', $ids);
        $this->assertContains('website.comparison_table', $ids);
        $this->assertContains('website.pricing_packages', $ids);
    }

    public function test_theme_components_are_available_on_any_page_theme(): void
    {
        $service = app(FrontendComponentService::class);

        $this->assertNotNull($service->getById('landwind.faq'));
        $this->assertNotNull($service->getById('play.team'));
        $this->assertNotNull($service->getById('vue_material.quote_cards'));

        $withoutTheme = $service->availableForPage(null)->pluck('id')->map(fn ($id) => strtolower((string) $id));
        $this->assertContains('landwind.faq', $withoutTheme);
        $this->assertContains('play.video_spotlight', $withoutTheme);
        $this->assertContains('vue_material.elevated_cards', $withoutTheme);
        $this->assertContains('website.google_reviews', $withoutTheme);

        $landwind = $service->availableForPage(null, 'landwind')->pluck('id')->map(fn ($id) => strtolower((string) $id));
        $this->assertContains('landwind.faq', $landwind);
        $this->assertContains('landwind.trusted_by', $landwind);
        $this->assertContains('play.team', $landwind);
        $this->assertContains('vue_material.quote_cards', $landwind);
        $this->assertTrue($landwind->search('landwind.faq') < $landwind->search('play.team'));

        $play = $service->availableForPage(null, 'play-tailwind')->pluck('id')->map(fn ($id) => strtolower((string) $id));
        $this->assertContains('play.about_overlap', $play);
        $this->assertContains('landwind.faq', $play);
        $this->assertTrue($play->search('play.team') < $play->search('landwind.faq'));

        $vue = $service->availableForPage(null, 'vue-material-kit')->pluck('id')->map(fn ($id) => strtolower((string) $id));
        $this->assertContains('vue_material.stats_counters', $vue);
        $this->assertContains('play.blog_preview', $vue);
        $this->assertTrue($vue->search('vue_material.author_header') < $vue->search('play.blog_preview'));
    }
}
