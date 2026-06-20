<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\TransportScheduleException;
use Carbon\Carbon;

class TransportScheduleExceptionService
{
    public function isExceptionDate(
        string $conn,
        int $companyId,
        Carbon $date,
        ?int $contractId = null,
    ): bool {
        $dateString = $date->toDateString();

        return TransportScheduleException::on($conn)
            ->where('company_id', $companyId)
            ->where('active', true)
            ->whereDate('exception_date', $dateString)
            ->where(function ($query) use ($contractId) {
                $query->whereNull('transport_contract_id');
                if ($contractId) {
                    $query->orWhere('transport_contract_id', $contractId);
                }
            })
            ->exists();
    }
}
