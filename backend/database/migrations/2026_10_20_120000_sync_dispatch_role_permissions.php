<?php

use App\Modules\NexaTaxi\Support\TaxiDispatchAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        TaxiDispatchAccess::syncKnownRolePresets();
    }

    public function down(): void
    {
        // Terugdraaien haalt ook later handmatig gezette vinkjes van deze rollen weg.
    }
};
