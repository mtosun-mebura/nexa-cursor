<?php

namespace App\Console\Commands;

use App\Modules\NexaTaxi\Services\TaxiTosunBookingGpsDemoService;
use Illuminate\Console\Command;

class TaxiTosunBookingGpsDemoCommand extends Command
{
    protected $signature = 'taxi:tosun-booking-gps-demo';

    protected $description = 'Zet GPS-trackers + tijdelijke 3-voertuigen-demo aan voor Taxi Tosun in de boekingsmodule v2';

    public function handle(TaxiTosunBookingGpsDemoService $demo): int
    {
        try {
            $result = $demo->enable();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $company = $result['company'];
        $this->info('Taxi Tosun GPS-demo klaar.');
        $this->line('Bedrijf: '.$company->name.' (id '.$company->id.', slug '.$company->slug.')');
        $this->line('GPS-module: aan');
        $this->line('Boekingspagina’s bijgewerkt: '.$result['pages']);
        $this->comment('Zet de demo later uit via het schuifje “Tijdelijke demo (3 voertuigen)” in de website-builder (alleen super-admin).');

        return self::SUCCESS;
    }
}
