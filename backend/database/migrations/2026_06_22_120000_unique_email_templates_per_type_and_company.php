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

        $this->deduplicateEmailTemplates();

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
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

    private function deduplicateEmailTemplates(): void
    {
        $this->deduplicateEmailTemplateScope(global: true);
        $this->deduplicateEmailTemplateScope(global: false);
    }

    private function deduplicateEmailTemplateScope(bool $global): void
    {
        $query = DB::table('email_templates')
            ->select(['id', 'type', 'company_id', 'updated_at'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($global) {
            $query->whereNull('company_id');
        } else {
            $query->whereNotNull('company_id');
        }

        $keepByKey = [];
        $deleteIds = [];
        $reassign = [];

        foreach ($query->get() as $row) {
            $type = (string) ($row->type ?? '');
            if ($type === '') {
                continue;
            }

            $key = $global
                ? $type
                : $type.'|'.(int) $row->company_id;

            if (isset($keepByKey[$key])) {
                $deleteIds[] = (int) $row->id;
                $reassign[(int) $row->id] = $keepByKey[$key];

                continue;
            }

            $keepByKey[$key] = (int) $row->id;
        }

        if ($deleteIds === []) {
            return;
        }

        if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'email_template_id')) {
            foreach ($reassign as $duplicateId => $keepId) {
                DB::table('notifications')
                    ->where('email_template_id', $duplicateId)
                    ->update(['email_template_id' => $keepId]);
            }
        }

        DB::table('email_templates')->whereIn('id', $deleteIds)->delete();
    }
};
