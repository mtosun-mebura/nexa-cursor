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
            if (! Schema::hasColumn('platform_billing_settings', 'mollie_api_key')) {
                $table->text('mollie_api_key')->nullable()->after('invoice_payment_terms_text');
            }
            if (! Schema::hasColumn('platform_billing_settings', 'mollie_webhook_url')) {
                $table->string('mollie_webhook_url', 500)->nullable()->after('mollie_api_key');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('platform_billing_settings')) {
            return;
        }

        Schema::table('platform_billing_settings', function (Blueprint $table) {
            foreach (['mollie_webhook_url', 'mollie_api_key'] as $column) {
                if (Schema::hasColumn('platform_billing_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
