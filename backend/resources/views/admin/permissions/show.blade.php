@extends('admin.layouts.app')

@section('title', 'Permissie Details - ' . ucfirst(str_replace('-', ' ', $permission->name)))

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
</style>
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

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            <div class="rounded-full border-3 border-primary size-[100px] shrink-0 bg-primary/10 flex items-center justify-center">
                <i class="ki-filled ki-key text-4xl text-primary"></i>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="text-lg leading-5 font-semibold text-mono">
                    {{ ucfirst(str_replace('-', ' ', $permission->name)) }}
                </div>
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-shield-user text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">
                        {{ $permission->roles->count() }} rollen
                    </span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-people text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">
                        {{ $permission->users->count() }} gebruikers
                    </span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-calendar text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">
                        {{ $permission->created_at->format('d-m-Y') }}
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
            <a href="{{ route('admin.permissions.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        <div class="flex items-center gap-2.5">
            @php
                $hasRoles = $permission->roles->count() > 0;
            @endphp
            @if($hasRoles)
                <label class="kt-label flex items-center">
                    <input type="checkbox" 
                           class="kt-switch kt-switch-sm" 
                           checked 
                           disabled/>
                    <span class="ms-2">Actief</span>
                </label>
            @else
                <label class="kt-label flex items-center">
                    <input type="checkbox" 
                           class="kt-switch kt-switch-sm" 
                           disabled/>
                    <span class="ms-2">Actief</span>
                </label>
            @endif
            <span class="text-orange-500">|</span>
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-permissions'))
                <a href="{{ route('admin.permissions.edit', $permission) }}" class="kt-btn kt-btn-warning">
                    <i class="ki-filled ki-pencil me-2"></i>
                    Bewerken
                </a>
            @endif
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-permissions'))
                @if(!$hasRoles)
                    <form action="{{ route('admin.permissions.destroy', $permission) }}" 
                          method="POST" 
                          class="inline"
                          onsubmit="return confirm('Weet je zeker dat je deze permissie wilt verwijderen?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="kt-btn kt-btn-danger">
                            <i class="ki-filled ki-trash me-2"></i>
                            Verwijderen
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
                <h3 class="kt-card-title">Basis Informatie</h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Permissie Naam</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground font-medium">{{ ucfirst(str_replace('-', ' ', $permission->name)) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Naam (technisch)</td>
                        <td class="min-w-48 w-full">
                            <code class="text-sm text-foreground">{{ $permission->name }}</code>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Groep</td>
                        <td class="min-w-48 w-full">
                            @if($permission->group)
                                <span class="kt-badge kt-badge-primary">{{ $permission->group }}</span>
                            @else
                                <span class="text-muted-foreground">Geen groep</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Guard</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground">{{ $permission->guard_name }}</span>
                        </td>
                    </tr>
                    @if($permission->description)
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">Beschrijving</td>
                        <td>
                            <span class="text-foreground">{{ $permission->description }}</span>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Status</td>
                        <td class="min-w-48 w-full">
                            @if($hasRoles)
                                <span class="kt-badge kt-badge-sm kt-badge-success">Toegewezen aan rollen</span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-warning">Niet toegewezen</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Aantal Rollen</td>
                        <td class="min-w-48 w-full">
                            <span class="kt-badge kt-badge-info">{{ $permission->roles->count() }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Aantal Gebruikers</td>
                        <td class="min-w-48 w-full">
                            <span class="kt-badge kt-badge-secondary">{{ $permission->users->count() }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Aangemaakt</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground">{{ $permission->created_at->format('d-m-Y H:i') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Laatst bijgewerkt</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground">{{ $permission->updated_at->format('d-m-Y H:i') }}</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Roles with this Permission -->
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Rollen met deze Permissie ({{ $permission->roles->count() }})</h3>
            </div>
            <div class="kt-card-content">
                @if($permission->roles->count() > 0)
                    <div class="kt-scrollable-x-auto">
                        <table class="kt-table kt-table-border align-middle">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]">Rol</th>
                                    <th class="min-w-[200px]">Beschrijving</th>
                                    <th class="min-w-[150px]">Type</th>
                                    <th class="min-w-[100px]">Aantal Gebruikers</th>
                                    <th class="min-w-[100px]">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permission->roles as $role)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-2.5">
                                            <i class="ki-filled ki-shield-user text-primary"></i>
                                            <a class="text-sm font-medium text-mono hover:text-primary" href="{{ route('admin.roles.show', $role) }}">
                                                {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-sm text-secondary-foreground">{{ $role->description ?? '-' }}</span>
                                    </td>
                                    <td>
                                        @if(in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                            <span class="kt-badge kt-badge-sm kt-badge-warning">Systeem</span>
                                        @else
                                            <span class="kt-badge kt-badge-sm kt-badge-success">Aangepast</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="kt-badge kt-badge-sm kt-badge-secondary">{{ $role->users->count() }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.roles.show', $role) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Bekijken">
                                            <i class="ki-filled ki-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-5">
                        <p class="text-muted-foreground">Deze permissie is niet toegewezen aan rollen.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Users with this Permission -->
        @if($permission->users->count() > 0)
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Gebruikers met deze Permissie ({{ $permission->users->count() }})</h3>
            </div>
            <div class="kt-card-content">
                <div class="kt-scrollable-x-auto">
                    <table class="kt-table kt-table-border align-middle">
                        <thead>
                            <tr>
                                <th class="min-w-[200px]">Gebruiker</th>
                                <th class="min-w-[200px]">Email</th>
                                <th class="min-w-[150px]">Bedrijf</th>
                                <th class="min-w-[150px]">Rollen</th>
                                <th class="min-w-[100px]">Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permission->users as $user)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        @if($user->photo_blob)
                                            <img alt="{{ $user->first_name }} {{ $user->last_name }}" class="rounded-full size-9 shrink-0" src="{{ route('admin.users.photo', $user) }}"/>
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
                                        <span class="kt-badge kt-badge-sm kt-badge-info">{{ $user->company->name }}</span>
                                    @else
                                        <span class="text-sm text-muted-foreground">Geen bedrijf</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->roles as $role)
                                            <span class="kt-badge kt-badge-sm kt-badge-info">{{ $role->name }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.users.show', $user) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Bekijken">
                                            <i class="ki-filled ki-eye"></i>
                                        </a>
                                        @can('edit-users')
                                        <a href="{{ route('admin.users.edit', $user) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Bewerken">
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
            </div>
        </div>
        @endif
    </div>
</div>

@endsection
