<?php

namespace App\Services;

use App\Models\Module;
use App\Modules\TaxiRoyaal\Models\DefaultRate;
use Illuminate\Support\Facades\Log;

/**
 * Haalt standaardtarieven op voor weergave op de frontend (o.a. pricing block).
 */
class TaxiRoyaalPublicRatesService
{
    public function __construct(
        protected ModuleDatabaseService $moduleDb
    ) {}

    /**
     * @return array{rates_1_4: \App\Modules\TaxiRoyaal\Models\DefaultRate|null, rates_5_8: \App\Modules\TaxiRoyaal\Models\DefaultRate|null, cleaning_costs: float|null}|null
     */
    public function getRatesForDisplay(): ?array
    {
        if (!Module::where('installed', true)->where('active', true)->whereRaw('LOWER(name) = ?', ['taxiroyaal'])->exists()) {
            return null;
        }
        try {
            $conn = $this->moduleDb->getModuleConnectionName('taxiroyaal');
        } catch (\Throwable $e) {
            Log::debug('TaxiRoyaalPublicRatesService: no connection', ['message' => $e->getMessage()]);
            return null;
        }
        try {
            $forEdit = DefaultRate::getRatesForEdit($conn);
            $rates1_4 = $forEdit['1-4'] ?? null;
            $rates5_8 = $forEdit['5-8'] ?? null;
            $cleaning = $rates1_4 && $rates1_4->cleaning_costs !== null
                ? (float) $rates1_4->cleaning_costs
                : null;
            return [
                'rates_1_4' => $rates1_4,
                'rates_5_8' => $rates5_8,
                'cleaning_costs' => $cleaning,
            ];
        } catch (\Throwable $e) {
            Log::debug('TaxiRoyaalPublicRatesService: getRatesForEdit failed', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
