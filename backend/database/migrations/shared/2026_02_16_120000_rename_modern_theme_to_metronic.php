<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Hernoem weergavenaam "Modern" naar "Metronic" voor het thema met slug 'modern'.
     */
    public function up(): void
    {
        DB::table('frontend_themes')
            ->where('slug', 'modern')
            ->update([
                'name' => 'Metronic',
                'description' => 'Strak Metronic-design met veel witruimte. Huidige website-layout (Home-pagina).',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('frontend_themes')
            ->where('slug', 'modern')
            ->update([
                'name' => 'Modern',
                'description' => 'Strak en modern design met veel witruimte. Huidige website-layout (Home-pagina).',
            ]);
    }
};
