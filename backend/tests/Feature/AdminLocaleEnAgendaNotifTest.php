<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Notification;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleEnAgendaNotifTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->loadFragmentTranslations();
    }

    /**
     * Fragmentvertalingen worden pas later in lang/en.json gemerged; laad ze hier
     * als extra JSON-pad zodat de test ook vóór (en na) de merge werkt.
     */
    private function loadFragmentTranslations(): void
    {
        $lines = [];
        foreach (glob(base_path('lang/_fragments/*.json')) ?: [] as $fragment) {
            $lines = array_merge($lines, json_decode((string) file_get_contents($fragment), true) ?: []);
        }
        if ($lines === []) {
            return;
        }

        $dir = sys_get_temp_dir().'/nexa-agenda-notif-lang-'.getmypid();
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir.'/en.json', json_encode($lines, JSON_UNESCAPED_UNICODE));
        app('translator')->getLoader()->addJsonPath($dir);
    }

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function tenant(): Company
    {
        return Company::query()->create(['name' => 'Agenda Taxi '.uniqid(), 'is_active' => true]);
    }

    private function notification(Company $company): Notification
    {
        $recipient = User::factory()->create(['company_id' => $company->id]);

        return Notification::query()->create([
            'user_id' => $recipient->id,
            'company_id' => $company->id,
            'type' => 'info',
            'title' => 'Titel van data',
            'message' => 'Bericht van data',
            'priority' => 'medium',
        ]);
    }

    private function page(string $locale, string $url, Company $company): string
    {
        return $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $company->id, 'locale' => $locale])
            ->get($url)
            ->assertOk()
            ->getContent();
    }

    #[Test]
    public function agenda_page_is_english_in_en_locale(): void
    {
        $html = $this->page('en', route('admin.agenda.index'), $this->tenant());

        $this->assertStringContainsString('Previous period', $html);
        $this->assertStringContainsString('Next period', $html);
        $this->assertStringContainsString('"Monday"', $html);
        $this->assertStringContainsString('"January"', $html);
        $this->assertStringContainsString('"Mon"', $html);
        $this->assertStringContainsString('Unknown company', $html);
        $this->assertStringNotContainsString('Vorige periode', $html);
        $this->assertStringNotContainsString('"Maandag"', $html);
        $this->assertStringNotContainsString('"januari"', $html);
        $this->assertStringNotContainsString('Onbekend bedrijf', $html);
    }

    #[Test]
    public function notifications_index_is_english_in_en_locale(): void
    {
        $company = $this->tenant();
        $this->notification($company);

        $html = $this->page('en', route('admin.notifications.index'), $company);

        $this->assertStringContainsString('Notification management', $html);
        $this->assertStringContainsString('New notification', $html);
        $this->assertStringContainsString('Sort by user', $html);
        $this->assertStringContainsString('Delete selected notifications', $html);
        $this->assertStringContainsString('Are you sure you want to delete this notification?', $html);
        $this->assertStringNotContainsString('Weet je zeker dat je deze notificatie', $html);
        $this->assertStringNotContainsString('Notificaties Beheer', $html);
        $this->assertStringNotContainsString('Sorteer op gebruiker', $html);
        $this->assertStringNotContainsString('Ongelezen', $html);
        // Data blijft ongemoeid.
        $this->assertStringContainsString('Titel van data', $html);
    }

    #[Test]
    public function notification_create_and_show_are_english_in_en_locale(): void
    {
        $company = $this->tenant();
        $notification = $this->notification($company);

        $create = $this->page('en', route('admin.notifications.create'), $company);
        $this->assertStringContainsString('Notification details', $create);
        $this->assertStringContainsString('Send notification', $create);
        $this->assertStringContainsString('Select recipient', $create);
        $this->assertStringNotContainsString('Notificatie Verzenden', $create);
        $this->assertStringNotContainsString('Selecteer ontvanger', $create);

        $show = $this->page('en', route('admin.notifications.show', $notification), $company);
        $this->assertStringContainsString('Notification information', $show);
        $this->assertStringContainsString('Message &amp; file', $show);
        // (de HTML-commentaarregel '<!-- Notificatie Informatie -->' blijft, daarom alleen zichtbare labels)
        $this->assertStringNotContainsString('Aangemaakt op', $show);
    }

    #[Test]
    public function bulk_delete_flash_is_english_in_en_locale(): void
    {
        $company = $this->tenant();
        $notification = $this->notification($company);

        $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $company->id, 'locale' => 'en'])
            ->delete(route('admin.notifications.bulk-destroy'), ['ids' => [$notification->id]])
            ->assertRedirect(route('admin.notifications.index'))
            ->assertSessionHas('success', '1 notification deleted successfully.');
    }

    #[Test]
    public function dutch_locale_stays_dutch(): void
    {
        $company = $this->tenant();
        $notification = $this->notification($company);

        $agenda = $this->page('nl', route('admin.agenda.index'), $company);
        $this->assertStringContainsString('Vorige periode', $agenda);
        $this->assertStringContainsString('"Maandag"', $agenda);
        $this->assertStringContainsString('"januari"', $agenda);

        $index = $this->page('nl', route('admin.notifications.index'), $company);
        $this->assertStringContainsString('Notificaties Beheer', $index);
        $this->assertStringContainsString('Sorteer op gebruiker', $index);

        $show = $this->page('nl', route('admin.notifications.show', $notification), $company);
        $this->assertStringContainsString('Aangemaakt op', $show);

        $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $company->id])
            ->delete(route('admin.notifications.bulk-destroy'), ['ids' => [$notification->id]])
            ->assertSessionHas('success', '1 notificatie succesvol verwijderd.');
    }
}
