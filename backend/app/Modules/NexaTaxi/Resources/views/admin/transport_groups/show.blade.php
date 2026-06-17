@extends('admin.layouts.app')

@section('title', $group->name)

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">{{ $group->name }}</h1>
            <p class="text-sm text-muted-foreground pt-2">{{ $contract->name }} · {{ $customer->name }}</p>
            <div class="pt-3 flex flex-wrap gap-2">
                <a href="{{ $backUrl }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug
                </a>
            </div>
        </div>
        @can('rides.update')
        <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_groups.edit', [$customer->id, $contract->id, $group->id]), url()->full()) }}" class="kt-btn kt-btn-outline shrink-0">Bewerken</a>
        @endcan
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5">

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header"><h3 class="kt-card-title mb-0">Groepsgegevens</h3></div>
            <div class="kt-card-content p-0">
                <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-medium">Eindlocatie</td>
                            <td>{{ $group->destination_address }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">Aankomsttijd</td>
                            <td>{{ substr($group->destination_arrival_time, 0, 5) }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">Status</td>
                            <td>
                                @if($group->active)
                                    <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                @else
                                    <span class="kt-badge kt-badge-secondary kt-badge-sm">Inactief</span>
                                @endif
                            </td>
                        </tr>
                        @if($group->notes)
                        <tr>
                            <td class="text-secondary-foreground font-medium">Notities</td>
                            <td class="whitespace-pre-wrap">{{ $group->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-2">
                <h3 class="kt-card-title mb-0">Leden ({{ $activeMembers->count() }})</h3>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                @can('rides.update')
                @if($availablePassengers->isNotEmpty())
                <div class="px-3 sm:px-5 py-4 border-b border-input">
                    <form method="POST" action="{{ route('admin.taxi.transport_groups.member_store', [$customer->id, $contract->id, $group->id]) }}" class="flex flex-col lg:flex-row flex-wrap gap-3 items-start lg:items-end">
                        @csrf
                        <div class="flex-1 min-w-[12rem] w-full lg:max-w-xl">
                            <label class="text-xs text-muted-foreground mb-1 block" for="transport_passenger_ids">Passagiers toevoegen</label>
                            @php
                                $selectedPassengerIds = collect(old('transport_passenger_id', []))->map(fn ($id) => (int) $id)->all();
                                $selectSize = min(8, max(4, $availablePassengers->count()));
                            @endphp
                            <select name="transport_passenger_id[]"
                                    id="transport_passenger_ids"
                                    class="kt-select w-full transport-group-member-select"
                                    multiple
                                    required
                                    size="{{ $selectSize }}">
                                @foreach($availablePassengers as $passenger)
                                    <option value="{{ $passenger->id }}" @selected(in_array($passenger->id, $selectedPassengerIds, true))>
                                        {{ $passenger->full_name }} — {{ Str::limit($passenger->pickup_address, 40) }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-muted-foreground mt-1">Houd <kbd class="px-1 py-0.5 rounded border border-input text-[0.7rem]">Ctrl</kbd> of <kbd class="px-1 py-0.5 rounded border border-input text-[0.7rem]">⌘</kbd> ingedrukt om meerdere passagiers te selecteren.</p>
                            @error('transport_passenger_id')
                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="text-xs text-muted-foreground mb-1 block">Ingangsdatum</label>
                            @include('taxi::admin.transport_customers.partials.date-picker-input', [
                                'name' => 'valid_from',
                                'value' => old('valid_from', now()->format('Y-m-d')),
                                'wrapperClass' => 'w-44',
                            ])
                        </div>
                        <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm shrink-0">Toevoegen</button>
                    </form>
                </div>
                @elseif($activeMembers->isEmpty())
                <div class="px-3 sm:px-5 py-4 border-b border-input text-sm text-muted-foreground">
                    Voeg eerst <a href="{{ route('admin.taxi.transport_passengers.create', [$customer->id, $contract->id]) }}" class="text-primary hover:underline">passagiers</a> toe aan het abonnement.
                </div>
                @else
                <div class="px-3 sm:px-5 py-4 border-b border-input text-sm text-muted-foreground">
                    Alle actieve passagiers zitten al in deze groep.
                </div>
                @endif
                @endcan

                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table id="transport-group-members-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="Naam">Naam</th>
                                <th data-label="Ophaaladres">Ophaaladres</th>
                                <th data-label="Sinds">Sinds</th>
                                @can('rides.update')
                                <th class="transport-group-members-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties"></th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeMembers as $member)
                            <tr>
                                <td class="font-medium">{{ $member->passenger?->full_name ?? '—' }}</td>
                                <td class="text-muted-foreground">{{ Str::limit($member->passenger?->pickup_address ?? '—', 50) }}</td>
                                <td class="text-muted-foreground">
                                    {{ $member->valid_from ? $member->valid_from->format('d-m-Y') : '—' }}
                                </td>
                                @can('rides.update')
                                <td class="transport-group-members-table__actions-col">
                                    <form method="POST" action="{{ route('admin.taxi.transport_groups.member_remove', [$customer->id, $contract->id, $group->id, $member->id]) }}" class="inline" onsubmit="return confirm('Passagier uit deze groep halen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-destructive" title="Uit groep halen" aria-label="Uit groep halen">
                                            <i class="ki-filled ki-trash"></i>
                                        </button>
                                    </form>
                                </td>
                                @endcan
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ auth()->user()->can('rides.update') ? 4 : 3 }}" class="text-center text-muted-foreground py-8">
                                    Nog geen leden in deze groep.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-2">
                <h3 class="kt-card-title mb-0">Route</h3>
                @can('rides.view')
                <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_groups.route.edit', [$customer->id, $contract->id, $group->id]), url()->full()) }}" class="kt-btn kt-btn-primary kt-btn-sm shrink-0">
                    <i class="ki-filled ki-route me-1"></i>
                    Routeplanner
                </a>
                @endcan
            </div>
            <div class="kt-card-content p-0 min-w-0">
                @if($routeTemplate && $routePickupStops->isNotEmpty())
                    @include('taxi::admin.transport_groups.partials.route-summary')
                @elseif($routeTemplate)
                    <div class="px-3 sm:px-5 pb-5 text-sm text-muted-foreground">
                        Route-instellingen staan klaar, maar er zijn nog geen stops berekend.
                        @can('rides.update')
                        Open de routeplanner en druk op <strong>Route berekenen</strong>.
                        @endcan
                    </div>
                @else
                    <div class="px-3 sm:px-5 pb-5 text-sm text-muted-foreground">
                        Nog geen route gepland.
                        @can('rides.update')
                        Open de routeplanner om weekdagen, stopvolgorde, tijden en vaste chauffeur in te stellen.
                        @endcan
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
    #content .transport-group-member-select {
        min-height: 8.5rem;
        height: auto;
    }

    #content .transport-group-member-select option {
        padding: 0.35rem 0.5rem;
    }

    #content #transport-group-members-table .transport-group-members-table__actions-col {
        width: 4.5rem !important;
        min-width: 4.5rem !important;
        max-width: 4.5rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }
</style>
@endpush
