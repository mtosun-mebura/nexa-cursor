<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class TaxiGpsTrackingService
{
    /** @var list<string> */
    public const MARKER_COLORS = [
        '#ea580c',
        '#2563eb',
        '#16a34a',
        '#dc2626',
        '#7c3aed',
        '#0891b2',
        '#ca8a04',
        '#db2777',
        '#4f46e5',
        '#059669',
        '#d97706',
        '#0d9488',
    ];

    public function __construct(
        protected TaxiGpsTrackingSettingsService $settings
    ) {}

    /**
     * @return array{
     *     vehicles: list<array<string, mixed>>,
     *     view: string,
     *     include_offline: bool,
     *     offline_code_set: bool,
     *     offline_unlocked: bool,
     *     server_now: string,
     *     completed_rides: list<array<string, mixed>>
     * }
     */
    public function positions(int $companyId, string $connection, string $view = 'online'): array
    {
        $view = $view === 'offline' ? 'offline' : 'online';
        $payload = [
            'vehicles' => [],
            'view' => $view,
            'include_offline' => $view === 'offline',
            'offline_code_set' => $this->settings->hasOfflineCode($companyId),
            'offline_unlocked' => $this->settings->isOfflineUnlocked($companyId),
            'server_now' => now()->toIso8601String(),
            'completed_rides' => [],
        ];

        if (! TaxiDispatchSchema::driverAvailabilityExists($connection)) {
            return $payload;
        }

        TaxiDispatchSchema::ensureVehicleIdColumn($connection);

        $query = DriverAvailability::on($connection)
            ->where('company_id', $companyId)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where('is_online', $view === 'online');

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return $payload;
        }

        $driverIds = $rows->pluck('driver_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $users = User::query()
            ->whereIn('id', $driverIds)
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->keyBy('id');

        $hasVehicleColumn = Schema::connection($connection)->hasColumn('driver_availability', 'vehicle_id');
        $rideVehicles = $this->currentAssignedVehicles($connection, $companyId, $driverIds);

        $bestByVehicle = [];
        foreach ($rows as $row) {
            $driverId = (int) $row->driver_id;
            $fromAvailability = $hasVehicleColumn && $row->vehicle_id ? (int) $row->vehicle_id : 0;
            $vehicleId = $fromAvailability ?: (int) ($rideVehicles[$driverId] ?? 0);
            if ($vehicleId <= 0) {
                continue;
            }
            $updatedAt = $row->location_updated_at?->getTimestamp() ?? $row->last_seen_at?->getTimestamp() ?? 0;
            $existing = $bestByVehicle[$vehicleId] ?? null;
            if ($existing !== null && ($existing['updated_at'] ?? 0) >= $updatedAt) {
                continue;
            }
            $bestByVehicle[$vehicleId] = [
                'row' => $row,
                'driver_id' => $driverId,
                'vehicle_id' => $vehicleId,
                'updated_at' => $updatedAt,
            ];
        }

        $vehicleIds = array_keys($bestByVehicle);
        $vehicles = $vehicleIds === []
            ? collect()
            : Vehicle::on($connection)->whereIn('id', $vehicleIds)->get()->keyBy('id');

        $appearance = $this->settings->appearance($companyId);

        $out = [];
        foreach ($bestByVehicle as $item) {
            $row = $item['row'];
            $driverId = $item['driver_id'];
            $vehicleId = $item['vehicle_id'];
            $user = $users->get($driverId);
            $vehicle = $vehicles->get($vehicleId);
            $plate = $vehicle ? trim((string) ($vehicle->license_plate ?? '')) : '';
            $style = TaxiGpsTrackingSettingsService::styleFromVehicleType($vehicle?->type);

            $heading = Cache::get('taxi-gps-heading:'.$companyId.':'.$driverId);
            $out[] = [
                'id' => 'vehicle-'.$vehicleId,
                'driver_id' => $driverId,
                'driver_name' => $this->driverDisplayName($user),
                'vehicle_id' => $vehicleId,
                'vehicle_name' => $vehicle ? (string) ($vehicle->name ?? '') : null,
                'license_plate' => $plate !== '' ? $plate : null,
                'car_style' => $style,
                'lat' => (float) $row->lat,
                'lng' => (float) $row->lng,
                'heading' => is_numeric($heading) ? round((float) $heading, 1) : null,
                'is_online' => (bool) $row->is_online,
                'location_updated_at' => $row->location_updated_at?->toIso8601String(),
                'last_seen_at' => $row->last_seen_at?->toIso8601String(),
                'color' => $this->resolveMarkerColor($appearance, $vehicleId, $driverId, $style),
            ];
        }

        usort($out, function (array $a, array $b) {
            $plateA = strtoupper((string) ($a['license_plate'] ?? ''));
            $plateB = strtoupper((string) ($b['license_plate'] ?? ''));
            if ($plateA !== $plateB) {
                return $plateA <=> $plateB;
            }

            return strcasecmp((string) $a['driver_name'], (string) $b['driver_name']);
        });

        $payload['vehicles'] = $out;

        return $payload;
    }

    public function colorFor(int $id): string
    {
        $colors = self::MARKER_COLORS;
        $index = abs($id) % count($colors);

        return $colors[$index];
    }

    /**
     * @param  array<string, mixed>  $appearance
     */
    public function resolveMarkerColor(array $appearance, int $vehicleId, int $driverId, ?string $carStyle = null): string
    {
        if (($appearance['car_color_mode'] ?? TaxiGpsTrackingSettingsService::COLOR_MODE_SINGLE) === TaxiGpsTrackingSettingsService::COLOR_MODE_SINGLE) {
            return $this->settings->colorForStyle($appearance, (string) $carStyle);
        }

        $key = $vehicleId > 0 ? (string) $vehicleId : '';
        $custom = $key !== '' ? ($appearance['vehicle_colors'][$key] ?? null) : null;
        if (is_string($custom) && preg_match('/^#[0-9a-fA-F]{6}$/', $custom) === 1) {
            return strtolower($custom);
        }

        return $this->colorFor($vehicleId > 0 ? $vehicleId : $driverId);
    }

    /**
     * Alle geconfigureerde voertuigen van de tenant, klaar voor de live-kaart-demo.
     *
     * @return list<array<string, mixed>>
     */
    public function configuredFleetForDemo(int $companyId, string $connection): array
    {
        if ($companyId <= 0 || ! Schema::connection($connection)->hasTable('vehicles')) {
            return [];
        }

        $base = Vehicle::on($connection)->where('company_id', $companyId);
        $vehicles = (clone $base)
            ->where('active', true)
            ->orderBy('license_plate')
            ->orderBy('name')
            ->get();
        if ($vehicles->isEmpty()) {
            $vehicles = $base->orderBy('license_plate')->orderBy('name')->get();
        }
        if ($vehicles->isEmpty()) {
            return [];
        }

        $assigned = $this->assignedDriversByVehicle($companyId, $connection);
        $driverIds = array_values(array_unique(array_map('intval', $assigned)));
        $users = $driverIds === []
            ? collect()
            : User::query()
                ->whereIn('id', $driverIds)
                ->get(['id', 'first_name', 'last_name', 'email'])
                ->keyBy('id');
        $appearance = $this->settings->appearance($companyId);
        $now = now()->toIso8601String();

        $out = [];
        foreach ($vehicles as $vehicle) {
            $vehicleId = (int) $vehicle->id;
            $driverId = (int) ($assigned[$vehicleId] ?? 0);
            $user = $driverId > 0 ? $users->get($driverId) : null;
            $plate = trim((string) ($vehicle->license_plate ?? ''));
            $name = trim((string) ($vehicle->name ?? ''));
            $style = TaxiGpsTrackingSettingsService::styleFromVehicleType($vehicle->type);

            $out[] = [
                'id' => 'vehicle-'.$vehicleId,
                'driver_id' => $driverId,
                'driver_name' => $user ? $this->driverDisplayName($user) : ($name !== '' ? $name : 'Chauffeur'),
                'vehicle_id' => $vehicleId,
                'vehicle_name' => $name !== '' ? $name : null,
                'license_plate' => $plate !== '' ? $plate : ($name !== '' ? $name : 'Voertuig #'.$vehicleId),
                'car_style' => $style,
                'lat' => 0.0,
                'lng' => 0.0,
                'is_online' => true,
                'location_updated_at' => $now,
                'last_seen_at' => $now,
                'color' => $this->resolveMarkerColor($appearance, $vehicleId, $driverId, $style),
            ];
        }

        return $out;
    }

    /**
     * Voertuigen voor de kleur-instellingen: kenteken, chauffeur en huidige kleur.
     *
     * @param  array<string, mixed>  $appearance
     * @return list<array{id: int, license_plate: string, name: string, driver_name: string, active: bool, color: string, car_style: string}>
     */
    public function appearanceFleet(int $companyId, string $connection, array $appearance = []): array
    {
        if (! Schema::connection($connection)->hasTable('vehicles')) {
            return [];
        }

        $vehicles = Vehicle::on($connection)
            ->where('company_id', $companyId)
            ->orderByDesc('active')
            ->orderBy('license_plate')
            ->orderBy('name')
            ->get();

        if ($vehicles->isEmpty()) {
            return [];
        }

        $assigned = $this->assignedDriversByVehicle($companyId, $connection);
        $driverIds = array_values(array_unique(array_map('intval', $assigned)));
        $users = $driverIds === []
            ? collect()
            : User::query()
                ->whereIn('id', $driverIds)
                ->get(['id', 'first_name', 'last_name', 'email'])
                ->keyBy('id');

        $stored = is_array($appearance['vehicle_colors'] ?? null) ? $appearance['vehicle_colors'] : [];

        $out = [];
        foreach ($vehicles as $vehicle) {
            $id = (int) $vehicle->id;
            $driverId = (int) ($assigned[$id] ?? 0);
            $user = $driverId > 0 ? $users->get($driverId) : null;
            $plate = trim((string) ($vehicle->license_plate ?? ''));
            $name = trim((string) ($vehicle->name ?? ''));
            $custom = $stored[(string) $id] ?? $stored[$id] ?? null;

            $out[] = [
                'id' => $id,
                'license_plate' => $plate !== '' ? $plate : ($name !== '' ? $name : 'Voertuig #'.$id),
                'name' => $name,
                'driver_name' => $user ? $this->driverDisplayName($user) : 'Geen chauffeur',
                'active' => (bool) $vehicle->active,
                'car_style' => TaxiGpsTrackingSettingsService::styleFromVehicleType($vehicle->type),
                'color' => is_string($custom) && preg_match('/^#[0-9a-fA-F]{6}$/', $custom) === 1
                    ? strtolower($custom)
                    : $this->colorFor($id),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, int>
     */
    private function assignedDriversByVehicle(int $companyId, string $connection): array
    {
        if (! TaxiDispatchSchema::driverAvailabilityExists($connection)) {
            return [];
        }
        if (! Schema::connection($connection)->hasColumn('driver_availability', 'vehicle_id')) {
            return [];
        }

        $rows = DriverAvailability::on($connection)
            ->where('company_id', $companyId)
            ->whereNotNull('vehicle_id')
            ->orderByDesc('is_online')
            ->orderByDesc('last_seen_at')
            ->get(['driver_id', 'vehicle_id']);

        $assigned = [];
        foreach ($rows as $row) {
            $vehicleId = (int) $row->vehicle_id;
            if ($vehicleId > 0 && ! isset($assigned[$vehicleId])) {
                $assigned[$vehicleId] = (int) $row->driver_id;
            }
        }

        return $assigned;
    }

    /**
     * Auto van een chauffeur die nu onderweg is (toegewezen rit), niet van oude geaccepteerde boekingen.
     *
     * @param  list<int>  $driverIds
     * @return array<int, int>
     */
    private function currentAssignedVehicles(string $connection, int $companyId, array $driverIds): array
    {
        if ($driverIds === [] || ! Schema::connection($connection)->hasTable('ride_requests')) {
            return [];
        }

        $query = RideRequest::on($connection)
            ->where('company_id', $companyId)
            ->whereIn('driver_id', $driverIds)
            ->where('status', RideRequest::STATUS_ASSIGNED)
            ->whereNotNull('vehicle_id')
            ->orderByDesc('updated_at');

        $hasPickup = Schema::connection($connection)->hasColumn('ride_requests', 'pickup_at');
        $hasTripStarted = Schema::connection($connection)->hasColumn('ride_requests', 'trip_started_at');
        if ($hasPickup || $hasTripStarted) {
            $query->where(function ($inner) use ($hasPickup, $hasTripStarted) {
                if ($hasTripStarted) {
                    $inner->whereNotNull('trip_started_at');
                }
                if ($hasPickup) {
                    $method = $hasTripStarted ? 'orWhereBetween' : 'whereBetween';
                    $inner->{$method}('pickup_at', [now()->subHours(6), now()->addHours(4)]);
                }
            });
        }

        $map = [];
        foreach ($query->get(['driver_id', 'vehicle_id']) as $ride) {
            $driverId = (int) $ride->driver_id;
            if (! isset($map[$driverId])) {
                $map[$driverId] = (int) $ride->vehicle_id;
            }
        }

        return $map;
    }

    private function driverDisplayName(?User $user): string
    {
        if (! $user) {
            return 'Onbekende chauffeur';
        }
        $name = trim(trim((string) ($user->first_name ?? '')).' '.trim((string) ($user->last_name ?? '')));
        if ($name !== '') {
            return $name;
        }

        return (string) ($user->email ?? 'Chauffeur');
    }
}
