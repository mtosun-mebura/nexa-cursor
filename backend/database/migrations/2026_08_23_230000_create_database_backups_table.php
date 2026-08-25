<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('disk_path');
            $table->string('connection', 64)->default('pgsql');
            $table->string('database_name', 128);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('status', 32)->default('completed');
            $table->string('trigger', 32)->default('manual');
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_backups');
    }
};
