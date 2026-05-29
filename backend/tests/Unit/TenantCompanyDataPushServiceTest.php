<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Services\TenantCompanyDataPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantCompanyDataPushServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function sync_scope_includes_roles_and_model_has_roles(): void
    {
        $scope = app(TenantCompanyDataPushService::class)->describeSyncScope();
        $tables = $scope['tables_with_company_id'] ?? [];
        $excluded = $scope['excluded_tables'] ?? [];

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'company_id')) {
            $this->assertContains('roles', $tables);
            $this->assertNotContains('roles', $excluded);
        }
        if (Schema::hasTable('model_has_roles') && Schema::hasColumn('model_has_roles', 'company_id')) {
            $this->assertContains('model_has_roles', $tables);
            $this->assertNotContains('model_has_roles', $excluded);
        }
        $this->assertContains('permissions', $excluded);
    }

    #[Test]
    public function sync_scope_lists_taxi_module_tables(): void
    {
        $scope = app(TenantCompanyDataPushService::class)->describeSyncScope();
        $taxiTables = $scope['taxi_module_tables'] ?? [];

        $this->assertContains('vehicles', $taxiTables);
        $this->assertContains('default_rates', $taxiTables);
    }

    #[Test]
    public function sync_scope_includes_prerequisite_tables_for_module_foreign_keys(): void
    {
        $scope = app(TenantCompanyDataPushService::class)->describeSyncScope();
        $prerequisite = $scope['prerequisite_tables'] ?? [];

        if (Schema::hasTable('modules') && Schema::hasTable('company_module')) {
            $this->assertContains('modules', $prerequisite);
        }
        if (Schema::hasTable('frontend_themes') && Schema::hasTable('modules')
            && Schema::hasColumn('modules', 'frontend_theme_id')) {
            $themePos = array_search('frontend_themes', $prerequisite, true);
            $modulePos = array_search('modules', $prerequisite, true);
            if ($themePos !== false && $modulePos !== false) {
                $this->assertLessThan($modulePos, $themePos, 'frontend_themes must sync before modules');
            }
        }
    }

    #[Test]
    public function sync_scope_lists_users_table_when_present(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'company_id')) {
            $this->markTestSkipped('users.company_id required');
        }

        $scope = app(TenantCompanyDataPushService::class)->describeSyncScope();
        $this->assertContains('users', $scope['tables_with_company_id'] ?? []);
    }

    #[Test]
    public function find_existing_email_template_skips_duplicate_type_per_company(): void
    {
        if (! Schema::hasTable('email_templates') || ! Schema::hasTable('companies')) {
            $this->markTestSkipped('email_templates required');
        }

        $company = Company::query()->create(['name' => 'Tpl Co', 'slug' => 'tpl-co-'.uniqid()]);
        DB::table('email_templates')->insert([
            'name' => 'Contact',
            'subject' => 'Onderwerp',
            'html_content' => '<p>Hi</p>',
            'type' => 'contact_form',
            'company_id' => $company->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $method = new \ReflectionMethod(TenantCompanyDataPushService::class, 'findExistingEmailTemplateId');
        $method->setAccessible(true);
        $service = app(TenantCompanyDataPushService::class);
        $conn = (string) config('database.default');

        $found = $method->invoke($service, $conn, [
            'company_id' => $company->id,
            'type' => 'contact_form',
            'name' => 'Other name',
        ]);

        $this->assertNotNull($found);
        $this->assertNull($method->invoke($service, $conn, [
            'company_id' => $company->id,
            'type' => 'other_type',
            'name' => 'Contact',
        ]));
    }

    #[Test]
    public function backfill_timestamps_fills_null_created_at_on_target(): void
    {
        if (! Schema::hasTable('companies')) {
            $this->markTestSkipped('companies table required');
        }

        $company = Company::query()->create([
            'name' => 'Timestamp Backfill Co',
            'slug' => 'timestamp-backfill-'.uniqid(),
        ]);
        $sourceCreated = now()->subDays(10);
        DB::table('companies')->where('id', $company->id)->update([
            'created_at' => null,
            'updated_at' => null,
        ]);

        $service = app(TenantCompanyDataPushService::class);
        $method = new \ReflectionMethod(TenantCompanyDataPushService::class, 'backfillTimestampsIfMissingOnTarget');
        $method->setAccessible(true);
        $conn = (string) config('database.default');
        $method->invoke($service, $conn, 'companies', (int) $company->id, [
            'created_at' => $sourceCreated,
            'updated_at' => now()->subDay(),
        ]);

        $refreshed = DB::table('companies')->where('id', $company->id)->first();
        $this->assertNotNull($refreshed->created_at);
        $this->assertSame($sourceCreated->format('Y-m-d H:i:s'), (string) $refreshed->created_at);
    }

    #[Test]
    public function normalize_binary_columns_converts_stream_resource_to_bytes(): void
    {
        $service = app(TenantCompanyDataPushService::class);
        $method = new \ReflectionMethod(TenantCompanyDataPushService::class, 'normalizeBinaryColumns');
        $method->setAccessible(true);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, 'BINARY-AVATAR-BYTES');
        rewind($stream);

        $out = $method->invoke($service, 'users', ['photo_blob' => $stream, 'name' => 'Jan']);

        $this->assertSame('BINARY-AVATAR-BYTES', $out['photo_blob']);
        $this->assertSame('Jan', $out['name']);
    }

    #[Test]
    public function avatar_update_only_fills_when_target_has_no_photo(): void
    {
        $service = app(TenantCompanyDataPushService::class);
        $method = new \ReflectionMethod(TenantCompanyDataPushService::class, 'avatarUpdateForExistingTargetUser');
        $method->setAccessible(true);

        $payload = ['photo_blob' => 'BYTES', 'photo_mime_type' => 'image/png', 'photo' => null];

        $emptyTarget = (object) ['photo_blob' => null, 'photo' => null];
        $update = $method->invoke($service, $emptyTarget, $payload);
        $this->assertSame('BYTES', $update['photo_blob'] ?? null);
        $this->assertSame('image/png', $update['photo_mime_type'] ?? null);
        $this->assertArrayNotHasKey('photo', $update);

        $targetWithPhoto = (object) ['photo_blob' => 'EXISTING', 'photo' => null];
        $this->assertSame([], $method->invoke($service, $targetWithPhoto, $payload));
    }

    #[Test]
    public function global_general_setting_keys_include_whatsapp_widget(): void
    {
        $keys = config('tenant_sync.global_general_setting_keys', []);

        $this->assertContains('WHATSAPP_WIDGET_ENABLED', $keys);
        $this->assertContains('WHATSAPP_WIDGET_PHONE', $keys);
    }

    #[Test]
    public function collect_users_includes_role_linked_users_without_company_id(): void
    {
        if (! Schema::hasTable('model_has_roles') || ! Schema::hasColumn('model_has_roles', 'company_id')) {
            $this->markTestSkipped('model_has_roles.company_id required');
        }

        $company = Company::query()->create(['name' => 'Taxi Royaal Test', 'slug' => 'taxi-royaal-test-'.uniqid()]);
        $role = Role::create([
            'name' => 'chauffeur-sync-test',
            'guard_name' => 'web',
            'company_id' => $company->id,
        ]);
        $user = User::factory()->create([
            'email' => 'chauffeur-sync-'.uniqid().'@example.test',
            'company_id' => null,
        ]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole($role);
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        $conn = (string) config('database.default');
        $method = new \ReflectionMethod(TenantCompanyDataPushService::class, 'collectUsersRowsForCompany');
        $method->setAccessible(true);
        $rows = $method->invoke(app(TenantCompanyDataPushService::class), $conn, (int) $company->id);

        $this->assertTrue(
            $rows->contains(fn ($row) => (int) $row->id === (int) $user->id),
            'User linked only via model_has_roles should be included in tenant user sync set.'
        );
    }
}
