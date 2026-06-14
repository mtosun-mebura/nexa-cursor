<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Module;
use App\Services\ModuleConfigurationService;
use App\Services\WebsiteBuilderService;
use Tests\TestCase;

class ModuleConfigurationServiceTest extends TestCase
{
    public function test_tenant_settings_override_global_module_defaults(): void
    {
        $module = Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'app_name' => 'Globaal Taxi',
                'dashboard_link_visible' => '0',
                'dashboard_link_label' => 'Globaal label',
            ],
        ]);

        $company = Company::create([
            'name' => 'Tenant',
            'slug' => 'tenant-'.uniqid(),
            'email' => 't-'.uniqid().'@example.com',
            'is_active' => true,
        ]);
        $company->modules()->attach($module->id, [
            'settings' => json_encode([
                'dashboard_link_visible' => '1',
                'dashboard_link_label' => 'Tenant label',
            ]),
        ]);

        $service = app(ModuleConfigurationService::class);
        $config = $service->getConfiguration($module, $company->id);

        $this->assertSame('Globaal Taxi', $config['app_name']);
        $this->assertSame('1', $config['dashboard_link_visible']);
        $this->assertSame('Tenant label', $config['dashboard_link_label']);
    }

    public function test_website_branding_uses_tenant_module_configuration(): void
    {
        $module = Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'dashboard_link_visible' => '0',
            ],
        ]);

        $company = Company::create([
            'name' => 'Tenant branding',
            'slug' => 'tenant-brand-'.uniqid(),
            'email' => 'brand-'.uniqid().'@example.com',
            'is_active' => true,
        ]);
        $company->modules()->attach($module->id, [
            'settings' => json_encode([
                'dashboard_link_visible' => '1',
                'dashboard_link_label' => 'Mijn Tenant Taxi',
                'app_name' => 'Tenant Taxi Site',
            ]),
        ]);

        $branding = app(WebsiteBuilderService::class)->getSiteBranding('taxi', false, $company->id);

        $this->assertTrue($branding['dashboard_link_visible']);
        $this->assertSame('Mijn Tenant Taxi', $branding['dashboard_link_label']);
        $this->assertSame('Tenant Taxi Site', $branding['site_name']);
    }
}
