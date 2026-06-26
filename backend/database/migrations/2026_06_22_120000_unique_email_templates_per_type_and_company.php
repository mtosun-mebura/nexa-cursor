<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS email_templates_type_global_unique ON email_templates (type) WHERE company_id IS NULL');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS email_templates_type_company_unique ON email_templates (type, company_id) WHERE company_id IS NOT NULL');
        } elseif ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS email_templates_type_global_unique ON email_templates (type) WHERE company_id IS NULL');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS email_templates_type_company_unique ON email_templates (type, company_id) WHERE company_id IS NOT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS email_templates_type_global_unique');
            DB::statement('DROP INDEX IF EXISTS email_templates_type_company_unique');
        }
    }
};
