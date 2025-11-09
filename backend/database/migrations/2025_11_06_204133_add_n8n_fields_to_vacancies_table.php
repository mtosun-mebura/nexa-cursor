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
            $table->json('required_skills')->nullable()->after('description');
            $table->json('nice_to_have')->nullable()->after('required_skills');
            $table->json('tools_tech')->nullable()->after('nice_to_have');
            $table->string('sector')->nullable()->after('category_id');
            $table->string('location_city')->nullable()->after('location');
            $table->string('work_mode')->nullable()->after('remote_work'); // 'locatie' | 'hybride' | 'remote'
            $table->integer('min_experience')->nullable()->after('experience_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn([
                'required_skills',
                'nice_to_have',
                'tools_tech',
                'sector',
                'location_city',
                'work_mode',
                'min_experience'
            ]);
        });
    }
};
