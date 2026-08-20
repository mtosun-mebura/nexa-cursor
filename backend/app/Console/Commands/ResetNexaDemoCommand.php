<?php

namespace App\Console\Commands;

use App\Services\NexaDemoAccountService;
use Illuminate\Console\Command;

class ResetNexaDemoCommand extends Command
{
    protected $signature = 'nexa:reset-demo';

    protected $description = 'Zet het Nexa Taxi demo-account terug en veeg operationele data van het demobedrijf';

    public function handle(NexaDemoAccountService $demo): int
    {
        $result = $demo->reset();
        if ($result['user'] === null) {
            $this->warn('Demo niet gereset (uitgeschakeld of tabellen ontbreken).');

            return self::SUCCESS;
        }

        $this->info('Demo gereset: '.$result['user']->email);
        if ($result['company']) {
            $this->line('Bedrijf: '.$result['company']->name);
        }

        return self::SUCCESS;
    }
}
