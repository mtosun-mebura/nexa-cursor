<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Models\WebsitePage;
use App\Services\GoogleReviewsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebsitePageGoogleReviewsFrontendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    #[Test]
    #[Group('website-pages')]
    public function preview_shows_google_reviews_when_component_in_section_order(): void
    {
        if (! Schema::hasTable('general_settings')) {
            $this->markTestSkipped('general_settings table required');
        }

        $company = Company::create(['name' => 'Reviews Frontend BV', 'is_active' => true]);

        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );
        FrontendTheme::query()->update(['is_active' => false]);
        $theme->update(['is_active' => true]);

        $componentKey = 'component:website.google_reviews';
        $page = WebsitePage::create([
            'slug' => 'home-grw-frontend',
            'title' => 'Home GRW',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', $componentKey],
                'visibility' => ['hero' => true, $componentKey => true],
                'hero' => ['title' => 'Welkom'],
                'footer' => [],
                'copyright' => '',
            ],
        ]);

        GeneralSetting::set('google_reviews_place_id', 'ChIJ_frontend_test_place_id_12', $company->id);

        $this->mock(GoogleReviewsService::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getReviews')
                ->once()
                ->with($company->id)
                ->andReturn([
                    'place_name' => 'Test Taxi',
                    'rating' => 4.8,
                    'user_rating_count' => 42,
                    'place_id' => 'ChIJ_frontend_test_place_id_12',
                    'reviews' => [
                        [
                            'author_name' => 'Jan Tester',
                            'rating' => 5,
                            'text' => 'Geweldige rit, zeer vriendelijke chauffeur.',
                            'time' => '1 week geleden',
                            'profile_photo_url' => null,
                        ],
                    ],
                    'write_review_url' => 'https://search.google.com/local/writereview?placeid=ChIJ_frontend_test_place_id_12',
                ]);
        });

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->get(route('admin.website-pages.preview', $page));

        $response->assertOk();
        $response->assertSee('Geweldige rit, zeer vriendelijke chauffeur.', false);
        $response->assertSee('Jan Tester', false);
        $response->assertSee('Test Taxi', false);
    }
}
