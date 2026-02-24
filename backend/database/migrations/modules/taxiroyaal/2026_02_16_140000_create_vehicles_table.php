<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Voertuigen voor Taxi Royaal (per bedrijf).
     */
    public function up(): void
    {
        if (Schema::hasTable('vehicles')) {
            return;
        }
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name'); // bijv. "Auto 1", "Busje"
            $table->string('type', 20)->default('car'); // car, van, bus
            $table->string('license_plate')->nullable();
            $table->unsignedSmallInteger('seats')->default(4); // capaciteit
            $table->boolean('active')->default(true);
            $table->decimal('base_fare', 10, 2)->nullable();
            $table->decimal('price_per_km', 10, 2)->default(0);
            $table->decimal('price_per_min', 10, 2)->default(0);
            $table->decimal('min_fare', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
