<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Instellingen die platform-breed blijven (niet per tenant). */
    private const GLOBAL_PLATFORM_KEYS = [
        'tenant_sync_target_database_url',
        'tenant_sync_push_enabled',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('general_settings')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (! Schema::hasColumn('general_settings', 'company_id')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            });
        }

        $this->dropLegacyUniqueOnKeyColumn($driver);

        try {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->unique(['company_id', 'key']);
            });
        } catch (\Throwable) {
            // bestaat al
        }

        if (! Schema::hasTable('companies')) {
            $this->addCompanyForeignKeyIfPossible();

            return;
        }

        $companyIds = DB::table('companies')->pluck('id');
        if ($companyIds->isEmpty()) {
            $this->addCompanyForeignKeyIfPossible();

            return;
        }

        $rows = DB::table('general_settings')->whereNull('company_id')->get(['key', 'value']);
        foreach ($companyIds as $companyId) {
            foreach ($rows as $row) {
                if (in_array($row->key, self::GLOBAL_PLATFORM_KEYS, true)) {
                    continue;
                }
                $exists = DB::table('general_settings')
                    ->where('company_id', $companyId)
                    ->where('key', $row->key)
                    ->exists();
                if ($exists) {
                    continue;
                }
                DB::table('general_settings')->insert([
                    'company_id' => $companyId,
                    'key' => $row->key,
                    'value' => $row->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('general_settings')
            ->whereNull('company_id')
            ->whereNotIn('key', self::GLOBAL_PLATFORM_KEYS)
            ->delete();

        $this->addCompanyForeignKeyIfPossible();
    }

    private function dropLegacyUniqueOnKeyColumn(string $driver): void
    {
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE general_settings DROP CONSTRAINT IF EXISTS general_settings_key_unique');

            return;
        }

        if ($driver === 'sqlite') {
            // SQLite: indexnaam varieert; meerdere pogingen
            foreach (['general_settings_key_unique', 'sqlite_autoindex_general_settings_2'] as $name) {
                try {
                    DB::statement('DROP INDEX IF EXISTS "'.$name.'"');
                } catch (\Throwable) {
                }
            }

            return;
        }

        try {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropUnique(['key']);
            });
        } catch (\Throwable) {
        }
    }

    private function addCompanyForeignKeyIfPossible(): void
    {
        if (! Schema::hasTable('general_settings') || ! Schema::hasTable('companies')) {
            return;
        }
        if (! Schema::hasColumn('general_settings', 'company_id')) {
            return;
        }
        try {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            });
        } catch (\Throwable) {
            // FK bestaat al
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('general_settings')) {
            return;
        }

        try {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropUnique(['company_id', 'key']);
            });
        } catch (\Throwable) {
        }

        DB::table('general_settings')->whereNotNull('company_id')->delete();

        if (Schema::hasColumn('general_settings', 'company_id')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('company_id');
            });
        }

        try {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->unique('key');
            });
        } catch (\Throwable) {
        }
    }
};
