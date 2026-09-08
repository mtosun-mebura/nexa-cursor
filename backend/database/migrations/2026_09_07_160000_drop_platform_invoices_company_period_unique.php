<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_invoices')) {
            return;
        }

        try {
            Schema::table('platform_invoices', function (Blueprint $table) {
                $table->dropUnique(['company_id', 'billing_period']);
            });
        } catch (\Throwable) {
            try {
                Schema::table('platform_invoices', function (Blueprint $table) {
                    $table->dropUnique('platform_invoices_company_id_billing_period_unique');
                });
            } catch (\Throwable) {
            }
        }

        try {
            Schema::table('platform_invoices', function (Blueprint $table) {
                $table->index(['company_id', 'billing_period'], 'platform_invoices_company_period_index');
            });
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('platform_invoices')) {
            return;
        }

        try {
            Schema::table('platform_invoices', function (Blueprint $table) {
                $table->dropIndex('platform_invoices_company_period_index');
            });
        } catch (\Throwable) {
        }

        Schema::table('platform_invoices', function (Blueprint $table) {
            $table->unique(['company_id', 'billing_period']);
        });
    }
};
