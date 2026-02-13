<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('page_type'); // home, about, contact, custom, module
            $table->string('module_name')->nullable();
            $table->foreignId('frontend_theme_id')->nullable()->constrained('frontend_themes')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['page_type', 'is_active']);
            $table->index(['module_name', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_pages');
    }
};
