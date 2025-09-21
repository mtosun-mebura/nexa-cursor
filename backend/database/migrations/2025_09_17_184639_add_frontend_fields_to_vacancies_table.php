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
        Schema::table('vacancies', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('status');
            $table->timestamp('published_at')->nullable()->after('is_active');
            $table->integer('salary_min')->nullable()->after('salary_range');
            $table->integer('salary_max')->nullable()->after('salary_min');
            $table->string('experience_level', 50)->nullable()->after('employment_type');
            $table->text('benefits')->nullable()->after('offer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'published_at', 'salary_min', 'salary_max', 'experience_level', 'benefits']);
        });
    }
};
