<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Algemene standaardtarieven (één rij, niet per voertuig).
     */
    public function up(): void
    {
        if (Schema::hasTable('default_rates')) {
            return;
        }
        Schema::create('default_rates', function (Blueprint $table) {
            $table->id();
            $table->decimal('base_fare', 10, 2)->nullable();
            $table->decimal('min_fare', 10, 2)->default(0);
            $table->decimal('price_per_km', 10, 2)->default(0);
            $table->decimal('price_per_min', 10, 2)->default(0);
            $table->timestamps();
        });
        // Eerste rij aanmaken op dezelfde connection als de migratie
        Schema::getConnection()->table('default_rates')->insert([
            'base_fare' => null,
            'min_fare' => 0,
            'price_per_km' => 0,
            'price_per_min' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('default_rates');
    }
};
