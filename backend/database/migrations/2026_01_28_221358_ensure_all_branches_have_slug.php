<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update alle branches zonder slug
        $branches = DB::table('branches')->whereNull('slug')->orWhere('slug', '')->get();
        
        foreach ($branches as $branch) {
            $slug = Str::slug($branch->name);
            $baseSlug = $slug;
            $counter = 1;
            
            // Zorg dat slug uniek is
            while (DB::table('branches')->where('slug', $slug)->where('id', '!=', $branch->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            DB::table('branches')
                ->where('id', $branch->id)
                ->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Geen rollback nodig - slugs kunnen blijven bestaan
    }
};
