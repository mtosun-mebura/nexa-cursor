<?php

namespace App\Modules\NexaTaxi\Traits;

use App\Services\ModuleDatabaseService;

trait UsesModuleDatabase
{
    protected function moduleConnection(): string
    {
        $dbService = app(ModuleDatabaseService::class);
        $dbService->ensureModuleStorageReady('taxi');

        return $dbService->getModuleConnectionName('taxi');
    }
}
