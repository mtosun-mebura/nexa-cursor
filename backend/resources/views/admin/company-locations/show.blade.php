@extends('admin.layouts.app')

@section('title', 'Vestiging Details')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Vestiging Details
            </h1>
        </div>
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.companies.show', $company) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
            @can('edit-companies')
            <a href="{{ route('admin.companies.locations.edit', [$company, $location]) }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-pencil me-2"></i>
                Bewerken
            </a>
            @endcan
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <!-- Vestiging Informatie -->
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Vestiging Informatie
                </h3>
                <div class="flex items-center gap-2">
                    @if($location->is_main)
                        <span class="kt-badge kt-badge-success">Hoofdkantoor</span>
                    @endif
                    @if($location->is_active)
                        <span class="kt-badge kt-badge-success">Actief</span>
                    @else
                        <span class="kt-badge kt-badge-danger">Inactief</span>
                    @endif
                </div>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">
                            Naam
                        </td>
                        <td class="min-w-48 w-full text-foreground font-normal">
                            {{ $location->name }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Straat
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $location->street ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Huisnummer
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $location->house_number }}{{ $location->house_number_extension ? '-' . $location->house_number_extension : '' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Postcode
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $location->postal_code ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Plaats
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $location->city ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Land
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $location->country ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Telefoon
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $location->phone ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            E-mail
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $location->email ?? '-' }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Remove all borders between table rows in show forms */
    .kt-table-border-dashed tbody tr {
        border-bottom: none !important;
    }
    /* Uniform row height for all table rows */
    .kt-table-border-dashed tbody tr,
    .kt-table-border-dashed tbody tr td {
        height: auto;
        min-height: 48px;
    }
    .kt-table-border-dashed tbody tr td {
        padding-top: 12px;
        padding-bottom: 12px;
        vertical-align: middle;
    }
</style>
@endpush

@endsection

