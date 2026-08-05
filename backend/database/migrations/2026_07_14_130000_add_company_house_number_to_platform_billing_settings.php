<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_billing_settings')) {
            return;
        }

        Schema::table('platform_billing_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_billing_settings', 'company_house_number')) {
                $table->string('company_house_number', 20)->nullable()->after('company_address');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('platform_billing_settings')) {
            return;
        }

        Schema::table('platform_billing_settings', function (Blueprint $table) {
            if (Schema::hasColumn('platform_billing_settings', 'company_house_number')) {
                $table->dropColumn('company_house_number');
            }
        });
    }
};
