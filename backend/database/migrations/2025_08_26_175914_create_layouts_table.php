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
        Schema::create('layouts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('type', 50);
            $table->string('version', 20)->nullable();
            $table->text('description')->nullable();
            $table->longText('html_content');
            $table->text('css_content')->nullable();
            $table->string('header_color', 7)->nullable(); // Hex color code
            $table->string('footer_color', 7)->nullable(); // Hex color code
            $table->string('logo_url', 500)->nullable();
            $table->string('footer_text', 255)->nullable();
            $table->text('metadata')->nullable(); // JSON metadata
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layouts');
    }
};
