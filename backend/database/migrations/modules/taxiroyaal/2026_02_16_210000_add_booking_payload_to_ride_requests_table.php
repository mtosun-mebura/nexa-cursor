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
            if (! Schema::hasColumn('ride_requests', 'booking_payload')) {
                $table->json('booking_payload')->nullable()->after('quote_expires_at');
            }
            if (! Schema::hasColumn('ride_requests', 'selected_offer_payload')) {
                $table->json('selected_offer_payload')->nullable()->after('booking_payload');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ride_requests')) {
            return;
        }
        Schema::table('ride_requests', function (Blueprint $table) {
            if (Schema::hasColumn('ride_requests', 'selected_offer_payload')) {
                $table->dropColumn('selected_offer_payload');
            }
            if (Schema::hasColumn('ride_requests', 'booking_payload')) {
                $table->dropColumn('booking_payload');
            }
        });
    }
};

