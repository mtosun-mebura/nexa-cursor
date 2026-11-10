<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Incident;
use App\Models\User;
use App\Support\IncidentCatalog;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLocaleEnIncidentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Storage::fake('public');
        $this->loadIncidentFragment();
    }

    /**
     * Fragment (nog) niet in lang/en.json gemerged? Laad hem dan als extra JSON-pad.
     */
    private function loadIncidentFragment(): void
    {
        $fragment = base_path('lang/_fragments/incidents.json');
        if (! is_file($fragment)) {
            return;
        }

        $dir = sys_get_temp_dir().'/nexa-incidents-lang-'.getmypid();
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        copy($fragment, $dir.'/en.json');
        app('translator')->addJsonPath($dir);
    }

    private function companyAdmin(): array
    {
        $company = Company::query()->create(['name' => 'Incident Co', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('company-admin');

        return [$company, $admin];
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function bootstrapFrom(string $html): array
    {
        $this->assertSame(1, preg_match('/window\.__INCIDENT_APP__ = (\{.*?\});\s*<\/script>/s', $html, $m));

        return json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
    }

    #[Test]
    public function incidents_page_bootstrap_is_english_with_translation_map(): void
    {
        [, $admin] = $this->companyAdmin();

        $html = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.incidents.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('"locale":"en"', $html);
        $this->assertStringContainsString('"translations":{', $html);
        $this->assertStringContainsString('<title>', $html);
        $this->assertStringContainsString('Report incident', $html);

        $bootstrap = $this->bootstrapFrom($html);
        $this->assertSame('en', $bootstrap['locale']);
        $this->assertSame(IncidentCatalog::uiStrings(), array_keys($bootstrap['translations']));
        $this->assertSame('Need help?', $bootstrap['translations']['Hulp nodig?']);
        $this->assertSame('New incident', $bootstrap['translations']['Nieuw incident']);
        $this->assertSame('Showing :from to :to of :total incidents', $bootstrap['translations']['Toon :from tot :to van :total incidenten']);

        $kinds = array_column($bootstrap['kinds'], 'label', 'value');
        $this->assertSame('Outage', $kinds[IncidentCatalog::KIND_STORING]);
        $statuses = array_column($bootstrap['statuses'], 'label', 'value');
        $this->assertSame('New', $statuses[IncidentCatalog::STATUS_OPEN]);
        $this->assertSame('In progress', $statuses[IncidentCatalog::STATUS_IN_PROGRESS]);
        $priorities = array_column($bootstrap['priorities'], 'label', 'value');
        $this->assertSame('High', $priorities[IncidentCatalog::PRIORITY_HIGH]);
        $this->assertSame("Something doesn't work or shows an error", $bootstrap['kinds'][0]['hint']);
        // opgeslagen waarden blijven ongewijzigd
        $this->assertSame(IncidentCatalog::kinds(), array_column($bootstrap['kinds'], 'value'));
    }

    #[Test]
    public function super_admin_incidents_page_title_and_settings_page_are_english(): void
    {
        $super = $this->superAdmin();

        $html = $this->actingAs($super)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.incidents.index'))
            ->assertOk()
            ->getContent();
        $bootstrap = $this->bootstrapFrom($html);
        $this->assertTrue($bootstrap['is_super_admin']);
        $this->assertSame('Incidents', $bootstrap['translations']['Incidenten']);
        $this->assertSame('Resolved', $bootstrap['translations']['Afgehandeld']);

        $this->actingAs($super)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.incidents.settings.edit'))
            ->assertOk()
            ->assertSee('Incident settings', false)
            ->assertSee('Notification e-mail', false)
            ->assertSee('E-mail address', false)
            ->assertSee('Default: '.IncidentCatalog::DEFAULT_NOTIFICATION_EMAIL.'.', false)
            ->assertDontSee('Incidentinstellingen', false)
            ->assertDontSee('Notificatie-e-mail', false)
            ->assertDontSee('Standaard:', false);
    }

    #[Test]
    public function json_responses_and_messages_are_english(): void
    {
        [$company, $admin] = $this->companyAdmin();
        $incident = Incident::query()->create([
            'company_id' => $company->id,
            'reporter_user_id' => $admin->id,
            'reference' => Incident::nextReference(),
            'kind' => IncidentCatalog::KIND_VRAAG,
            'title' => 'Hoe werkt dit',
            'description' => 'Ik heb een vraag over de planning.',
            'priority' => IncidentCatalog::PRIORITY_URGENT,
            'status' => IncidentCatalog::STATUS_OPEN,
        ]);

        $row = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->getJson(route('admin.incidents.list'))
            ->assertOk()
            ->json('incidents.0');

        $this->assertSame(IncidentCatalog::STATUS_OPEN, $row['status']);
        $this->assertSame('New', $row['status_label']);
        $this->assertSame('Question', $row['kind_label']);
        $this->assertSame(IncidentCatalog::PRIORITY_URGENT, $row['priority']);
        $this->assertSame('Urgent', $row['priority_label']);
        // gebruikersinhoud blijft ongemoeid
        $this->assertSame('Hoe werkt dit', $row['title']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->patchJson(route('admin.incidents.update', $incident), ['status' => IncidentCatalog::STATUS_CLOSED])
            ->assertForbidden()
            ->assertJsonPath('message', 'Only a super admin can handle incidents.');

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->postJson(route('admin.incidents.store'), [
                'kind' => IncidentCatalog::KIND_STORING,
                'title' => 'Planning opent niet',
                'description' => 'Op mobiel blijft de planning leeg na het inloggen.',
                'priority' => IncidentCatalog::PRIORITY_NORMAL,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Your incident has been submitted. We will pick it up as soon as possible.')
            ->assertJsonPath('incident.status_label', 'New');
    }

    #[Test]
    public function dutch_stays_unchanged(): void
    {
        [$company, $admin] = $this->companyAdmin();
        $super = $this->superAdmin();

        $html = $this->actingAs($admin)
            ->get(route('admin.incidents.index'))
            ->assertOk()
            ->assertSee('Incident melden', false)
            ->getContent();

        $bootstrap = $this->bootstrapFrom($html);
        $this->assertSame('nl', $bootstrap['locale']);
        $this->assertArrayNotHasKey('translations', $bootstrap);
        $this->assertSame(IncidentCatalog::kindOptions(), $bootstrap['kinds']);
        $this->assertSame(IncidentCatalog::statusOptions(), $bootstrap['statuses']);
        $this->assertSame(IncidentCatalog::priorityOptions(), $bootstrap['priorities']);

        $this->actingAs($super)
            ->get(route('admin.incidents.settings.edit'))
            ->assertOk()
            ->assertSee('Incidentinstellingen', false)
            ->assertSee('Standaard: '.IncidentCatalog::DEFAULT_NOTIFICATION_EMAIL.'.', false);

        $this->actingAs($admin)
            ->patchJson(route('admin.incidents.update', Incident::query()->create([
                'company_id' => $company->id,
                'reporter_user_id' => $admin->id,
                'reference' => Incident::nextReference(),
                'kind' => IncidentCatalog::KIND_WENS,
                'title' => 'Wens',
                'description' => 'Een lange omschrijving van de wens.',
                'priority' => IncidentCatalog::PRIORITY_LOW,
                'status' => IncidentCatalog::STATUS_OPEN,
            ])), ['status' => IncidentCatalog::STATUS_CLOSED])
            ->assertForbidden()
            ->assertJsonPath('message', 'Alleen een super-admin kan incidenten afhandelen.');
    }

    #[Test]
    public function every_t_literal_in_incident_app_is_in_ui_strings_list(): void
    {
        $source = file_get_contents(resource_path('js/incidents/IncidentApp.vue'));
        $this->assertNotFalse($source);

        preg_match_all("/(?<![\\w.\$])t\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", $source, $matches);
        $used = array_values(array_unique(array_map(
            static fn (string $literal) => stripslashes($literal),
            $matches[1]
        )));

        $this->assertNotEmpty($used);
        $listed = IncidentCatalog::uiStrings();
        $this->assertSame($listed, array_values(array_unique($listed)), 'uiStrings() bevat dubbele keys.');
        $this->assertSame([], array_values(array_diff($used, $listed)), 't()-literals in IncidentApp.vue die niet in IncidentCatalog::uiStrings() staan.');
        $this->assertSame([], array_values(array_diff($listed, $used)), 'Keys in IncidentCatalog::uiStrings() die IncidentApp.vue niet (meer) gebruikt.');

        // t() mag alleen met een letterlijke string aangeroepen worden (anders ziet deze test de key niet)
        $this->assertSame(1, preg_match_all("/(?<![\\w.\$])t\\((?!\\s*')/", $source), 'Alleen de helper-definitie mag t( zonder literal bevatten.');
    }
}
