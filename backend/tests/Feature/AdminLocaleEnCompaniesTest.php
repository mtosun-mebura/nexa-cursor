<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleEnCompaniesTest extends TestCase
{
    private ?string $mergedLangDir = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Fragmenten worden pas door de coordinator in en.json samengevoegd: laad ze hier mee.
        $lines = json_decode((string) file_get_contents(lang_path('en.json')), true) ?: [];
        foreach (glob(lang_path('_fragments/*.json')) ?: [] as $fragment) {
            $lines = array_merge($lines, json_decode((string) file_get_contents($fragment), true) ?: []);
        }
        $this->mergedLangDir = sys_get_temp_dir().'/nexa-lang-companies-'.uniqid();
        mkdir($this->mergedLangDir);
        file_put_contents($this->mergedLangDir.'/en.json', json_encode($lines, JSON_UNESCAPED_UNICODE));
        app('translator')->addJsonPath($this->mergedLangDir);
    }

    protected function tearDown(): void
    {
        if ($this->mergedLangDir) {
            @unlink($this->mergedLangDir.'/en.json');
            @rmdir($this->mergedLangDir);
        }

        parent::tearDown();
    }

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    private function localeSession(string $locale, ?Company $tenant = null): array
    {
        $tenant ??= Company::query()->firstOrCreate(['name' => 'Locale Taxi'], ['is_active' => true]);

        return ['selected_tenant' => $tenant->id, 'locale' => $locale];
    }

    private function company(): Company
    {
        $company = Company::query()->create([
            'name' => 'Locale Companies Taxi',
            'is_active' => true,
            'package_key' => 'business',
            'kvk_number' => '12345678',
            'industry' => 'Taxi',
            'street' => 'Annastraat',
            'house_number' => '10',
            'postal_code' => '7543TP',
            'city' => 'Enschede',
            'email' => 'info@example.com',
            'phone' => '0531234567',
        ]);
        CompanyLocation::query()->create([
            'company_id' => $company->id,
            'name' => 'Station',
            'is_active' => true,
        ]);

        return $company;
    }

    #[Test]
    public function companies_index_is_english_in_en_locale(): void
    {
        $this->company();

        $html = $this->actingAs($this->superAdmin())
            ->withSession($this->localeSession('en'))
            ->get(route('admin.companies.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame('en', app()->getLocale());
        $this->assertStringContainsString('Company management', $html);
        $this->assertStringContainsString('New tenant (wizard)', $html);
        $this->assertStringContainsString('New company', $html);
        $this->assertStringContainsString('Search companies...', $html);
        $this->assertMatchesRegularExpression('/Showing 1 to \d+ of \d+ companies/', $html);
        $this->assertStringContainsString('Are you sure you want to delete this company?', $html);
        $this->assertStringNotContainsString('Bedrijven Beheer', $html);
        $this->assertStringNotContainsString('Nieuw bedrijf', $html);
        $this->assertStringNotContainsString('Zoek bedrijven...', $html);
        $this->assertStringNotContainsString('Weet je zeker', $html);
    }

    #[Test]
    public function company_show_is_english_in_en_locale(): void
    {
        $company = $this->company();

        $html = $this->actingAs($this->superAdmin())
            ->withSession($this->localeSession('en', $company))
            ->get(route('admin.companies.show', $company))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Company information', $html);
        $this->assertStringContainsString('Sections on this page', $html);
        $this->assertStringContainsString('Wizard step not completed yet', $html);
        $this->assertStringContainsString('Tenant domains (SaaS)', $html);
        $this->assertStringContainsString('No domains linked yet.', $html);
        $this->assertStringContainsString('Access to configurations', $html);
        $this->assertStringContainsString('Add domain', $html);
        $this->assertStringNotContainsString('kt-tab-title">Bedrijfsinformatie', $html);
        $this->assertStringNotContainsString('>Contact Informatie<', $html);
        $this->assertStringNotContainsString('Onderdelen op deze pagina', $html);
        $this->assertStringNotContainsString('Wizardstap nog niet afgerond', $html);
        $this->assertStringNotContainsString('Nog geen domeinen gekoppeld.', $html);
        $this->assertStringNotContainsString('Domein toevoegen', $html);
    }

    #[Test]
    public function company_create_and_wizard_step_are_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();

        $create = $this->actingAs($admin)
            ->withSession($this->localeSession('en'))
            ->get(route('admin.companies.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('New company', $create);
        $this->assertStringContainsString('General information', $create);
        $this->assertStringContainsString('Add extra location', $create);
        $this->assertStringContainsString('Save company', $create);
        $this->assertStringNotContainsString('Algemene Informatie', $create);
        $this->assertStringNotContainsString('Extra Vestiging Toevoegen', $create);
        $this->assertStringNotContainsString('Bedrijf Opslaan', $create);

        $company = $this->company();
        $wizard = $this->actingAs($admin)
            ->withSession($this->localeSession('en', $company))
            ->get(route('admin.companies.wizard.step', [$company, 3]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Tenant onboarding', $wizard);
        $this->assertStringContainsString('Step plan', $wizard);
        $this->assertStringContainsString('Tenant domain', $wizard);
        $this->assertStringNotContainsString('Stappenplan', $wizard);
        $this->assertStringNotContainsString('Tenant-domein', $wizard);
    }

    #[Test]
    public function company_pages_stay_dutch_in_nl_locale(): void
    {
        $company = $this->company();
        $admin = $this->superAdmin();

        $index = $this->actingAs($admin)
            ->withSession($this->localeSession('nl'))
            ->get(route('admin.companies.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Bedrijven Beheer', $index);
        $this->assertStringContainsString('Nieuw bedrijf', $index);
        $this->assertStringContainsString('Weet je zeker dat je dit bedrijf wilt verwijderen?', $index);
        $this->assertStringNotContainsString('Company management', $index);

        $show = $this->actingAs($admin)
            ->withSession($this->localeSession('nl', $company))
            ->get(route('admin.companies.show', $company))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Bedrijfsinformatie', $show);
        $this->assertStringContainsString('Tenant domeinen (SaaS)', $show);
        $this->assertStringContainsString('Nog geen domeinen gekoppeld.', $show);
        $this->assertStringContainsString('Wizardstap nog niet afgerond', $show);
        $this->assertStringNotContainsString('Company information', $show);
    }

    #[Test]
    public function company_flash_messages_are_translated_in_en_locale(): void
    {
        $company = $this->company();

        $this->actingAs($this->superAdmin())
            ->withSession($this->localeSession('en', $company))
            ->post(route('admin.companies.toggle-status', $company))
            ->assertRedirect(route('admin.companies.index'))
            ->assertSessionHas('success', "Company 'Locale Companies Taxi' was deactivated successfully.");

        $this->actingAs($this->superAdmin())
            ->withSession($this->localeSession('nl', $company))
            ->post(route('admin.companies.toggle-status', $company))
            ->assertSessionHas('success', "Bedrijf 'Locale Companies Taxi' is succesvol geactiveerd.");
    }
}
