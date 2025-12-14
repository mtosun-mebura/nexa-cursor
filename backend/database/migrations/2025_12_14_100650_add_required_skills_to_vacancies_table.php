<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            if (!Schema::hasColumn('vacancies', 'required_skills')) {
                // Prefer JSON when supported; fallback to TEXT for SQLite (tests).
                if (DB::getDriverName() === 'sqlite') {
                    $table->text('required_skills')->nullable()->after('requirements');
                } else {
                    $table->json('required_skills')->nullable()->after('requirements');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            if (Schema::hasColumn('vacancies', 'required_skills')) {
                $table->dropColumn('required_skills');
            }
        });
    }
};
