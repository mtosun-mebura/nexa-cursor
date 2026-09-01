<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Services\ModuleDatabaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TaxiDriverEarningsService
{
    public function __construct(
        protected ModuleDatabaseService $moduleDb
    ) {}

    /**
     * @return array{
     *   date: string,
     *   label: string,
     *   is_today: bool,
     *   currency: string,
     *   day_total: float,
     *   ride_count: int,
     *   rides: list<array<string, mixed>>,
     *   month: array{year: int, month: int, label: string, total: float, ride_count: int}|null
     * }
     */
    public function forDriverDay(int $companyId, int $driverId, string $date, bool $includeMonth): array
    {
        $tz = ContractTransportTimezone::TIMEZONE;
        $day = Carbon::parse($date, $tz)->startOfDay();
        $today = Carbon::now($tz)->startOfDay();
        if ($day->gt($today)) {
            $day = $today->copy();
        }

        $conn = $this->moduleDb->getModuleConnectionName('taxi');
        $rides = $this->completedRidesForDay($conn, $companyId, $driverId, $day);

        $items = [];
        $dayTotal = 0.0;
        foreach ($rides as $ride) {
            $amount = $ride->earningsAmountForDriver($driverId);
            $amountValue = $amount !== null ? round((float) $amount, 2) : 0.0;
            $dayTotal += $amountValue;
            $items[] = $this->serializeRide($ride, $amountValue, $driverId);
        }

        $payload = [
            'date' => $day->toDateString(),
            'label' => $this->dayLabel($day, $today),
            'is_today' => $day->isSameDay($today),
            'currency' => 'EUR',
            'day_total' => round($dayTotal, 2),
            'ride_count' => count($items),
            'rides' => $items,
            'month' => null,
        ];

        if ($includeMonth) {
            $payload['month'] = $this->monthSummary($conn, $companyId, $driverId, $day);
        }

        return $payload;
    }

    /**
     * @return Collection<int, RideRequest>
     */
    private function completedRidesForDay(string $conn, int $companyId, int $driverId, Carbon $day): Collection
    {
        $startUtc = $day->copy()->startOfDay()->utc();
        $endUtc = $day->copy()->endOfDay()->utc();

        return RideRequest::on($conn)
            ->where('company_id', $companyId)
            ->where('status', RideRequest::STATUS_COMPLETED)
            ->where(function ($q) use ($driverId) {
                $q->where('driver_id', $driverId)
                    ->orWhere('outbound_driver_id', $driverId);
            })
            ->whereBetween('updated_at', [$startUtc, $endUtc])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array{year: int, month: int, label: string, total: float, ride_count: int}
     */
    private function monthSummary(string $conn, int $companyId, int $driverId, Carbon $day): array
    {
        $monthStart = $day->copy()->startOfMonth();
        $monthEnd = $day->copy()->endOfMonth();
        $today = Carbon::now(ContractTransportTimezone::TIMEZONE);
        if ($monthEnd->gt($today)) {
            $monthEnd = $today->copy()->endOfDay();
        }

        $rides = RideRequest::on($conn)
            ->where('company_id', $companyId)
            ->where('status', RideRequest::STATUS_COMPLETED)
            ->where(function ($q) use ($driverId) {
                $q->where('driver_id', $driverId)
                    ->orWhere('outbound_driver_id', $driverId);
            })
            ->whereBetween('updated_at', [
                $monthStart->copy()->startOfDay()->utc(),
                $monthEnd->copy()->endOfDay()->utc(),
            ])
            ->get(['id', 'driver_id', 'outbound_driver_id', 'final_price', 'quoted_price', 'payment_method', 'return_at', 'booking_payload']);

        $total = 0.0;
        foreach ($rides as $ride) {
            $amount = $ride->earningsAmountForDriver($driverId);
            if ($amount !== null) {
                $total += (float) $amount;
            }
        }

        return [
            'year' => (int) $day->year,
            'month' => (int) $day->month,
            'label' => $day->locale('nl')->translatedFormat('F Y'),
            'total' => round($total, 2),
            'ride_count' => $rides->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRide(RideRequest $ride, float $amount, int $driverId): array
    {
        $completedAt = $ride->updated_at
            ? $ride->updated_at->copy()->timezone(ContractTransportTimezone::TIMEZONE)
            : null;

        return [
            'id' => (int) $ride->id,
            'completed_at' => $completedAt?->toIso8601String(),
            'completed_time' => $completedAt?->format('H:i'),
            'pickup_address' => (string) ($ride->pickup_address ?: '—'),
            'dropoff_address' => (string) ($ride->dropoff_address ?: '—'),
            'amount' => $amount,
            'currency' => 'EUR',
            'payment_method' => $ride->payment_method,
            'payment_status' => $ride->payment_status,
            'is_contract' => $ride->isContractRide(),
            'customer_name' => $ride->customer_name,
            'credited_as' => (int) $ride->driver_id === $driverId
                ? 'full'
                : 'outbound',
        ];
    }

    private function dayLabel(Carbon $day, Carbon $today): string
    {
        if ($day->isSameDay($today)) {
            return 'Vandaag';
        }
        if ($day->isSameDay($today->copy()->subDay())) {
            return 'Gisteren';
        }

        return $day->locale('nl')->translatedFormat('l j F Y');
    }
}
