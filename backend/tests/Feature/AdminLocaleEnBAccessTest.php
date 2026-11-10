<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleEnBAccessTest extends TestCase
{
    use RefreshDatabase;

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
        return (string) preg_replace('/<!--.*?-->/s', '', $html);
    }

    /** @return array<string, mixed> */
    private function localeSession(string $locale): array
    {
        $tenant = Company::query()->create(['name' => 'Locale Access Taxi', 'is_active' => true]);

        return ['selected_tenant' => $tenant->id, 'locale' => $locale];
    }

    private function page(string $locale, string $url): string
    {
        return $this->actingAs($this->superAdmin())
            ->withSession($this->localeSession($locale))
            ->get($url)
            ->assertOk()
            ->getContent();
    }

    /**
     * @param  list<string>  $english
     * @param  list<string>  $dutch
     */
    private function assertMarkers(string $html, array $english, array $dutch): void
    {
        foreach ($english as $marker) {
            $this->assertStringContainsString($marker, $html, $marker);
        }
        $visible = $this->visibleHtml($html);
        foreach ($dutch as $marker) {
            $this->assertStringNotContainsString($marker, $visible, $marker);
        }
    }

    private function customRole(): Role
    {
        $perm = Permission::firstOrCreate(['name' => 'view-users', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'locale-test-role', 'guard_name' => 'web', 'description' => 'Locale test']);
        $role->givePermissionTo($perm);

        return $role;
    }

    #[Test]
    public function roles_pages_are_english_in_en_locale(): void
    {
        $role = $this->customRole();
        $company = Company::query()->create(['name' => 'Locale Role Company', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole($role);

        $this->assertMarkers(
            $this->page('en', route('admin.roles.index')),
            ['Role management', 'Total roles', 'Custom roles', 'Search roles...', 'All statuses', 'Without users'],
            ['Rollen Beheer', 'Totaal Rollen', 'Aangepaste Rollen', 'Zoek rollen', 'Alle statussen', 'Zonder gebruikers'],
        );

        $this->assertMarkers(
            $this->page('en', route('admin.roles.create')),
            ['New role', 'Create role', 'Role name *', 'Assign permissions *', 'Quick apply: permission sets', 'Clear all', 'Cancel', 'View only', 'Driver dispatch'],
            ['Nieuwe Rol', 'Rol Aanmaken', 'Rol Naam', 'Permissies Toewijzen', 'Alles Wissen', 'Annuleren', 'Alleen Bekijken', 'Kies welk onderdeel'],
        );

        $this->assertMarkers(
            $this->page('en', route('admin.roles.edit', $role)),
            ['Edit role', 'Basic Information', 'Save changes', 'Role name *', 'Module / Resource', 'Select at least one permission.'],
            ['Rol Bewerken', 'Basis Informatie', 'Wijzigingen Opslaan', 'Rol Naam', 'Selecteer minimaal één recht.'],
        );

        $this->assertMarkers(
            $this->page('en', route('admin.roles.show', $role)),
            ['Role details', 'Number of permissions', 'Assigned permissions (', 'Users with this role (1)', 'Search user or email...', 'All companies', 'Reset filters', 'Are you sure you want to delete this role?'],
            ['Rol Details', 'Aantal Rechten', 'Toegewezen Rechten', 'Gebruikers met deze rol', 'Zoek gebruiker of e-mail', 'Alle bedrijven', 'Weet je zeker dat je deze rol'],
        );
    }

    #[Test]
    public function permissions_pages_are_english_in_en_locale(): void
    {
        $permission = Permission::create(['name' => 'view-locale-test', 'guard_name' => 'web', 'description' => 'Locale test permission']);
        Role::create(['name' => 'locale-perm-role', 'guard_name' => 'web'])->givePermissionTo($permission);

        $this->assertMarkers(
            $this->page('en', route('admin.permissions.index')),
            ['Permissions management', 'Add permission', 'Bulk create', 'Bulk edit', 'Total permissions', 'Search permissions...', 'All modules', 'Assigned to', 'Cannot be deleted: assigned to roles', 'Delete selected permissions'],
            ['Permissies Beheer', 'Permissie toevoegen', 'Bulk Aanmaken', 'Totaal Permissies', 'Zoek permissies', 'Alle modules', 'Toegewezen aan', 'Niet verwijderbaar'],
        );

        $this->assertMarkers(
            $this->page('en', route('admin.permissions.create')),
            ['New permission', 'Create permission', 'Describe what this permission does...', 'Cancel'],
            ['Nieuw Recht', 'Permissie Aanmaken', 'Beschrijf wat deze permissie doet', 'Annuleren'],
        );

        $this->assertMarkers(
            $this->page('en', route('admin.permissions.edit', $permission)),
            ['Edit permission', 'Save changes', 'Basic Information'],
            ['Permissie Bewerken', 'Wijzigingen Opslaan', 'Basis Informatie'],
        );

        $this->assertMarkers(
            $this->page('en', route('admin.permissions.show', $permission)),
            ['Permission details', 'Name (technical)', 'Assigned to roles', 'Roles with this permission (1)', 'No group'],
            ['Permissie Details', 'Naam (technisch)', 'Toegewezen aan rollen', 'Rollen met deze Permissie', 'Geen groep'],
        );

        $this->assertMarkers(
            $this->page('en', route('admin.permissions.bulk-create')),
            ['Bulk create permissions', 'Select modules and actions to see a preview...', 'Basic CRUD actions', 'Specific actions', 'Creating new items', 'Select one or more modules. "General" applies to all modules.'],
            ['Permissies Bulk Aanmaken', 'Selecteer modules en acties', 'Basis CRUD Acties', 'Specifieke Acties', 'Nieuwe items aanmaken', 'Selecteer één of meerdere modules'],
        );

        $this->assertMarkers(
            $this->page('en', route('admin.permissions.bulk-edit')),
            ['Bulk edit permissions', 'Update permissions', 'Preview of permissions being edited', 'Editing existing items'],
            ['Permissies Bulk Bewerken', 'Permissies Bijwerken', 'Voorbeeld Permissies die worden bewerkt', 'Bestaande items bewerken'],
        );
    }

    #[Test]
    public function modules_pages_are_english_in_en_locale(): void
    {
        Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
            'configuration' => ['enabled_menu_items' => ['vehicles']],
        ]);

        $this->assertMarkers(
            $this->page('en', route('admin.modules.index')),
            ['Modules management', 'Total modules', 'Version', 'Installed', 'Internal', 'How do modules work?', 'Processing...', 'Are you sure you want to delete this module?'],
            ['Modules Beheer', 'Totaal Modules', 'Versie', 'Geïnstalleerd', '>Intern<', 'Hoe werken modules?', 'Bezig met verwerken', 'Weet je zeker dat je deze module'],
        );

        $this->assertMarkers(
            $this->page('en', route('admin.modules.config', 'taxi')),
            ['Module configuration', 'Components in menu', 'Application name', 'Show My environment button', 'Light mode', 'How does module configuration work?', 'Cancel'],
            ['Module configuratie', 'Onderdelen in menu', 'Naam van de applicatie', 'Knop Mijn-omgeving tonen', 'Hoe werkt module configuratie', 'Annuleren'],
        );
    }

    #[Test]
    public function flash_messages_are_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $assigned = Permission::create(['name' => 'view-assigned-locale', 'guard_name' => 'web']);
        Role::create(['name' => 'locale-flash-role', 'guard_name' => 'web'])->givePermissionTo($assigned);

        $this->actingAs($admin)
            ->withSession($this->localeSession('en'))
            ->delete(route('admin.roles.destroy', Role::where('name', 'staff')->first()))
            ->assertSessionHas('error', 'System roles cannot be deleted.');

        $this->actingAs($admin)
            ->withSession($this->localeSession('en'))
            ->delete(route('admin.permissions.destroy', $assigned))
            ->assertSessionHas('error', 'This permission is assigned to roles and cannot be deleted.');

        $this->actingAs($admin)
            ->withSession($this->localeSession('en'))
            ->delete(route('admin.permissions.bulk-delete'), ['permissions' => [$assigned->id]])
            ->assertSessionHas('error', 'No permissions deleted. All selected permissions are assigned to roles.');

        $free = Permission::create(['name' => 'view-free-locale', 'guard_name' => 'web']);
        $this->actingAs($admin)
            ->withSession($this->localeSession('en'))
            ->delete(route('admin.permissions.bulk-delete'), ['permissions' => [$free->id]])
            ->assertSessionHas('success', '1 permission deleted successfully.');

        $this->actingAs($admin)
            ->withSession($this->localeSession('en'))
            ->post(route('admin.modules.activate', 'does-not-exist'))
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->withSession($this->localeSession('en'))
            ->get(route('admin.modules.config', 'does-not-exist'))
            ->assertSessionHas('error', 'Module not found.');
    }

    #[Test]
    public function access_pages_keep_dutch_text_in_nl_locale(): void
    {
        $role = $this->customRole();
        $permission = Permission::create(['name' => 'view-locale-test', 'guard_name' => 'web']);
        Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
            'configuration' => ['enabled_menu_items' => ['vehicles']],
        ]);

        $this->assertMarkers($this->page('nl', route('admin.roles.index')), ['Rollen Beheer', 'Totaal Rollen', 'Aangepaste Rollen', 'Zoek rollen...', 'Alle statussen', 'Zonder gebruikers'], []);
        $this->assertMarkers($this->page('nl', route('admin.roles.create')), ['Nieuwe Rol', 'Rol Aanmaken', 'Permissies Toewijzen *', 'Alles Wissen', 'Alleen Bekijken', 'Kies welk onderdeel deze rol mag zien.'], []);
        $this->assertMarkers($this->page('nl', route('admin.roles.edit', $role)), ['Rol Bewerken', 'Wijzigingen Opslaan', 'Module / Resource'], []);
        $this->assertMarkers($this->page('nl', route('admin.roles.show', $role)), ['Rol Details', 'Aantal Rechten', 'Weet je zeker dat je deze rol wilt verwijderen?'], []);
        $this->assertMarkers($this->page('nl', route('admin.permissions.index')), ['Permissies Beheer', 'Permissie toevoegen', 'Zoek permissies...'], []);
        $this->assertMarkers($this->page('nl', route('admin.permissions.show', $permission)), ['Permissie Details', 'Naam (technisch)', 'Geen groep'], []);
        $this->assertMarkers($this->page('nl', route('admin.permissions.bulk-create')), ['Permissies Bulk Aanmaken', 'Basis CRUD Acties', 'Selecteer één of meerdere modules. "Algemeen" geldt voor alle modules.', 'View - Bekijken van items'], []);
        $this->assertMarkers($this->page('nl', route('admin.permissions.bulk-edit')), ['Permissies Bulk Bewerken', 'Permissies Bijwerken'], []);
        $this->assertMarkers($this->page('nl', route('admin.modules.index')), ['Modules Beheer', 'Totaal Modules', 'Hoe werken modules?'], []);
        $this->assertMarkers($this->page('nl', route('admin.modules.config', 'taxi')), ['Module configuratie – Nexa Taxi', 'Onderdelen in menu', 'Knop Mijn-omgeving tonen'], []);

        $this->actingAs($this->superAdmin())
            ->withSession($this->localeSession('nl'))
            ->get(route('admin.modules.config', 'does-not-exist'))
            ->assertSessionHas('error', 'Module niet gevonden.');
    }
}
