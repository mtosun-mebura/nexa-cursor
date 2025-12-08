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
        // Rename table from categories to branches
        Schema::rename('categories', 'branches');
        
        // For SQLite, we need to recreate the table with the new column name
        if (DB::getDriverName() === 'sqlite') {
            // Drop old foreign key constraint by recreating vacancies table
            Schema::table('vacancies', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
            });
            
            // Rename column using raw SQL for SQLite
            DB::statement('ALTER TABLE vacancies RENAME COLUMN category_id TO branch_id');
            
            // Re-add foreign key constraint
            Schema::table('vacancies', function (Blueprint $table) {
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            });
        } else {
            // For other databases, use standard renameColumn
            Schema::table('vacancies', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
            });
            
            Schema::table('vacancies', function (Blueprint $table) {
                $table->renameColumn('category_id', 'branch_id');
            });
            
            Schema::table('vacancies', function (Blueprint $table) {
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraint
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });
        
        // Rename column back
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE vacancies RENAME COLUMN branch_id TO category_id');
        } else {
            Schema::table('vacancies', function (Blueprint $table) {
                $table->renameColumn('branch_id', 'category_id');
            });
        }
        
        // Add old foreign key constraint
        Schema::table('vacancies', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });
        
        // Rename table back
        Schema::rename('branches', 'categories');
    }
};
