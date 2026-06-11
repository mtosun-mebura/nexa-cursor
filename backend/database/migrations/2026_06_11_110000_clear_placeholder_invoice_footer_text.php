<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoice_settings') || ! Schema::hasColumn('invoice_settings', 'invoice_footer_text')) {
            return;
        }

        DB::table('invoice_settings')
            ->where('invoice_footer_text', 'Hier komt een footer tekst.')
            ->update(['invoice_footer_text' => null]);
    }

    public function down(): void
    {
        // Niet terugdraaien: placeholder was geen gewenste productie-inhoud.
    }
};
