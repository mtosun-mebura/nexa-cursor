<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generated_images', function (Blueprint $table) {
            $table->id();
            $table->uuid('website_media_uuid');
            $table->text('prompt');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('website_media_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generated_images');
    }
};
