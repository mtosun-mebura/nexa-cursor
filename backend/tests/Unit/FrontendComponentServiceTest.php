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
}
