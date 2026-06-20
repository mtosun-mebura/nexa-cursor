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

    <div class="grid gap-4 lg:grid-cols-7">
        @foreach($days as $day)
        <div class="kt-card min-w-0">
            <div class="kt-card-header py-3">
                <h3 class="kt-card-title text-sm mb-0">{{ $day['label'] }}</h3>
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
                    @endphp
                    <div class="rounded border px-2 py-2 text-xs" style="{{ $cardStyle }}">
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
                    </div>
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
@endsection
