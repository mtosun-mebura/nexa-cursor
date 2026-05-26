<?php

namespace App\Jobs;

use App\Services\ModuleManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InstallModuleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(
        public string $moduleName
    ) {}

    public function handle(ModuleManager $moduleManager): void
    {
        $key = 'module_installing_' . $this->moduleName;
        Cache::put($key, true, 300);

        try {
            $moduleManager->installModule($this->moduleName);
        } catch (\Throwable $e) {
            Log::error('Module install job failed: ' . $this->moduleName, [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            Cache::forget($key);
        }
    }
}
