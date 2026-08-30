<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Services\EnvService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnvServiceMailFallbackTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function tenant_without_mail_settings_uses_nexa_saas_mail_server(): void
    {
        $tenant = Company::query()->create(['name' => 'Mail Fallback Tenant', 'is_active' => true]);

        GeneralSetting::set('MAIL_MAILER', 'smtp', null);
        GeneralSetting::set('MAIL_HOST', 'smtp.nexa.test', null);
        GeneralSetting::set('MAIL_FROM_ADDRESS', 'noreply@nexa.test', null);
        GeneralSetting::set('MAIL_FROM_NAME', 'NEXA Suite', null);

        $mail = app(EnvService::class)->getMailOverlayValues((int) $tenant->id);

        $this->assertSame('smtp', $mail['MAIL_MAILER']);
        $this->assertSame('smtp.nexa.test', $mail['MAIL_HOST']);
        $this->assertSame('noreply@nexa.test', $mail['MAIL_FROM_ADDRESS']);
    }

    #[Test]
    public function tenant_mail_settings_override_nexa_saas_mail_server(): void
    {
        $tenant = Company::query()->create(['name' => 'Mail Override Tenant', 'is_active' => true]);

        GeneralSetting::set('MAIL_MAILER', 'smtp', null);
        GeneralSetting::set('MAIL_HOST', 'smtp.nexa.test', null);
        GeneralSetting::set('MAIL_HOST', 'smtp.tenant.test', (int) $tenant->id);

        $mail = app(EnvService::class)->getMailOverlayValues((int) $tenant->id);

        $this->assertSame('smtp.tenant.test', $mail['MAIL_HOST']);
        $this->assertSame('smtp', $mail['MAIL_MAILER']);
    }

    #[Test]
    public function platform_only_mail_ignores_tenant_smtp(): void
    {
        $tenant = Company::query()->create(['name' => 'Mail Platform Only Tenant', 'is_active' => true]);

        GeneralSetting::set('MAIL_MAILER', 'smtp', null);
        GeneralSetting::set('MAIL_HOST', 'smtp.nexa.test', null);
        GeneralSetting::set('MAIL_HOST', 'smtp.tenant.test', (int) $tenant->id);

        $mail = app(EnvService::class)->getMailOverlayValues((int) $tenant->id, true);

        $this->assertSame('smtp.nexa.test', $mail['MAIL_HOST']);
    }

    #[Test]
    public function apply_mail_config_keeps_phpunit_array_mailer(): void
    {
        GeneralSetting::set('MAIL_MAILER', 'smtp', null);
        GeneralSetting::set('MAIL_HOST', 'smtp.nexa.test', null);

        app(EnvService::class)->applyMailConfigToRuntime();

        $this->assertSame('array', config('mail.default'));
    }
}
