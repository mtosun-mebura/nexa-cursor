@extends('admin.layouts.app')

@section('title', 'Groepen')

@section('content')
@php
    $contractWorkspaceTab = 'groepen';
    $contractWorkspaceCounts = ['groups' => $groups->total()];
    $workspaceTitle = 'Groepen & routes';
    $workspaceSubtitle = 'Vaste heen- en terugritten voor dit abonnement';
@endphp
@can('rides.create')
    @php
        $workspaceHeaderActions = view('taxi::admin.transport_customers.partials.contract-workspace-action-btn', [
            'url' => transport_admin_url_with_return(route('admin.taxi.transport_groups.create', [$customer->id, $contract->id]), url()->current()),
            'label' => 'Nieuwe groep',
            'primary' => true,
        ])->render();
    @endphp
@endcan
<div class="kt-container-fixed min-w-0">
    @include('taxi::admin.transport_customers.partials.contract-workspace-header')
    @include('taxi::admin.transport_customers.partials.contract-workspace-styles')

    <div class="grid gap-5 lg:gap-7.5 pt-5">
        <div class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm w-full">Groepen onder dit abonnement</h3>
                <form method="GET" action="{{ route('admin.taxi.transport_groups.index', [$customer->id, $contract->id]) }}" class="flex flex-wrap gap-2 w-full sm:justify-end">
                    <label class="kt-input w-full sm:w-64">
                        <i class="ki-filled ki-magnifier"></i>
                        <input placeholder="Zoek groep of adres..." type="text" name="search" value="{{ request('search') }}">
                    </label>
                    <select class="kt-select w-full sm:w-36" name="active" onchange="this.form.submit()">
                        <option value="">Alle statussen</option>
                        <option value="1" @selected(request('active') === '1')>Actief</option>
                        <option value="0" @selected(request('active') === '0')>Inactief</option>
                    </select>
                    <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm">Zoeken</button>
                </form>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table id="transport-groups-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="Naam">Naam</th>
                                <th data-label="Eindlocatie">Eindlocatie</th>
                                <th data-label="Aankomst">Aankomst</th>
                                <th data-label="Leden">Leden</th>
                                <th data-label="Status">Status</th>
                                <th class="transport-groups-table__actions-col text-center" data-label="Acties"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($groups as $group)
                            <tr
                                data-row-href="{{ transport_admin_url_with_return(route('admin.taxi.transport_groups.show', [$customer->id, $contract->id, $group->id]), url()->current()) }}"
                                class="cursor-pointer hover:bg-muted/40"
                                tabindex="0"
                                role="link"
                                aria-label="Bekijk groep {{ $group->name }}"
                            >
                                <td>
                                    <span class="font-medium text-foreground">{{ $group->name }}</span>
                                </td>
                                <td class="text-muted-foreground">{{ Str::limit($group->destination_address, 45) }}</td>
                                <td class="text-muted-foreground">{{ substr($group->destination_arrival_time, 0, 5) }}</td>
                                <td>{{ $memberCounts[$group->id] ?? 0 }}</td>
                                <td>
                                    @if($group->active)
                                        <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                    @else
                                        <span class="kt-badge kt-badge-secondary kt-badge-sm">Inactief</span>
                                    @endif
                                </td>
                                <td class="transport-groups-table__actions-col" data-no-row-link onclick="event.stopPropagation();">
                                    <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_groups.show', [$customer->id, $contract->id, $group->id]), url()->current()) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Bekijken" aria-label="Bekijken">
                                        <i class="ki-filled ki-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted-foreground py-8">
                                    Nog geen groepen.
                                    @can('rides.create')
                                    <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_groups.create', [$customer->id, $contract->id]), url()->current()) }}" class="text-primary hover:underline">Eerste groep aanmaken</a>.
                                    @endcan
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($groups->hasPages())
                <div class="px-3 sm:px-5 py-4 border-t border-input">{{ $groups->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #content .transport-groups-table__actions-col {
        width: 4.5rem !important;
        min-width: 4.5rem !important;
        max-width: 4.5rem !important;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#transport-groups-table tr[data-row-href]').forEach(function(row) {
        row.addEventListener('click', function(event) {
            if (event.target.closest('[data-no-row-link]')) {
                return;
            }
            window.location.href = row.getAttribute('data-row-href');
        });

        row.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                if (event.target.closest('[data-no-row-link]')) {
                    return;
                }
                event.preventDefault();
                window.location.href = row.getAttribute('data-row-href');
            }
        });
    });
});
</script>
@endpush
