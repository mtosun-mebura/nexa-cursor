<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\CoolifyVpsPublicIp;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTenantSetupChecklistTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function super_admin_can_open_tenant_setup_checklist(): void
    {
        $company = Company::query()->create(['name' => 'Checklist Co', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        CoolifyVpsPublicIp::set('152.239.119.238');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.tenant-setup-checklist'))
            ->assertOk()
            ->assertSee('Tenant configureren', false)
            ->assertSee('Nieuwe tenant-wizard', false)
            ->assertSee(route('admin.companies.wizard.start'), false)
            ->assertSee('152.239.119.238', false)
            ->assertSee('checklistco.nl', false)
            ->assertSee('DNS A-records bij de registrar', false)
            ->assertSee('MX-records', false)
            ->assertSee('Domeinen toevoegen in Coolify', false)
            ->assertSee(route('admin.companies.show', $company).'#company-domains', false)
            ->assertSee('WhatsApp-contactnummer van de tenant', false)
            ->assertSee('Configuraties → WhatsApp (tenant)', false)
            ->assertSee('from=tenant-setup', false)
            ->assertSee('open=whatsapp', false)
            ->assertDontSee('Widget telefoonnummer', false)
            ->assertSee(route('admin.settings.index'), false);
    }

    #[Test]
    public function checklist_domain_example_strips_spaces_from_selected_tenant_name(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Nexa', 'slug' => 'taxi-nexa', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.tenant-setup-checklist'))
            ->assertOk()
            ->assertSee('taxinexa.nl', false)
            ->assertSee('www.taxinexa.nl', false)
            ->assertSee('taxi-nexa.nexasuite.nl', false)
            ->assertDontSee('royaaltaxi.nl', false);
    }

    #[Test]
    public function company_admin_cannot_open_tenant_setup_checklist(): void
    {
        $company = Company::query()->create(['name' => 'Checklist Tenant', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company-admin');

        $this->actingAs($user)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.tenant-setup-checklist'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    #[Test]
    public function sidebar_shows_checklist_link_for_super_admin_only(): void
    {
        $company = Company::query()->create(['name' => 'Sidebar Co', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.tenant-setup-checklist'), false)
            ->assertSee('Tenant configureren', false);

        $tenantAdmin = User::factory()->create(['company_id' => $company->id]);
        $tenantAdmin->assignRole('company-admin');

        $this->actingAs($tenantAdmin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.tenant-setup-checklist'), false);
    }

    #[Test]
    public function super_admin_can_update_vps_ip_on_upgrade_page(): void
    {
        $company = Company::query()->create(['name' => 'Upgrade Co', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.settings.upgrade.index'))
            ->assertOk()
            ->assertSee('Coolify VPS-IP', false)
            ->assertSee('coolify_vps_public_ip', false);

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->post(route('admin.settings.upgrade.vps-ip.update'), [
                'coolify_vps_public_ip' => '152.239.119.238',
            ])
            ->assertRedirect(route('admin.settings.upgrade.index', ['saved' => 1]));

        $this->assertSame('152.239.119.238', CoolifyVpsPublicIp::get());

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.tenant-setup-checklist'))
            ->assertOk()
            ->assertSee('152.239.119.238', false);
    }

    #[Test]
    public function settings_from_tenant_setup_shows_back_button_on_whatsapp(): void
    {
        $company = Company::query()->create(['name' => 'Back Nav Co', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.settings.index', ['from' => 'tenant-setup', 'open' => 'whatsapp']))
            ->assertOk()
            ->assertSee('Terug naar Tenant configureren', false)
            ->assertSee(route('admin.tenant-setup-checklist'), false)
            ->assertSee('ki-arrow-left', false);
    }

    #[Test]
    public function whatsapp_save_from_settings_still_works_with_return_to_checklist(): void
    {
        $company = Company::query()->create(['name' => 'WhatsApp Co', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->post(route('admin.settings.whatsapp.update'), [
                'WHATSAPP_CLICK_TO_CHAT_ENABLED' => '0',
                'WHATSAPP_CLICK_TO_CHAT_NUMBER' => '',
                'WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER' => '0612345678',
                'WHATSAPP_WIDGET_ENABLED' => '1',
                'WHATSAPP_WIDGET_PHONE' => '0687654321',
                'WHATSAPP_WIDGET_DEFAULT_MESSAGE' => 'Hallo vanaf de site',
                'return_to' => route('admin.tenant-setup-checklist'),
            ])
            ->assertRedirect(route('admin.tenant-setup-checklist'))
            ->assertSessionHas('success');

        $this->assertSame(
            '+31687654321',
            \App\Models\GeneralSetting::get('WHATSAPP_WIDGET_PHONE', '', $company->id)
        );
    }
}
