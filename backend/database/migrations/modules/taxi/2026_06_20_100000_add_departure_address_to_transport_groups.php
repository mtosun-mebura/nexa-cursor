<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transport_groups')) {
            return;
        }

        Schema::table('transport_groups', function (Blueprint $table) {
            if (! Schema::hasColumn('transport_groups', 'departure_address')) {
                $table->string('departure_address')->nullable()->after('name');
            }
            if (! Schema::hasColumn('transport_groups', 'departure_lat')) {
                $table->decimal('departure_lat', 10, 7)->nullable()->after('departure_address');
            }
            if (! Schema::hasColumn('transport_groups', 'departure_lng')) {
                $table->decimal('departure_lng', 10, 7)->nullable()->after('departure_lat');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transport_groups')) {
            return;
        }

        Schema::table('transport_groups', function (Blueprint $table) {
            if (Schema::hasColumn('transport_groups', 'departure_lng')) {
                $table->dropColumn('departure_lng');
            }
            if (Schema::hasColumn('transport_groups', 'departure_lat')) {
                $table->dropColumn('departure_lat');
            }
            if (Schema::hasColumn('transport_groups', 'departure_address')) {
                $table->dropColumn('departure_address');
            }
        });
    }
};
