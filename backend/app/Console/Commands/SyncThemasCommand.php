<?php

namespace App\Console\Commands;

use App\Models\Module;
use App\Services\ThemeCopyService;
use Illuminate\Console\Command;

class SyncThemasCommand extends Command
{
    protected $signature = 'themas:sync
                            {--modules : Ook kopiëren naar alle geïnstalleerde modules}';

    protected $description = 'Kopieer thema\'s uit backend/themas/ naar public/frontend-themes/ (en optioneel naar elke geïnstalleerde module)';

    public function handle(ThemeCopyService $themeCopy): int
    {
        if (!$themeCopy->hasThemasSource()) {
            $this->warn('Themas-bronmap niet gevonden. Zet THEMAS_SOURCE_PATH of zorg dat backend/themas/ bestaat.');
            return self::FAILURE;
        }

        $this->info('Kopiëren naar public/frontend-themes/ ...');
        $public = $themeCopy->copyThemesToPublic();
        $this->info('Gekopieerd: ' . implode(', ', $public));

        if ($this->option('modules')) {
            $modules = Module::where('installed', true)->pluck('name');
            foreach ($modules as $name) {
                $this->info("Kopiëren naar module {$name} ...");
                $themeCopy->copyThemesToModule($name);
            }
        }

        $this->info('Klaar.');
        return self::SUCCESS;
    }
}
