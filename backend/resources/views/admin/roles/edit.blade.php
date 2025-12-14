@extends('admin.layouts.app')

@section('title', 'Rol Bewerken - ' . ucfirst(str_replace('-', ' ', $role->name)))

@push('styles')
<style>
    /* Duidelijkere checkbox styling voor permissions tabel */
    table[data-required-checkbox-group] .kt-checkbox {
        border-width: 2px;
        border-color: #555555;
        color: #555555;
    }
    
    table[data-required-checkbox-group] .kt-checkbox:hover {
        border-color: #555555;
        background-color: rgba(85, 85, 85, 0.1);
    }
    
    table[data-required-checkbox-group] .kt-checkbox:checked {
        border-color: #10b981 !important;
        background-color: transparent !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20' fill='none'%3E%3Cpath fill-rule='evenodd' d='M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z' clip-rule='evenodd' fill='%2310b981'/%3E%3C/svg%3E") !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        background-size: 20px 20px !important;
        border-width: 2px;
        color: #10b981 !important;
    }
    
    table[data-required-checkbox-group] .kt-checkbox:focus-visible {
        --tw-ring-color: #555555;
        --tw-ring-offset-width: 2px;
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
            <a href="{{ route('admin.roles.show', $role) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        <div class="flex items-center gap-2.5">
        </div>
    </div>

    <form action="{{ route('admin.roles.update', $role) }}" method="POST" data-validate="true">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <!-- Basis Informatie -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Basis Informatie</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        @php
                            $isSystemRole = in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']);
                        @endphp
                        @if(!$isSystemRole)
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Rol Naam *</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           name="name" 
                                           class="kt-input @error('name') border-destructive @enderror" 
                                           value="{{ old('name', $role->name) }}"
                                           required>
                                </div>
                                <div class="field-feedback text-xs mt-1 hidden" data-field="name"></div>
                                @error('name')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        @else
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Rol Naam</td>
                            <td class="min-w-48 w-full">
                                <span class="text-foreground font-medium">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
                                <div class="text-xs text-muted-foreground mt-1">Systeem rollen kunnen niet worden hernoemd</div>
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Beschrijving</td>
                            <td>
                                <textarea name="description" 
                                          rows="4" 
                                          class="kt-input pt-1 @error('description') border-destructive @enderror">{{ old('description', $role->description) }}</textarea>
                                @error('description')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
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
                    </table>
                </div>
            </div>

            <!-- Rechten -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Rechten Toewijzen *</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3" data-required-checkbox-group="permissions[]">
                    @error('permissions')
                        <div class="kt-alert kt-alert-danger mb-5 mx-5">
                            <i class="ki-filled ki-cross-circle me-2"></i>
                            {{ $message }}
                        </div>
                    @enderror
                    
                    <div class="field-feedback text-xs mt-1 hidden" data-field="permissions"></div>
                    
                    @php
                        // Get currently selected permissions (from old input or role)
                        $rolePermissionNames = old('permissions', $role->permissions->pluck('name')->toArray());
                        
                        // Flatten all permissions from grouped structure
                        $allPermissions = collect($permissions)->flatten();
                        
                        // Parse permissions: structure is "action-module" (e.g., "view-users", "create-vacancies")
                        // Group by module (the part after the action)
                        $permissionModules = $allPermissions->groupBy(function($permission) {
                            $parts = explode('-', $permission->name);
                            if (count($parts) > 1) {
                                array_shift($parts);
                                return implode('-', $parts);
                            }
                            return 'other';
                        });
                        
                        // Get all unique actions
                        $allActions = $allPermissions->map(function($permission) {
                            $parts = explode('-', $permission->name);
                            return $parts[0] ?? 'other';
                        })->unique()->sort()->values();
                        
                        // Create a map of module => [permissions by action]
                        $permissionMap = [];
                        foreach ($allPermissions as $permission) {
                            $parts = explode('-', $permission->name);
                            $action = $parts[0] ?? 'other';
                            array_shift($parts);
                            $module = implode('-', $parts) ?: 'other';
                            if (!isset($permissionMap[$module])) {
                                $permissionMap[$module] = [];
                            }
                            $permissionMap[$module][$action] = $permission;
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
                    
                    <table class="kt-table kt-table-border align-middle text-sm" data-required-checkbox-group="permissions[]">
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
                            @foreach($permissionModules as $module => $modulePermissions)
                                <tr>
                                    <td class="text-foreground font-medium">
                                        {{ $moduleNames[$module] ?? ucfirst(str_replace(['-', '_'], ' ', $module)) }}
                                    </td>
                                    @foreach($allActions as $action)
                                        <td class="text-center">
                                            @if(isset($permissionMap[$module][$action]))
                                                @php
                                                    $permission = $permissionMap[$module][$action];
                                                @endphp
                                                <label class="kt-label flex items-center justify-center cursor-pointer">
                                                    <input type="checkbox" 
                                                           class="kt-checkbox" 
                                                           name="permissions[]" 
                                                           value="{{ $permission->name }}" 
                                                           id="permission_{{ $permission->id }}"
                                                           data-checkbox-group="permissions[]"
                                                           {{ in_array($permission->name, $rolePermissionNames) ? 'checked' : '' }}>
                                                </label>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.roles.show', $role) }}" class="kt-btn kt-btn-outline">
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Wijzigingen Opslaan
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
@endpush

@endsection
