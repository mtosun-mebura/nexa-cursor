<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // If the base table doesn't exist yet, don't do anything.
        if (!Schema::hasTable('branches') || !Schema::hasTable('branch_functions')) {
            return;
        }

        $kunstFunctions = [
            'Grafisch_Ontwerper',
            'Illustrator',
            'Animator',
            'Art_Director',
            'Fotograaf',
            'Videograaf',
            'Muzikant',
            'Beeldhouwer',
            'Conservator',
            'Creatief_Producer',
        ];

        $artsFunctions = [
            'Huisarts',
            'Chirurg',
            'Hartchirurg',
            'Kinderarts',
            'Internist',
            'Neuroloog',
            'Psychiater',
            'Oncoloog',
            'Anesthesioloog',
            'Gynaecoloog',
            'Radioloog',
            'Revalidatiearts',
            'Spoedeisende_Hulp_Arts',
            'Bedrijfsarts',
        ];

        $now = now();

        // Helper: get branch by name/slug
        $getBranchId = function (string $name, string $slug) {
            return DB::table('branches')
                ->where('slug', $slug)
                ->orWhere('name', $name)
                ->value('id');
        };

        // 1) "pas de Branch voor de huidige arts aan naar Kunst"
        $kunstSlug = Str::slug('Kunst');
        $artsSlug = Str::slug('Arts');

        $artsOldId = DB::table('branches')->where('name', 'Arts')->value('id');
        $kunstId = $getBranchId('Kunst', $kunstSlug);

        if ($artsOldId && !$kunstId) {
            // Rename the existing "Arts" branch to "Kunst"
            DB::table('branches')->where('id', $artsOldId)->update([
                'name' => 'Kunst',
                'slug' => $kunstSlug,
                'updated_at' => $now,
            ]);
            $kunstId = $artsOldId;
        } elseif ($artsOldId && $kunstId && $artsOldId !== $kunstId) {
            // If Kunst already exists, move references and remove the old Arts-branch.
            if (Schema::hasTable('vacancies')) {
                DB::table('vacancies')->where('branch_id', $artsOldId)->update([
                    'branch_id' => $kunstId,
                ]);
            }

            // Deleting the old branch will cascade-delete its branch_functions.
            DB::table('branches')->where('id', $artsOldId)->delete();
        }

        // Ensure Kunst exists
        $kunstId = $getBranchId('Kunst', $kunstSlug);
        if (!$kunstId) {
            $kunstId = DB::table('branches')->insertGetId([
                'name' => 'Kunst',
                'slug' => $kunstSlug,
                'description' => null,
                'color' => null,
                'icon' => null,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 2) Ensure "Arts" exists (new medical branch)
        $artsId = $getBranchId('Arts', $artsSlug);
        if (!$artsId) {
            $artsId = DB::table('branches')->insertGetId([
                'name' => 'Arts',
                'slug' => $artsSlug,
                'description' => null,
                'color' => null,
                'icon' => null,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            // Normalize name/slug if needed
            DB::table('branches')->where('id', $artsId)->update([
                'name' => 'Arts',
                'slug' => $artsSlug,
                'updated_at' => $now,
            ]);
        }

        // 3) Replace functions for both branches (exact lists)
        DB::table('branch_functions')->where('branch_id', $kunstId)->delete();
        foreach ($kunstFunctions as $fn) {
            DB::table('branch_functions')->insert([
                'branch_id' => $kunstId,
                'name' => $fn,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('branch_functions')->where('branch_id', $artsId)->delete();
        foreach ($artsFunctions as $fn) {
            DB::table('branch_functions')->insert([
                'branch_id' => $artsId,
                'name' => $fn,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally left blank (data migration).
    }
};




