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
        // First ensure job_titles table exists
        if (!Schema::hasTable('job_titles')) {
            Schema::create('job_titles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->integer('usage_count')->default(0);
                $table->timestamps();
                $table->index('name');
            });
        }
        
        Schema::table('users', function (Blueprint $table) {
            $table->string('function')->nullable()->after('last_name');
            $table->foreignId('job_title_id')->nullable()->after('function')->constrained('job_titles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['job_title_id']);
            $table->dropColumn(['function', 'job_title_id']);
        });
    }
};
