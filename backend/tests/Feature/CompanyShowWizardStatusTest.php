<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyDomain;
use App\Models\CompanyLocation;
use App\Models\User;
use App\Support\CompanyShowWizardStatus;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyShowWizardStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function company_info_and_contact_follow_wizard_required_fields(): void
    {
        $company = Company::query()->create([
            'name' => 'Wizard Status Taxi',
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
            'contact_first_name' => 'Ada',
            'contact_last_name' => 'Tosun',
            'building_image' => 1,
        ]);

        $this->assertTrue(CompanyShowWizardStatus::isComplete($company, 'company-info'));
        $this->assertTrue(CompanyShowWizardStatus::isComplete($company, 'company-contact'));
        $this->assertFalse(CompanyShowWizardStatus::isComplete($company, 'company-locations'));
        $this->assertFalse(CompanyShowWizardStatus::isComplete($company, 'company-domains'));
        $this->assertFalse(CompanyShowWizardStatus::isComplete($company, 'company-modules'));
        $this->assertFalse(CompanyShowWizardStatus::isComplete($company, 'company-users-website'));
        $this->assertFalse(CompanyShowWizardStatus::isComplete($company, 'config-access'));

        CompanyLocation::query()->create([
            'company_id' => $company->id,
            'name' => 'Hoofdkantoor',
            'is_active' => true,
        ]);
        CompanyDomain::query()->create([
            'company_id' => $company->id,
            'host' => 'wizardstatus.nexasuite.nl',
            'is_primary' => true,
        ]);
        User::factory()->create([
            'company_id' => $company->id,
            'email' => 'info@example.com',
        ]);
        $company->unsetRelation('locations');
        $company->unsetRelation('domains');
        $company->unsetRelation('users');

        $this->assertTrue(CompanyShowWizardStatus::isComplete($company, 'company-locations'));
        $this->assertTrue(CompanyShowWizardStatus::isComplete($company, 'company-domains'));
        $this->assertTrue(CompanyShowWizardStatus::isComplete($company, 'company-users-website'));
        $this->assertTrue(CompanyShowWizardStatus::isComplete($company, 'config-access'));
    }

    #[Test]
    public function company_show_marks_incomplete_wizard_tabs_and_opens_building_lightbox(): void
    {
        $company = Company::query()->create([
            'name' => 'Lightbox Taxi',
            'is_active' => true,
            'package_key' => 'business',
            'kvk_number' => '87654321',
            'industry' => 'Taxi',
            'street' => 'Annastraat',
            'house_number' => '10',
            'postal_code' => '7543TP',
            'city' => 'Enschede',
            'building_image' => 1,
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $html = $this->actingAs($admin)
            ->get(route('admin.companies.show', $company))
            ->assertOk()
            ->assertSee('data-wizard-complete="1"', false)
            ->assertSee('data-wizard-complete="0"', false)
            ->assertSee('data-company-show-tab="company-locations"', false)
            ->assertSee('Wizardstap nog niet afgerond', false)
            ->assertSee('data-building-lightbox-open', false)
            ->assertSee('company-building-lightbox', false)
            ->assertSee('Oranje gevel', false)
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/data-company-show-tab="company-locations"[^>]*data-wizard-complete="0"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-company-show-tab="company-info"[^>]*data-wizard-complete="1"/',
            $html
        );
    }
}
