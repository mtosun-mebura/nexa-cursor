<?php

namespace App\Services;

use App\Models\Module;
use App\Modules\NexaTaxi\Models\Vehicle;
use Illuminate\Support\Facades\Log;

/**
 * Voertuigen en afbeeldingen voor weergave (o.a. website tarieven-kaarten).
 */
class NexaTaxiVehicleDisplayService
{
    public function __construct(
        protected ModuleDatabaseService $moduleDb,
        protected WebsiteBuilderService $websiteBuilder
    ) {}

    /**
     * Afbeelding-URL voor een voertuig (voor frontend weergave). Gebruikt /file/ route.
     *
     * @param  ?int  $forCompanyId  Optioneel: alleen matchen als het voertuig bij dit bedrijf hoort.
     */
    public function getImageUrl(?int $vehicleId, ?int $forCompanyId = null): ?string
    {
        if ($vehicleId === null || $vehicleId <= 0) {
            return null;
        }
        try {
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
        } catch (\Throwable $e) {
            return null;
        }
        if (! Module::where('installed', true)->where('active', true)->whereRaw('LOWER(name) = ?', ['taxi'])->exists()) {
            return null;
        }
        try {
            $query = Vehicle::on($conn)->whereKey($vehicleId);
            if ($forCompanyId !== null && $forCompanyId > 0) {
                $query->where('company_id', $forCompanyId);
            }
            $vehicle = $query->first();
            if (! $vehicle || empty($vehicle->image_url)) {
                return null;
            }
            $displayUrl = $this->websiteBuilder->storageUrlToDisplayUrl(trim((string) $vehicle->image_url));

            return $displayUrl === '' ? null : $displayUrl;
        } catch (\Throwable $e) {
            Log::debug('NexaTaxiVehicleDisplayService: getImageUrl failed', ['id' => $vehicleId, 'message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Lijst voertuigen voor select (id, name, image_url als weergave-URL, person_range).
     *
     * @param  ?int  $forCompanyId  Alleen voertuigen van dit bedrijf; null = geen filter (alle actieve).
     * @return array<int, array{id: int, name: string, image_url: string|null, person_range: string|null}>
     */
    public function getVehiclesForSelect(?int $forCompanyId = null): array
    {
        if (! Module::where('installed', true)->where('active', true)->whereRaw('LOWER(name) = ?', ['taxi'])->exists()) {
            return [];
        }
        try {
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
        } catch (\Throwable $e) {
            return [];
        }
        try {
            $q = Vehicle::on($conn)
                ->where('active', true);
            if ($forCompanyId !== null && $forCompanyId > 0) {
                $q->where('company_id', $forCompanyId);
            }

            return $q
                ->orderBy('name')
                ->get(['id', 'name', 'image_url', 'person_range'])
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'image_url' => $v->image_url ? $this->websiteBuilder->storageUrlToDisplayUrl(trim((string) $v->image_url)) : null,
                    'person_range' => isset($v->person_range) && trim((string) $v->person_range) !== '' ? trim((string) $v->person_range) : null,
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::debug('NexaTaxiVehicleDisplayService: getVehiclesForSelect failed', ['message' => $e->getMessage()]);

            return [];
        }
    }
}
