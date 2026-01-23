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
            // Add original_notification_id to link related notifications
            // This links response notifications and confirmation notifications to their original notification
            $table->unsignedBigInteger('original_notification_id')->nullable()->after('location_id');
            $table->foreign('original_notification_id')->references('id')->on('notifications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['original_notification_id']);
            $table->dropColumn('original_notification_id');
        });
    }
};
