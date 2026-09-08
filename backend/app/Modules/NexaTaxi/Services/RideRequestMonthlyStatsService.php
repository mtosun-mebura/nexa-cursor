<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Modules\NexaTaxi\Models\RideRequest;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;

class RideRequestMonthlyStatsService
{
    /**
     * @param  callable(\Illuminate\Database\Eloquent\Builder): void  $scopeRides
     * @return array<string, mixed>
     */
    public function forMonth(string $conn, CarbonInterface $monthStart, callable $scopeRides): array
    {
        $start = $monthStart->copy()->startOfMonth();
        $end = $start->copy()->addMonth();
        $previousStart = $start->copy()->subMonthNoOverflow();

        $current = $this->snapshot($conn, $start, $end, $scopeRides, true);
        $previous = $this->snapshot($conn, $previousStart, $start, $scopeRides, false);

        $current['month'] = $start->format('Y-m');
        $current['month_label'] = $start->copy()->locale('nl')->translatedFormat('F Y');
        $current['previous_month_label'] = $previousStart->copy()->locale('nl')->translatedFormat('F');
        $current['deltas'] = [
            'completed' => $this->delta((int) $current['completed'], (int) $previous['completed']),
            'revenue' => $this->delta((float) $current['revenue_total'], (float) $previous['revenue_total']),
            'cash' => $this->delta((float) $current['revenue_cash'], (float) $previous['revenue_cash']),
            'mollie' => $this->delta((float) $current['revenue_mollie'], (float) $previous['revenue_mollie']),
            'not_accepted' => $this->delta((int) $current['not_accepted'], (int) $previous['not_accepted']),
            'time_reoffered' => $this->delta((int) $current['time_reoffered'], (int) $previous['time_reoffered']),
            'cancelled' => $this->delta((int) $current['cancelled'], (int) $previous['cancelled']),
            'total' => $this->delta((int) $current['total'], (int) $previous['total']),
        ];

        return $current;
    }

    /**
     * @param  callable(\Illuminate\Database\Eloquent\Builder): void  $scopeRides
     * @return array<string, mixed>
     */
    protected function snapshot(
        string $conn,
        CarbonInterface $start,
        CarbonInterface $end,
        callable $scopeRides,
        bool $withSeries
    ): array {
        $schema = Schema::connection($conn);
        $hasProposalStatus = $schema->hasColumn('ride_requests', 'pickup_proposal_status');
        $hasProposalSentAt = $schema->hasColumn('ride_requests', 'pickup_proposal_sent_at');
        $hasPayments = $schema->hasTable('ride_payments');
        $hasOffers = $schema->hasTable('ride_dispatch_offers');

        $rides = RideRequest::on($conn)
            ->where('pickup_at', '>=', $start)
            ->where('pickup_at', '<', $end);
        $scopeRides($rides);

        $byStatus = (clone $rides)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();

        $statusLabels = RideRequest::statusLabels();
        $total = array_sum($byStatus);
        $completed = $byStatus[RideRequest::STATUS_COMPLETED] ?? 0;
        $cancelled = $byStatus[RideRequest::STATUS_CANCELLED] ?? 0;
        $open = ($byStatus[RideRequest::STATUS_PENDING_DISPATCH] ?? 0)
            + ($byStatus[RideRequest::STATUS_OFFERED] ?? 0);
        $inProgress = ($byStatus[RideRequest::STATUS_ACCEPTED] ?? 0)
            + ($byStatus[RideRequest::STATUS_ASSIGNED] ?? 0);
        $pendingPayment = $byStatus[RideRequest::STATUS_PENDING_PAYMENT] ?? 0;
        $quoted = $byStatus[RideRequest::STATUS_QUOTED] ?? 0;
        $draft = $byStatus[RideRequest::STATUS_DRAFT] ?? 0;

        $timeReoffered = 0;
        $timeReofferedAccepted = 0;
        $timeReofferedDeclined = 0;
        $timeReofferedPending = 0;
        if ($hasProposalStatus) {
            $proposalQuery = RideRequest::on($conn);
            $scopeRides($proposalQuery);
            if ($hasProposalSentAt) {
                $proposalQuery
                    ->whereNotNull('pickup_proposal_sent_at')
                    ->where('pickup_proposal_sent_at', '>=', $start)
                    ->where('pickup_proposal_sent_at', '<', $end);
            } else {
                $proposalQuery
                    ->whereNotNull('pickup_proposal_status')
                    ->where('pickup_at', '>=', $start)
                    ->where('pickup_at', '<', $end);
            }

            $proposalCounts = (clone $proposalQuery)
                ->selectRaw('pickup_proposal_status, COUNT(*) as aggregate')
                ->groupBy('pickup_proposal_status')
                ->pluck('aggregate', 'pickup_proposal_status')
                ->map(fn ($count) => (int) $count)
                ->all();

            $timeReoffered = array_sum($proposalCounts);
            $timeReofferedAccepted = $proposalCounts[RideRequest::PICKUP_PROPOSAL_ACCEPTED] ?? 0;
            $timeReofferedDeclined = $proposalCounts[RideRequest::PICKUP_PROPOSAL_DECLINED] ?? 0;
            $timeReofferedPending = $proposalCounts[RideRequest::PICKUP_PROPOSAL_PENDING] ?? 0;
        }

        $notAccepted = 0;
        $expiredOffers = 0;
        $redispatched = 0;
        if ($hasOffers) {
            $notAccepted = $this->distinctOfferRides(
                $conn,
                $start,
                $end,
                $scopeRides,
                [RideDispatchOffer::STATUS_DECLINED]
            );
            $expiredOffers = $this->distinctOfferRides(
                $conn,
                $start,
                $end,
                $scopeRides,
                [RideDispatchOffer::STATUS_EXPIRED]
            );
            if ($schema->hasColumn('ride_dispatch_offers', 'wave')) {
                $redispatched = (int) RideDispatchOffer::on($conn)
                    ->where('wave', '>', 1)
                    ->where('offered_at', '>=', $start)
                    ->where('offered_at', '<', $end)
                    ->whereHas('rideRequest', $scopeRides)
                    ->distinct()
                    ->count('ride_request_id');
            }
        }

        $revenueCash = 0.0;
        $revenueMollie = 0.0;
        $paidByDay = [];
        if ($hasPayments) {
            $payments = RidePayment::on($conn)
                ->where('status', RidePayment::STATUS_PAID)
                ->whereNotNull('paid_at')
                ->where('paid_at', '>=', $start)
                ->where('paid_at', '<', $end)
                ->whereHas('rideRequest', $scopeRides)
                ->get(['channel', 'amount', 'paid_at']);

            foreach ($payments as $payment) {
                $amount = round((float) $payment->amount, 2);
                if ($payment->channel === RidePayment::CHANNEL_CASH) {
                    $revenueCash += $amount;
                } else {
                    $revenueMollie += $amount;
                }
                if ($withSeries && $payment->paid_at) {
                    $day = $payment->paid_at->toDateString();
                    $paidByDay[$day] = round(($paidByDay[$day] ?? 0) + $amount, 2);
                }
            }
        }

        $revenueTotal = round($revenueCash + $revenueMollie, 2);
        $statusChart = [];
        foreach ($statusLabels as $value => $label) {
            $count = $byStatus[$value] ?? 0;
            if ($count > 0) {
                $statusChart[] = ['label' => $label, 'value' => $count];
            }
        }

        $result = [
            'total' => $total,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'open' => $open,
            'in_progress' => $inProgress,
            'pending_payment' => $pendingPayment,
            'quoted' => $quoted,
            'draft' => $draft,
            'not_accepted' => $notAccepted,
            'expired_offers' => $expiredOffers,
            'redispatched' => $redispatched,
            'time_reoffered' => $timeReoffered,
            'time_reoffered_accepted' => $timeReofferedAccepted,
            'time_reoffered_declined' => $timeReofferedDeclined,
            'time_reoffered_pending' => $timeReofferedPending,
            'revenue_total' => $revenueTotal,
            'revenue_cash' => round($revenueCash, 2),
            'revenue_mollie' => round($revenueMollie, 2),
            'by_status' => $byStatus,
            'status_chart' => $statusChart,
            'daily' => [],
        ];

        if ($withSeries) {
            $completedByDay = (clone $rides)
                ->where('status', RideRequest::STATUS_COMPLETED)
                ->selectRaw('DATE(pickup_at) as day, COUNT(*) as aggregate')
                ->groupByRaw('DATE(pickup_at)')
                ->pluck('aggregate', 'day')
                ->all();
            $cancelledByDay = (clone $rides)
                ->where('status', RideRequest::STATUS_CANCELLED)
                ->selectRaw('DATE(pickup_at) as day, COUNT(*) as aggregate')
                ->groupByRaw('DATE(pickup_at)')
                ->pluck('aggregate', 'day')
                ->all();

            $cursor = $start->copy();
            while ($cursor->lt($end)) {
                $key = $cursor->toDateString();
                $result['daily'][] = [
                    'date' => $key,
                    'label' => $cursor->format('j'),
                    'completed' => (int) ($completedByDay[$key] ?? 0),
                    'cancelled' => (int) ($cancelledByDay[$key] ?? 0),
                    'revenue' => round((float) ($paidByDay[$key] ?? 0), 2),
                ];
                $cursor->addDay();
            }
        }

        return $result;
    }

    /**
     * @param  callable(\Illuminate\Database\Eloquent\Builder): void  $scopeRides
     * @param  list<string>  $statuses
     */
    protected function distinctOfferRides(
        string $conn,
        CarbonInterface $start,
        CarbonInterface $end,
        callable $scopeRides,
        array $statuses
    ): int {
        return (int) RideDispatchOffer::on($conn)
            ->whereIn('status', $statuses)
            ->where(function ($query) use ($start, $end) {
                $query->where(function ($inner) use ($start, $end) {
                    $inner->whereNotNull('responded_at')
                        ->where('responded_at', '>=', $start)
                        ->where('responded_at', '<', $end);
                })->orWhere(function ($inner) use ($start, $end) {
                    $inner->whereNull('responded_at')
                        ->where('offered_at', '>=', $start)
                        ->where('offered_at', '<', $end);
                });
            })
            ->whereHas('rideRequest', $scopeRides)
            ->distinct()
            ->count('ride_request_id');
    }

    /**
     * @return array{pct: float, dir: string, label: string}
     */
    protected function delta(int|float $current, int|float $previous): array
    {
        if ((float) $previous === 0.0) {
            $dir = (float) $current > 0 ? 'up' : 'flat';
            $pct = (float) $current > 0 ? 100.0 : 0.0;
        } else {
            $pct = round((((float) $current - (float) $previous) / (float) $previous) * 100, 1);
            $dir = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat');
            $pct = abs($pct);
        }

        $prefix = $dir === 'down' ? '−' : ($dir === 'up' ? '+' : '');

        return [
            'pct' => $pct,
            'dir' => $dir,
            'label' => $prefix.rtrim(rtrim(number_format($pct, 1, ',', ''), '0'), ',').'%',
        ];
    }
}
