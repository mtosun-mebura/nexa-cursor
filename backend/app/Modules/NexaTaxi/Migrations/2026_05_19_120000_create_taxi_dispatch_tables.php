<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ride_dispatch_offers')) {
            Schema::create('ride_dispatch_offers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ride_request_id')->index();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('driver_id')->index();
                $table->string('status', 24)->default('pending')->index();
                $table->unsignedSmallInteger('wave')->default(1);
                $table->timestamp('offered_at');
                $table->timestamp('expires_at')->index();
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();

                $table->unique(['ride_request_id', 'driver_id']);
            });
        }

        if (! Schema::hasTable('driver_availability')) {
            Schema::create('driver_availability', function (Blueprint $table) {
                $table->unsignedBigInteger('driver_id')->primary();
                $table->unsignedBigInteger('company_id')->index();
                $table->boolean('is_online')->default(false)->index();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->timestamp('location_updated_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_availability');
        Schema::dropIfExists('ride_dispatch_offers');
    }
};
