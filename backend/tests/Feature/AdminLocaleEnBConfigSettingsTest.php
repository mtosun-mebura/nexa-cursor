<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleEnBConfigSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    private function visibleHtml(string $html): string
    {
        $html = (string) preg_replace('/<!--.*?-->/s', '', $html);

        return (string) preg_replace('/<script\b[^>]*>.*?<\/script>/s', '', $html);
    }

    #[Test]
    public function settings_index_mail_server_is_english_in_en_locale(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'en'])
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('This is the default mail server of NEXA Suite.', $html);
        $this->assertStringContainsString('SMTP server hostname', $html);
        $this->assertStringNotContainsString('Dit is de standaard mailserver van NEXA Suite', $this->visibleHtml($html));
        $this->assertStringNotContainsString('Mail Server Instellingen', $this->visibleHtml($html));
    }

    #[Test]
    public function settings_index_keeps_dutch_in_nl_locale(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'nl'])
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Dit is de standaard mailserver van NEXA Suite.', $html);
        $this->assertStringContainsString('Mail Server Instellingen', $html);
    }

    #[Test]
    public function general_settings_avatar_labels_are_english_in_en_locale(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'en'])
            ->get(route('admin.settings.general.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('NEXA Suite avatar', $html);
        $this->assertStringNotContainsString('NEXA Suite-avatar', $this->visibleHtml($html));
    }

    #[Test]
    public function general_settings_avatar_label_stays_dutch_in_nl_locale(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'nl'])
            ->get(route('admin.settings.general.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('NEXA Suite-avatar', $html);
    }

    #[Test]
    public function mail_validation_message_is_english_in_en_locale_and_dutch_in_nl(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->from(route('admin.settings.index'))
            ->post(route('admin.settings.mail.update'), ['MAIL_MAILER' => 'smtp'])
            ->assertSessionHasErrors(['MAIL_HOST' => 'SMTP host is required when the SMTP mailer is selected.']);

        $this->actingAs($admin)->withSession(['locale' => 'nl'])
            ->from(route('admin.settings.index'))
            ->post(route('admin.settings.mail.update'), ['MAIL_MAILER' => 'smtp'])
            ->assertSessionHasErrors(['MAIL_HOST' => 'SMTP host is verplicht wanneer SMTP mailer is geselecteerd.']);
    }

    #[Test]
    public function logo_size_json_message_is_translated(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->postJson(route('admin.settings.logo-size.update'), ['logo_size' => 40])
            ->assertJsonFragment(['message' => 'Logo size updated successfully.']);

        $this->actingAs($admin)->withSession(['locale' => 'nl'])
            ->postJson(route('admin.settings.logo-size.update'), ['logo_size' => 40])
            ->assertJsonFragment(['message' => 'Logo grootte succesvol bijgewerkt.']);
    }
}
