@extends('admin.layouts.app')

@section('title', 'Rol Details - ' . ucfirst(str_replace('-', ' ', $role->name)))

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
                    <span class="kt-badge kt-badge-sm kt-badge-warning">Systeem</span>
                @else
                    <span class="kt-badge kt-badge-sm kt-badge-success">Aangepast</span>
                @endif
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-key text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">
                        {{ $role->permissions->count() }} rechten
                    </span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-people text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">
                        {{ $role->users->count() }} gebruikers
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
                Terug
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
                    <span class="ms-2">Actief</span>
                </label>
            </form>
            @else
            <label class="kt-label flex items-center">
                <input type="checkbox" 
                       class="kt-switch kt-switch-sm" 
                       checked 
                       disabled/>
                <span class="ms-2">Actief</span>
            </label>
            @endif
            <span class="text-orange-500">|</span>
            <a href="{{ route('admin.roles.edit', $role) }}" class="kt-btn kt-btn-warning">
                <i class="ki-filled ki-pencil me-2"></i>
                Bewerken
            </a>
            @endif
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-roles'))
            @if(!in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
            <form action="{{ route('admin.roles.destroy', $role) }}" 
                  method="POST" 
                  class="inline"
                  onsubmit="return confirm('Weet je zeker dat je deze rol wilt verwijderen?')">
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
                        <td class="min-w-56 text-secondary-foreground font-normal">Rol Naam</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground font-medium">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Type</td>
                        <td class="min-w-48 w-full">
                            @if(in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                <span class="kt-badge kt-badge-warning">Systeem</span>
                            @else
                                <span class="kt-badge kt-badge-success">Aangepast</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Status</td>
                        <td class="min-w-48 w-full">
                            @php
                                $isActive = $role->is_active ?? true;
                            @endphp
                            @if($isActive)
                                <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                            @endif
                        </td>
                    </tr>
                    @if($role->description)
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">Beschrijving</td>
                        <td>
                            <span class="text-foreground">{{ $role->description }}</span>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Aantal Rechten</td>
                        <td class="min-w-48 w-full">
                            <span class="kt-badge kt-badge-info">{{ $role->permissions->count() }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Aantal Gebruikers</td>
                        <td class="min-w-48 w-full">
                            <span class="kt-badge kt-badge-secondary">{{ $role->users->count() }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Aangemaakt</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground">{{ $role->created_at->format('d-m-Y H:i') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Laatst bijgewerkt</td>
                        <td class="min-w-48 w-full">
                            <span class="text-foreground">{{ $role->updated_at->format('d-m-Y H:i') }}</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Permissions -->
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Toegewezen Rechten ({{ $role->permissions->count() }})</h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                @if($role->permissions->count() > 0)
                    @php
                        // Parse permissions: structure is "action-module" (e.g., "view-users", "create-vacancies")
                        // Group by module (the part after the action)
                        $permissionModules = $role->permissions->groupBy(function($permission) {
                            $parts = explode('-', $permission->name);
                            // Get the module name (everything except the first part which is the action)
                            if (count($parts) > 1) {
                                array_shift($parts); // Remove the action part
                                return implode('-', $parts);
                            }
                            return 'other';
                        });
                        
                        // Get all unique actions (view, create, edit, delete, etc.) - the first part
                        $allActions = $role->permissions->map(function($permission) {
                            $parts = explode('-', $permission->name);
                            return $parts[0] ?? 'other'; // Get the first part (action)
                        })->unique()->sort()->values();
                        
                        // Create a map of module => [actions] for quick lookup
                        $permissionMap = [];
                        foreach ($role->permissions as $permission) {
                            $parts = explode('-', $permission->name);
                            $action = $parts[0] ?? 'other';
                            array_shift($parts);
                            $module = implode('-', $parts) ?: 'other';
                            if (!isset($permissionMap[$module])) {
                                $permissionMap[$module] = [];
                            }
                            $permissionMap[$module][] = $action;
                        }
                        
                        // Module display names
                        $moduleNames = [
                            'users' => 'Gebruikers',
                            'vacancies' => 'Vacatures',
                            'matches' => 'Matches',
                            'interviews' => 'Interviews',
                            'notifications' => 'Notificaties',
                            'email-templates' => 'E-mail Templates',
                            'email_templates' => 'E-mail Templates',
                            'tenant-dashboard' => 'Tenant Dashboard',
                            'tenant_dashboard' => 'Tenant Dashboard',
                            'agenda' => 'Agenda',
                            'companies' => 'Bedrijven',
                            'branches' => 'Branches',
                            'categories' => 'Categorieën',
                            'roles' => 'Rollen',
                            'permissions' => 'Permissies',
                            'job-configurations' => 'Job Configuraties',
                            'job_configurations' => 'Job Configuraties',
                            'dashboard' => 'Dashboard',
                        ];
                        
                        // Action display names
                        $actionNames = [
                            'view' => 'View',
                            'create' => 'Create',
                            'edit' => 'Edit',
                            'delete' => 'Delete',
                            'publish' => 'Publish',
                            'approve' => 'Approve',
                            'schedule' => 'Schedule',
                            'send' => 'Send',
                            'assign' => 'Assign',
                        ];
                    @endphp
                    <table class="kt-table kt-table-border align-middle text-sm">
                        <thead>
                            <tr>
                                <th class="min-w-[200px] text-left text-secondary-foreground font-normal">Module</th>
                                @foreach($allActions as $action)
                                    <th class="min-w-[100px] text-center text-secondary-foreground font-normal">
                                        {{ $actionNames[$action] ?? ucfirst($action) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permissionModules as $module => $permissions)
                                <tr>
                                    <td class="text-foreground font-medium">
                                        {{ $moduleNames[$module] ?? ucfirst(str_replace(['-', '_'], ' ', $module)) }}
                                    </td>
                                    @foreach($allActions as $action)
                                        <td class="text-center">
                                            @if(isset($permissionMap[$module]) && in_array($action, $permissionMap[$module]))
                                                <x-heroicon-s-check class="w-5 h-5 text-blue-500 mx-auto" />
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="p-5">
                        <p class="text-muted-foreground">Geen rechten toegewezen aan deze rol.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Users with this role -->
        @if($role->users->count() > 0)
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Gebruikers met deze rol ({{ $role->users->count() }})</h3>
            </div>
            <div class="kt-card-content">
                <div class="kt-scrollable-x-auto">
                    <table class="kt-table kt-table-border align-middle">
                        <thead>
                            <tr>
                                <th class="min-w-[200px]">Gebruiker</th>
                                <th class="min-w-[200px]">Email</th>
                                <th class="min-w-[150px]">Bedrijf</th>
                                <th class="min-w-[100px]">Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($role->users as $user)
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
                                        <span class="kt-badge kt-badge-info">{{ $user->company->name }}</span>
                                    @else
                                        <span class="text-sm text-muted-foreground">Geen bedrijf</span>
                                    @endif
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
