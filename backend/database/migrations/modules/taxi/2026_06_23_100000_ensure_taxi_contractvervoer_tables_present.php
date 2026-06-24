<?php

use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(TaxiContractvervoerSchemaService::class)->ensureTablesExist();
    }

    public function down(): void
    {
        // Geen down: tabellen blijven behouden.
    }
};
