<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoice_settings') && ! Schema::hasColumn('invoice_settings', 'company_id')) {
            Schema::table('invoice_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                $table->unsignedBigInteger('location_id')->nullable()->after('company_id')->index();
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('invoices', 'module')) {
                    $table->string('module', 32)->nullable()->after('company_id')->index();
                }
                if (! Schema::hasColumn('invoices', 'module_reference_id')) {
                    $table->unsignedBigInteger('module_reference_id')->nullable()->after('module')->index();
                }
                if (! Schema::hasColumn('invoices', 'customer_name')) {
                    $table->string('customer_name')->nullable()->after('module_reference_id');
                }
                if (! Schema::hasColumn('invoices', 'customer_email')) {
                    $table->string('customer_email')->nullable()->after('customer_name');
                }
            });

            if (Schema::hasColumn('invoices', 'module') && Schema::hasColumn('invoices', 'module_reference_id')) {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->unique(['module', 'module_reference_id'], 'invoices_module_reference_unique');
                });
            }
        }

        if (Schema::hasTable('ride_requests') && ! Schema::hasColumn('ride_requests', 'invoice_id')) {
            Schema::table('ride_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after('final_price')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ride_requests') && Schema::hasColumn('ride_requests', 'invoice_id')) {
            Schema::table('ride_requests', function (Blueprint $table) {
                $table->dropColumn('invoice_id');
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (Schema::hasColumn('invoices', 'module')) {
                    $table->dropUnique('invoices_module_reference_unique');
                }
                foreach (['customer_email', 'customer_name', 'module_reference_id', 'module'] as $col) {
                    if (Schema::hasColumn('invoices', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('invoice_settings') && Schema::hasColumn('invoice_settings', 'company_id')) {
            Schema::table('invoice_settings', function (Blueprint $table) {
                $table->dropColumn(['company_id', 'location_id']);
            });
        }
    }
};
