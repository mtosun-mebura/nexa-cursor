<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see database/migrations/modules/taxi/2026_08_27_080000_add_pickup_proposal_whatsapp_wamid_to_ride_requests.php
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ride_requests')) {
            return;
        }

        if (Schema::hasColumn('ride_requests', 'pickup_proposal_whatsapp_wamid')) {
            return;
        }

        Schema::table('ride_requests', function (Blueprint $table) {
            $table->string('pickup_proposal_whatsapp_wamid', 191)->nullable()->after('pickup_proposal_responded_at');
            $table->index('pickup_proposal_whatsapp_wamid', 'ride_requests_pickup_proposal_wamid_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ride_requests')) {
            return;
        }

        if (! Schema::hasColumn('ride_requests', 'pickup_proposal_whatsapp_wamid')) {
            return;
        }

        Schema::table('ride_requests', function (Blueprint $table) {
            $table->dropIndex('ride_requests_pickup_proposal_wamid_index');
            $table->dropColumn('pickup_proposal_whatsapp_wamid');
        });
    }
};
