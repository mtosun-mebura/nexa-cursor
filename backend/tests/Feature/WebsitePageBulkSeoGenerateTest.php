<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\User;
use App\Models\WebsitePage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebsitePageBulkSeoGenerateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        config()->set('services.openai.api_key', '');
        Http::fake();
    }

    #[Test]
    public function bulk_seo_generate_updates_all_listed_central_pages(): void
    {
        if (! Schema::hasTable('website_pages')) {
            $this->markTestSkipped('website_pages table required');
        }

        $theme = FrontendTheme::query()->firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );

        $home = WebsitePage::query()->create([
            'slug' => 'home-seo-'.uniqid(),
            'title' => 'Home',
            'menu_title' => 'Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => Schema::hasColumn('website_pages', 'company_id') ? null : null,
            'is_active' => true,
            'sort_order' => 0,
            'meta_description' => '',
            'home_sections' => [
                'section_order' => ['hero', 'cta'],
                'visibility' => ['hero' => true, 'cta' => true],
                'hero' => [
                    'title' => 'Oude hero',
                    'subtitle' => 'Oude ondertitel',
                    'cta_primary_text' => 'Ga',
                    'cta_secondary_text' => 'Meer',
                ],
                'cta' => ['title' => 'Contact'],
            ],
        ]);

        $contact = WebsitePage::query()->create([
            'slug' => 'contact-seo-'.uniqid(),
            'title' => 'Contact',
            'menu_title' => 'Contact',
            'page_type' => 'contact',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => Schema::hasColumn('website_pages', 'company_id') ? null : null,
            'is_active' => true,
            'sort_order' => 1,
            'meta_description' => '',
        ]);

        if (Schema::hasColumn('website_pages', 'company_id')) {
            $tenant = Company::query()->create(['name' => 'Andere tenant', 'slug' => 'andere-tenant-'.uniqid()]);
            WebsitePage::query()->create([
                'slug' => 'tenant-hidden-'.uniqid(),
                'title' => 'Tenant pagina',
                'page_type' => 'custom',
                'frontend_theme_id' => $theme->id,
                'module_name' => null,
                'company_id' => $tenant->id,
                'is_active' => true,
                'sort_order' => 0,
                'meta_description' => 'Niet aanraken',
            ]);
        }

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->post(route('admin.website-pages.generate-seo-all'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $home->refresh();
        $contact->refresh();

        $this->assertNotSame('', (string) $home->meta_description);
        $this->assertGreaterThanOrEqual(150, mb_strlen((string) $home->meta_description));
        $this->assertLessThanOrEqual(160, mb_strlen((string) $home->meta_description));
        $this->assertSame('Neem contact op', $home->home_sections['hero']['cta_primary_text'] ?? null);
        $this->assertSame('Contact', $home->home_sections['cta']['title'] ?? null);

        $this->assertSame('Home', (string) $home->menu_title);
        $this->assertSame('Contact', (string) $contact->menu_title);
        $this->assertSame('Home', $home->publicNavLabel());
        $this->assertSame('Contact', $contact->publicNavLabel());

        if (Schema::hasColumn('website_pages', 'company_id')) {
            $this->assertSame(
                'Niet aanraken',
                WebsitePage::query()->where('title', 'Tenant pagina')->value('meta_description')
            );
        }
    }

    #[Test]
    public function bulk_seo_generate_requires_super_admin(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('admin.website-pages.generate-seo-all'));
        $this->assertTrue(in_array($response->status(), [302, 403], true));
    }
}
