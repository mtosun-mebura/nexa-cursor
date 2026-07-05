<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_upgrade_logs', function (Blueprint $table) {
            $table->id();
            $table->string('from_release', 32);
            $table->string('to_release', 32)->nullable();
            $table->string('status', 32)->default('running');
            $table->json('from_stack')->nullable();
            $table->json('to_stack')->nullable();
            $table->json('steps_log')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('triggered_by_user_id')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_upgrade_logs');
    }
};
