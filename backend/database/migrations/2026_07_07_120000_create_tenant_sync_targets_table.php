<?php

use App\Models\GeneralSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_sync_targets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('ssh_enabled')->default(false);
            $table->string('ssh_host')->nullable();
            $table->unsignedInteger('ssh_port')->default(22);
            $table->string('ssh_username')->nullable();
            $table->text('ssh_password_enc')->nullable();
            $table->string('remote_db_host')->default('127.0.0.1');
            $table->unsignedInteger('remote_db_port')->default(5432);
            $table->string('db_username')->nullable();
            $table->string('db_database')->nullable();
            $table->text('database_url')->nullable();
            $table->text('database_password_enc')->nullable();
            $table->boolean('push_enabled')->default(false);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        $this->seedDefaultTargetFromLegacySettings();
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_sync_targets');
    }

    /**
     * Migreer de bestaande enkele configuratie (flat general_settings keys)
     * naar een standaard sync-doel, zodat de sync na de upgrade blijft werken.
     */
    private function seedDefaultTargetFromLegacySettings(): void
    {
        if (! Schema::hasTable('general_settings')) {
            return;
        }

        $url = trim((string) GeneralSetting::get('tenant_sync_target_database_url', ''));
        $sshHost = trim((string) GeneralSetting::get('tenant_sync_ssh_host', ''));
        $dbUser = trim((string) GeneralSetting::get('tenant_sync_ssh_db_username', ''));

        // Alleen seeden als er zinvolle configuratie bestaat.
        if ($url === '' && $sshHost === '' && $dbUser === '') {
            return;
        }

        \App\Models\TenantSyncTarget::query()->create([
            'name' => 'Standaard',
            'ssh_enabled' => GeneralSetting::get('tenant_sync_ssh_enabled', '0') === '1',
            'ssh_host' => $sshHost !== '' ? $sshHost : null,
            'ssh_port' => max(1, min(65535, (int) GeneralSetting::get('tenant_sync_ssh_port', '22'))),
            'ssh_username' => trim((string) GeneralSetting::get('tenant_sync_ssh_username', '')) ?: null,
            'ssh_password_enc' => $this->legacyEnc('tenant_sync_ssh_password_enc'),
            'remote_db_host' => trim((string) GeneralSetting::get('tenant_sync_ssh_remote_db_host', '127.0.0.1')) ?: '127.0.0.1',
            'remote_db_port' => max(1, min(65535, (int) GeneralSetting::get('tenant_sync_ssh_remote_db_port', '5432'))),
            'db_username' => $dbUser !== '' ? $dbUser : null,
            'db_database' => trim((string) GeneralSetting::get('tenant_sync_ssh_db_database', '')) ?: null,
            'database_url' => $url !== '' ? $url : null,
            'database_password_enc' => $this->legacyEnc('tenant_sync_target_database_password_enc'),
            'push_enabled' => GeneralSetting::get('tenant_sync_push_enabled', '0') === '1',
            'is_active' => true,
        ]);
    }

    /**
     * De oude _enc keys staan al versleuteld opgeslagen (enc:...); we nemen ze
     * ongewijzigd over in de nieuwe kolommen zodat ze met dezelfde APP_KEY werken.
     */
    private function legacyEnc(string $key): ?string
    {
        $stored = trim((string) GeneralSetting::get($key, ''));

        return $stored !== '' ? $stored : null;
    }
};
