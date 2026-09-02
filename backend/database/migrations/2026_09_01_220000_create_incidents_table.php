<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('incidents')) {
            return;
        }

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reporter_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reference', 32)->unique();
            $table->string('kind', 32);
            $table->string('title');
            $table->string('page_url', 2048)->nullable();
            $table->text('description');
            $table->string('priority', 20)->default('normal');
            $table->string('status', 32)->default('open');
            $table->json('screenshots')->nullable();
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['reporter_user_id', 'created_at']);
            $table->index(['status', 'priority', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
