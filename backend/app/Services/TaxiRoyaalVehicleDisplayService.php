<?php

namespace App\Services;

use App\Models\Module;
use App\Modules\TaxiRoyaal\Models\Vehicle;
use Illuminate\Support\Facades\Log;

/**
 * Voertuigen en afbeeldingen voor weergave (o.a. website tarieven-kaarten).
 */
class TaxiRoyaalVehicleDisplayService
{
    public function __construct(
        protected ModuleDatabaseService $moduleDb
    ) {}

    /**
     * Afbeelding-URL voor een voertuig (voor frontend weergave).
     */
    public function getImageUrl(?int $vehicleId): ?string
    {
        if ($vehicleId === null || $vehicleId <= 0) {
            return null;
        }
        try {
            $conn = $this->moduleDb->getModuleConnectionName('taxiroyaal');
        } catch (\Throwable $e) {
            return null;
        }
        if (! Module::where('installed', true)->where('active', true)->whereRaw('LOWER(name) = ?', ['taxiroyaal'])->exists()) {
            return null;
        }
        try {
            $vehicle = Vehicle::on($conn)->find($vehicleId);
            if (! $vehicle || empty($vehicle->image_url)) {
                return null;
            }
            $url = trim((string) $vehicle->image_url);
            return $url === '' ? null : (str_starts_with($url, 'http') ? $url : asset(ltrim($url, '/')));
        } catch (\Throwable $e) {
            Log::debug('TaxiRoyaalVehicleDisplayService: getImageUrl failed', ['id' => $vehicleId, 'message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Lijst voertuigen voor select (id, name, image_url).
     *
     * @return array<int, array{id: int, name: string, image_url: string|null}>
     */
    public function getVehiclesForSelect(): array
    {
        if (! Module::where('installed', true)->where('active', true)->whereRaw('LOWER(name) = ?', ['taxiroyaal'])->exists()) {
            return [];
        }
        try {
            $conn = $this->moduleDb->getModuleConnectionName('taxiroyaal');
        } catch (\Throwable $e) {
            return [];
        }
        try {
            return Vehicle::on($conn)
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'image_url'])
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'image_url' => $v->image_url ? trim((string) $v->image_url) : null,
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::debug('TaxiRoyaalVehicleDisplayService: getVehiclesForSelect failed', ['message' => $e->getMessage()]);

            return [];
        }
    }
}
