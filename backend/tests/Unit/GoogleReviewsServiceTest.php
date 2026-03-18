<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use App\Models\GeneralSetting;
use App\Services\GoogleReviewsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GoogleReviewsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        if (Schema::hasTable('general_settings')) {
            GeneralSetting::set('google_reviews_place_id', '');
            GeneralSetting::set('google_reviews_business_name', '');
        }
    }

    #[Test]
    public function get_reviews_returns_empty_structure_when_no_place_or_business_configured(): void
    {
        if (!Schema::hasTable('general_settings')) {
            $this->markTestSkipped('general_settings table required');
        }
        $service = app(GoogleReviewsService::class);
        $result = $service->getReviews();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('place_name', $result);
        $this->assertArrayHasKey('rating', $result);
        $this->assertArrayHasKey('user_rating_count', $result);
        $this->assertArrayHasKey('place_id', $result);
        $this->assertArrayHasKey('reviews', $result);
        $this->assertArrayHasKey('write_review_url', $result);
        $this->assertSame('', $result['place_name']);
        $this->assertSame(0.0, $result['rating']);
        $this->assertSame(0, $result['user_rating_count']);
        $this->assertSame('', $result['place_id']);
        $this->assertIsArray($result['reviews']);
        $this->assertCount(0, $result['reviews']);
    }

    #[Test]
    public function get_place_and_reviews_unfiltered_returns_empty_structure_when_no_place_configured(): void
    {
        if (!Schema::hasTable('general_settings')) {
            $this->markTestSkipped('general_settings table required');
        }
        $service = app(GoogleReviewsService::class);
        $result = $service->getPlaceAndReviewsUnfiltered();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('reviews', $result);
        $this->assertIsArray($result['reviews']);
        $this->assertCount(0, $result['reviews']);
    }

    #[Test]
    public function normalize_place_id_strips_places_prefix(): void
    {
        $this->assertSame('ChIJ123', GoogleReviewsService::normalizePlaceId('places/ChIJ123'));
        $this->assertSame('ChIJ123', GoogleReviewsService::normalizePlaceId('ChIJ123'));
    }

    #[Test]
    public function looks_like_place_id_accepts_typical_google_place_ids(): void
    {
        $this->assertTrue(GoogleReviewsService::looksLikePlaceId('ChIJ71_DnQAUuEcR6FcDgn8_Jww'));
        $this->assertTrue(GoogleReviewsService::looksLikePlaceId('places/ChIJ71_DnQAUuEcR6FcDgn8_Jww'));
        $this->assertFalse(GoogleReviewsService::looksLikePlaceId(''));
        $this->assertFalse(GoogleReviewsService::looksLikePlaceId('short'));
    }
}
