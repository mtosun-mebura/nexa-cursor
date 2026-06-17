@extends('admin.layouts.app')

@section('title', 'Passagiers')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">Passagiers</h1>
            <p class="text-sm text-muted-foreground pt-2">{{ $contract->name }} · {{ $customer->name }}</p>
            <div class="pt-3">
                <a href="{{ $backUrl }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug
                </a>
            </div>
        </div>
        @can('rides.create')
        <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_passengers.create', [$customer->id, $contract->id]), url()->current()) }}" class="kt-btn kt-btn-primary shrink-0">
            <svg class="w-4 h-4 me-2 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Nieuwe passagier
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    <div class="kt-card w-full min-w-0 mb-5">
        <div class="kt-card-content">
            <div class="flex flex-col sm:flex-row lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl leading-none font-semibold">{{ $activeCount ?? 0 }}</span>
                    <span class="text-secondary-foreground text-sm">Actief</span>
                </div>
                <span class="hidden sm:block border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl leading-none font-semibold">{{ $passengers->total() }}</span>
                    <span class="text-secondary-foreground text-sm">Totaal</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                @php
                    $from = $passengers->isEmpty() ? 0 : (($passengers->currentPage() - 1) * $passengers->perPage()) + 1;
                    $to = min($passengers->currentPage() * $passengers->perPage(), $passengers->total());
                @endphp
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon {{ $from }} tot {{ $to }} van {{ $passengers->total() }} passagiers
                </h3>
                <div class="flex flex-col sm:flex-row flex-wrap gap-2 lg:gap-5 justify-center sm:justify-end items-center w-full">
                    <form method="GET" action="{{ route('admin.taxi.transport_passengers.index', [$customer->id, $contract->id]) }}" class="flex gap-2">
                        @if(request('active') !== null && request('active') !== '')<input type="hidden" name="active" value="{{ request('active') }}">@endif
                        <label class="kt-input w-full sm:w-64">
                            <i class="ki-filled ki-magnifier"></i>
                            <input placeholder="Zoek naam of adres..." type="text" name="search" value="{{ request('search') }}">
                        </label>
                        <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm">Zoeken</button>
                    </form>
                    <form method="GET" action="{{ route('admin.taxi.transport_passengers.index', [$customer->id, $contract->id]) }}" class="flex gap-2">
                        @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                        <select class="kt-select w-full sm:w-36" name="active" onchange="this.form.submit()">
                            <option value="">Alle statussen</option>
                            <option value="1" @selected(request('active') === '1')>Actief</option>
                            <option value="0" @selected(request('active') === '0')>Inactief</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table id="transport-passengers-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="Naam">Naam</th>
                                <th data-label="Telefoon">Telefoon</th>
                                <th data-label="Ophaaladres">Ophaaladres</th>
                                <th data-label="Status">Status</th>
                                <th class="transport-passengers-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($passengers as $passenger)
                            <tr>
                                <td>
                                    <span class="font-medium text-foreground">{{ $passenger->full_name }}</span>
                                </td>
                                <td class="text-muted-foreground">{{ $passenger->phone ?: '—' }}</td>
                                <td class="text-muted-foreground">{{ Str::limit($passenger->pickup_address, 60) }}</td>
                                <td>
                                    @if($passenger->active)
                                        <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                    @else
                                        <span class="kt-badge kt-badge-secondary kt-badge-sm">Inactief</span>
                                    @endif
                                </td>
                                <td class="transport-passengers-table__actions-col">
                                    <div class="flex items-center justify-center gap-1">
                                        @can('rides.update')
                                        <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_passengers.edit', [$customer->id, $contract->id, $passenger->id]), url()->current()) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Bewerken" aria-label="Bewerken">
                                            <i class="ki-filled ki-notepad-edit"></i>
                                        </a>
                                        @endcan
                                        @can('rides.delete')
                                        @if($passenger->active)
                                        <form method="POST" action="{{ route('admin.taxi.transport_passengers.destroy', [$customer->id, $contract->id, $passenger->id]) }}" class="inline" onsubmit="return confirm('Passagier deactiveren? Historie blijft bewaard.');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="return" value="{{ $backUrl }}">
                                            <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-destructive" title="Deactiveren" aria-label="Deactiveren">
                                                <i class="ki-filled ki-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted-foreground py-8">
                                    Nog geen passagiers.
                                    @can('rides.create')
                                    <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_passengers.create', [$customer->id, $contract->id]), url()->current()) }}" class="text-primary hover:underline">Eerste passagier aanmaken</a>.
                                    @endcan
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($passengers->hasPages())
                <div class="px-3 sm:px-5 py-4 border-t border-input">
                    {{ $passengers->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #content #transport-passengers-table .transport-passengers-table__actions-col {
        width: 5.5rem !important;
        min-width: 5.5rem !important;
        max-width: 5.5rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }
</style>
@endpush
