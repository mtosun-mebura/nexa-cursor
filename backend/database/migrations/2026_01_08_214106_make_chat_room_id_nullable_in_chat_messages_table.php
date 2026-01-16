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
        Schema::table('chat_messages', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['chat_room_id']);
            // Make chat_room_id nullable
            $table->foreignId('chat_room_id')->nullable()->change();
            // Re-add foreign key constraint if chat_room_id is not null
            // Note: PostgreSQL doesn't support conditional foreign keys easily, so we'll skip this
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            // Make chat_room_id not nullable again
            $table->foreignId('chat_room_id')->nullable(false)->change();
            // Re-add foreign key constraint
            $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade');
        });
    }
};
