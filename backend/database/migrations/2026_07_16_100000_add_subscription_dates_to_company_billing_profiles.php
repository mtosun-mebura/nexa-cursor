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
            if (! Schema::hasColumn('company_billing_profiles', 'subscription_start_date')) {
                $table->date('subscription_start_date')->nullable()->after('auto_collect_enabled');
            }
            if (! Schema::hasColumn('company_billing_profiles', 'subscription_end_date')) {
                $table->date('subscription_end_date')->nullable()->after('subscription_start_date');
            }
            if (! Schema::hasColumn('company_billing_profiles', 'mollie_subscription_id')) {
                $table->string('mollie_subscription_id', 64)->nullable()->after('subscription_end_date');
            }
            if (! Schema::hasColumn('company_billing_profiles', 'mollie_subscription_status')) {
                $table->string('mollie_subscription_status', 32)->nullable()->after('mollie_subscription_id');
            }
            if (! Schema::hasColumn('company_billing_profiles', 'mollie_subscription_synced_at')) {
                $table->timestamp('mollie_subscription_synced_at')->nullable()->after('mollie_subscription_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('company_billing_profiles')) {
            return;
        }

        Schema::table('company_billing_profiles', function (Blueprint $table) {
            foreach ([
                'mollie_subscription_synced_at',
                'mollie_subscription_status',
                'mollie_subscription_id',
                'subscription_end_date',
                'subscription_start_date',
            ] as $column) {
                if (Schema::hasColumn('company_billing_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
