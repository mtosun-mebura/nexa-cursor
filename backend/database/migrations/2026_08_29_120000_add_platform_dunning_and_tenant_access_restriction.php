<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_billing_profiles')) {
            Schema::table('company_billing_profiles', function (Blueprint $table) {
                if (! Schema::hasColumn('company_billing_profiles', 'overdue_block_mode')) {
                    $table->string('overdue_block_mode', 20)->default('bookings')->after('auto_collect_enabled');
                }
                if (! Schema::hasColumn('company_billing_profiles', 'access_restriction')) {
                    $table->string('access_restriction', 20)->default('none')->after('overdue_block_mode');
                }
                if (! Schema::hasColumn('company_billing_profiles', 'access_restriction_source')) {
                    $table->string('access_restriction_source', 20)->nullable()->after('access_restriction');
                }
                if (! Schema::hasColumn('company_billing_profiles', 'access_restricted_at')) {
                    $table->timestamp('access_restricted_at')->nullable()->after('access_restriction_source');
                }
                if (! Schema::hasColumn('company_billing_profiles', 'access_restricted_invoice_id')) {
                    $table->unsignedBigInteger('access_restricted_invoice_id')->nullable()->after('access_restricted_at');
                }
            });
        }

        if (Schema::hasTable('platform_invoices')) {
            Schema::table('platform_invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('platform_invoices', 'first_reminder_sent_at')) {
                    $table->timestamp('first_reminder_sent_at')->nullable()->after('sent_at');
                }
                if (! Schema::hasColumn('platform_invoices', 'second_reminder_sent_at')) {
                    $table->timestamp('second_reminder_sent_at')->nullable()->after('first_reminder_sent_at');
                }
                if (! Schema::hasColumn('platform_invoices', 'blocked_at')) {
                    $table->timestamp('blocked_at')->nullable()->after('second_reminder_sent_at');
                }
                if (! Schema::hasColumn('platform_invoices', 'block_waived_at')) {
                    $table->timestamp('block_waived_at')->nullable()->after('blocked_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company_billing_profiles')) {
            Schema::table('company_billing_profiles', function (Blueprint $table) {
                foreach (['overdue_block_mode', 'access_restriction', 'access_restriction_source', 'access_restricted_at', 'access_restricted_invoice_id'] as $column) {
                    if (Schema::hasColumn('company_billing_profiles', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('platform_invoices')) {
            Schema::table('platform_invoices', function (Blueprint $table) {
                foreach (['first_reminder_sent_at', 'second_reminder_sent_at', 'blocked_at', 'block_waived_at'] as $column) {
                    if (Schema::hasColumn('platform_invoices', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
