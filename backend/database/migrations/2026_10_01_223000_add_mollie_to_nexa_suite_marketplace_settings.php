<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nexa_suite_marketplace_settings')) {
            return;
        }

        Schema::table('nexa_suite_marketplace_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'mollie_api_key')) {
                $table->text('mollie_api_key')->nullable()->after('invoice_payment_terms_text');
            }
            if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'mollie_webhook_url')) {
                $table->string('mollie_webhook_url', 500)->nullable()->after('mollie_api_key');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('nexa_suite_marketplace_settings')) {
            return;
        }

        Schema::table('nexa_suite_marketplace_settings', function (Blueprint $table) {
            if (Schema::hasColumn('nexa_suite_marketplace_settings', 'mollie_webhook_url')) {
                $table->dropColumn('mollie_webhook_url');
            }
            if (Schema::hasColumn('nexa_suite_marketplace_settings', 'mollie_api_key')) {
                $table->dropColumn('mollie_api_key');
            }
        });
    }
};
