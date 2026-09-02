<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_config_access_grants')) {
            return;
        }

        Schema::create('company_config_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('capability', 64);
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'user_id', 'capability'], 'company_config_access_unique');
            $table->index(['user_id', 'capability']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_config_access_grants');
    }
};
