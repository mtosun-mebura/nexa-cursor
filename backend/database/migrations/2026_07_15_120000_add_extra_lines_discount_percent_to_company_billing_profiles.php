<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_billing_profiles')) {
            return;
        }

        Schema::table('company_billing_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('company_billing_profiles', 'extra_lines_discount_percent')) {
                $table->unsignedTinyInteger('extra_lines_discount_percent')->default(0)->after('extra_lines_applied_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('company_billing_profiles')) {
            return;
        }

        Schema::table('company_billing_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('company_billing_profiles', 'extra_lines_discount_percent')) {
                $table->dropColumn('extra_lines_discount_percent');
            }
        });
    }
};
