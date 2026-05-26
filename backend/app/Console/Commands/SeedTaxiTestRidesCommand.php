<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\ModuleDatabaseService;
use Illuminate\Console\Command;

class SeedTaxiTestRidesCommand extends Command
{
    protected $signature = 'taxi:seed-test-rides
                            {--count=3 : Aantal testritten}
                            {--driver= : E-mailadres van de chauffeur}
                            {--company= : Company ID (optioneel, anders van chauffeur)}
                            {--assign-first : Eerste rit direct toewijzen (status assigned, voor betalen-test)}';

    protected $description = 'Voeg testritten toe voor de chauffeur-app (dispatch + optioneel één actieve rit)';

    public function handle(
        ModuleDatabaseService $moduleDb,
        TaxiDriverEligibilityService $eligibility,
        TaxiDispatchSettingsService $dispatchSettings
    ): int {
        $conn = $moduleDb->getModuleConnectionName('taxi');

        if (! TaxiDispatchSchema::tablesExist($conn)) {
            $this->error('Dispatch-tabellen ontbreken. Voer uit: php artisan modules:migrate taxi');

            return self::FAILURE;
        }

        $driver = $this->resolveDriver($eligibility);
        if (! $driver) {
            return self::FAILURE;
        }

        $companyId = (int) ($this->option('company') ?: $driver->company_id);
        if ($companyId <= 0) {
            $this->error('Geen company_id. Geef --company= aan of koppel de chauffeur aan een bedrijf.');

            return self::FAILURE;
        }

        if (! $eligibility->isChauffeurForCompany($driver, $companyId)) {
            $this->error("Gebruiker {$driver->email} is geen chauffeur voor company_id {$companyId}.");

            return self::FAILURE;
        }

        $existingActive = RideRequest::on($conn)
            ->where('driver_id', $driver->id)
            ->whereIn('status', [RideRequest::STATUS_ASSIGNED, RideRequest::STATUS_ACCEPTED])
            ->exists();

        if ($existingActive && $this->option('assign-first')) {
            $this->warn('Er staat al een actieve rit op deze chauffeur; --assign-first wordt overgeslagen voor rit 1.');
        }

        $ttl = $dispatchSettings->offerTtlSeconds($companyId);
        $expires = now()->addSeconds($ttl);
        $now = now();

        $count = max(1, min(50, (int) $this->option('count')));
        $batch = now()->format('His');
        $fixtures = array_slice($this->fixturePool(), 0, $count);
        if ($count > count($fixtures)) {
            $base = $this->fixturePool();
            for ($i = count($fixtures); $i < $count; $i++) {
                $template = $base[$i % count($base)];
                $n = $i + 1;
                $fixtures[] = array_merge($template, [
                    'label' => "Testrit {$n} – {$template['city']}",
                    'customer' => "Test Klant {$n}",
                    'phone' => '06-'.str_pad((string) (1000000 + $i), 8, '0', STR_PAD_LEFT),
                    'price' => round($template['price'] + ($i * 2.5), 2),
                ]);
            }
        }

        $assignFirst = $this->option('assign-first') && ! $existingActive;
        $created = [];

        foreach ($fixtures as $index => $fixture) {
            $assignThis = $assignFirst && $index === 0;

            $ride = RideRequest::on($conn)->create([
                'company_id' => $companyId,
                'driver_id' => $assignThis ? $driver->id : null,
                'status' => $assignThis ? RideRequest::STATUS_ASSIGNED : RideRequest::STATUS_OFFERED,
                'pickup_address' => $fixture['pickup'],
                'dropoff_address' => $fixture['dropoff'],
                'pickup_lat' => null,
                'pickup_lng' => null,
                'dropoff_lat' => null,
                'dropoff_lng' => null,
                'distance_meters' => 12000 + ($index * 3000),
                'duration_seconds' => 1800 + ($index * 600),
                'passengers' => 1 + $index,
                'pickup_at' => $now->copy()->addHour(),
                'quoted_price' => $fixture['price'],
                'payment_method' => RideRequest::PAYMENT_METHOD_DRIVER,
                'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
                'final_price' => null,
                'customer_name' => $fixture['customer'],
                'customer_email' => "test-{$batch}-{$index}@example.test",
                'customer_phone' => $fixture['phone'],
                'customer_note' => $fixture['label'].' (seed taxi:seed-test-rides)',
            ]);

            if (! $assignThis) {
                RideDispatchOffer::on($conn)->updateOrCreate(
                    [
                        'ride_request_id' => $ride->id,
                        'driver_id' => $driver->id,
                    ],
                    [
                        'company_id' => $companyId,
                        'status' => RideDispatchOffer::STATUS_PENDING,
                        'wave' => 1,
                        'offered_at' => $now,
                        'expires_at' => $expires,
                        'responded_at' => null,
                    ]
                );
            }

            $created[] = [
                'id' => $ride->id,
                'label' => $fixture['label'],
                'status' => $ride->status,
                'price' => $fixture['price'],
                'mode' => $assignThis ? 'actief (betalen)' : 'aanbod inbox',
            ];
        }

        if (TaxiDispatchSchema::driverAvailabilityExists($conn)) {
            DriverAvailability::on($conn)->updateOrCreate(
                ['driver_id' => $driver->id],
                [
                    'company_id' => $companyId,
                    'is_online' => true,
                    'last_seen_at' => $now,
                ]
            );
        }

        $this->info("{$count} testritten aangemaakt voor {$driver->email} (company_id {$companyId}).");
        $this->table(['ID', 'Omschrijving', 'Status', 'Prijs', 'In app'], $created);
        $this->newLine();
        $this->line('Chauffeur-app: ga online → inbox. Met --assign-first staat rit 1 direct op “Jouw rit” (Betalen).');
        $this->line('Reset betaling: zie eerdere SQL (DELETE ride_payments + payment_status pending).');

        return self::SUCCESS;
    }

    protected function resolveDriver(TaxiDriverEligibilityService $eligibility): ?User
    {
        $email = $this->option('driver');
        if ($email) {
            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                $this->error("Geen gebruiker met e-mail: {$email}");

                return null;
            }

            return $user;
        }

        $companyId = (int) $this->option('company');
        if ($companyId > 0) {
            $user = $eligibility->buildChauffeurQuery($companyId)->first();
            if ($user) {
                $this->line("Chauffeur: {$user->email} (eerste chauffeur van company {$companyId})");

                return $user;
            }
        }

        $user = User::query()
            ->whereNotNull('company_id')
            ->where('company_id', '>', 0)
            ->orderBy('id')
            ->get()
            ->first(fn (User $u) => $eligibility->isChauffeurForCompany($u, (int) $u->company_id));

        if ($user) {
            $this->line("Chauffeur: {$user->email} (company_id {$user->company_id})");

            return $user;
        }

        $this->error('Geen chauffeur gevonden. Geef --driver=email@... of --company=ID op.');

        return null;
    }

    /**
     * @return list<array{label: string, city: string, pickup: string, dropoff: string, price: float, customer: string, phone: string}>
     */
    protected function fixturePool(): array
    {
        return [
            [
                'label' => 'Testrit – Schiphol',
                'city' => 'Schiphol',
                'pickup' => 'Stationsplein 9, Amsterdam',
                'dropoff' => 'Vertrekhal, Schiphol',
                'price' => 45.00,
                'customer' => 'Test Klant Schiphol',
                'phone' => '06-11111111',
            ],
            [
                'label' => 'Testrit – Rotterdam',
                'city' => 'Rotterdam',
                'pickup' => 'Stationshal, Utrecht Centraal',
                'dropoff' => 'Centraal Station, Rotterdam',
                'price' => 38.50,
                'customer' => 'Test Klant Rotterdam',
                'phone' => '06-22222222',
            ],
            [
                'label' => 'Testrit – Leiden',
                'city' => 'Leiden',
                'pickup' => 'Hollands Spoor, Den Haag',
                'dropoff' => 'Stationsplein, Leiden',
                'price' => 28.00,
                'customer' => 'Test Klant Leiden',
                'phone' => '06-33333333',
            ],
            [
                'label' => 'Testrit – Eindhoven',
                'city' => 'Eindhoven',
                'pickup' => 'Stationsplein, Breda',
                'dropoff' => 'Stationsplein, Eindhoven',
                'price' => 52.00,
                'customer' => 'Test Klant Eindhoven',
                'phone' => '06-44444444',
            ],
            [
                'label' => 'Testrit – Groningen',
                'city' => 'Groningen',
                'pickup' => 'Stationsplein, Zwolle',
                'dropoff' => 'Hoofdstation, Groningen',
                'price' => 65.00,
                'customer' => 'Test Klant Groningen',
                'phone' => '06-55555555',
            ],
            [
                'label' => 'Testrit – Maastricht',
                'city' => 'Maastricht',
                'pickup' => 'Centraal Station, Nijmegen',
                'dropoff' => 'Stationsplein, Maastricht',
                'price' => 72.00,
                'customer' => 'Test Klant Maastricht',
                'phone' => '06-66666666',
            ],
            [
                'label' => 'Testrit – Haarlem',
                'city' => 'Haarlem',
                'pickup' => 'Damrak, Amsterdam',
                'dropoff' => 'Stationsplein, Haarlem',
                'price' => 32.00,
                'customer' => 'Test Klant Haarlem',
                'phone' => '06-77777777',
            ],
            [
                'label' => 'Testrit – Arnhem',
                'city' => 'Arnhem',
                'pickup' => 'Centraal Station, Apeldoorn',
                'dropoff' => 'Centraal Station, Arnhem',
                'price' => 24.50,
                'customer' => 'Test Klant Arnhem',
                'phone' => '06-88888888',
            ],
            [
                'label' => 'Testrit – Alkmaar',
                'city' => 'Alkmaar',
                'pickup' => 'Centraal Station, Zaandam',
                'dropoff' => 'Stationsplein, Alkmaar',
                'price' => 41.00,
                'customer' => 'Test Klant Alkmaar',
                'phone' => '06-99999999',
            ],
        ];
    }
}
