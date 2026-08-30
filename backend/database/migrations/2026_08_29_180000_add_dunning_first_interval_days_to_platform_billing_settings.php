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

        if (! Schema::hasColumn('platform_billing_settings', 'dunning_first_interval_days')) {
            Schema::table('platform_billing_settings', function (Blueprint $table) {
                $after = Schema::hasColumn('platform_billing_settings', 'dunning_interval_days')
                    ? 'dunning_interval_days'
                    : 'payment_terms_days';
                $table->unsignedSmallInteger('dunning_first_interval_days')->default(1)->after($after);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_billing_settings') && Schema::hasColumn('platform_billing_settings', 'dunning_first_interval_days')) {
            Schema::table('platform_billing_settings', function (Blueprint $table) {
                $table->dropColumn('dunning_first_interval_days');
            });
        }
    }
};
