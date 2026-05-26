<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ride_request_notification_logs')) {
            Schema::create('ride_request_notification_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ride_request_id')->index();
                $table->string('channel', 16)->index();
                $table->string('status', 16)->index();
                $table->string('recipient_name')->nullable();
                $table->string('recipient_address')->nullable();
                $table->unsignedBigInteger('driver_id')->nullable()->index();
                $table->text('detail')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['ride_request_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_request_notification_logs');
    }
};
