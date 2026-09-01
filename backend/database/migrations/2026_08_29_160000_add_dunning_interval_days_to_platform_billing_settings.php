<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_billing_settings')) {
            return;
        }

        if (! Schema::hasColumn('platform_billing_settings', 'dunning_interval_days')) {
            Schema::table('platform_billing_settings', function (Blueprint $table) {
                $table->unsignedSmallInteger('dunning_interval_days')->default(14)->after('payment_terms_days');
            });
        }

        foreach (DB::table('platform_billing_settings')->get(['id', 'payment_terms_days']) as $row) {
            $days = max(1, min(365, (int) ($row->payment_terms_days ?: 14)));
            DB::table('platform_billing_settings')->where('id', $row->id)->update([
                'dunning_interval_days' => $days,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_billing_settings') && Schema::hasColumn('platform_billing_settings', 'dunning_interval_days')) {
            Schema::table('platform_billing_settings', function (Blueprint $table) {
                $table->dropColumn('dunning_interval_days');
            });
        }
    }
};
