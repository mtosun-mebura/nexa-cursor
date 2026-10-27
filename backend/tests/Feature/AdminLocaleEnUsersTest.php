<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleEnUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Zolang de fragmenten nog niet in lang/en.json zijn samengevoegd, laden we ze hier mee.
        $merged = [];
        foreach (glob(base_path('lang/_fragments/*.json')) ?: [] as $file) {
            $merged = array_merge($merged, json_decode((string) file_get_contents($file), true) ?: []);
        }
        if ($merged !== []) {
            $dir = sys_get_temp_dir().'/nexa-i18n-users-test';
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($dir.'/en.json', json_encode($merged, JSON_UNESCAPED_UNICODE));
            app('translator')->getLoader()->addJsonPath($dir);
        }
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
        return (string) preg_replace('/<!--.*?-->/s', '', $html);
    }

    private function tenantSession(array $extra = []): array
    {
        $tenant = Company::query()->create(['name' => 'Locale Users Taxi', 'is_active' => true]);

        return ['selected_tenant' => $tenant->id] + $extra;
    }

    #[Test]
    public function users_index_is_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession(['locale' => 'en']);
        User::factory()->create(['first_name' => 'Piet', 'last_name' => 'Jansen', 'company_id' => $session['selected_tenant']]);

        $html = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->getContent();

        foreach (['New user', 'Search users...', 'All statuses', 'Created', 'Actions', 'Select all', 'Delete selected users', 'Are you sure you want to delete this user? This cannot be undone.', 'Copy email address'] as $english) {
            $this->assertStringContainsString($english, $html, $english);
        }
        foreach (['Nieuwe Gebruiker', 'Zoek gebruikers...', 'Alle statussen', 'Aangemaakt', 'Weet je zeker dat je deze gebruiker', 'Alles selecteren', 'E-mailadres kopiëren', 'Bezig...'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($html), $dutch);
        }
    }

    #[Test]
    public function users_create_edit_and_show_are_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create(['first_name' => 'Piet', 'last_name' => 'Jansen', 'email_verified_at' => null]);
        $session = $this->tenantSession(['locale' => 'en']);

        $create = $this->actingAs($admin)->withSession($session)->get(route('admin.users.create'))->assertOk()->getContent();
        foreach (['New user', 'General information', 'First name *', 'Generate password', 'Date of birth', 'Save user', 'Select one or more roles'] as $english) {
            $this->assertStringContainsString($english, $create, $english);
        }
        foreach (['Algemene Informatie', 'Voornaam *', 'Genereer wachtwoord', 'Geboortedatum', 'Gebruiker Opslaan'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($create), $dutch);
        }

        $edit = $this->actingAs($admin)->withSession($session)->get(route('admin.users.edit', $target))->assertOk()->getContent();
        foreach (['Edit user', 'New password', 'Calendar color', 'Save changes', 'Leave empty to keep unchanged'] as $english) {
            $this->assertStringContainsString($english, $edit, $english);
        }
        foreach (['Gebruiker Bewerken', 'Nieuw Wachtwoord', 'Agenda kleur', 'Wijzigingen Opslaan'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($edit), $dutch);
        }

        $show = $this->actingAs($admin)->withSession($session)->get(route('admin.users.show', $target))->assertOk()->getContent();
        foreach (['Profile', 'Account status', 'Email verified', 'Phone verified', 'Send activation link', 'Created on', 'Last updated', 'Send verification email to '.$target->email] as $english) {
            $this->assertStringContainsString($english, $show, $english);
        }
        foreach (['Profiel', 'E-mail geverifieerd', 'Telefoon geverifieerd', 'Verstuur activatielink', 'Aangemaakt op', 'Laatst bijgewerkt'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($show), $dutch);
        }
    }

    #[Test]
    public function roles_index_is_english_in_en_locale(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->withSession($this->tenantSession(['locale' => 'en']))
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->getContent();

        foreach (['Role management', 'New role', 'Total roles', 'Search roles...', 'All types', 'Showing 1 to'] as $english) {
            $this->assertStringContainsString($english, $html, $english);
        }
        foreach (['Rollen Beheer', 'Nieuwe Rol', 'Totaal Rollen', 'Zoek rollen...', 'Alle types', 'Toon 1 tot'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($html), $dutch);
        }
    }

    #[Test]
    public function flash_and_validation_messages_follow_the_locale(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create();
        $other = $this->tenantSession(['locale' => 'en']);

        $this->actingAs($admin)->withSession($other)
            ->delete(route('admin.users.destroy', $admin))
            ->assertSessionHas('error', 'You cannot delete yourself.');

        $this->actingAs($admin)->withSession($other)
            ->post(route('admin.users.store'), ['first_name' => '', 'last_name' => '', 'email' => '', 'roles' => []])
            ->assertSessionHasErrors(['first_name' => 'First name is required.', 'roles' => 'Select at least one role.']);

        $this->actingAs($admin)->withSession($this->tenantSession(['locale' => 'nl']))
            ->delete(route('admin.users.destroy', $admin))
            ->assertSessionHas('error', 'Je kunt jezelf niet verwijderen.');

        $this->actingAs($admin)->withSession($this->tenantSession(['locale' => 'nl']))
            ->post(route('admin.users.store'), ['first_name' => '', 'last_name' => '', 'email' => '', 'roles' => []])
            ->assertSessionHasErrors(['first_name' => 'Voornaam is verplicht.', 'roles' => 'Selecteer minimaal één rol.']);

        $this->assertNotNull($target->id);
    }

    #[Test]
    public function dutch_locale_still_shows_dutch_strings(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create();
        $session = $this->tenantSession(['locale' => 'nl']);

        $index = $this->actingAs($admin)->withSession($session)->get(route('admin.users.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Nieuwe Gebruiker', $index);
        $this->assertStringContainsString('Zoek gebruikers...', $index);
        $this->assertStringContainsString('Weet je zeker dat je deze gebruiker wilt verwijderen? Dit kan niet ongedaan worden gemaakt.', $index);

        $create = $this->actingAs($admin)->withSession($session)->get(route('admin.users.create'))->assertOk()->getContent();
        $this->assertStringContainsString('Algemene Informatie', $create);
        $this->assertStringContainsString('Genereer wachtwoord', $create);

        $show = $this->actingAs($admin)->withSession($session)->get(route('admin.users.show', $target))->assertOk()->getContent();
        $this->assertStringContainsString('Account Status', $show);
        $this->assertStringContainsString('Laatst bijgewerkt', $show);

        $roles = $this->actingAs($admin)->withSession($session)->get(route('admin.roles.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Rollen Beheer', $roles);
        $this->assertStringContainsString('Zoek rollen...', $roles);
    }
}
