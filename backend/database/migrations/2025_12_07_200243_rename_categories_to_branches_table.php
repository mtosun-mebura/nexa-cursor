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
        // Check if categories table exists and branches doesn't exist
        if (Schema::hasTable('categories') && !Schema::hasTable('branches')) {
            // Rename table from categories to branches
            Schema::rename('categories', 'branches');
            
            // For SQLite, we need to recreate the table with the new column name
            if (DB::getDriverName() === 'sqlite') {
                // Drop old foreign key constraint by recreating vacancies table
                if (Schema::hasTable('vacancies')) {
                    try {
                        Schema::table('vacancies', function (Blueprint $table) {
                            $table->dropForeign(['category_id']);
                        });
                    } catch (\Exception $e) {
                        // Foreign key might not exist, continue
                    }
                    
                    // Rename column using raw SQL for SQLite
                    DB::statement('ALTER TABLE vacancies RENAME COLUMN category_id TO branch_id');
                    
                    // Re-add foreign key constraint
                    Schema::table('vacancies', function (Blueprint $table) {
                        $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                    });
                }
            } else {
                // For other databases, use standard renameColumn
                if (Schema::hasTable('vacancies')) {
                    try {
                        Schema::table('vacancies', function (Blueprint $table) {
                            $table->dropForeign(['category_id']);
                        });
                    } catch (\Exception $e) {
                        // Foreign key might not exist, continue
                    }
                    
                    // Check if category_id column exists before renaming
                    if (Schema::hasColumn('vacancies', 'category_id')) {
                        Schema::table('vacancies', function (Blueprint $table) {
                            $table->renameColumn('category_id', 'branch_id');
                        });
                    }
                    
                    // Check if foreign key doesn't already exist
                    if (!Schema::hasColumn('vacancies', 'branch_id') || 
                        !$this->foreignKeyExists('vacancies', 'vacancies_branch_id_foreign')) {
                        Schema::table('vacancies', function (Blueprint $table) {
                            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                        });
                    }
                }
            }
        } elseif (Schema::hasTable('branches')) {
            // Branches table already exists, skip migration
            echo "Branches table already exists, skipping rename migration.\n";
        } else {
            // Neither table exists, skip migration
            echo "Categories table does not exist, skipping rename migration.\n";
        }
    }
    
    /**
     * Check if a foreign key constraint exists
     */
    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        
        if ($driver === 'pgsql') {
            $result = DB::selectOne(
                "SELECT constraint_name 
                 FROM information_schema.table_constraints 
                 WHERE table_name = ? AND constraint_name = ? AND constraint_type = 'FOREIGN KEY'",
                [$table, $constraintName]
            );
            return $result !== null;
        }
        
        // For other databases, assume it doesn't exist to be safe
        return false;
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
