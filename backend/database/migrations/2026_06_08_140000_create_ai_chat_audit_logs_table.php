<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_chat_audit_logs')) {
            return;
        }

        Schema::create('ai_chat_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('channel', 20);
            $table->string('intent', 40)->index();
            $table->boolean('is_admin')->default(false);
            $table->boolean('allow_live_data')->default(false);
            $table->text('message');
            $table->string('data_source', 20);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_audit_logs');
    }
};
