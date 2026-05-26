<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Incrementele module-migratie voor Nexa Taxi (vehicles, ride_requests, default_rates).
 * Sluit aan op de eindtoestand van de Pre2026 taxiroyaal-set; bij bestaande tabellen geen dubbele CREATE.
 *
 * @see database/migrations/modules/taxi/ (canonical pad voor artisan migrate --database=module_taxi)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            Schema::create('vehicles', function (Blueprint $table) {
                $table->id();
                // Geen FK: company_id verwijst naar companies op de hoofd-DB (losse module-DB).
                $table->unsignedBigInteger('company_id')->index();
                $table->string('name');
                $table->string('type', 20)->default('car');
                $table->string('license_plate')->nullable();
                $table->unsignedSmallInteger('seats')->default(4);
                $table->string('person_range', 10)->default('1-4');
                $table->boolean('active')->default(true);
                $table->decimal('base_fare', 10, 2)->nullable();
                $table->decimal('price_per_km', 10, 2)->default(0);
                $table->decimal('price_per_min', 10, 2)->default(0);
                $table->decimal('min_fare', 10, 2)->default(0);
                $table->decimal('cleaning_costs', 10, 2)->nullable();
                $table->text('notes')->nullable();
                $table->string('image_url', 500)->nullable();
                $table->boolean('show_photo')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ride_requests')) {
            Schema::create('ride_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 20)->default('draft');
                $table->string('pickup_address');
                $table->string('dropoff_address');
                $table->decimal('pickup_lat', 10, 7)->nullable();
                $table->decimal('pickup_lng', 10, 7)->nullable();
                $table->decimal('dropoff_lat', 10, 7)->nullable();
                $table->decimal('dropoff_lng', 10, 7)->nullable();
                $table->unsignedInteger('distance_meters')->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->unsignedSmallInteger('passengers')->default(1);
                $table->dateTime('pickup_at');
                $table->decimal('quoted_price', 10, 2)->nullable();
                $table->string('customer_name');
                $table->string('customer_email')->nullable();
                $table->string('customer_phone')->nullable();
                $table->text('customer_note')->nullable();
                $table->dateTime('quote_expires_at')->nullable();
                $table->json('booking_payload')->nullable();
                $table->json('selected_offer_payload')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('default_rates')) {
            Schema::create('default_rates', function (Blueprint $table) {
                $table->id();
                $table->string('person_range', 10)->default('1-4');
                $table->decimal('base_fare', 10, 2)->nullable();
                $table->decimal('min_fare', 10, 2)->default(0);
                $table->decimal('price_per_km', 10, 2)->default(0);
                $table->decimal('price_per_min', 10, 2)->default(0);
                $table->decimal('cleaning_costs', 10, 2)->nullable();
                $table->timestamps();
            });

            $now = now();
            foreach (['1-4', '5-8'] as $range) {
                DB::table('default_rates')->insert([
                    'person_range' => $range,
                    'base_fare' => null,
                    'min_fare' => 0,
                    'price_per_km' => 0,
                    'price_per_min' => 0,
                    'cleaning_costs' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_requests');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('default_rates');
    }
};
