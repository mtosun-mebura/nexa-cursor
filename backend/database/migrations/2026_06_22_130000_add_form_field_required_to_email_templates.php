<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        if (Schema::hasColumn('email_templates', 'form_field_required')) {
            return;
        }

        Schema::table('email_templates', function (Blueprint $table) {
            $table->json('form_field_required')->nullable()->after('form_field_order');
        });

        if (Schema::hasTable('info_request_form_fields')) {
            \Illuminate\Support\Facades\DB::table('info_request_form_fields')
                ->where('name', 'telefoonnummer')
                ->update(['is_required' => true]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_templates') || ! Schema::hasColumn('email_templates', 'form_field_required')) {
            return;
        }

        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn('form_field_required');
        });
    }
};
