<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_configurations')) {
            return;
        }

        Schema::create('job_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->foreignId('type_id')->nullable()->constrained('job_configuration_types')->nullOnDelete();
            $table->text('value');
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['type_id', 'company_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_configurations');
    }
};
