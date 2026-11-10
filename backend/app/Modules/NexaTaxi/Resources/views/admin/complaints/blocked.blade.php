@extends('admin.layouts.app')

@section('title', __('Geblokkeerde chauffeurs'))

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">{{ __('Geblokkeerde chauffeurs') }}</h1>
        <a href="{{ route('admin.taxi.complaints.index') }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            {{ __('Terug naar klachten') }}
        </a>
    </div>

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-header py-5 px-5 flex-wrap gap-2 min-w-0">
            <h3 class="kt-card-title text-sm pb-3 w-full mb-0">{{ __('Geblokkeerde taxi’s / chauffeurs') }}</h3>
            @if(auth()->user()->hasRole('super-admin') && ($allCompanies ?? collect())->isNotEmpty())
                <form method="GET" action="{{ route('admin.taxi.complaints.blocked') }}" class="admin-filter-panel flex flex-col sm:flex-row flex-wrap gap-2.5 w-full sm:w-auto min-w-0 items-stretch sm:items-center">
                    <select name="company_id" class="kt-select admin-field-fit" data-kt-select="true">
                        <option value="">{{ __('Alle bedrijven') }}</option>
                        @foreach($allCompanies as $company)
                            <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="kt-btn kt-btn-outline w-full sm:w-auto shrink-0">{{ __('Filteren') }}</button>
                </form>
            @endif
        </div>
        <div class="kt-card-content p-0 min-w-0">
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                    <thead>
                        <tr>
                            <th data-label="{{ __('Chauffeur') }}">{{ __('Chauffeur') }}</th>
                            <th data-label="{{ __('Bedrijf') }}">{{ __('Bedrijf') }}</th>
                            <th data-label="{{ __('Voertuig') }}">{{ __('Voertuig') }}</th>
                            <th data-label="{{ __('Geblokkeerd') }}">{{ __('Geblokkeerd') }}</th>
                            <th data-label="{{ __('Reden') }}">{{ __('Reden') }}</th>
                            <th data-label="{{ __('Acties') }}">{{ __('Acties') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>
                                    {{ $item['driver_name'] ?: ('#'.$item['driver_id']) }}
                                    <div class="text-xs text-muted-foreground">{{ $item['driver_email'] }}</div>
                                </td>
                                <td>{{ $item['company_name'] ?: '—' }}</td>
                                <td>{{ $item['vehicle_name'] ?: '—' }}</td>
                                <td>{{ $item['blocked_at'] ? \Illuminate\Support\Carbon::parse($item['blocked_at'])->format('d-m-Y H:i') : '—' }}</td>
                                <td>{{ $item['reason'] ?: '—' }}</td>
                                <td>
                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('complaints.manage'))
                                        <form method="POST" action="{{ route('admin.taxi.complaints.unblock', $item['driver_id']) }}" onsubmit="return confirm(@json(__('Chauffeur deblokkeren?')));">
                                            @csrf
                                            <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">{{ __('Deblokkeren') }}</button>
                                        </form>
                                    @endif
                                    @if(!empty($item['complaint_id']))
                                        <a href="{{ route('admin.taxi.complaints.show', $item['complaint_id']) }}" class="kt-btn kt-btn-sm kt-btn-ghost">{{ __('Klacht') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-5 text-sm text-muted-foreground">{{ __('Geen geblokkeerde chauffeurs.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
