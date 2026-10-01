<?php

namespace App\Console\Commands;

use App\Modules\NexaTaxi\Services\TaxiNetworkDemoSeedService;
use Illuminate\Console\Command;

class SeedTaxiNetworkDemoCommand extends Command
{
    protected $signature = 'taxi:seed-network-demo
                            {--keep-rides : Bestaande [network-demo]-ritten niet vervangen}';

    protected $description = 'Maak twee NEXA Network-tenants + logins + testritten (hand-over, radius, auto-fallback)';

    public function handle(TaxiNetworkDemoSeedService $demo): int
    {
        $result = $demo->ensure(! $this->option('keep-rides'));

        $base = rtrim((string) config('app.url'), '/');
        $driverApp = $base.'/taxi/chauffeur';
        $admin = $base.'/admin';
        $dispatch = $base.'/admin/taxi/dispatch-instellingen';

        $this->newLine();
        $this->info('NEXA Network demo klaar');
        $this->line('Owner:   '.$result['owner']->name.' (id '.$result['owner']->id.')');
        $this->line('Partner: '.$result['partner']->name.' (id '.$result['partner']->id.')');
        $this->line(sprintf(
            'Network: modus=%s, radius=%dkm, fallback=%ds, %s',
            $result['network']['mode'],
            $result['network']['radius_km'],
            $result['network']['fallback_seconds'],
            $result['network']['partnership']
        ));

        $this->newLine();
        $this->info('Logins (wachtwoord voor allen: '.TaxiNetworkDemoSeedService::PASSWORD.')');
        $this->table(
            ['Rol', 'E-mail', 'Bedrijf'],
            array_map(fn (array $a) => [$a['role'], $a['email'], $a['company']], $result['accounts'])
        );

        $this->newLine();
        $this->info('Testritten (owner)');
        $this->table(
            ['ID', 'Doel', 'Status'],
            array_map(fn (array $r) => [$r['id'], $r['label'], $r['status']], $result['rides'])
        );

        $this->newLine();
        $this->info('URLs');
        $this->line('Chauffeur-app:  '.$driverApp);
        $this->line('Admin:          '.$admin);
        $this->line('Chauffeur dispatch / Network: '.$dispatch);
        $this->newLine();
        $this->line('Testplan:');
        $this->line('1. Login als '.TaxiNetworkDemoSeedService::OWNER_DRIVER_EMAIL.' in de chauffeur-app → online.');
        $this->line('2. Open de geaccepteerde rit → “Naar network” → partner dichtbij krijgt offer.');
        $this->line('3. Login als '.TaxiNetworkDemoSeedService::PARTNER_NEAR_EMAIL.' → claim de network-rit.');
        $this->line('4. '.TaxiNetworkDemoSeedService::PARTNER_FAR_EMAIL.' blijft buiten 10 km → geen offer.');
        $this->line('5. Admin owner: Chauffeur dispatch → NEXA Network (radius/fallback controleren).');
        $this->line('6. Auto-fallback: laat open offer verlopen (>60s) → escalate biedt partners binnen radius aan.');

        return self::SUCCESS;
    }
}
