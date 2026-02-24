<?php

namespace App\Modules\TaxiRoyaal\Traits;

use App\Services\ModuleDatabaseService;

trait UsesModuleDatabase
{
    protected function moduleConnection(): string
    {
        return app(ModuleDatabaseService::class)->getModuleConnectionName('taxiroyaal');
    }
}
