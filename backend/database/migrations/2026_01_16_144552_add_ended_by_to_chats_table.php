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
            $table->string('ended_by_type')->nullable()->after('ended_at');
            $table->unsignedBigInteger('ended_by_id')->nullable()->after('ended_by_type');
            $table->index(['ended_by_type', 'ended_by_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropIndex(['ended_by_type', 'ended_by_id']);
            $table->dropColumn(['ended_by_type', 'ended_by_id']);
        });
    }
};
