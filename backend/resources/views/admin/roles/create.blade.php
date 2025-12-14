@extends('admin.layouts.app')

@section('title', 'Nieuwe Rol')

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

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Nieuwe Rol
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.roles.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.roles.store') }}" method="POST" data-validate="true">
        @csrf

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <!-- Basis Informatie -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Basis Informatie</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Rol Naam *</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text" 
                                           name="name" 
                                           class="kt-input @error('name') border-destructive @enderror" 
                                           value="{{ old('name') }}"
                                           required>
                                </div>
                                <div class="field-feedback text-xs mt-1 hidden" data-field="name"></div>
                                @error('name')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Beschrijving</td>
                            <td>
                                <textarea name="description" 
                                          rows="4" 
                                          class="kt-input pt-1 @error('description') border-destructive @enderror">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
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
                                                           {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}>
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
                <a href="{{ route('admin.roles.index') }}" class="kt-btn kt-btn-outline">
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Rol Aanmaken
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script>
// Select all permissions in a group
function selectAllInGroup(groupName) {
    const checkboxes = document.querySelectorAll(`[data-group="${groupName}"]`);
    const selectAllCheckbox = document.getElementById(`select_all_${groupName}`);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

// Update "select all" checkboxes on page load
document.addEventListener('DOMContentLoaded', function() {
    const groups = @json(array_keys($permissions->toArray()));
    
    groups.forEach(group => {
        const checkboxes = document.querySelectorAll(`[data-group="${group}"]`);
        const selectAllCheckbox = document.getElementById(`select_all_${group}`);
        if (!selectAllCheckbox) return;
        
        // Update select all when individual checkboxes change
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checked = Array.from(checkboxes).filter(cb => cb.checked).length;
                if (checked === checkboxes.length) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.indeterminate = false;
                } else if (checked > 0) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = true;
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                }
            });
        });
    });
});
</script>
@endpush

@endsection
