<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->longText('contact_photo_blob')->nullable()->after('contact_phone');
            $table->string('contact_photo_mime_type')->nullable()->after('contact_photo_blob');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn(['contact_photo_blob', 'contact_photo_mime_type']);
        });
    }
};

