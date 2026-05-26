<?php

namespace App\Services;

use App\Models\Module;
use App\Modules\NexaTaxi\Models\DefaultRate;
use Illuminate\Support\Facades\Log;

/**
 * Haalt standaardtarieven op voor weergave op de frontend (o.a. pricing block).
 */
class NexaTaxiPublicRatesService
{
    public function __construct(
        protected ModuleDatabaseService $moduleDb
    ) {}

    /**
     * @return array{rates_1_4: \App\Modules\NexaTaxi\Models\DefaultRate|null, rates_5_8: \App\Modules\NexaTaxi\Models\DefaultRate|null, cleaning_costs: float|null}|null
     */
    public function getRatesForDisplay(): ?array
    {
        if (!Module::where('installed', true)->where('active', true)->whereRaw('LOWER(name) = ?', ['taxi'])->exists()) {
            return null;
        }
        try {
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
        } catch (\Throwable $e) {
            Log::debug('NexaTaxiPublicRatesService: no connection', ['message' => $e->getMessage()]);
            return null;
        }
        try {
            $forEdit = DefaultRate::getRatesForEdit($conn);
            $rates1_4 = $forEdit->firstWhere('person_range', '1-4');
            $rates5_8 = $forEdit->firstWhere('person_range', '5-8');
            $cleaning = null;
            if ($rates1_4 && $rates1_4->cleaning_costs !== null) {
                $cleaning = (float) $rates1_4->cleaning_costs;
            } elseif ($rates5_8 && $rates5_8->cleaning_costs !== null) {
                $cleaning = (float) $rates5_8->cleaning_costs;
            }
            return [
                'rates_1_4' => $rates1_4,
                'rates_5_8' => $rates5_8,
                'cleaning_costs' => $cleaning,
            ];
        } catch (\Throwable $e) {
            Log::debug('NexaTaxiPublicRatesService: getRatesForEdit failed', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
