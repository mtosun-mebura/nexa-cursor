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
        Schema::table('notifications', function (Blueprint $table) {
            // Rename content to message
            $table->renameColumn('content', 'message');
            
            // Add new columns
            $table->string('type', 50)->nullable()->after('user_id');
            $table->string('title', 255)->nullable()->after('type');
            $table->string('priority', 20)->default('normal')->after('message');
            $table->timestamp('read_at')->nullable()->after('priority');
            $table->string('action_url', 500)->nullable()->after('read_at');
            $table->text('data')->nullable()->after('action_url');
            $table->timestamp('scheduled_at')->nullable()->after('data');
            
            // Drop is_read as it's replaced by read_at
            $table->dropColumn('is_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Revert changes
            $table->renameColumn('message', 'content');
            $table->boolean('is_read')->default(false)->after('content');
            $table->dropColumn(['type', 'title', 'priority', 'read_at', 'action_url', 'data', 'scheduled_at']);
        });
    }
};
