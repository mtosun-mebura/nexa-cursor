<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'billing_period')) {
                $table->string('billing_period', 7)->nullable()->after('module_reference_id')->index();
            }
        });

        if (
            Schema::hasColumn('invoices', 'module')
            && Schema::hasColumn('invoices', 'module_reference_id')
            && Schema::hasColumn('invoices', 'billing_period')
        ) {
            Schema::table('invoices', function (Blueprint $table) {
                try {
                    $table->dropUnique('invoices_module_reference_unique');
                } catch (\Throwable) {
                    // Index may not exist in all environments.
                }
            });

            Schema::table('invoices', function (Blueprint $table) {
                $table->unique(
                    ['module', 'module_reference_id', 'billing_period'],
                    'invoices_module_reference_period_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            try {
                $table->dropUnique('invoices_module_reference_period_unique');
            } catch (\Throwable) {
                //
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'billing_period')) {
                $table->dropColumn('billing_period');
            }

            if (Schema::hasColumn('invoices', 'module') && Schema::hasColumn('invoices', 'module_reference_id')) {
                $table->unique(['module', 'module_reference_id'], 'invoices_module_reference_unique');
            }
        });
    }
};
