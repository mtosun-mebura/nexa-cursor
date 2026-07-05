<?php

use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use Illuminate\Database\Migrations\Migration;

/**
 * Contractklanten / abonnementen (contractvervoer MVP).
 *
 * @see database/migrations/modules/taxi/
 */
return new class extends Migration
{
    public function up(): void
    {
        app(TaxiContractvervoerSchemaService::class)->ensureTablesExist();
    }

    public function down(): void
    {
        // MVP down-migrations weglaten (veiligheid). Re-run deploy in production via nieuwe migrations.
    }
};
