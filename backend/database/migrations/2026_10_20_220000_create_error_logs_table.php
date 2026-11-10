<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('error_logs')) {
            return;
        }

        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('source', 80)->index();
            $table->boolean('resolved')->default(false)->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
