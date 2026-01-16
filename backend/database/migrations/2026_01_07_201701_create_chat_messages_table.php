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
        if (!Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_id')->constrained('chats')->onDelete('cascade');
                $table->morphs('sender'); // sender_id and sender_type (user or candidate)
                $table->text('message');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                
                $table->index(['chat_id', 'created_at']);
            });
        } else {
            // Table exists, check if we need to add columns
            Schema::table('chat_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('chat_messages', 'sender_id')) {
                    $table->morphs('sender');
                }
                if (!Schema::hasColumn('chat_messages', 'read_at')) {
                    $table->timestamp('read_at')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
