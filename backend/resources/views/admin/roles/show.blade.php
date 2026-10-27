@extends('admin.layouts.app')

@section('title', __('Rol Details - :name', ['name' => ucfirst(str_replace('-', ' ', $role->name))]))

@push('styles')
<style>
    /* Danger button styles */
    .kt-btn-danger {
        background-color: #ef4444 !important;
        color: white !important;
    }
    .kt-btn-danger:hover {
        background-color: #dc2626 !important;
    }
    .dark .kt-btn-danger {
        background-color: #dc2626 !important;
    }
    .dark .kt-btn-danger:hover {
        background-color: #b91c1c !important;
    }

    .role-assigned-permissions.role-permissions-scroll.kt-card-table {
        overflow-x: auto !important;
        overflow-y: visible !important;
        max-width: 100%;
    }

    .role-assigned-permissions .role-permissions-matrix {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100%;
        table-layout: fixed;
    }

    .role-assigned-permissions .role-permissions-matrix th,
    .role-assigned-permissions .role-permissions-matrix td {
        min-width: 0 !important;
    }

    .role-assigned-permissions .role-permissions-matrix th:first-child,
    .role-assigned-permissions .role-permissions-matrix td.role-permission-resource-name {
        width: 22%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .role-assigned-permissions .role-permissions-matrix th:not(:first-child),
    .role-assigned-permissions .role-permissions-matrix td.role-permission-action-cell {
        white-space: nowrap;
        padding-inline: 0.25rem;
        text-align: center;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    @media (max-width: 1023px) {
        .role-assigned-permissions.role-permissions-scroll.kt-card-table {
            overflow: visible !important;
            padding: 0.75rem 1.25rem 1.25rem;
            border: none;
        }

        .role-assigned-permissions .role-permissions-matrix {
            display: block;
            width: 100% !important;
            min-width: 0 !important;
            table-layout: auto;
        }

        .role-assigned-permissions .role-permissions-matrix thead {
            display: none;
        }

        .role-assigned-permissions .role-permissions-matrix tbody {
            display: block;
        }

        .role-assigned-permissions .role-permissions-matrix tr {
            display: block;
            width: 100%;
        }

        .role-assigned-permissions .role-permissions-matrix tr.role-permission-module-row {
            margin-top: 0.75rem;
            margin-bottom: 0.375rem;
            border-radius: 0.5rem;
        }

        .role-assigned-permissions .role-permissions-matrix tr.role-permission-module-row:first-child {
            margin-top: 0;
        }

        .role-assigned-permissions .role-permissions-matrix tr.role-permission-module-row td {
            display: block;
            width: 100% !important;
            padding: 0.5rem 0.75rem !important;
            border: none !important;
            white-space: normal;
            overflow: visible;
        }

        .role-assigned-permissions .role-permissions-matrix tr.role-permission-resource-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.375rem 0.5rem;
            margin-bottom: 0.75rem;
            padding: 0.75rem 0.875rem;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            background: var(--card, var(--background));
        }

        .role-assigned-permissions .role-permissions-matrix tr.role-permission-resource-row td {
            border: none !important;
            overflow: visible !important;
            text-overflow: unset;
        }

        .role-assigned-permissions .role-permissions-matrix tr.role-permission-resource-row td.role-permission-resource-name {
            display: block;
            flex: 1 0 100%;
            width: 100% !important;
            padding: 0 0 0.25rem !important;
            white-space: normal;
            text-align: left;
        }

        .role-assigned-permissions .role-permissions-matrix tr.role-permission-resource-row td.role-permission-action-cell:not(:has(svg)) {
            display: none !important;
        }

        .role-assigned-permissions .role-permissions-matrix tr.role-permission-resource-row td.role-permission-action-cell:has(svg) {
            display: inline-flex !important;
            align-items: center;
            gap: 0.25rem;
            width: auto !important;
            padding: 0.25rem 0.625rem !important;
            border-radius: 9999px;
            background: color-mix(in oklab, var(--primary) 14%, transparent);
            white-space: nowrap;
        }

        .role-assigned-permissions .role-permissions-matrix tr.role-permission-resource-row td.role-permission-action-cell:has(svg)::before {
            content: attr(data-permission-action);
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--foreground);
        }

        .role-assigned-permissions .role-permissions-matrix tr.role-permission-resource-row td.role-permission-action-cell svg {
            width: 0.875rem;
            height: 0.875rem;
            margin: 0 !important;
            flex: 0 0 auto;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleCheckbox = document.getElementById('toggle-status-checkbox');
    const toggleForm = document.getElementById('toggle-status-form');
    
    if (toggleCheckbox && toggleForm) {
        toggleCheckbox.addEventListener('change', function() {
            toggleForm.submit();
        });
    }
});
</script>
@endpush

@section('content')

<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
</style>

@php
    $permissionMatrix = app(\App\Support\RolePermissionMatrix::class)->build($role->permissions);
    $visiblePermissionCount = $permissionMatrix['count'];
@endphp

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            @php
                $isActive = $role->is_active ?? true;
            @endphp
            <div class="rounded-full border-3 {{ $isActive ? 'border-green-500' : 'border-red-500' }} size-[100px] shrink-0 bg-primary/10 flex items-center justify-center">
                <i class="ki-filled ki-profile-circle text-4xl text-primary"></i>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="text-lg leading-5 font-semibold text-mono">
                    {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                </div>
                @if(in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                    <span class="kt-badge kt-badge-sm kt-badge-warning">{{ __('Systeem') }}</span>
                @else
                    <span class="kt-badge kt-badge-sm kt-badge-success">{{ __('Aangepast') }}</span>
                @endif
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-key text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">
                        {{ $visiblePermissionCount }} {{ __('rechten') }}
                    </span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-people text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">
                        {{ $role->users->count() }} {{ __('gebruikers') }}
                    </span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-calendar text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">
                        {{ $role->created_at->format('d-m-Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</div>

<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.roles.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                {{ __('Terug') }}
            </a>
        </div>
        <div class="flex items-center gap-2.5">
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-roles'))
            @if(!in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
            <form action="{{ route('admin.roles.toggle-status', $role) }}" method="POST" id="toggle-status-form" class="inline">
                @csrf
                <label class="kt-label flex items-center">
                    @php
                        $isActive = $role->is_active ?? true;
                    @endphp
                    <input type="checkbox" 
                           class="kt-switch kt-switch-sm" 
                           id="toggle-status-checkbox"
                           {{ $isActive ? 'checked' : '' }}/>
                    <span class="ms-2">{{ __('Actief') }}</span>
                </label>
            </form>
            @else
            <label class="kt-label flex items-center">
                <input type="checkbox" 
                       class="kt-switch kt-switch-sm" 
                       checked 
                       disabled/>
                <span class="ms-2">{{ __('Actief') }}</span>
            </label>
            @endif
            <span class="text-orange-500">|</span>
            <a href="{{ route('admin.roles.edit', $role) }}" class="kt-btn kt-btn-warning">
                <i class="ki-filled ki-pencil me-2"></i>
                {{ __('Bewerken') }}
            </a>
            @endif
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-roles'))
            @if(!in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
            <form action="{{ route('admin.roles.destroy', $role) }}" 
                  method="POST" 
                  class="inline"
                  onsubmit="return confirm(@js(__('Weet je zeker dat je deze rol wilt verwijderen?')))">
                @csrf
                @method('DELETE')
                <button type="submit" class="kt-btn kt-btn-danger">
                    <i class="ki-filled ki-trash me-2"></i>
                    {{ __('Verwijderen') }}
                </button>
            </form>
            @endif
            @endif
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <!-- Basic Information -->
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('Basis Informatie') }}</h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Rol Naam') }}</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground font-medium">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Type') }}</td>
                        <td class="min-w-48 w-full">
                            @if(in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                <span class="kt-badge kt-badge-warning">{{ __('Systeem') }}</span>
                            @else
                                <span class="kt-badge kt-badge-success">{{ __('Aangepast') }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Status') }}</td>
                        <td class="min-w-48 w-full">
                            @php
                                $isActive = $role->is_active ?? true;
                            @endphp
                            @if($isActive)
                                <span class="kt-badge kt-badge-sm kt-badge-success">{{ __('Actief') }}</span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-danger">{{ __('Inactief') }}</span>
                            @endif
                        </td>
                    </tr>
                    @if($role->description)
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">{{ __('Beschrijving') }}</td>
                        <td>
                            <span class="text-foreground">{{ $role->description }}</span>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Aantal Rechten') }}</td>
                        <td class="min-w-48 w-full">
                            <span class="kt-badge kt-badge-info">{{ $visiblePermissionCount }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Aantal Gebruikers') }}</td>
                        <td class="min-w-48 w-full">
                            <span class="kt-badge kt-badge-secondary">{{ $role->users->count() }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Aangemaakt') }}</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground">{{ $role->created_at->format('d-m-Y H:i') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Laatst bijgewerkt') }}</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground">{{ $role->updated_at->format('d-m-Y H:i') }}</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Permissions -->
        <div class="kt-card min-w-full">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title">{{ __('Toegewezen Rechten (:count)', ['count' => $visiblePermissionCount]) }}</h3>
            </div>
            <div class="role-assigned-permissions role-permissions-scroll admin-table-scroll-wrap kt-card-table kt-scrollable-x-auto pb-3 min-w-0">
                @if($visiblePermissionCount > 0)
                    <table class="kt-table kt-table-border align-middle text-sm w-full admin-keep-table-layout role-permissions-matrix">
                        <thead>
                            <tr>
                                <th class="text-left text-secondary-foreground font-normal">{{ __('Module / Resource') }}</th>
                                @foreach($permissionMatrix['allActions'] as $action)
                                    <th class="text-center text-secondary-foreground font-normal">
                                        {{ __($permissionMatrix['actionNames'][$action] ?? ucfirst(str_replace('_', ' ', $action))) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permissionMatrix['sortedModuleOrder'] as $mainModule)
                                @if(isset($permissionMatrix['permissionsByMainModule'][$mainModule]))
                                    @php
                                        $isActiveModule = false;
                                        $headerDisplayName = $permissionMatrix['moduleNames'][$mainModule]
                                            ?? ucfirst(str_replace(['-', '_'], ' ', $mainModule));
                                        foreach ($permissionMatrix['modulePermissions'] as $modDisplayName => $modData) {
                                            if (($modData['module'] ?? null) === $mainModule) {
                                                $headerDisplayName = $modDisplayName;
                                                $isActiveModule = true;
                                                break;
                                            }
                                        }
                                        if (! $isActiveModule && $mainModule === 'other') {
                                            $headerDisplayName = 'Algemeen';
                                        }
                                    @endphp
                                    <tr class="role-permission-module-row bg-muted/30">
                                        <td colspan="{{ count($permissionMatrix['allActions']) + 1 }}" class="py-2 px-4">
                                            @if($isActiveModule)
                                                <span class="font-semibold text-foreground text-sm flex items-center gap-1">
                                                    <x-heroicon-s-puzzle-piece class="w-4 h-4 text-primary flex-shrink-0" />
                                                    {{ __($headerDisplayName) }}
                                                </span>
                                            @else
                                                <span class="font-semibold text-foreground text-sm">
                                                    {{ __($headerDisplayName) }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @foreach($permissionMatrix['permissionsByMainModule'][$mainModule] as $moduleGroup)
                                        @php
                                            $moduleKey = $moduleGroup['key'];
                                            $resource = $moduleGroup['resource'];
                                        @endphp
                                        <tr class="role-permission-resource-row">
                                            <td class="text-foreground pl-6 role-permission-resource-name">
                                                <span class="font-medium text-sm">
                                                    {{ __($permissionMatrix['moduleNames'][$moduleKey] ?? ucfirst(str_replace(['-', '_'], ' ', $resource ?? $moduleKey))) }}
                                                </span>
                                            </td>
                                            @foreach($permissionMatrix['allActions'] as $action)
                                                <td class="text-center role-permission-action-cell"@if(isset($permissionMatrix['permissionMap'][$moduleKey][$action])) data-permission-action="{{ __($permissionMatrix['actionNames'][$action] ?? ucfirst(str_replace('_', ' ', $action))) }}"@endif>
                                                    @if(isset($permissionMatrix['permissionMap'][$moduleKey][$action]))
                                                        <x-heroicon-s-check class="w-5 h-5 text-blue-500 mx-auto" />
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="p-5">
                        <p class="text-muted-foreground mb-0">{{ __('Geen rechten toegewezen aan deze rol.') }}</p>
                    </div>
                @endif
            </div>
        </div>

        @include('admin.roles.partials.dispatch-permissions', ['dispatchEditable' => false])

        <!-- Users with this role -->
        @php
            $roleUsers = $role->users;
            $roleUserCompanies = $roleUsers->pluck('company')->filter()->unique('id')->sortBy('name')->values();
        @endphp
        <div class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header px-5 py-5 flex-wrap gap-2 min-w-0">
                <h3 class="kt-card-title text-sm pb-3 w-full mb-0">{{ __('Gebruikers met deze rol (:count)', ['count' => $roleUsers->count()]) }}</h3>
                @if($roleUsers->count() > 0)
                <div class="admin-filter-panel flex flex-col sm:flex-row flex-wrap gap-2.5 w-full sm:w-auto min-w-0 items-stretch sm:items-center"
                     data-admin-live-filter="off">
                    <label class="kt-input w-full sm:w-64 min-w-0">
                        <i class="ki-filled ki-magnifier"></i>
                        <input placeholder="{{ __('Zoek gebruiker of e-mail...') }}"
                               type="text"
                               name="search"
                               id="role-users-search"
                               value=""
                               autocomplete="off"
                               data-admin-datatable-search="#role_users_table">
                    </label>
                    @if($roleUserCompanies->count() > 0)
                    <select class="kt-select w-full sm:w-44" name="company" data-admin-datatable-filter="company" aria-label="{{ __('Bedrijf') }}">
                        <option value="">{{ __('Alle bedrijven') }}</option>
                        @foreach($roleUserCompanies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                        @if($roleUsers->contains(fn ($roleUser) => $roleUser->company === null))
                            <option value="none">{{ __('Geen bedrijf') }}</option>
                        @endif
                    </select>
                    @endif
                    <button type="button"
                            data-admin-datatable-reset
                            class="kt-btn kt-btn-outline kt-btn-icon shrink-0 hidden"
                            title="{{ __('Filters resetten') }}">
                        <i class="ki-filled ki-arrows-circle text-base"></i>
                    </button>
                </div>
                @endif
            </div>
            <div class="kt-card-content p-0 min-w-0">
                @if($roleUsers->count() > 0)
                <div class="grid w-full min-w-0" data-admin-datatable="true" data-admin-datatable-page-size="10" id="role_users_table" data-admin-datatable-label="{{ __('gebruikers') }}">
                    <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                        <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                            <thead>
                                <tr>
                                    <th class="text-secondary-foreground font-normal" data-label="{{ __('Gebruiker') }}">{{ __('Gebruiker') }}</th>
                                    <th class="text-secondary-foreground font-normal" data-label="{{ __('Email') }}">{{ __('Email') }}</th>
                                    <th class="text-secondary-foreground font-normal" data-label="{{ __('Bedrijf') }}">{{ __('Bedrijf') }}</th>
                                    <th class="text-secondary-foreground font-normal text-center" data-label="{{ __('Acties') }}">{{ __('Acties') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roleUsers as $user)
                                <tr data-company="{{ $user->company ? $user->company->id : 'none' }}"
                                    data-search-text="{{ trim($user->first_name.' '.$user->last_name.' '.$user->email.' '.($user->company->name ?? '')) }}">
                                    <td>
                                        <div class="flex items-center gap-2.5">
                                            @if($user->photo_blob)
                                                <img alt="{{ $user->first_name }} {{ $user->last_name }}" class="rounded-full size-9 shrink-0" src="{{ route('secure.photo', ['token' => $user->getPhotoToken()]) }}"/>
                                            @else
                                                <div class="rounded-full size-9 shrink-0 bg-accent/60 border border-input flex items-center justify-center">
                                                    <span class="text-xs font-semibold text-secondary-foreground">
                                                        {{ strtoupper(substr($user->first_name ?? 'U', 0, 1) . substr($user->last_name ?? '', 0, 1)) }}
                                                    </span>
                                                </div>
                                            @endif
                                            <div class="flex flex-col">
                                                <a class="text-sm font-medium text-mono hover:text-primary" href="{{ route('admin.users.show', $user) }}">
                                                    {{ $user->first_name }} {{ $user->last_name }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a class="text-sm text-secondary-foreground hover:text-primary" href="mailto:{{ $user->email }}">
                                            {{ $user->email }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($user->company)
                                            <span class="kt-badge kt-badge-info">{{ $user->company->name }}</span>
                                        @else
                                            <span class="text-sm text-muted-foreground">{{ __('Geen bedrijf') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('admin.users.show', $user) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="{{ __('Bekijken') }}">
                                                <i class="ki-filled ki-eye"></i>
                                            </a>
                                            @can('edit-users')
                                            <a href="{{ route('admin.users.edit', $user) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="{{ __('Bewerken') }}">
                                                <i class="ki-filled ki-pencil"></i>
                                            </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="kt-card-footer admin-datatable-footer text-secondary-foreground text-sm font-medium pt-5 min-w-0">
                        <div class="admin-datatable-footer__perpage flex flex-wrap items-center gap-2">
                            {{ __('Toon') }}
                            <select class="kt-select w-24" data-admin-datatable-size="true" data-kt-select="" name="perpage">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            {{ __('per pagina') }}
                        </div>
                        <div class="admin-datatable-footer__pagination max-w-full overflow-x-auto">
                            <div class="kt-datatable-pagination" data-admin-datatable-pagination="true"></div>
                        </div>
                        <span class="admin-datatable-footer__info" data-admin-datatable-info="true"></span>
                    </div>
                </div>
                @else
                    <div class="p-5">
                        <p class="text-sm text-muted-foreground mb-0">{{ __('Geen gebruikers met deze rol.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
