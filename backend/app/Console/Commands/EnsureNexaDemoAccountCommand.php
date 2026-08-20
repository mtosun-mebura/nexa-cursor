<?php

namespace App\Console\Commands;

use App\Services\NexaDemoAccountService;
use Illuminate\Console\Command;

class EnsureNexaDemoAccountCommand extends Command
{
    protected $signature = 'nexa:ensure-demo-account';

    protected $description = 'Maak of werk het openbare Nexa Taxi demo-adminaccount bij (alleen taxi-module)';

    public function handle(NexaDemoAccountService $demo): int
    {
        $result = $demo->ensure();
        if ($result['user'] === null) {
            $this->warn('Demo-account niet aangemaakt (uitgeschakeld of tabellen ontbreken).');

            return self::SUCCESS;
        }

        $verb = $result['created'] ? 'aangemaakt' : 'bijgewerkt';
        $this->info('Demo-account '.$verb.': '.$result['user']->email);
        if ($result['company']) {
            $this->line('Bedrijf: '.$result['company']->name);
        }

        return self::SUCCESS;
    }
}
