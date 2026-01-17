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
        Schema::table('chats', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->after('ended_by_id');
            $table->string('deleted_by_type')->nullable()->after('deleted_at');
            $table->unsignedBigInteger('deleted_by_id')->nullable()->after('deleted_by_type');
            $table->index(['deleted_by_type', 'deleted_by_id']);
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropIndex(['deleted_by_type', 'deleted_by_id']);
            $table->dropIndex(['deleted_at']);
            $table->dropColumn(['deleted_at', 'deleted_by_type', 'deleted_by_id']);
        });
    }
};
