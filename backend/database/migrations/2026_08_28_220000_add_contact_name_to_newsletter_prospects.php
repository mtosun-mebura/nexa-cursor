<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('newsletter_prospects')) {
            return;
        }

        Schema::table('newsletter_prospects', function (Blueprint $table) {
            if (! Schema::hasColumn('newsletter_prospects', 'first_name')) {
                $table->string('first_name', 80)->nullable()->after('company_name');
            }
            if (! Schema::hasColumn('newsletter_prospects', 'middle_name')) {
                $table->string('middle_name', 40)->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('newsletter_prospects', 'last_name')) {
                $table->string('last_name', 80)->nullable()->after('middle_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('newsletter_prospects')) {
            return;
        }

        Schema::table('newsletter_prospects', function (Blueprint $table) {
            foreach (['last_name', 'middle_name', 'first_name'] as $column) {
                if (Schema::hasColumn('newsletter_prospects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
