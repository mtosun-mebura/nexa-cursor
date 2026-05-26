<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\WebsitePage;
use App\Services\GoogleReviewsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleReviewsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        if (! Schema::hasTable('general_settings') || ! Schema::hasTable('companies')) {
            return;
        }
        $cid = (int) DB::table('companies')->orderBy('id')->value('id');
        if ($cid < 1) {
            return;
        }
        app()->instance('resolved_tenant_id', $cid);
        GeneralSetting::set('google_reviews_place_id', '', $cid);
        GeneralSetting::set('google_reviews_business_name', '', $cid);
    }

    #[Test]
    public function resolve_company_id_prefers_page_company_over_tenant(): void
    {
        if (! Schema::hasTable('companies')) {
            $this->markTestSkipped('companies table required');
        }
        $pageCompany = Company::create(['name' => 'Page Co', 'is_active' => true]);
        $tenantCompany = Company::create(['name' => 'Tenant Co', 'is_active' => true]);
        app()->instance('resolved_tenant_id', (int) $tenantCompany->id);

        $page = new WebsitePage(['company_id' => $pageCompany->id]);

        $this->assertSame(
            (int) $pageCompany->id,
            GoogleReviewsService::resolveCompanyIdForWebsitePage($page)
        );
    }

    #[Test]
    public function get_reviews_returns_empty_structure_when_no_place_or_business_configured(): void
    {
        if (! Schema::hasTable('general_settings')) {
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
        $this->assertArrayHasKey('section_title', $result);
        $this->assertArrayHasKey('section_background', $result);
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
        if (! Schema::hasTable('general_settings')) {
            $this->markTestSkipped('general_settings table required');
        }
        $service = app(GoogleReviewsService::class);
        $result = $service->getPlaceAndReviewsUnfiltered();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('reviews', $result);
        $this->assertIsArray($result['reviews']);
        $this->assertCount(0, $result['reviews']);
        $this->assertArrayHasKey('section_title', $result);
        $this->assertArrayHasKey('section_background', $result);
    }

    #[Test]
    public function normalize_hex_color_accepts_three_and_six_digit_hex(): void
    {
        $this->assertSame('#ffaabb', GoogleReviewsService::normalizeHexColor('#FaB'));
        $this->assertSame('#aabbcc', GoogleReviewsService::normalizeHexColor('abc'));
        $this->assertSame('', GoogleReviewsService::normalizeHexColor('not-a-color'));
        $this->assertSame('', GoogleReviewsService::normalizeHexColor(''));
    }

    #[Test]
    public function persist_settings_for_company_stores_section_title(): void
    {
        if (! Schema::hasTable('general_settings')) {
            $this->markTestSkipped('general_settings table required');
        }
        $cid = (int) DB::table('companies')->orderBy('id')->value('id');
        if ($cid < 1) {
            $this->markTestSkipped('company required');
        }

        app(GoogleReviewsService::class)->persistSettingsForCompany($cid, [
            'section_title' => 'Onze klanten',
        ]);

        $this->assertSame('Onze klanten', GeneralSetting::get('google_reviews_section_title', '', $cid));

        GeneralSetting::set('google_reviews_section_title', '', $cid);
    }

    #[Test]
    public function get_reviews_returns_empty_when_places_api_unreachable(): void
    {
        if (! Schema::hasTable('general_settings')) {
            $this->markTestSkipped('general_settings table required');
        }
        $cid = (int) DB::table('companies')->orderBy('id')->value('id');
        if ($cid < 1) {
            $this->markTestSkipped('company required');
        }

        GeneralSetting::set('google_reviews_place_id', 'ChIJ71_DnQAUuEcR6FcDgn8_Jww', $cid);
        config(['maps.api_key' => 'test-api-key']);

        Http::fake(function () {
            throw new ConnectionException('Could not resolve host: places.googleapis.com');
        });

        $result = app(GoogleReviewsService::class)->getReviews($cid);

        $this->assertSame('', $result['place_name']);
        $this->assertSame([], $result['reviews']);
        $this->assertSame('ChIJ71_DnQAUuEcR6FcDgn8_Jww', $result['place_id']);

        GeneralSetting::set('google_reviews_place_id', '', $cid);
        config(['maps.api_key' => null]);
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

    #[Test]
    public function persist_settings_for_company_stores_per_tenant_place_id(): void
    {
        if (! Schema::hasTable('general_settings')) {
            $this->markTestSkipped('general_settings table required');
        }
        $cid = (int) DB::table('companies')->orderBy('id')->value('id');
        if ($cid < 1) {
            $this->markTestSkipped('company required');
        }

        app(GoogleReviewsService::class)->persistSettingsForCompany($cid, [
            'place_id' => 'ChIJ_test_place_id_1234567890',
            'business_name' => 'Test Bedrijf',
        ]);

        $this->assertSame(
            'ChIJ_test_place_id_1234567890',
            GeneralSetting::get('google_reviews_place_id', '', $cid)
        );
        $this->assertSame('Test Bedrijf', GeneralSetting::get('google_reviews_business_name', '', $cid));

        GeneralSetting::set('google_reviews_place_id', '', $cid);
        GeneralSetting::set('google_reviews_business_name', '', $cid);
    }
}
