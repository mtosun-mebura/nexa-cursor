<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('newsletter_campaigns')) {
            Schema::create('newsletter_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('subject');
                $table->string('preview_text')->nullable();
                $table->json('blocks')->nullable();
                $table->string('status', 20)->default('draft');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('last_sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('newsletter_prospects')) {
            Schema::create('newsletter_prospects', function (Blueprint $table) {
                $table->id();
                $table->string('company_name');
                $table->string('email');
                $table->string('phone', 40)->nullable();
                $table->string('website')->nullable();
                $table->string('city')->nullable();
                $table->string('province', 40)->nullable();
                $table->string('branch', 80)->default('taxi');
                $table->string('source', 40)->default('ai_web');
                $table->string('source_url')->nullable();
                $table->string('status', 20)->default('subscribed');
                $table->string('unsubscribe_token', 64)->unique();
                $table->timestamp('unsubscribed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique('email');
                $table->index(['status', 'province']);
                $table->index('branch');
            });
        }

        if (! Schema::hasTable('newsletter_sends')) {
            Schema::create('newsletter_sends', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('newsletter_campaigns')->cascadeOnDelete();
                $table->foreignId('prospect_id')->constrained('newsletter_prospects')->cascadeOnDelete();
                $table->string('status', 20)->default('sent');
                $table->timestamp('sent_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->unique(['campaign_id', 'prospect_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_sends');
        Schema::dropIfExists('newsletter_prospects');
        Schema::dropIfExists('newsletter_campaigns');
    }
};
