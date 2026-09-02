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
            if (! Schema::hasColumn('company_billing_profiles', 'trial_started_at')) {
                $table->date('trial_started_at')->nullable()->after('subscription_end_date');
            }
            if (! Schema::hasColumn('company_billing_profiles', 'trial_ends_at')) {
                $table->date('trial_ends_at')->nullable()->after('trial_started_at');
            }
            if (! Schema::hasColumn('company_billing_profiles', 'trial_notice_sent_at')) {
                $table->timestamp('trial_notice_sent_at')->nullable()->after('trial_ends_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('company_billing_profiles')) {
            return;
        }

        Schema::table('company_billing_profiles', function (Blueprint $table) {
            foreach (['trial_notice_sent_at', 'trial_ends_at', 'trial_started_at'] as $column) {
                if (Schema::hasColumn('company_billing_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
