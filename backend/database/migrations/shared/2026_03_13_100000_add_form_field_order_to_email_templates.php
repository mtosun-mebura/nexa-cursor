<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Volgorde en selectie van formuliervelden per template (type informatieaanvraag).
     * JSON array van info_request_form_field ids, bijv. [1,3,2,5,4]. Null = alle velden in standaard volgorde.
     */
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->json('form_field_order')->nullable()->after('recipient_email');
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn('form_field_order');
        });
    }
};
