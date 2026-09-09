<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Incident;
use App\Models\Notification;
use App\Models\User;
use App\Support\IncidentCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IncidentTicketTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Storage::fake('public');
    }

    #[Test]
    public function guest_is_redirected_from_incidents(): void
    {
        $this->get(route('admin.incidents.index'))
            ->assertRedirect();
    }

    #[Test]
    public function super_admin_can_open_incidents_without_selected_tenant(): void
    {
        $super = $this->superAdmin();
        session()->forget('selected_tenant');

        $this->actingAs($super)
            ->get(route('admin.incidents.index'))
            ->assertOk()
            ->assertSee('window.__INCIDENT_APP__', false)
            ->assertDontSee('Kies links in de zijbalk een tenant', false);
    }

    #[Test]
    public function company_admin_can_submit_incident_and_super_admin_is_notified(): void
    {
        [$company, $admin] = $this->companyAdmin();
        $super = $this->superAdmin();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.incidents.store'), [
                'kind' => IncidentCatalog::KIND_STORING,
                'title' => 'Planning opent niet',
                'page_url' => 'https://demo.nexa.test/planning',
                'description' => 'Op mobiel blijft de planning leeg na het inloggen.',
                'priority' => IncidentCatalog::PRIORITY_HIGH,
                'screenshots' => [
                    UploadedFile::fake()->image('fout.png', 640, 480),
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $incident = Incident::query()->first();
        $this->assertNotNull($incident);
        $this->assertSame($company->id, $incident->company_id);
        $this->assertSame($admin->id, $incident->reporter_user_id);
        $this->assertSame('https://demo.nexa.test/planning', $incident->page_url);
        $this->assertSame(IncidentCatalog::STATUS_OPEN, $incident->status);
        $this->assertNotEmpty($incident->screenshots);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $super->id,
            'type' => IncidentCatalog::NOTIFICATION_TYPE,
            'category' => IncidentCatalog::NOTIFICATION_CATEGORY,
            'title' => 'Nieuw incident '.$incident->reference,
        ]);
    }

    #[Test]
    public function super_admin_can_create_an_incident(): void
    {
        $super = $this->superAdmin();
        [$company] = $this->companyAdmin();

        $this->actingAs($super)
            ->postJson(route('admin.incidents.store'), [
                'kind' => IncidentCatalog::KIND_VRAAG,
                'title' => 'Interne melding',
                'description' => 'Super-admin dient zelf een incident in zonder bedrijf.',
                'priority' => IncidentCatalog::PRIORITY_NORMAL,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $internal = Incident::query()->where('title', 'Interne melding')->first();
        $this->assertNotNull($internal);
        $this->assertNull($internal->company_id);
        $this->assertSame($super->id, $internal->reporter_user_id);

        $this->actingAs($super)
            ->postJson(route('admin.incidents.store'), [
                'kind' => IncidentCatalog::KIND_STORING,
                'title' => 'Namens de klant',
                'description' => 'Super-admin koppelt het incident aan een bedrijf.',
                'priority' => IncidentCatalog::PRIORITY_HIGH,
                'company_id' => $company->id,
            ])
            ->assertCreated();

        $linked = Incident::query()->where('title', 'Namens de klant')->first();
        $this->assertNotNull($linked);
        $this->assertSame($company->id, $linked->company_id);
    }

    #[Test]
    public function resolving_incident_notifies_the_customer(): void
    {
        [$company, $admin] = $this->companyAdmin();
        $super = $this->superAdmin();
        $incident = Incident::query()->create([
            'company_id' => $company->id,
            'reporter_user_id' => $admin->id,
            'reference' => 'INC-2026-0001',
            'kind' => IncidentCatalog::KIND_STORING,
            'title' => 'Kaart laadt niet',
            'description' => 'De GPS-kaart blijft wit.',
            'priority' => IncidentCatalog::PRIORITY_URGENT,
            'status' => IncidentCatalog::STATUS_OPEN,
        ]);

        $this->actingAs($super)
            ->patchJson(route('admin.incidents.update', $incident), [
                'status' => IncidentCatalog::STATUS_RESOLVED,
                'resolution_note' => 'Cache geleegd, kaart werkt weer.',
            ])
            ->assertOk()
            ->assertJsonPath('patch.status', IncidentCatalog::STATUS_RESOLVED);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'company_id' => $company->id,
            'type' => IncidentCatalog::NOTIFICATION_TYPE,
            'category' => IncidentCatalog::NOTIFICATION_CATEGORY,
            'title' => 'Incident INC-2026-0001 is afgehandeld',
        ]);
    }

    #[Test]
    public function company_admin_cannot_see_another_tenants_incident(): void
    {
        [$companyA, $adminA] = $this->companyAdmin('Tenant A');
        [, $adminB] = $this->companyAdmin('Tenant B');
        $incident = Incident::query()->create([
            'company_id' => $companyA->id,
            'reporter_user_id' => $adminA->id,
            'reference' => 'INC-2026-0002',
            'kind' => IncidentCatalog::KIND_VRAAG,
            'title' => 'Alleen voor A',
            'description' => 'Interne vraag van tenant A.',
            'priority' => IncidentCatalog::PRIORITY_NORMAL,
            'status' => IncidentCatalog::STATUS_OPEN,
        ]);

        $this->actingAs($adminB)
            ->getJson(route('admin.incidents.show', $incident))
            ->assertForbidden();

        $this->actingAs($adminB)
            ->getJson(route('admin.incidents.list'))
            ->assertOk()
            ->assertJsonCount(0, 'incidents');
    }

    #[Test]
    public function super_admin_sees_all_incidents_in_the_list(): void
    {
        [$company, $admin] = $this->companyAdmin();
        $super = $this->superAdmin();
        Incident::query()->create([
            'company_id' => $company->id,
            'reporter_user_id' => $admin->id,
            'reference' => 'INC-2026-0003',
            'kind' => IncidentCatalog::KIND_WENS,
            'title' => 'Export naar Excel',
            'description' => 'Graag een exportknop op de rittenlijst.',
            'priority' => IncidentCatalog::PRIORITY_LOW,
            'status' => IncidentCatalog::STATUS_OPEN,
            'created_at' => now()->subHour(),
        ]);
        Incident::query()->create([
            'company_id' => $company->id,
            'reporter_user_id' => $admin->id,
            'reference' => 'INC-2026-0004',
            'kind' => IncidentCatalog::KIND_STORING,
            'title' => 'Nieuwere melding',
            'description' => 'Deze moet bovenaan staan.',
            'priority' => IncidentCatalog::PRIORITY_NORMAL,
            'status' => IncidentCatalog::STATUS_CLOSED,
            'created_at' => now(),
        ]);

        $this->actingAs($super)
            ->get(route('admin.incidents.index'))
            ->assertOk()
            ->assertSee('window.__INCIDENT_APP__', false);

        $this->actingAs($super)
            ->getJson(route('admin.incidents.list'))
            ->assertOk()
            ->assertJsonPath('incidents.0.reference', 'INC-2026-0004')
            ->assertJsonPath('incidents.1.reference', 'INC-2026-0003');
    }

    #[Test]
    public function super_admin_can_change_status_without_refreshing_the_note(): void
    {
        [$company, $admin] = $this->companyAdmin();
        $super = $this->superAdmin();
        $incident = Incident::query()->create([
            'company_id' => $company->id,
            'reporter_user_id' => $admin->id,
            'reference' => 'INC-2026-0005',
            'kind' => IncidentCatalog::KIND_STORING,
            'title' => 'Status alleen',
            'description' => 'Alleen de status wijzigen.',
            'priority' => IncidentCatalog::PRIORITY_NORMAL,
            'status' => IncidentCatalog::STATUS_OPEN,
            'resolution_note' => 'Bestaande toelichting.',
        ]);

        $this->actingAs($super)
            ->patchJson(route('admin.incidents.update', $incident), [
                'status' => IncidentCatalog::STATUS_IN_PROGRESS,
            ])
            ->assertOk()
            ->assertJsonPath('patch.status', IncidentCatalog::STATUS_IN_PROGRESS)
            ->assertJsonMissingPath('patch.resolution_note')
            ->assertJsonMissingPath('incident.description');

        $this->assertSame('Bestaande toelichting.', $incident->fresh()->resolution_note);
        $this->assertSame(IncidentCatalog::STATUS_IN_PROGRESS, $incident->fresh()->status);
    }

    #[Test]
    public function super_admin_can_add_an_internal_comment(): void
    {
        [$company, $admin] = $this->companyAdmin();
        $super = $this->superAdmin(['first_name' => 'Mehmet', 'last_name' => 'Tosun']);
        $incident = Incident::query()->create([
            'company_id' => $company->id,
            'reporter_user_id' => $admin->id,
            'reference' => 'INC-2026-0006',
            'kind' => IncidentCatalog::KIND_STORING,
            'title' => 'Commentaar',
            'description' => 'Interne notitie nodig.',
            'priority' => IncidentCatalog::PRIORITY_HIGH,
            'status' => IncidentCatalog::STATUS_OPEN,
        ]);

        $this->actingAs($super)
            ->postJson(route('admin.incidents.comments.store', $incident), [
                'body' => 'Check de cache-header op de GPS-route.',
            ])
            ->assertCreated()
            ->assertJsonPath('comment.body', 'Check de cache-header op de GPS-route.')
            ->assertJsonPath('comment.user.name', 'Mehmet Tosun');

        $this->actingAs($super)
            ->getJson(route('admin.incidents.show', $incident))
            ->assertOk()
            ->assertJsonPath('incident.comments.0.body', 'Check de cache-header op de GPS-route.')
            ->assertJsonPath('incident.comments.0.user.name', 'Mehmet Tosun');

        $this->actingAs($admin)
            ->getJson(route('admin.incidents.show', $incident))
            ->assertOk()
            ->assertJsonMissingPath('incident.comments');
    }

    #[Test]
    public function company_admin_cannot_add_an_internal_comment(): void
    {
        [$company, $admin] = $this->companyAdmin();
        $incident = Incident::query()->create([
            'company_id' => $company->id,
            'reporter_user_id' => $admin->id,
            'reference' => 'INC-2026-0007',
            'kind' => IncidentCatalog::KIND_VRAAG,
            'title' => 'Geen interne notes',
            'description' => 'Klant mag dit niet.',
            'priority' => IncidentCatalog::PRIORITY_LOW,
            'status' => IncidentCatalog::STATUS_OPEN,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.incidents.comments.store', $incident), [
                'body' => 'Mag niet.',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function super_admin_can_delete_an_internal_comment(): void
    {
        [$company, $admin] = $this->companyAdmin();
        $super = $this->superAdmin();
        $incident = Incident::query()->create([
            'company_id' => $company->id,
            'reporter_user_id' => $admin->id,
            'reference' => 'INC-2026-0008',
            'kind' => IncidentCatalog::KIND_STORING,
            'title' => 'Commentaar wissen',
            'description' => 'Interne notitie wissen.',
            'priority' => IncidentCatalog::PRIORITY_HIGH,
            'status' => IncidentCatalog::STATUS_OPEN,
        ]);
        $comment = $incident->comments()->create([
            'user_id' => $super->id,
            'body' => 'Tijdelijke notitie.',
        ]);

        $this->actingAs($super)
            ->deleteJson(route('admin.incidents.comments.destroy', [$incident, $comment]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('incident_comments', ['id' => $comment->id]);
    }

    #[Test]
    public function company_admin_cannot_delete_an_internal_comment(): void
    {
        [$company, $admin] = $this->companyAdmin();
        $super = $this->superAdmin();
        $incident = Incident::query()->create([
            'company_id' => $company->id,
            'reporter_user_id' => $admin->id,
            'reference' => 'INC-2026-0009',
            'kind' => IncidentCatalog::KIND_VRAAG,
            'title' => 'Geen wissen',
            'description' => 'Klant mag dit niet.',
            'priority' => IncidentCatalog::PRIORITY_LOW,
            'status' => IncidentCatalog::STATUS_OPEN,
        ]);
        $comment = $incident->comments()->create([
            'user_id' => $super->id,
            'body' => 'Blijft staan.',
        ]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.incidents.comments.destroy', [$incident, $comment]))
            ->assertForbidden();

        $this->assertDatabaseHas('incident_comments', ['id' => $comment->id]);
    }

    #[Test]
    public function list_paginates_and_hides_archived_incidents(): void
    {
        [$company, $admin] = $this->companyAdmin();
        $super = $this->superAdmin();

        for ($i = 1; $i <= 12; $i++) {
            Incident::query()->create([
                'company_id' => $company->id,
                'reporter_user_id' => $admin->id,
                'reference' => sprintf('INC-2026-%04d', 20 + $i),
                'kind' => IncidentCatalog::KIND_STORING,
                'title' => 'Melding '.$i,
                'description' => 'Beschrijving '.$i,
                'priority' => IncidentCatalog::PRIORITY_NORMAL,
                'status' => IncidentCatalog::STATUS_OPEN,
                'created_at' => now()->subMinutes($i),
            ]);
        }
        $archived = Incident::query()->create([
            'company_id' => $company->id,
            'reporter_user_id' => $admin->id,
            'reference' => 'INC-2026-0099',
            'kind' => IncidentCatalog::KIND_STORING,
            'title' => 'Gearchiveerd',
            'description' => 'Zit in archief.',
            'priority' => IncidentCatalog::PRIORITY_LOW,
            'status' => IncidentCatalog::STATUS_RESOLVED,
            'archived_at' => now(),
        ]);

        $this->actingAs($super)
            ->getJson(route('admin.incidents.list', ['per_page' => 10, 'page' => 1]))
            ->assertOk()
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonCount(10, 'incidents')
            ->assertJsonMissing(['reference' => 'INC-2026-0099'])
            ->assertJsonPath('stats.archived', 1);

        $toArchive = Incident::query()->whereNull('archived_at')->orderByDesc('created_at')->first();
        $this->assertNotNull($toArchive);
        $this->actingAs($super)
            ->postJson(route('admin.incidents.archive'), [
                'ids' => [$toArchive->id],
                'archived' => true,
            ])
            ->assertOk()
            ->assertJsonPath('count', 1);
        $this->assertNotNull($toArchive->fresh()->archived_at);

        $this->actingAs($super)
            ->getJson(route('admin.incidents.list', ['per_page' => 10, 'page' => 1]))
            ->assertOk()
            ->assertJsonPath('meta.total', 11)
            ->assertJsonPath('stats.archived', 2);

        $this->actingAs($super)
            ->getJson(route('admin.incidents.list', ['archived' => 1]))
            ->assertOk()
            ->assertJsonCount(2, 'incidents')
            ->assertJsonFragment(['reference' => 'INC-2026-0099'])
            ->assertJsonPath('incidents.0.is_archived', true);

        $this->actingAs($super)
            ->postJson(route('admin.incidents.archive'), [
                'ids' => [$archived->id],
                'archived' => false,
            ])
            ->assertOk()
            ->assertJsonPath('count', 1);

        $this->assertNull($archived->fresh()->archived_at);
    }

    #[Test]
    public function company_admin_cannot_archive_another_tenants_incident(): void
    {
        [$companyA, $adminA] = $this->companyAdmin('Archive A');
        [, $adminB] = $this->companyAdmin('Archive B');
        $incident = Incident::query()->create([
            'company_id' => $companyA->id,
            'reporter_user_id' => $adminA->id,
            'reference' => 'INC-2026-0088',
            'kind' => IncidentCatalog::KIND_VRAAG,
            'title' => 'Niet van B',
            'description' => 'Alleen A.',
            'priority' => IncidentCatalog::PRIORITY_NORMAL,
            'status' => IncidentCatalog::STATUS_OPEN,
        ]);

        $this->actingAs($adminB)
            ->postJson(route('admin.incidents.archive'), [
                'ids' => [$incident->id],
                'archived' => true,
            ])
            ->assertForbidden();

        $this->assertNull($incident->fresh()->archived_at);
    }

    #[Test]
    public function incident_show_includes_screenshot_gallery_urls(): void
    {
        [$company, $admin] = $this->companyAdmin();
        $super = $this->superAdmin();

        $this->actingAs($admin)
            ->post(route('admin.incidents.store'), [
                'kind' => IncidentCatalog::KIND_STORING,
                'title' => 'Kaart blijft wit',
                'description' => 'Na het openen van GPS-tracking blijft de kaart leeg.',
                'priority' => IncidentCatalog::PRIORITY_HIGH,
                'screenshots' => [
                    UploadedFile::fake()->image('gps-wit.png', 800, 600),
                    UploadedFile::fake()->image('gps-menu.jpg', 640, 480),
                ],
            ])
            ->assertCreated();

        $incident = Incident::query()->where('title', 'Kaart blijft wit')->first();
        $this->assertNotNull($incident);
        $this->assertCount(2, $incident->screenshots ?? []);

        $show = $this->actingAs($super)
            ->getJson(route('admin.incidents.show', $incident))
            ->assertOk();

        $shots = $show->json('incident.screenshots');
        $this->assertIsArray($shots);
        $this->assertCount(2, $shots);
        $this->assertSame('gps-wit.png', $shots[0]['name']);
        $this->assertStringContainsString('/screenshots/0', $shots[0]['url']);
        $this->assertStringContainsString('/screenshots/1', $shots[1]['url']);

        $this->actingAs($super)
            ->get($shots[0]['url'])
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    #[Test]
    public function company_admin_cannot_open_another_tenants_screenshot(): void
    {
        [$companyA, $adminA] = $this->companyAdmin('Shot A');
        [, $adminB] = $this->companyAdmin('Shot B');

        $this->actingAs($adminA)
            ->post(route('admin.incidents.store'), [
                'kind' => IncidentCatalog::KIND_STORING,
                'title' => 'Screenshot van A',
                'description' => 'Deze bijlage is alleen voor tenant A.',
                'priority' => IncidentCatalog::PRIORITY_NORMAL,
                'screenshots' => [
                    UploadedFile::fake()->image('alleen-a.png', 320, 240),
                ],
            ])
            ->assertCreated();

        $incident = Incident::query()->where('title', 'Screenshot van A')->first();
        $this->assertNotNull($incident);

        $this->actingAs($adminB)
            ->get(route('admin.incidents.screenshot', ['incident' => $incident, 'index' => 0]))
            ->assertForbidden();
    }

    /**
     * @return array{0: Company, 1: User}
     */
    private function companyAdmin(string $name = 'Incident Co'): array
    {
        $company = Company::query()->create(['name' => $name, 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('company-admin');

        return [$company, $admin];
    }

    private function superAdmin(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['company_id' => null], $attributes));
        $user->assignRole('super-admin');

        return $user;
    }
}
