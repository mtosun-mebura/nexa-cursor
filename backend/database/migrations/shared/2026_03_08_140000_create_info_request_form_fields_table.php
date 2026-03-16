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
        Schema::create('info_request_form_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Slug/name voor request (voornaam, achternaam, email, etc.)');
            $table->string('label')->comment('Label op het formulier');
            $table->boolean('is_required')->default(false);
            $table->string('validation_rule', 100)->nullable()->comment('email, tel, number, of regex:...');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('info_request_form_fields');
    }
};
