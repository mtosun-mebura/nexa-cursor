<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Services\UserRoleAssignmentService;
use App\Support\MailSmtpProviderCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminMailSmtpProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function settings_page_shows_smtp_provider_select_with_hostinger_and_one_com_first(): void
    {
        $admin = $this->companyAdmin();

        $html = $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('id="MAIL_SMTP_PROVIDER"', false)
            ->assertSee('Hostinger', false)
            ->assertSee('One.com', false)
            ->assertSee('smtp.hostinger.com', false)
            ->assertSee('send.one.com', false)
            ->getContent();

        $hostingerPos = strpos($html, '>Hostinger<');
        $oneComPos = strpos($html, '>One.com<');
        $gmailPos = strpos($html, '>Gmail / Google Workspace<');
        $this->assertNotFalse($hostingerPos);
        $this->assertNotFalse($oneComPos);
        $this->assertNotFalse($gmailPos);
        $this->assertLessThan($oneComPos, $hostingerPos);
        $this->assertLessThan($gmailPos, $oneComPos);
    }

    #[Test]
    public function saving_unknown_smtp_host_adds_custom_provider_for_tenant(): void
    {
        $admin = $this->companyAdmin();

        $this->actingAs($admin)
            ->post(route('admin.settings.mail.update'), [
                'MAIL_MAILER' => 'smtp',
                'MAIL_SMTP_PROVIDER' => MailSmtpProviderCatalog::MANUAL_ID,
                'MAIL_SMTP_PROVIDER_NAME' => 'Eigen VPS',
                'MAIL_HOST' => 'smtp.eigen-vps.test',
                'MAIL_PORT' => '2525',
                'MAIL_ENCRYPTION' => 'tls',
                'MAIL_USERNAME' => 'user@eigen-vps.test',
                'MAIL_PASSWORD' => 'secret',
                'MAIL_FROM_ADDRESS' => 'info@eigen-vps.test',
                'MAIL_FROM_NAME' => 'Eigen VPS Mail',
            ])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHas('success');

        $customs = MailSmtpProviderCatalog::customProviders($admin->company_id);
        $this->assertCount(1, $customs);
        $this->assertSame('smtp.eigen-vps.test', $customs[0]['host']);
        $this->assertSame(2525, $customs[0]['port']);
        $this->assertSame('tls', $customs[0]['encryption']);
        $this->assertSame('Eigen VPS', $customs[0]['name']);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Eigen VPS', false)
            ->assertSee('smtp.eigen-vps.test', false);
    }

    #[Test]
    public function choosing_hostinger_preset_is_remembered_on_save(): void
    {
        $admin = $this->companyAdmin();
        $hostinger = collect(MailSmtpProviderCatalog::builtins())->firstWhere('id', 'hostinger');
        $this->assertNotNull($hostinger);

        $this->actingAs($admin)
            ->post(route('admin.settings.mail.update'), [
                'MAIL_MAILER' => 'smtp',
                'MAIL_SMTP_PROVIDER' => 'hostinger',
                'MAIL_HOST' => $hostinger['host'],
                'MAIL_PORT' => $hostinger['port'],
                'MAIL_ENCRYPTION' => $hostinger['encryption'],
                'MAIL_USERNAME' => 'info@example.com',
                'MAIL_PASSWORD' => 'secret',
                'MAIL_FROM_ADDRESS' => 'info@example.com',
                'MAIL_FROM_NAME' => 'Example',
            ])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertSame(
            'hostinger',
            GeneralSetting::get(MailSmtpProviderCatalog::SELECTED_SETTING_KEY, '', $admin->company_id)
        );
        $this->assertSame(
            'smtp.hostinger.com',
            GeneralSetting::get('MAIL_HOST', '', $admin->company_id)
        );
    }

    #[Test]
    public function test_email_uses_from_address_and_name_from_request(): void
    {
        $admin = $this->companyAdmin();

        GeneralSetting::set('MAIL_MAILER', 'log', $admin->company_id);
        GeneralSetting::set('MAIL_FROM_ADDRESS', 'saved@example.com', $admin->company_id);
        GeneralSetting::set('MAIL_FROM_NAME', 'Saved Name', $admin->company_id);

        $captured = ['address' => null, 'name' => null, 'to' => null];
        \Illuminate\Support\Facades\Mail::shouldReceive('raw')
            ->once()
            ->andReturnUsing(function (string $text, $callback) use (&$captured) {
                $email = new \Symfony\Component\Mime\Email;
                $message = new \Illuminate\Mail\Message($email);
                $callback($message);
                $from = $email->getFrom();
                $to = $email->getTo();
                $captured['address'] = $from[0]->getAddress() ?? null;
                $captured['name'] = $from[0]->getName() ?? null;
                $captured['to'] = $to[0]->getAddress() ?? null;

                return true;
            });

        $this->actingAs($admin)
            ->postJson(route('admin.settings.mail.test'), [
                'test_email' => 'recipient@example.com',
                'from_address' => 'info@nexasuite.nl',
                'from_name' => 'NEXA Suite',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('info@nexasuite.nl', $captured['address']);
        $this->assertSame('NEXA Suite', $captured['name']);
        $this->assertSame('recipient@example.com', $captured['to']);
    }

    private function companyAdmin(): User
    {
        $company = Company::query()->create(['name' => 'Taxi SMTP BV', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        return $user->fresh();
    }
}
