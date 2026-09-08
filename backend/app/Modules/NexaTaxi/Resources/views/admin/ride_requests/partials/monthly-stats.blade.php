@php
    $stats = $monthlyStats;
    $deltaClass = static function (array $delta): string {
        return match ($delta['dir'] ?? 'flat') {
            'up' => 'text-emerald-600 dark:text-emerald-400',
            'down' => 'text-rose-600 dark:text-rose-400',
            default => 'text-muted-foreground',
        };
    };
    $money = static fn (float $amount): string => '€ '.number_format($amount, 2, ',', '.');
    $vsLabel = 'vs '.$stats['previous_month_label'];
@endphp

<div class="flex flex-col gap-5 mb-5" id="ride-stats-collapsible-root">
    <div class="kt-card settings-collapsible-card w-full min-w-0" id="ride-stats-resultaten">
        <div class="kt-card-header settings-collapsible-header flex items-center gap-3 px-5 py-5">
            <button type="button"
                    class="settings-collapsible-toggle min-w-0 flex-1 text-start border-0 bg-transparent p-0 cursor-pointer"
                    aria-expanded="true"
                    aria-controls="ride-stats-resultaten-body">
                <h3 class="kt-card-title mb-0">Resultaten</h3>
                <p class="text-xs text-muted-foreground mb-0 mt-1">{{ $stats['month_label'] }} · ritten, omzet en opvolging</p>
            </button>
            <form method="GET"
                  action="{{ route('admin.taxi.ride_requests.index') }}"
                  id="ride-stats-month-form"
                  class="flex flex-wrap items-center gap-2 min-w-0 shrink-0">
                @if(request('status') !== null && request('status') !== '')
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('vehicle_id'))
                    <input type="hidden" name="vehicle_id" value="{{ request('vehicle_id') }}">
                @endif
                @if(request('from'))
                    <input type="hidden" name="from" value="{{ request('from') }}">
                @endif
                @if(request('to'))
                    <input type="hidden" name="to" value="{{ request('to') }}">
                @endif
                @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
                @include('admin.partials.month-picker-input', [
                    'name' => 'stats_month',
                    'id' => 'ride-stats-month',
                    'value' => $stats['month'],
                    'wrapperClass' => 'w-full sm:w-44',
                ])
            </form>
            <span class="settings-collapsible-chevron shrink-0 flex items-center text-muted-foreground" aria-hidden="true">
                <i class="ki-filled ki-down settings-collapsible-icon-down text-base"></i>
                <i class="ki-filled ki-up settings-collapsible-icon-up text-base"></i>
            </span>
        </div>
        <div class="settings-collapsible-body kt-card-content p-5" id="ride-stats-resultaten-body">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                @include('taxi::admin.ride_requests.partials.stat-tile', [
                    'label' => 'Voltooide ritten',
                    'value' => number_format($stats['completed']),
                    'hint' => $stats['deltas']['completed']['label'].' '.$vsLabel,
                    'hintClass' => $deltaClass($stats['deltas']['completed']),
                    'icon' => 'ki-check-circle',
                    'tone' => 'success',
                ])
                @include('taxi::admin.ride_requests.partials.stat-tile', [
                    'label' => 'Omzet',
                    'value' => $money((float) $stats['revenue_total']),
                    'hint' => $stats['deltas']['revenue']['label'].' '.$vsLabel,
                    'hintClass' => $deltaClass($stats['deltas']['revenue']),
                    'icon' => 'ki-wallet',
                    'tone' => 'primary',
                ])
                @include('taxi::admin.ride_requests.partials.stat-tile', [
                    'label' => 'Cash',
                    'value' => $money((float) $stats['revenue_cash']),
                    'hint' => $stats['deltas']['cash']['label'].' '.$vsLabel,
                    'hintClass' => $deltaClass($stats['deltas']['cash']),
                    'icon' => 'ki-dollar',
                    'tone' => 'warning',
                ])
                @include('taxi::admin.ride_requests.partials.stat-tile', [
                    'label' => 'Mollie',
                    'value' => $money((float) $stats['revenue_mollie']),
                    'hint' => $stats['deltas']['mollie']['label'].' '.$vsLabel,
                    'hintClass' => $deltaClass($stats['deltas']['mollie']),
                    'icon' => 'ki-credit-cart',
                    'tone' => 'info',
                ])
                @include('taxi::admin.ride_requests.partials.stat-tile', [
                    'label' => 'Niet geaccepteerd',
                    'value' => number_format($stats['not_accepted']),
                    'hint' => 'Chauffeur geweigerd · '.$stats['deltas']['not_accepted']['label'].' '.$vsLabel,
                    'hintClass' => $deltaClass($stats['deltas']['not_accepted']),
                    'icon' => 'ki-cross-circle',
                    'tone' => 'danger',
                ])
                @include('taxi::admin.ride_requests.partials.stat-tile', [
                    'label' => 'Andere tijd aangeboden',
                    'value' => number_format($stats['time_reoffered']),
                    'hint' => $stats['time_reoffered_declined'].' geweigerd · '.$stats['time_reoffered_accepted'].' akkoord',
                    'hintClass' => 'text-muted-foreground',
                    'icon' => 'ki-time',
                    'tone' => 'warning',
                ])
                @include('taxi::admin.ride_requests.partials.stat-tile', [
                    'label' => 'Geannuleerd',
                    'value' => number_format($stats['cancelled']),
                    'hint' => $stats['deltas']['cancelled']['label'].' '.$vsLabel,
                    'hintClass' => $deltaClass($stats['deltas']['cancelled']),
                    'icon' => 'ki-minus-circle',
                    'tone' => 'secondary',
                ])
                @include('taxi::admin.ride_requests.partials.stat-tile', [
                    'label' => 'Totaal ritten',
                    'value' => number_format($stats['total']),
                    'hint' => $stats['open'].' open · '.$stats['in_progress'].' onderweg',
                    'hintClass' => 'text-muted-foreground',
                    'icon' => 'ki-calendar',
                    'tone' => 'primary',
                ])
            </div>

            <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-2">
                @foreach([
                    ['label' => 'Open / aangeboden', 'value' => $stats['open']],
                    ['label' => 'Onderweg', 'value' => $stats['in_progress']],
                    ['label' => 'Wacht op betaling', 'value' => $stats['pending_payment']],
                    ['label' => 'Offerte', 'value' => $stats['quoted']],
                    ['label' => 'Verlopen aanbieding', 'value' => $stats['expired_offers']],
                    ['label' => 'Opnieuw naar chauffeurs', 'value' => $stats['redispatched']],
                ] as $chip)
                    <div class="rounded-lg border border-border px-3 py-2.5 min-w-0">
                        <div class="text-[11px] text-secondary-foreground leading-tight">{{ $chip['label'] }}</div>
                        <div class="mt-0.5 text-sm font-semibold text-mono tabular-nums">{{ number_format($chip['value']) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="kt-card settings-collapsible-card w-full min-w-0" id="ride-stats-verloop">
        <div class="kt-card-header settings-collapsible-header flex items-center gap-3 px-5 py-5">
            <button type="button"
                    class="settings-collapsible-toggle min-w-0 flex-1 text-start border-0 bg-transparent p-0 cursor-pointer"
                    aria-expanded="true"
                    aria-controls="ride-stats-verloop-body">
                <h3 class="kt-card-title mb-0">Verloop</h3>
                <p class="text-xs text-muted-foreground mb-0 mt-1">Dagelijkse ritten en omzet, betaalmix en statusverdeling</p>
            </button>
            <span class="settings-collapsible-chevron shrink-0 flex items-center text-muted-foreground" aria-hidden="true">
                <i class="ki-filled ki-down settings-collapsible-icon-down text-base"></i>
                <i class="ki-filled ki-up settings-collapsible-icon-up text-base"></i>
            </span>
        </div>
        <div class="settings-collapsible-body kt-card-content p-5" id="ride-stats-verloop-body">
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 min-w-0">
                <div class="xl:col-span-8 min-w-0">
                    <div class="text-xs font-medium text-secondary-foreground mb-3">Ritten en omzet per dag</div>
                    <div id="ride-stats-trend-chart" class="min-h-[280px]"></div>
                </div>
                <div class="xl:col-span-4 min-w-0 flex flex-col gap-8">
                    <div>
                        <div class="text-xs font-medium text-secondary-foreground mb-3">Betaalmix</div>
                        <div id="ride-stats-payment-chart" class="min-h-[220px]"></div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-secondary-foreground mb-3">Statusverdeling</div>
                        <div id="ride-stats-status-chart" class="min-h-[220px]"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
