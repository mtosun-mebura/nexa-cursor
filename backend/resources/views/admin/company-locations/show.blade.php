@extends('admin.layouts.app')

@section('title', __('Vestiging Details'))

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                {{ __('Vestiging Details') }}
            </h1>
        </div>
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.companies.show', $company) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                {{ __('Terug') }}
            </a>
            @can('edit-companies')
            <a href="{{ route('admin.companies.locations.edit', [$company, $location]) }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-pencil me-2"></i>
                {{ __('Bewerken') }}
            </a>
            @endcan
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <!-- Vestiging Informatie -->
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    {{ __('Vestiging Informatie') }}
                </h3>
                <div class="flex items-center gap-2">
                    @if($location->is_main)
                        <span class="kt-badge kt-badge-success">{{ __('Hoofdkantoor') }}</span>
                    @endif
                    @if($location->is_active)
                        <span class="kt-badge kt-badge-success">{{ __('Actief') }}</span>
                    @else
                        <span class="kt-badge kt-badge-sm kt-badge-danger">{{ __('Inactief') }}</span>
                    @endif
                </div>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">
                            {{ __('Naam') }}
                        </td>
                        <td class="min-w-48 w-full text-foreground font-normal">
                            {{ $location->name }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            {{ __('Straat') }}
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $location->street ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            {{ __('Huisnummer') }}
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $location->house_number }}{{ $location->house_number_extension ? '-' . $location->house_number_extension : '' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            {{ __('Postcode') }}
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $location->postal_code ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            {{ __('Plaats') }}
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $location->city ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            {{ __('Land') }}
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $location->country ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            {{ __('Telefoon') }}
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $location->phone ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            {{ __('E-mail') }}
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

@endsection

