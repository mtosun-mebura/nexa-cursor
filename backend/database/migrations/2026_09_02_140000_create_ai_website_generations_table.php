<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_website_generations')) {
            return;
        }

        Schema::create('ai_website_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('homepage_page_id')->nullable();
            $table->string('status', 40)->default('context');
            $table->string('current_step', 40)->default('context');
            $table->string('source_type', 40)->default('new');
            $table->string('source_url', 500)->nullable();
            $table->text('source_context')->nullable();
            $table->json('website_brief_json')->nullable();
            $table->json('sitemap_json')->nullable();
            $table->json('page_plans_json')->nullable();
            $table->json('generation_settings_json')->nullable();
            $table->json('source_extract_json')->nullable();
            $table->string('prompt_versions', 120)->nullable();
            $table->boolean('used_openai')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_website_generations');
    }
};
