<?php

namespace App\Modules\NexaTaxi\Traits;

use App\Services\ModuleDatabaseService;

trait UsesModuleDatabase
{
    protected function moduleConnection(): string
    {
        return app(ModuleDatabaseService::class)->getModuleConnectionName('taxi');
    }
}
