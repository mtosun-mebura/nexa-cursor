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

class AdminLocaleEnBFrontendSectionsTest extends TestCase
{
    use RefreshDatabase;

    private function visibleHtml(string $html): string
    {
        return (string) preg_replace('/<!--.*?-->/s', '', $html);
    }

    private function editPageHtml(string $locale, string $pageType = 'custom'): string
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $company = Company::query()->create(['name' => 'Locale Sections Taxi', 'is_active' => true]);
        $theme = FrontendTheme::firstOrCreate(['slug' => 'modern'], ['name' => 'Metronic', 'is_active' => true]);
        $page = WebsitePage::query()->create([
            'slug' => 'locale-sections-'.$pageType,
            'title' => 'Locale sections',
            'page_type' => $pageType,
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', 'stats', 'why_nexa', 'features', 'cards_ronde_hoeken', 'featured_services', 'email_template', 'text_block', 'cta', 'carousel', 'component:taxi.tarieven', 'component:taxi.boekingsmodule', 'component:website.nexa_modules_overview', 'footer', 'copyright'],
                'visibility' => ['hero' => true, 'footer' => true],
            ],
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        return $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id, 'locale' => $locale])
            ->get(route('admin.website-pages.edit', $page))
            ->assertOk()
            ->getContent();
    }

    #[Test]
    public function website_page_sections_are_english_in_en_locale(): void
    {
        $html = $this->editPageHtml('en');

        foreach ([
            'Show/hide title', 'Highlight color', 'Subtitle width', 'Show/hide buttons', 'Button 1 text',
            'Add card', 'Services block with scroll animation.', 'Rich text block with an optional component',
            'Carousel slides (website media, stored encrypted).', 'Nexa Taxi rates', 'Taxi booking module',
            'Step labels (tabs)', 'Footer map (Google Maps)', 'Show/hide tagline', 'Quick links column title',
            'Copyright text', 'Expand section', 'Delete section', 'Hide on website',
        ] as $english) {
            $this->assertStringContainsString($english, $html, $english);
        }
        // JS strings (json-encoded) are translated as well.
        $this->assertStringContainsString('Upload failed', $html, 'JS upload message');
        $this->assertStringContainsString('Could not find a location for this input.', $html);

        $visible = $this->visibleHtml($html);
        foreach ([
            'Titel tonen/verbergen', 'Kleur benadrukking', 'Knoppen tonen/verbergen', 'Kaart toevoegen',
            'Tagline tonen/verbergen', 'Footer-kaart (Google Maps)', 'Titel kolom Snelle Links', 'Copyrighttekst',
            'Sectie verwijderen', 'Kon geen locatie vinden voor deze invoer.', 'Klik of sleep afbeeldingen',
        ] as $dutch) {
            $this->assertStringNotContainsString($dutch, $visible, $dutch);
        }

        // Default content that visitors see on the public website stays Dutch.
        $this->assertStringContainsString('Snelle Links', $visible);
        $this->assertStringContainsString('placeholder="Vind je droombaan met AI"', $visible);
    }

    #[Test]
    public function website_page_sections_stay_dutch_in_nl_locale(): void
    {
        $html = $this->editPageHtml('nl', 'home');
        $visible = $this->visibleHtml($html);

        foreach ([
            'Titel tonen/verbergen', 'Kleur benadrukking', 'Knoppen tonen/verbergen', 'Kaart toevoegen',
            'Tagline tonen/verbergen', 'Footer-kaart (Google Maps)', 'Titel kolom Snelle Links', 'Copyrighttekst',
            'Sectie verwijderen', 'Kon geen locatie vinden voor deze invoer.', 'Klik of sleep afbeeldingen',
            'Verbergen op website',
        ] as $dutch) {
            $this->assertStringContainsString($dutch, $visible, $dutch);
        }
        foreach (['Show/hide title', 'Highlight color', 'Add card'] as $english) {
            $this->assertStringNotContainsString($english, $visible, $english);
        }
    }
}
