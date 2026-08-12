@extends('admin.layouts.app')

@section('title', 'Contractvervoer planning')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">Planningsoverzicht</h1>
            <p class="text-sm text-muted-foreground pt-2">
                Week {{ $weekStart->locale('nl')->translatedFormat('d M') }} – {{ $weekEnd->locale('nl')->translatedFormat('d M Y') }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.taxi.transport_planning.index', array_filter(['week' => $prevWeek, 'contract_id' => $contractFilter])) }}" class="kt-btn kt-btn-outline">← Vorige week</a>
            <a
                href="{{ route('admin.taxi.transport_planning.index', array_filter(['week' => $todayWeek, 'contract_id' => $contractFilter])) }}"
                class="kt-btn {{ $isCurrentWeek ? 'kt-btn-primary' : 'kt-btn-outline' }}"
                @if($isCurrentWeek) aria-current="date" @endif
            >Vandaag</a>
            <a href="{{ route('admin.taxi.transport_planning.index', array_filter(['week' => $nextWeek, 'contract_id' => $contractFilter])) }}" class="kt-btn kt-btn-outline">Volgende week →</a>
        </div>
    </div>

    <form method="GET" class="kt-card mb-5">
        <div class="kt-card-content flex flex-wrap items-end gap-3 p-4">
            <div>
                <label class="text-sm text-secondary-foreground block mb-1">Week (maandag)</label>
                @include('taxi::admin.transport_customers.partials.date-picker-input', [
                    'name' => 'week',
                    'value' => $weekStart->format('Y-m-d'),
                    'wrapperClass' => 'w-full',
                    'placeholder' => 'Selecteer week',
                ])
            </div>
            <div>
                <label class="text-sm text-secondary-foreground block mb-1">Abonnement</label>
                <select name="contract_id" class="kt-select min-w-48">
                    <option value="">Alle abonnementen</option>
                    @foreach($contracts as $contractOption)
                        <option value="{{ $contractOption->id }}" @selected($contractFilter == $contractOption->id)>{{ $contractOption->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="kt-btn kt-btn-primary">Filteren</button>
        </div>
    </form>

    @if($contracts->isNotEmpty())
    <div class="flex flex-wrap gap-3 mb-5">
        @foreach($contracts as $contractOption)
            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                <span
                    class="inline-block h-3 w-3 rounded-full border"
                    style="background-color: {{ $contractOption->planningColorHex() }}; border-color: {{ $contractOption->planningColorHex() }};"
                ></span>
                <span>{{ $contractOption->name }}</span>
            </div>
        @endforeach
    </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-7" id="planning-week-grid">
        @php
            $planningOccurrenceDetails = [];
        @endphp
        @foreach($days as $day)
        <div class="kt-card min-w-0{{ $day['isToday'] ? ' border-2 border-primary' : '' }}">
            <div class="kt-card-header py-3">
                <h3 class="kt-card-title text-sm mb-0{{ $day['isToday'] ? ' text-primary' : '' }}">{{ $day['label'] }}</h3>
            </div>
            <div class="kt-card-content p-3 space-y-2 min-h-32">
                @foreach($day['exceptions'] as $exception)
                    <div class="rounded border border-dashed border-warning/60 bg-warning/10 px-2 py-1 text-xs text-warning-foreground">
                        {{ $exception->name }}
                        @if($exception->transport_contract_id)
                            <span class="text-muted-foreground">(abonnement)</span>
                        @else
                            <span class="text-muted-foreground">(bedrijf)</span>
                        @endif
                    </div>
                @endforeach

                @forelse($day['occurrences'] as $occurrence)
                    @php
                        $ride = $occurrence->rideRequest;
                        $contract = $occurrence->contract;
                        $isGroup = $occurrence->occurrence_type === 'group' || $ride?->ride_type === 'contract_group';
                        $title = $isGroup
                            ? ($occurrence->routeTemplate?->group?->name ?? 'Groepsrit')
                            : ($occurrence->individualBooking?->passenger?->full_name ?? 'Individuele rit');
                        $status = $ride?->status ?? $occurrence->status;
                        $statusLabel = match($status) {
                            'completed' => 'Voltooid',
                            'assigned' => 'Onderweg',
                            'accepted' => 'Gepland',
                            'cancelled' => 'Geannuleerd',
                            default => ucfirst((string) $status),
                        };
                        $cardStyle = $contract ? $contract->planningCardStyle() : 'background-color: rgba(148, 163, 184, 0.16); border-color: rgba(148, 163, 184, 0.55);';
                        $driver = $ride?->driver ?? $occurrence->routeTemplate?->assignment?->driver;
                        $driverName = $driver ? trim($driver->first_name.' '.$driver->last_name) : '';
                        $vehicle = $ride?->vehicle ?? $occurrence->routeTemplate?->assignment?->vehicle;
                        $vehicleLabel = $vehicle
                            ? trim(($vehicle->name ?? '').' '.($vehicle->license_plate ?? ''))
                            : '';
                        $vehicleLabel = $vehicleLabel !== '' ? $vehicleLabel : '—';
                        $dateLabel = $occurrence->scheduled_date
                            ? $occurrence->scheduled_date->locale('nl')->translatedFormat('l d F Y')
                            : $day['label'];
                        $timeLabel = $occurrence->scheduled_at
                            ? $occurrence->scheduled_at->format('H:i')
                            : '—';
                        $stops = ($ride?->rideStops ?? collect())
                            ->sortBy('sequence')
                            ->values()
                            ->map(function ($stop) {
                                $typeLabel = match ($stop->stop_type) {
                                    'pickup' => 'Ophalen',
                                    'dropoff', 'destination' => 'Afzetten',
                                    default => ucfirst((string) $stop->stop_type),
                                };
                                $planned = $stop->planned_at ? $stop->planned_at->format('H:i') : null;

                                return [
                                    'type' => $typeLabel,
                                    'name' => $stop->passenger_name ?: null,
                                    'address' => $stop->address ?: '—',
                                    'time' => $planned,
                                ];
                            })
                            ->all();
                        $detailUrl = $ride
                            ? route('admin.taxi.ride_requests.show', $ride->id)
                            : null;
                        $planningOccurrenceDetails[(string) $occurrence->id] = [
                            'title' => $title,
                            'contract' => $contract?->name,
                            'type' => $isGroup ? 'Groepsrit' : 'Individuele rit',
                            'date' => $dateLabel,
                            'time' => $timeLabel,
                            'status' => $statusLabel,
                            'statusKey' => (string) $status,
                            'driver' => $driverName !== '' ? $driverName : '—',
                            'vehicle' => $vehicleLabel,
                            'stops' => $stops,
                            'detailUrl' => $detailUrl,
                        ];
                    @endphp
                    <button
                        type="button"
                        class="planning-occurrence-card w-full text-left rounded border px-2 py-2 text-xs cursor-pointer transition hover:brightness-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        style="{{ $cardStyle }}"
                        data-planning-occurrence="{{ $occurrence->id }}"
                        aria-haspopup="dialog"
                    >
                        @if(!$contractFilter && $contract)
                            <div class="text-[10px] uppercase tracking-wide text-muted-foreground pb-0.5">{{ $contract->name }}</div>
                        @endif
                        <div class="font-medium text-foreground">{{ $title }}</div>
                        <div class="text-muted-foreground">
                            {{ $isGroup ? 'Groep' : 'Individueel' }}
                            @if($occurrence->scheduled_at)
                                · {{ $occurrence->scheduled_at->format('H:i') }}
                            @endif
                        </div>
                        <div class="text-muted-foreground">
                            Chauffeur: {{ $driverName !== '' ? $driverName : '—' }}
                        </div>
                        <div class="pt-1">
                            <span class="kt-badge kt-badge-sm {{ $status === 'completed' ? 'kt-badge-success' : 'kt-badge-light' }}">{{ $statusLabel }}</span>
                        </div>
                    </button>
                @empty
                    @if($day['exceptions']->isEmpty())
                        <p class="text-xs text-muted-foreground">Geen ritten</p>
                    @endif
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</div>

<script type="application/json" id="planning-occurrence-details-data">@json($planningOccurrenceDetails ?? new \stdClass())</script>

<div id="planning-occurrence-modal"
     class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="planning-occurrence-modal-title"
     aria-hidden="true">
    <div class="w-full max-w-lg max-h-[min(90vh,40rem)] flex flex-col rounded-xl border border-input bg-background shadow-xl overflow-hidden"
         data-planning-modal-panel>
        <div class="flex items-start justify-between gap-3 border-b border-input px-5 py-4 shrink-0">
            <div class="min-w-0">
                <p class="text-xs uppercase tracking-wide text-muted-foreground mb-1" data-planning-modal-contract></p>
                <h3 id="planning-occurrence-modal-title" class="text-lg font-semibold text-foreground mb-0 break-words" data-planning-modal-title></h3>
            </div>
            <button type="button"
                    class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0"
                    data-planning-modal-close
                    aria-label="Sluiten">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="flex-1 min-h-0 overflow-y-auto px-5 py-4 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <div class="text-xs text-muted-foreground mb-0.5">Datum</div>
                    <div class="font-medium text-foreground" data-planning-modal-date></div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground mb-0.5">Tijd</div>
                    <div class="font-medium text-foreground" data-planning-modal-time></div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground mb-0.5">Type</div>
                    <div class="font-medium text-foreground" data-planning-modal-type></div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground mb-0.5">Status</div>
                    <div><span class="kt-badge kt-badge-sm kt-badge-light" data-planning-modal-status></span></div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground mb-0.5">Chauffeur</div>
                    <div class="font-medium text-foreground" data-planning-modal-driver></div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground mb-0.5">Voertuig</div>
                    <div class="font-medium text-foreground" data-planning-modal-vehicle></div>
                </div>
            </div>

            <div>
                <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground mb-2">Stops</div>
                <div class="space-y-2" data-planning-modal-stops>
                    <p class="text-sm text-muted-foreground mb-0">Geen stops beschikbaar.</p>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-input px-5 py-3 shrink-0">
            <button type="button" class="kt-btn kt-btn-outline" data-planning-modal-close>Sluiten</button>
            <a href="#" class="kt-btn kt-btn-primary hidden" data-planning-modal-detail>Rit openen</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('planning-occurrence-modal');
    if (!modal) {
        return;
    }

    var details = {};
    var detailsNode = document.getElementById('planning-occurrence-details-data');
    if (detailsNode) {
        try {
            details = JSON.parse(detailsNode.textContent || '{}') || {};
        } catch (err) {
            details = {};
        }
    }

    var titleEl = modal.querySelector('[data-planning-modal-title]');
    var contractEl = modal.querySelector('[data-planning-modal-contract]');
    var dateEl = modal.querySelector('[data-planning-modal-date]');
    var timeEl = modal.querySelector('[data-planning-modal-time]');
    var typeEl = modal.querySelector('[data-planning-modal-type]');
    var statusEl = modal.querySelector('[data-planning-modal-status]');
    var driverEl = modal.querySelector('[data-planning-modal-driver]');
    var vehicleEl = modal.querySelector('[data-planning-modal-vehicle]');
    var stopsEl = modal.querySelector('[data-planning-modal-stops]');
    var detailEl = modal.querySelector('[data-planning-modal-detail]');
    var lastFocus = null;

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function openModal(detail) {
        lastFocus = document.activeElement;
        detail = detail || {};
        titleEl.textContent = detail.title || 'Planning';
        contractEl.textContent = detail.contract || '';
        contractEl.classList.toggle('hidden', !detail.contract);
        dateEl.textContent = detail.date || '—';
        timeEl.textContent = detail.time || '—';
        typeEl.textContent = detail.type || '—';
        statusEl.textContent = detail.status || '—';
        statusEl.className = 'kt-badge kt-badge-sm ' + (detail.statusKey === 'completed' ? 'kt-badge-success' : 'kt-badge-light');
        driverEl.textContent = detail.driver || '—';
        vehicleEl.textContent = detail.vehicle || '—';

        var stops = Array.isArray(detail.stops) ? detail.stops : [];
        if (!stops.length) {
            stopsEl.innerHTML = '<p class="text-sm text-muted-foreground mb-0">Geen stops beschikbaar.</p>';
        } else {
            stopsEl.innerHTML = stops.map(function (stop) {
                return '<div class="rounded-lg border border-input px-3 py-2 text-sm">'
                    + '<div class="flex flex-wrap items-center justify-between gap-2">'
                    + '<span class="font-medium text-foreground">' + escapeHtml(stop.type || 'Stop') + '</span>'
                    + (stop.time ? '<span class="text-xs text-muted-foreground tabular-nums">' + escapeHtml(stop.time) + '</span>' : '')
                    + '</div>'
                    + (stop.name ? '<div class="text-foreground mt-0.5">' + escapeHtml(stop.name) + '</div>' : '')
                    + '<div class="text-muted-foreground mt-0.5 break-words">' + escapeHtml(stop.address || '—') + '</div>'
                    + '</div>';
            }).join('');
        }

        if (detail.detailUrl) {
            detailEl.href = detail.detailUrl;
            detailEl.classList.remove('hidden');
        } else {
            detailEl.href = '#';
            detailEl.classList.add('hidden');
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        var closeBtn = modal.querySelector('[data-planning-modal-close]');
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
    }

    document.querySelectorAll('[data-planning-occurrence]').forEach(function (card) {
        card.addEventListener('click', function () {
            var id = String(card.getAttribute('data-planning-occurrence') || '');
            openModal(details[id] || {});
        });
    });

    modal.querySelectorAll('[data-planning-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
});
</script>
@endpush
