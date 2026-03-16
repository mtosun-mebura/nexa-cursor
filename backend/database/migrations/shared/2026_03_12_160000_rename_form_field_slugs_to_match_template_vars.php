<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Hernoem slug 'email' -> 'email_aanvraag' en 'telefoon' -> 'telefoonnummer'
     * zodat template-variabelen ({{ EMAIL_AANVRAAG }}, {{ TELEFOONNUMMER }}) overeenkomen met de slug.
     */
    public function up(): void
    {
        DB::table('info_request_form_fields')
            ->where('name', 'email')
            ->update(['name' => 'email_aanvraag']);
        DB::table('info_request_form_fields')
            ->where('name', 'telefoon')
            ->update(['name' => 'telefoonnummer']);
    }

    public function down(): void
    {
        DB::table('info_request_form_fields')
            ->where('name', 'email_aanvraag')
            ->update(['name' => 'email']);
        DB::table('info_request_form_fields')
            ->where('name', 'telefoonnummer')
            ->update(['name' => 'telefoon']);
    }
};
