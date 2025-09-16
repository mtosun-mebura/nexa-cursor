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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Nederland');
            $table->string('cv_path')->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('website_url')->nullable();
            $table->integer('experience_years')->default(0);
            $table->enum('education_level', [
                'high_school',
                'vocational',
                'bachelor',
                'master',
                'phd'
            ])->nullable();
            $table->string('current_position')->nullable();
            $table->string('desired_position')->nullable();
            $table->decimal('salary_expectation', 10, 2)->nullable();
            $table->enum('availability', [
                'immediate',
                '2_weeks',
                '1_month',
                '3_months',
                'custom'
            ])->default('immediate');
            $table->enum('preferred_work_type', [
                'full_time',
                'part_time',
                'freelance',
                'contract',
                'hybrid',
                'remote'
            ])->default('full_time');
            $table->string('preferred_location')->nullable();
            $table->json('skills')->nullable();
            $table->json('languages')->nullable();
            $table->enum('status', [
                'pending',
                'active',
                'rejected',
                'hired'
            ])->default('pending');
            $table->text('notes')->nullable();
            $table->string('source')->default('website');
            $table->boolean('consent_gdpr')->default(false);
            $table->boolean('consent_marketing')->default(false);
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['email']);
            $table->index(['experience_years']);
            $table->index(['education_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
