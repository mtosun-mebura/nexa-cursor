<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Services\ModuleDatabaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TaxiDriverEarningsService
{
    public const PERIOD_DAY = 'day';

    public const PERIOD_WEEK = 'week';

    public const PERIOD_MONTH = 'month';

    public function __construct(
        protected ModuleDatabaseService $moduleDb
    ) {}

    /**
     * @return array{
     *   period: string,
     *   date: string,
     *   from: string,
     *   to: string,
     *   label: string,
     *   sub_label: string,
     *   total_label: string,
     *   empty_message: string,
     *   is_today: bool,
     *   is_current: bool,
     *   currency: string,
     *   day_total: float,
     *   period_total: float,
     *   ride_count: int,
     *   rides: list<array<string, mixed>>,
     *   month: array{year: int, month: int, label: string, total: float, ride_count: int}|null
     * }
     */
    public function forDriverDay(int $companyId, int $driverId, string $date, bool $includeMonth): array
    {
        return $this->forDriverPeriod($companyId, $driverId, $date, self::PERIOD_DAY, $includeMonth);
    }

    /**
     * @return array{
     *   period: string,
     *   date: string,
     *   from: string,
     *   to: string,
     *   label: string,
     *   sub_label: string,
     *   total_label: string,
     *   empty_message: string,
     *   is_today: bool,
     *   is_current: bool,
     *   currency: string,
     *   day_total: float,
     *   period_total: float,
     *   ride_count: int,
     *   rides: list<array<string, mixed>>,
     *   month: array{year: int, month: int, label: string, total: float, ride_count: int}|null
     * }
     */
    public function forDriverPeriod(
        int $companyId,
        int $driverId,
        string $date,
        string $period,
        bool $includeMonth
    ): array {
        $period = $this->normalizePeriod($period);
        $tz = ContractTransportTimezone::TIMEZONE;
        $today = Carbon::now($tz)->startOfDay();
        $anchor = Carbon::parse($date, $tz)->startOfDay();
        if ($anchor->gt($today)) {
            $anchor = $today->copy();
        }

        [$from, $to] = $this->periodBounds($period, $anchor, $today);

        $conn = $this->moduleDb->getModuleConnectionName('taxi');
        $rides = $this->completedRidesForRange($conn, $companyId, $driverId, $from, $to);

        $items = [];
        $total = 0.0;
        foreach ($rides as $ride) {
            $amount = $ride->earningsAmountForDriver($driverId);
            $amountValue = $amount !== null ? round((float) $amount, 2) : 0.0;
            $total += $amountValue;
            $items[] = $this->serializeRide($ride, $amountValue, $driverId);
        }

        $isCurrent = $this->isCurrentPeriod($period, $from, $today);
        $labels = $this->periodLabels($period, $from, $to, $today, $isCurrent);

        $payload = [
            'period' => $period,
            'date' => $anchor->toDateString(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'label' => $labels['label'],
            'sub_label' => $labels['sub_label'],
            'total_label' => $labels['total_label'],
            'empty_message' => $labels['empty_message'],
            'is_today' => $period === self::PERIOD_DAY && $isCurrent,
            'is_current' => $isCurrent,
            'currency' => 'EUR',
            'day_total' => round($total, 2),
            'period_total' => round($total, 2),
            'ride_count' => count($items),
            'rides' => $items,
            'month' => null,
        ];

        if ($includeMonth && $period === self::PERIOD_DAY) {
            $payload['month'] = $this->monthSummary($conn, $companyId, $driverId, $anchor);
        }

        return $payload;
    }

    /**
     * @return Collection<int, RideRequest>
     */
    private function completedRidesForRange(string $conn, int $companyId, int $driverId, Carbon $from, Carbon $to): Collection
    {
        return RideRequest::on($conn)
            ->where('company_id', $companyId)
            ->where('status', RideRequest::STATUS_COMPLETED)
            ->where(function ($q) use ($driverId) {
                $q->where('driver_id', $driverId)
                    ->orWhere('outbound_driver_id', $driverId);
            })
            ->whereBetween('updated_at', [
                $from->copy()->startOfDay()->utc(),
                $to->copy()->endOfDay()->utc(),
            ])
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
        $today = Carbon::now(ContractTransportTimezone::TIMEZONE)->startOfDay();
        if ($monthEnd->gt($today)) {
            $monthEnd = $today->copy();
        }

        $rides = $this->completedRidesForRange($conn, $companyId, $driverId, $monthStart, $monthEnd);

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

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodBounds(string $period, Carbon $anchor, Carbon $today): array
    {
        if ($period === self::PERIOD_WEEK) {
            $from = $anchor->copy()->startOfWeek(Carbon::MONDAY);
            $to = $anchor->copy()->endOfWeek(Carbon::SUNDAY);
        } elseif ($period === self::PERIOD_MONTH) {
            $from = $anchor->copy()->startOfMonth();
            $to = $anchor->copy()->endOfMonth();
        } else {
            $from = $anchor->copy();
            $to = $anchor->copy();
        }

        if ($to->gt($today)) {
            $to = $today->copy();
        }

        return [$from->startOfDay(), $to->startOfDay()];
    }

    private function isCurrentPeriod(string $period, Carbon $from, Carbon $today): bool
    {
        if ($period === self::PERIOD_WEEK) {
            return $today->betweenIncluded(
                $from->copy()->startOfWeek(Carbon::MONDAY),
                $from->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay()
            );
        }
        if ($period === self::PERIOD_MONTH) {
            return $today->year === $from->year && $today->month === $from->month;
        }

        return $from->isSameDay($today);
    }

    /**
     * @return array{label: string, sub_label: string, total_label: string, empty_message: string}
     */
    private function periodLabels(string $period, Carbon $from, Carbon $to, Carbon $today, bool $isCurrent): array
    {
        $fromNl = $from->copy()->locale('nl');
        $toNl = $to->copy()->locale('nl');

        if ($period === self::PERIOD_WEEK) {
            return [
                'label' => $isCurrent ? 'Deze week' : 'Week '.$from->isoWeek(),
                'sub_label' => $fromNl->translatedFormat('j M').' – '.$toNl->translatedFormat('j M Y'),
                'total_label' => 'Totaal deze week',
                'empty_message' => 'Geen afgeronde ritten in deze week.',
            ];
        }
        if ($period === self::PERIOD_MONTH) {
            return [
                'label' => $fromNl->translatedFormat('F Y'),
                'sub_label' => $isCurrent ? 'Deze maand' : $fromNl->translatedFormat('j').' – '.$toNl->translatedFormat('j M'),
                'total_label' => 'Totaal deze maand',
                'empty_message' => 'Geen afgeronde ritten in deze maand.',
            ];
        }

        return [
            'label' => $this->dayLabel($from, $today),
            'sub_label' => $from->toDateString(),
            'total_label' => 'Totaal deze dag',
            'empty_message' => 'Geen afgeronde ritten op deze dag.',
        ];
    }

    private function normalizePeriod(string $period): string
    {
        return in_array($period, [self::PERIOD_DAY, self::PERIOD_WEEK, self::PERIOD_MONTH], true)
            ? $period
            : self::PERIOD_DAY;
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
