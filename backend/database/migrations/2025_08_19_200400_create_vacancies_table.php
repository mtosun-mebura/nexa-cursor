<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('title');
            $table->string('location')->nullable();
            $table->string('employment_type', 50)->nullable();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->text('offer')->nullable();
            $table->text('application_instructions')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->string('reference_number', 100)->nullable();
            $table->string('logo', 255)->nullable();
            $table->string('salary_range', 100)->nullable();
            $table->date('start_date')->nullable();
            $table->string('working_hours', 50)->nullable();
            $table->boolean('travel_expenses')->default(false);
            $table->boolean('remote_work')->default(false);
            $table->string('status', 20)->default('Open');
            $table->string('language', 20)->default('Nederlands');
            $table->timestamp('publication_date')->nullable();
            $table->timestamp('closing_date')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancies');
    }
};


