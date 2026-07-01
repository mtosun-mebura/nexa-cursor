<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ride_requests')) {
            return;
        }

        Schema::table('ride_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('ride_requests', 'return_at')) {
                $table->dateTime('return_at')->nullable()->after('pickup_at');
            }
            if (! Schema::hasColumn('ride_requests', 'outbound_completed_at')) {
                $table->dateTime('outbound_completed_at')->nullable()->after('return_at');
            }
            if (! Schema::hasColumn('ride_requests', 'outbound_driver_id')) {
                $table->unsignedBigInteger('outbound_driver_id')->nullable()->after('outbound_completed_at');
            }
            if (! Schema::hasColumn('ride_requests', 'return_started_at')) {
                $table->dateTime('return_started_at')->nullable()->after('outbound_driver_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ride_requests')) {
            return;
        }

        Schema::table('ride_requests', function (Blueprint $table) {
            foreach (['return_started_at', 'outbound_driver_id', 'outbound_completed_at', 'return_at'] as $column) {
                if (Schema::hasColumn('ride_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
