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
        // Create view that joins candidates with candidate_texts
        // SQLite doesn't support CREATE OR REPLACE VIEW, so we drop first if exists
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP VIEW IF EXISTS candidate_with_texts_view');
        }
        
        DB::statement("
            CREATE VIEW candidate_with_texts_view AS
            SELECT 
                c.*,
                ct.last_responsibilities,
                ct.top_skills,
                ct.tools_tech,
                ct.employer_values,
                ct.best_result
            FROM candidates c
            LEFT JOIN candidate_texts ct ON ct.candidate_id = c.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS candidate_with_texts_view');
    }
};
