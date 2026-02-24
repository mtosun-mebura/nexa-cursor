<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add columns to vehicles table if they were missing (e.g. table existed before Taxi Royaal migration).
     */
    public function up(): void
    {
        if (!Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'name')) {
                $table->string('name')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('vehicles', 'type')) {
                $table->string('type', 20)->default('car')->after('name');
            }
            if (!Schema::hasColumn('vehicles', 'license_plate')) {
                $table->string('license_plate')->nullable()->after('type');
            }
            if (!Schema::hasColumn('vehicles', 'seats')) {
                $table->unsignedSmallInteger('seats')->default(4)->after('license_plate');
            }
            if (!Schema::hasColumn('vehicles', 'active')) {
                $table->boolean('active')->default(true)->after('seats');
            }
            if (!Schema::hasColumn('vehicles', 'base_fare')) {
                $table->decimal('base_fare', 10, 2)->nullable()->after('active');
            }
            if (!Schema::hasColumn('vehicles', 'price_per_km')) {
                $table->decimal('price_per_km', 10, 2)->default(0)->after('base_fare');
            }
            if (!Schema::hasColumn('vehicles', 'price_per_min')) {
                $table->decimal('price_per_min', 10, 2)->default(0)->after('price_per_km');
            }
            if (!Schema::hasColumn('vehicles', 'min_fare')) {
                $table->decimal('min_fare', 10, 2)->default(0)->after('price_per_min');
            }
            if (!Schema::hasColumn('vehicles', 'notes')) {
                $table->text('notes')->nullable()->after('min_fare');
            }
            if (!Schema::hasColumn('vehicles', 'image_url')) {
                $table->string('image_url', 500)->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        // Optional: drop added columns. Skipping to avoid data loss if table was mixed-use.
    }
};
