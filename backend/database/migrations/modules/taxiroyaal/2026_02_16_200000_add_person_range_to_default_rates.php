<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Personenbereik (1-4 en 5-8) voor standaardtarieven.
     */
    public function up(): void
    {
        if (!Schema::hasTable('default_rates')) {
            return;
        }

        if (!Schema::hasColumn('default_rates', 'person_range')) {
            Schema::table('default_rates', function (Blueprint $table) {
                $table->string('person_range', 10)->default('1-4')->after('id');
            });
            Schema::getConnection()->table('default_rates')->update(['person_range' => '1-4']);
        }

        $exists = Schema::getConnection()->table('default_rates')->where('person_range', '5-8')->exists();
        if (!$exists) {
            Schema::getConnection()->table('default_rates')->insert([
                'person_range' => '5-8',
                'base_fare' => null,
                'min_fare' => 0,
                'price_per_km' => 0,
                'price_per_min' => 0,
                'cleaning_costs' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('default_rates') || !Schema::hasColumn('default_rates', 'person_range')) {
            return;
        }
        Schema::getConnection()->table('default_rates')->where('person_range', '5-8')->delete();
        Schema::table('default_rates', fn (Blueprint $table) => $table->dropColumn('person_range'));
    }
};
