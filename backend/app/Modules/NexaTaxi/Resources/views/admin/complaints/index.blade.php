@extends('admin.layouts.app')

@section('title', __('Klachten'))

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">{{ __('Klachten') }}</h1>
        <a href="{{ route('admin.taxi.complaints.blocked') }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-shield-cross me-2"></i>
            {{ __('Geblokkeerde chauffeurs') }}
        </a>
    </div>

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-header py-5 px-5 flex-wrap gap-2 min-w-0">
            <h3 class="kt-card-title text-sm pb-3 w-full mb-0">
                {{ __('Toon :from tot :to van :total klachten', [
                    'from' => $complaints->firstItem() ?? 0,
                    'to' => $complaints->lastItem() ?? 0,
                    'total' => $complaints->total(),
                ]) }}
            </h3>
            <form method="GET" action="{{ route('admin.taxi.complaints.index') }}" class="admin-filter-panel flex flex-col sm:flex-row flex-wrap gap-2.5 w-full sm:w-auto min-w-0 items-stretch sm:items-center">
                <label class="kt-input w-full sm:w-64 min-w-0">
                    <i class="ki-filled ki-magnifier"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Zoeken…') }}">
                </label>
                <select name="status" class="kt-select admin-field-fit" data-kt-select="true">
                    <option value="">{{ __('Alle statussen') }}</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ __($label) }}</option>
                    @endforeach
                </select>
                @if(auth()->user()->hasRole('super-admin') && $allCompanies->isNotEmpty())
                    <select name="company_id" class="kt-select admin-field-fit" data-kt-select="true">
                        <option value="">{{ __('Alle bedrijven') }}</option>
                        @foreach($allCompanies as $company)
                            <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                @endif
                <button type="submit" class="kt-btn kt-btn-outline w-full sm:w-auto shrink-0">{{ __('Filteren') }}</button>
            </form>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            <div class="complaints-table-wrap min-w-0">
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table id="complaints-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="{{ __('Datum') }}">{{ __('Datum') }}</th>
                                <th data-label="{{ __('Bedrijf') }}">{{ __('Bedrijf') }}</th>
                                <th data-label="{{ __('Chauffeur') }}">{{ __('Chauffeur') }}</th>
                                <th data-label="{{ __('Voertuig') }}">{{ __('Voertuig') }}</th>
                                <th data-label="{{ __('Categorie') }}">{{ __('Categorie') }}</th>
                                <th data-label="{{ __('Status') }}">{{ __('Status') }}</th>
                                <th class="complaints-table__actions-col text-secondary-foreground font-normal text-center" data-label="{{ __('Acties') }}" data-no-row-link>{{ __('Acties') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($complaints as $row)
                                @php
                                    $company = $companies->get($row->company_id);
                                    $driver = $drivers->get($row->driver_id);
                                    $vehicle = $vehicles->get($row->vehicle_id);
                                    $driverName = $driver ? trim(($driver->first_name ?? '').' '.($driver->last_name ?? '')) : ('#'.$row->driver_id);
                                @endphp
                                <tr class="cursor-pointer" onclick="window.location='{{ route('admin.taxi.complaints.show', $row->id) }}'">
                                    <td>{{ $row->created_at?->format('d-m-Y H:i') }}</td>
                                    <td>{{ $company?->name ?? '—' }}</td>
                                    <td>{{ $driverName !== '' ? $driverName : '—' }}</td>
                                    <td>{{ $vehicle?->fleetLabel() ?? ($vehicle?->name ?? '—') }}</td>
                                    <td>{{ __($categoryLabels[$row->category] ?? $row->category) }}</td>
                                    <td>{{ __($statusLabels[$row->status] ?? $row->status) }}</td>
                                    <td class="complaints-table__actions-col" data-no-row-link onclick="event.stopPropagation();">
                                        <div class="kt-menu" data-kt-menu="true">
                                            <button type="button" class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="{{ __('Acties') }}">
                                                <i class="ki-filled ki-dots-vertical"></i>
                                            </button>
                                            <div class="kt-menu-dropdown kt-menu-default w-[175px] min-w-[175px]">
                                                <div class="kt-menu-item">
                                                    <a href="{{ route('admin.taxi.complaints.show', $row->id) }}" class="kt-menu-link">
                                                        <span class="kt-menu-icon"><i class="ki-filled ki-eye"></i></span>
                                                        <span class="kt-menu-title">{{ __('Bekijken') }}</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-5 text-sm text-muted-foreground">{{ __('Geen klachten gevonden.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($complaints->hasPages())
                <div class="px-5 py-4">{{ $complaints->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
#content #complaints-table .complaints-table__actions-col {
    width: 4.5rem !important;
    min-width: 4.5rem !important;
    max-width: 4.5rem !important;
    padding-inline: 0.375rem !important;
    text-align: center !important;
    vertical-align: middle !important;
    white-space: nowrap;
    overflow: visible !important;
}
.complaints-table-wrap .kt-scrollable-x-auto { overflow-x: auto !important; overflow-y: visible !important; }
</style>
@endpush
