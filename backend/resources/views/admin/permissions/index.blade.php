@extends('admin.layouts.app')

@section('title', 'Rechten Beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Rechten Beheer
        </h1>
        <div class="flex items-center gap-2.5">
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('create-permissions'))
            <a href="{{ route('admin.permissions.bulk-create') }}" class="kt-btn kt-btn-success">
                <i class="ki-filled ki-plus me-2"></i>
                Bulk Aanmaken
            </a>
            <a href="{{ route('admin.permissions.create') }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Nieuw Recht
            </a>
            @endif
        </div>
    </div>

    <!-- Success Alert -->
    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" id="success-alert" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5" id="error-alert" role="alert">
            <i class="ki-filled ki-cross-circle me-2"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <div class="flex lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_permissions'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Totaal Rechten
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['assigned_permissions'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Toegewezen
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['unassigned_permissions'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Niet Toegewezen
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['permissions_by_group']->count() ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Groepen
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon alle rechten ({{ $allPermissions->count() }})
                </h3>
            </div>
            
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                @if($allPermissions->count() > 0)
                    @php
                        // Parse permissions: structure is "action-module" (e.g., "view-users", "create-vacancies")
                        // Group by module (the part after the action)
                        $permissionModules = $allPermissions->groupBy(function($permission) {
                            $parts = explode('-', $permission->name);
                            // Get the module name (everything except the first part which is the action)
                            if (count($parts) > 1) {
                                array_shift($parts); // Remove the action part
                                return implode('-', $parts);
                            }
                            return 'other';
                        });
                        
                        // Get all unique actions (view, create, edit, delete, etc.) - the first part
                        $allActions = $allPermissions->map(function($permission) {
                            $parts = explode('-', $permission->name);
                            return $parts[0] ?? 'other'; // Get the first part (action)
                        })->unique()->sort()->values();
                        
                        // Create a map of module => [actions] for quick lookup
                        $permissionMap = [];
                        // Also create a map of module-action => permission object for edit links
                        $permissionObjects = [];
                        foreach ($allPermissions as $permission) {
                            $parts = explode('-', $permission->name);
                            $action = $parts[0] ?? 'other';
                            array_shift($parts);
                            $module = implode('-', $parts) ?: 'other';
                            if (!isset($permissionMap[$module])) {
                                $permissionMap[$module] = [];
                            }
                            $permissionMap[$module][] = $action;
                            // Store permission object for edit links
                            $permissionObjects[$module . '-' . $action] = $permission;
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
                            'modify' => 'Modify',
                            'configure' => 'Configure',
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
                                <th class="min-w-[60px] text-center text-secondary-foreground font-normal">Acties</th>
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
                                                @php
                                                    $permissionKey = $module . '-' . $action;
                                                    $permission = $permissionObjects[$permissionKey] ?? null;
                                                @endphp
                                                @if($permission)
                                                    <a href="{{ route('admin.permissions.edit', $permission) }}" class="inline-block" title="Bewerken">
                                                        <x-heroicon-s-check class="w-5 h-5 text-blue-500 mx-auto hover:text-blue-600" />
                                                    </a>
                                                @else
                                                    <x-heroicon-s-check class="w-5 h-5 text-blue-500 mx-auto" />
                                                @endif
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-center" onclick="event.stopPropagation();">
                                        <div class="kt-menu flex justify-center" data-kt-menu="true">
                                            <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                    <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                </button>
                                                <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                    @can('create-permissions')
                                                    <div class="kt-menu-item">
                                                        <a class="kt-menu-link" href="{{ route('admin.permissions.create') }}">
                                                            <span class="kt-menu-icon">
                                                                <i class="ki-filled ki-plus"></i>
                                                            </span>
                                                            <span class="kt-menu-title">Nieuw Recht</span>
                                                        </a>
                                                    </div>
                                                    @endcan
                                                    @if($permissions->count() > 0)
                                                    <div class="kt-menu-separator"></div>
                                                    @foreach($permissions->take(3) as $permission)
                                                    @can('edit-permissions')
                                                    <div class="kt-menu-item">
                                                        <a class="kt-menu-link" href="{{ route('admin.permissions.edit', $permission) }}">
                                                            <span class="kt-menu-icon">
                                                                <i class="ki-filled ki-pencil"></i>
                                                            </span>
                                                            <span class="kt-menu-title">{{ ucfirst(str_replace('-', ' ', $permission->name)) }}</span>
                                                        </a>
                                                    </div>
                                                    @endcan
                                                    @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="p-5">
                        <p class="text-muted-foreground">Geen rechten gevonden.</p>
                        @can('create-permissions')
                        <a href="{{ route('admin.permissions.create') }}" class="kt-btn kt-btn-primary mt-3">
                            <i class="ki-filled ki-plus me-2"></i>
                            Eerste Recht Aanmaken
                        </a>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-dismiss success alert after 3 seconds
        const successAlert = document.getElementById('success-alert');
        if (successAlert) {
            setTimeout(function() {
                successAlert.style.transition = 'opacity 0.3s ease-out';
                successAlert.style.opacity = '0';
                setTimeout(function() {
                    successAlert.remove();
                }, 300);
            }, 3000);
        }
    });
</script>
@endpush

@endsection
