<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\NexaTaxi\Services\TaxiGpsDemoFleetService;
use Illuminate\Console\Command;

class GpsDemoFleetCommand extends Command
{
    protected $signature = 'gps:demo-fleet
                            {--drive : Laat de vier voertuigen blijven rijden}
                            {--interval= : Seconden tussen positie-updates (leeg = GPS-configuratie van het bedrijf)}
                            {--seconds=0 : Stop na N seconden (0 = tot Ctrl+C)}';

    protected $description = 'Maak het QA GPS-testaccount (4 voertuigen + GPS-tracker) en laat ze optioneel rijden';

    public function handle(TaxiGpsDemoFleetService $fleet): int
    {
        $result = $fleet->ensure();
        $company = $result['company'];
        $admin = $result['admin'];

        $this->info('QA GPS-vloot klaar.');
        $this->line('Bedrijf: '.$company->name.' (id '.$company->id.')');
        $this->line('Login:   '.$admin->email);
        $this->line('Wachtwoord: '.TaxiGpsDemoFleetService::PASSWORD);
        $this->line('Pagina:  /admin/taxi/gps-tracker');
        $this->line('Offline-pincode: '.TaxiGpsDemoFleetService::OFFLINE_PIN);
        $this->newLine();
        $this->table(
            ['Kenteken', 'Voertuig', 'Chauffeur'],
            collect($result['vehicles'])->map(function ($vehicle, $i) use ($result) {
                /** @var User $driver */
                $driver = $result['drivers'][$i];

                return [
                    $vehicle->license_plate,
                    $vehicle->name,
                    trim($driver->first_name.' '.$driver->last_name),
                ];
            })->all()
        );

        if (! $this->option('drive')) {
            $this->comment('Start rijden met: php artisan gps:demo-fleet --drive');

            return self::SUCCESS;
        }

        $intervalOpt = $this->option('interval');
        $interval = $intervalOpt === null || $intervalOpt === ''
            ? max(1, (int) app(\App\Modules\NexaTaxi\Services\TaxiGpsTrackingSettingsService::class)
                ->appearance((int) $company->id)['refresh_seconds'])
            : max(1, (int) $intervalOpt);
        $limit = max(0, (int) $this->option('seconds'));
        $started = time();

        $states = [];
        foreach ($result['drivers'] as $i => $driver) {
            $states[$i] = [
                'driver_id' => (int) $driver->id,
                'vehicle_id' => (int) $result['vehicles'][$i]->id,
                'step' => $i * 12,
            ];
        }

        $this->info("Voertuigen rijden nu (update elke {$interval}s). Laat dit proces aan tot je klaar bent met kijken.");

        while (true) {
            $states = $fleet->tick($result['connection'], (int) $company->id, $states, $interval);
            if ($limit > 0 && (time() - $started) >= $limit) {
                $this->info('Rijden gestopt na '.$limit.' seconden.');
                break;
            }
            sleep($interval);
        }

        return self::SUCCESS;
    }
}
