<?php

namespace App\Modules\NexaTaxi\Services;

use App\Helpers\GeoHelper;
use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\ModuleDatabaseService;
use App\Services\NexaDemoAccountService;
use App\Services\UserRoleAssignmentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Omgeving-brede app-demo: superadmin zet aan → alle klanten op lokaal/test
 * zien virtuele taxi’s; die staan online zodat marketplace-dispatch hen (én
 * echte chauffeurs) kan bereiken.
 */
class TaxiAppDemoModeService
{
    public const CACHE_KEY = 'nexa_taxi_app_demo_mode';

    public const FLEET_SIZE = 20;

    public const NEARBY_COUNT = 6;

    public const MID_COUNT = 10;

    public const DRIVER_EMAIL_PREFIX = 'app.demo.driver';

    public function __construct(
        protected ModuleDatabaseService $moduleDb,
        protected NexaDemoAccountService $demoAccount,
        protected UserRoleAssignmentService $roles,
    ) {}

    public function environmentAllowed(?string $host = null): bool
    {
        $host = strtolower(trim((string) ($host ?: request()?->getHost() ?? '')));
        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }
        if (str_ends_with($host, '.local') || str_ends_with($host, '.test') || str_ends_with($host, '.localhost')) {
            return true;
        }
        if ($host === 'nexasuite.online' || str_ends_with($host, '.nexasuite.online')) {
            return true;
        }
        if (preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.)/', $host) === 1) {
            return true;
        }
        // CLI / queue zonder host: volg APP_ENV
        if (app()->environment(['local', 'testing', 'test', 'staging'])) {
            return true;
        }

        return false;
    }

    public function isEnabled(): bool
    {
        if (! $this->environmentAllowed()) {
            return false;
        }

        return (bool) Cache::get(self::CACHE_KEY, false);
    }

    public function setEnabled(bool $on): bool
    {
        if (! $this->environmentAllowed()) {
            Cache::forget(self::CACHE_KEY);
            $this->takeFleetOffline();

            return false;
        }

        if ($on) {
            // Flag eerst zetten zodat de schakelaar niet faalt als de vloot-seed traag is.
            Cache::forever(self::CACHE_KEY, true);
            try {
                $this->ensureFleet();
                $this->tickFleet(null, null);
            } catch (\Throwable) {
                // Vloot wordt bij nearby-poll alsnog bijgewerkt.
            }

            return true;
        }

        Cache::forever(self::CACHE_KEY, false);
        try {
            $this->takeFleetOffline();
        } catch (\Throwable) {
            /* ignore */
        }

        return false;
    }

    /**
     * Status voor app-poll (alle clients).
     *
     * @return array{enabled: bool, environment_allowed: bool, fleet_size: int}
     */
    public function status(): array
    {
        $allowed = $this->environmentAllowed();

        return [
            'enabled' => $allowed && $this->isEnabled(),
            'environment_allowed' => $allowed,
            'fleet_size' => self::FLEET_SIZE,
        ];
    }

    /**
     * Zorg dat virtuele chauffeurs online staan en schuif posities bij.
     * Aanroepen vanuit nearby-taxis (throttled) zodat de kaart beweegt.
     */
    public function refreshFleetForNearby(?float $lat, ?float $lng): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->ensureFleet();
        $throttleKey = 'nexa_taxi_app_demo_fleet_tick';
        if (Cache::has($throttleKey)) {
            return;
        }
        Cache::put($throttleKey, 1, now()->addSeconds(2));
        $this->tickFleet($lat, $lng);
    }

    /**
     * @return list<array{id: string, lat: float, lng: float, car_style: string, distance_km: ?float, busy: bool, heading: ?float, demo: bool}>
     */
    public function vehiclesForNearby(?float $lat, ?float $lng, ?float $radiusKm = null): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        try {
            $this->moduleDb->ensureModuleStorageReady('taxi');
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
        } catch (\Throwable) {
            return $this->syntheticVehicles($lat, $lng, $radiusKm);
        }

        if (! TaxiDispatchSchema::driverAvailabilityExists($conn)) {
            return $this->syntheticVehicles($lat, $lng, $radiusKm);
        }

        try {
            $this->refreshFleetForNearby($lat, $lng);
        } catch (\Throwable) {
            /* fallback hieronder */
        }

        $company = $this->demoCompany();
        $driverIds = $company ? $this->demoDriverIds() : [];
        $out = [];
        if ($company && $driverIds !== []) {
            $rows = DriverAvailability::on($conn)
                ->where('company_id', (int) $company->id)
                ->whereIn('driver_id', $driverIds)
                ->where('is_online', true)
                ->get();

            $radius = $radiusKm !== null && $radiusKm > 0 ? $radiusKm : 80.0;
            $hasOrigin = $lat !== null && $lng !== null;
            foreach ($rows as $row) {
                $taxiLat = $row->lat !== null ? (float) $row->lat : null;
                $taxiLng = $row->lng !== null ? (float) $row->lng : null;
                if ($taxiLat === null || $taxiLng === null) {
                    continue;
                }
                $distanceKm = null;
                if ($hasOrigin) {
                    $distanceKm = round(GeoHelper::calculateDistance($lat, $lng, $taxiLat, $taxiLng), 2);
                    if ($distanceKm > $radius) {
                        continue;
                    }
                }
                $heading = Cache::get('taxi-gps-heading:'.(int) $row->company_id.':'.(int) $row->driver_id);
                $out[] = [
                    'id' => substr(hash_hmac('sha256', 'taxi-driver-'.(int) $row->driver_id, (string) config('app.key')), 0, 16),
                    'lat' => $taxiLat,
                    'lng' => $taxiLng,
                    'car_style' => 'sedan',
                    'distance_km' => $distanceKm,
                    'busy' => false,
                    'heading' => is_numeric($heading) ? round((float) $heading, 1) : null,
                    'demo' => true,
                ];
            }
        }

        // Nooit een lege demo-vloot teruggeven als de modus aan staat.
        if (count($out) < self::NEARBY_COUNT) {
            return $this->syntheticVehicles($lat, $lng, $radiusKm);
        }

        return $out;
    }

    /**
     * Cache-gebaseerde posities als DB-seed faalt — klant ziet wél ~20 taxi’s.
     *
     * @return list<array{id: string, lat: float, lng: float, car_style: string, distance_km: ?float, busy: bool, heading: ?float, demo: bool}>
     */
    private function syntheticVehicles(?float $lat, ?float $lng, ?float $radiusKm = null): array
    {
        $anchorLat = $lat ?? 52.2215;
        $anchorLng = $lng ?? 6.8936;
        $cacheKey = 'nexa_taxi_app_demo_synthetic_fleet';
        $states = Cache::get($cacheKey);
        if (! is_array($states) || count($states) < self::FLEET_SIZE) {
            $states = [];
            for ($i = 1; $i <= self::FLEET_SIZE; $i++) {
                $states[$i] = $this->spawnPoint($i, $anchorLat, $anchorLng);
            }
        } else {
            foreach ($states as $i => $state) {
                $fromLat = isset($state['lat']) ? (float) $state['lat'] : $anchorLat;
                $fromLng = isset($state['lng']) ? (float) $state['lng'] : $anchorLng;
                $states[$i] = $this->stepPoint((int) $i, $fromLat, $fromLng, $anchorLat, $anchorLng);
            }
        }
        Cache::put($cacheKey, $states, now()->addMinutes(30));

        $radius = $radiusKm !== null && $radiusKm > 0 ? $radiusKm : 80.0;
        $out = [];
        foreach ($states as $i => $state) {
            $taxiLat = (float) $state['lat'];
            $taxiLng = (float) $state['lng'];
            $distanceKm = round(GeoHelper::calculateDistance($anchorLat, $anchorLng, $taxiLat, $taxiLng), 2);
            if ($distanceKm > $radius) {
                continue;
            }
            $out[] = [
                'id' => 'demo-syn-'.$i,
                'lat' => $taxiLat,
                'lng' => $taxiLng,
                'car_style' => 'sedan',
                'distance_km' => $distanceKm,
                'busy' => false,
                'heading' => (float) ($state['heading'] ?? 0),
                'demo' => true,
            ];
        }

        return $out;
    }

    public function ensureFleet(): void
    {
        $ensured = $this->demoAccount->ensure();
        $company = $ensured['company'] ?? null;
        if (! $company instanceof Company) {
            return;
        }

        try {
            $this->moduleDb->ensureModuleStorageReady('taxi');
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
        } catch (\Throwable) {
            return;
        }

        TaxiDispatchSchema::ensureVehicleIdColumn($conn);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'api']);

        for ($i = 1; $i <= self::FLEET_SIZE; $i++) {
            $email = self::DRIVER_EMAIL_PREFIX.$i.'@nexa.test';
            $user = User::query()->firstOrNew(['email' => $email]);
            if (! $user->exists) {
                $user->fill([
                    'first_name' => 'Demo',
                    'last_name' => 'Taxi '.$i,
                    'password' => Hash::make(Str::random(32)),
                    'email_verified_at' => now(),
                    'company_id' => $company->id,
                ]);
                if (Schema::hasColumn('users', 'is_active')) {
                    $user->is_active = true;
                }
                $user->save();
            } else {
                $user->company_id = $company->id;
                if (Schema::hasColumn('users', 'is_active')) {
                    $user->is_active = true;
                }
                $user->save();
            }
            $this->roles->syncWebRoles($user, ['chauffeur']);

            $plate = sprintf('D-%02d-NEX', $i);
            $vehicle = Vehicle::on($conn)->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'license_plate' => $plate,
                ],
                [
                    'name' => 'Demo taxi '.$i,
                    'type' => Vehicle::TYPE_CAR,
                    'seats' => 4,
                    'person_range' => Vehicle::PERSON_RANGE_1_4,
                    'active' => true,
                    'base_fare' => 3.50,
                    'price_per_km' => 2.40,
                    'price_per_min' => 0.45,
                    'min_fare' => 8.00,
                ]
            );

            $existing = DriverAvailability::on($conn)->where('driver_id', (int) $user->id)->first();
            $pos = ($existing && $existing->lat !== null && $existing->lng !== null)
                ? [
                    'lat' => (float) $existing->lat,
                    'lng' => (float) $existing->lng,
                    'heading' => 0.0,
                ]
                : $this->spawnPoint($i, null, null);
            DriverAvailability::on($conn)->updateOrCreate(
                ['driver_id' => (int) $user->id],
                [
                    'company_id' => (int) $company->id,
                    'vehicle_id' => (int) $vehicle->id,
                    'is_online' => true,
                    'lat' => $pos['lat'],
                    'lng' => $pos['lng'],
                    'location_updated_at' => now(),
                    'last_seen_at' => now(),
                ]
            );
            Cache::put(
                'taxi-gps-heading:'.(int) $company->id.':'.(int) $user->id,
                $pos['heading'],
                now()->addMinutes(10)
            );
        }
    }

    public function tickFleet(?float $aroundLat, ?float $aroundLng): void
    {
        $company = $this->demoCompany();
        if (! $company) {
            return;
        }

        try {
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
        } catch (\Throwable) {
            return;
        }

        $driverIds = $this->demoDriverIds();
        $index = 0;
        foreach ($driverIds as $driverId) {
            $index++;
            $row = DriverAvailability::on($conn)->where('driver_id', $driverId)->first();
            if (! $row) {
                continue;
            }
            $fromLat = $row->lat !== null ? (float) $row->lat : null;
            $fromLng = $row->lng !== null ? (float) $row->lng : null;
            $next = $this->stepPoint($index, $fromLat, $fromLng, $aroundLat, $aroundLng);
            $row->is_online = true;
            $row->lat = $next['lat'];
            $row->lng = $next['lng'];
            $row->location_updated_at = now();
            $row->last_seen_at = now();
            $row->save();
            Cache::put(
                'taxi-gps-heading:'.(int) $company->id.':'.$driverId,
                $next['heading'],
                now()->addMinutes(10)
            );
        }
    }

    public function takeFleetOffline(): void
    {
        $company = $this->demoCompany();
        if (! $company) {
            return;
        }

        try {
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
        } catch (\Throwable) {
            return;
        }

        if (! TaxiDispatchSchema::driverAvailabilityExists($conn)) {
            return;
        }

        DriverAvailability::on($conn)
            ->where('company_id', (int) $company->id)
            ->whereIn('driver_id', $this->demoDriverIds())
            ->update([
                'is_online' => false,
                'last_seen_at' => now(),
            ]);
    }

    private function demoCompany(): ?Company
    {
        $slug = (string) config('nexa_demo.company_slug', 'nexa-taxi-demo');

        return Company::query()->where('slug', $slug)->first();
    }

    /**
     * @return list<int>
     */
    private function demoDriverIds(): array
    {
        $emails = [];
        for ($i = 1; $i <= self::FLEET_SIZE; $i++) {
            $emails[] = self::DRIVER_EMAIL_PREFIX.$i.'@nexa.test';
        }

        return User::query()
            ->whereIn('email', $emails)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();
    }

    /**
     * @return array{lat: float, lng: float, heading: float}
     */
    private function spawnPoint(int $index, ?float $aroundLat, ?float $aroundLng): array
    {
        $anchorLat = $aroundLat ?? 52.2215;
        $anchorLng = $aroundLng ?? 6.8936;
        $hubs = [
            [52.2215, 6.8936], // Enschede
            [52.2658, 6.7931], // Hengelo
            [52.3570, 6.6626], // Almelo
            [52.3134, 6.9292], // Oldenzaal
            [52.3014, 6.7550], // Borne
            [52.1567, 6.7389], // Haaksbergen
        ];

        if ($index <= self::NEARBY_COUNT) {
            return $this->offset($anchorLat, $anchorLng, 200 + $index * 180, ($index * 57) % 360);
        }
        if ($index <= self::NEARBY_COUNT + self::MID_COUNT) {
            return $this->offset($anchorLat, $anchorLng, 1600 + ($index * 220), ($index * 41) % 360);
        }
        $hub = $hubs[($index - 1) % count($hubs)];

        return $this->offset((float) $hub[0], (float) $hub[1], 300 + ($index * 70), ($index * 33) % 360);
    }

    /**
     * @return array{lat: float, lng: float, heading: float}
     */
    private function stepPoint(
        int $index,
        ?float $fromLat,
        ?float $fromLng,
        ?float $aroundLat,
        ?float $aroundLng
    ): array {
        if ($fromLat === null || $fromLng === null) {
            return $this->spawnPoint($index, $aroundLat, $aroundLng);
        }

        $heading = (float) (($index * 47 + (int) floor(microtime(true)) * 3) % 360);
        // ~18 m/s * 2s tick ≈ 36 m per refresh
        $meters = 28 + ($index % 5) * 6;
        $next = $this->offset($fromLat, $fromLng, $meters, $heading);

        // Houd nabije taxi’s in de buurt van de klant.
        if ($index <= self::NEARBY_COUNT && $aroundLat !== null && $aroundLng !== null) {
            $dist = GeoHelper::calculateDistance($aroundLat, $aroundLng, $next['lat'], $next['lng']);
            if ($dist > 2.2) {
                return $this->spawnPoint($index, $aroundLat, $aroundLng);
            }
        }

        return $next;
    }

    /**
     * @return array{lat: float, lng: float, heading: float}
     */
    private function offset(float $lat, float $lng, float $meters, float $bearingDeg): array
    {
        $rad = deg2rad($bearingDeg);
        $dLat = ($meters * cos($rad)) / 111320.0;
        $dLng = ($meters * sin($rad)) / (111320.0 * max(0.2, cos(deg2rad($lat))));

        return [
            'lat' => $lat + $dLat,
            'lng' => $lng + $dLng,
            'heading' => fmod($bearingDeg + 360.0, 360.0),
        ];
    }
}
