<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoice_settings')) {
            return;
        }

        if (! Schema::hasColumn('invoice_settings', 'invoice_payment_terms_text')) {
            Schema::table('invoice_settings', function (Blueprint $table) {
                $table->text('invoice_payment_terms_text')->nullable()->after('invoice_footer_text');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoice_settings')) {
            return;
        }

        if (Schema::hasColumn('invoice_settings', 'invoice_payment_terms_text')) {
            Schema::table('invoice_settings', function (Blueprint $table) {
                $table->dropColumn('invoice_payment_terms_text');
            });
        }
    }
};
