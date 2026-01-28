@extends('admin.layouts.app')

@section('title', 'Rol Bewerken - ' . ucfirst(str_replace('-', ' ', $role->name)))

@push('styles')
<style>
    /* Permission Set buttons styling */
    .permission-set-btn {
        transition: all 0.2s ease;
    }

    .permission-set-btn:hover {
        background-color: rgba(0, 204, 255, 0.15) !important;
        border-color: rgba(0, 204, 255, 0.5) !important;
        transform: translateY(-1px);
    }

    .permission-set-btn {
        overflow: hidden !important;
        align-items: flex-start !important;
    }

    .permission-set-btn > div {
        width: 100% !important;
        max-width: 100% !important;
        overflow: hidden !important;
        flex: 1 1 auto !important;
    }

    .permission-set-btn span.line-clamp-2 {
        display: -webkit-box !important;
        -webkit-line-clamp: 2 !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: normal !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
        max-width: 100% !important;
        width: 100% !important;
        line-height: 1.4 !important;
    }

    /* Duidelijkere checkbox styling voor permissions tabel */
    table[data-required-checkbox-group] .kt-checkbox {
        width: 20px !important;
        height: 20px !important;
        min-width: 20px !important;
        min-height: 20px !important;
        border-width: 1px !important;
        border-color: #555555;
        color: #555555;
        padding-right: 0 !important;
        padding-left: 0 !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
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
        border-width: 1px !important;
        color: #10b981 !important;
    }

    table[data-required-checkbox-group] .kt-checkbox:focus-visible {
        --tw-ring-color: #555555;
        --tw-ring-offset-width: 2px;
    }

    /* Duidelijke styling voor permissions validatie melding - oranje balk */
    .kt-card-content .field-feedback[data-field="permissions"] {
        display: block !important;
        padding: 0.75rem 1rem;
        margin: 0.75rem 1.25rem;
        background-color: rgba(251, 146, 60, 0.1);
        border: 1px solid rgba(251, 146, 60, 0.3);
        border-left: 4px solid #fb923c;
        border-radius: 0.375rem;
        color: #c2410c;
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.5;
        opacity: 1;
        transition: opacity 0.3s ease-out;
    }

    .kt-card-content .field-feedback[data-field="permissions"].fade-out {
        opacity: 0;
    }

    .kt-card-content .field-feedback[data-field="permissions"]:empty,
    .kt-card-content .field-feedback[data-field="permissions"].hidden {
        display: none !important;
        opacity: 0;
    }

    .kt-card-content .field-feedback[data-field="permissions"]:not(:empty):not(.hidden) {
        display: block !important;
        opacity: 1;
    }
    
    /* Verberg field-feedback berichten in de tabel */
    .kt-card-table .field-feedback[data-field="permissions[]"],
    table .field-feedback[data-field="permissions[]"],
    tbody .field-feedback[data-field="permissions[]"],
    td .field-feedback[data-field="permissions[]"] {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Remove padding from Rechten card content */
    .kt-card .kt-card-content.no-padding {
        padding-inline: 0 !important;
        padding-block: 0 !important;
    }
    
    /* Add bottom border to table rows */
    .kt-card-content.no-padding table.kt-table tbody tr {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    
    .kt-card-content.no-padding table.kt-table tbody tr td {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    
    .kt-card-content.no-padding table.kt-table tbody tr:last-child td {
        border-bottom: none !important;
    }
    
    /* Success alert styling - groene balk en tekst */
    .kt-alert-success {
        background-color: rgba(16, 185, 129, 0.1) !important;
        border: 1px solid rgba(16, 185, 129, 0.3) !important;
        border-left: 4px solid #10b981 !important;
        color: #059669 !important;
        padding: 0.75rem 1rem !important;
        border-radius: 0.375rem !important;
    }
    
    .kt-alert-success i {
        color: #10b981 !important;
    }
    
    /* Danger alert styling - rood balkje en tekst */
    .kt-alert-danger {
        background-color: rgba(239, 68, 68, 0.1) !important;
        border: 1px solid rgba(239, 68, 68, 0.3) !important;
        border-left: 4px solid #ef4444 !important;
        color: #dc2626 !important;
        padding: 0.75rem 1rem !important;
        border-radius: 0.375rem !important;
    }
    
    .kt-alert-danger i {
        color: #ef4444 !important;
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
                    <h3 class="kt-card-title">Permissies Toewijzen *</h3>
                </div>
                <div class="kt-card-content no-padding">
                    @error('permissions')
                        <div class="kt-alert kt-alert-danger mb-5">
                            <i class="ki-filled ki-cross-circle me-2"></i>
                            {{ $message }}
                        </div>
                    @enderror

                    <!-- Permission Sets -->
                    <div class="mb-5" style="background-color: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 0.5rem; padding: 1.25rem 1.5rem; margin: 16px;">
                        <div class="flex items-start gap-3 mb-4">
                            <div class="flex-shrink-0">
                                <x-heroicon-s-information-circle class="w-6 h-6 flex-shrink-0" style="color: rgb(59, 130, 246);" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-base font-medium text-foreground dark:text-white mb-1">Snel Toepassen: Permission Sets</h4>
                                <p class="text-sm text-muted-foreground dark:text-gray-300">Selecteer een set om automatisch alle bijbehorende rechten toe te passen op alle modules.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
                            @foreach($permissionSets as $setKey => $set)
                                @php
                                    // Get all modules from permissions
                                    $allModules = collect($permissions)->flatten()
                                        ->map(function($p) {
                                            $parts = explode('-', $p->name);
                                            if (count($parts) > 1) {
                                                array_shift($parts);
                                                return implode('-', $parts);
                                            }
                                            return null;
                                        })
                                        ->filter()
                                        ->unique()
                                        ->values()
                                        ->toArray();

                                    $setPermissions = \App\Services\PermissionSetService::getAllPermissionsForSet($setKey, $allModules);
                                @endphp
                                <button type="button"
                                        class="permission-set-btn kt-btn kt-btn-outline text-left justify-start h-auto py-3 px-3 w-full"
                                        style="background-color: rgba(0, 204, 255, 0.1); border-color: rgba(0, 204, 255, 0.3); min-height: 90px; align-items: flex-start;"
                                        data-set-key="{{ $setKey }}"
                                        data-permissions='@json($setPermissions)'>
                                    <div class="flex flex-col items-start gap-1.5 w-full overflow-hidden" style="width: 100%; max-width: 100%;">
                                        <span class="font-medium text-sm text-foreground leading-tight whitespace-normal" style="flex-shrink: 0;">{{ $set['name'] }}</span>
                                        <span class="text-xs text-muted-foreground leading-relaxed line-clamp-2" style="display: -webkit-box !important; -webkit-line-clamp: 2 !important; -webkit-box-orient: vertical !important; overflow: hidden !important; text-overflow: ellipsis !important; white-space: normal !important; word-wrap: break-word !important; word-break: break-word !important; max-width: 100% !important; width: 100% !important; line-height: 1.4 !important;">{{ $set['description'] }}</span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    class="kt-btn kt-btn-sm kt-btn-outline text-xs"
                                    id="clear-all-permissions">
                                Alles Wissen
                            </button>
                            <span class="text-xs text-muted-foreground">of selecteer individuele rechten hieronder</span>
                        </div>
                    </div>

                    <div class="field-feedback hidden" data-field="permissions"></div>

                    {{-- Module Permissions Info Section --}}
                    @php
                        $menuService = app(\App\Services\MenuService::class);
                        $modulePermissionsInfo = $menuService->getModulePermissionsGrouped();
                    @endphp
                    @if(isset($modulePermissionsInfo) && count($modulePermissionsInfo) > 0)
                        <div class="px-5 mb-5">
                            <div class="p-4 bg-primary/5 border border-border rounded-lg">
                                <h4 class="font-semibold mb-3 text-primary flex items-center gap-2">
                                    <x-heroicon-s-puzzle-piece class="w-5 h-5 text-primary flex-shrink-0" />
                                    <span>Module Permissies (Alleen van actieve modules)</span>
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach($modulePermissionsInfo as $moduleDisplayName => $moduleData)
                                        <div class="text-sm">
                                            <span class="font-medium text-foreground">{{ $moduleDisplayName }}:</span>
                                            <span class="text-muted-foreground">
                                                {{ count($moduleData['permissions']) }} permissies
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="kt-card-table kt-scrollable-x-auto pb-3" style="border-top: 1px solid rgba(0, 0, 0, 0.1); border-bottom: 1px solid rgba(0, 0, 0, 0.1);" data-required-checkbox-group="permissions[]">

                    @php
                        // Get currently selected permissions (from old input or role)
                        $rolePermissionNames = old('permissions', $role->permissions->pluck('name')->toArray());

                        // Flatten all permissions from grouped structure
                        $allPermissions = collect($permissions)->flatten();
                        
                        // Initialize collections and maps (will be built after all permissions are collected)
                        $permissionMap = [];

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
                            'roles' => 'Rollen en Permissies',
                            'permissions' => 'Permissies',
                            'dashboard' => 'Dashboard',
                        ];
                        
                        // Add module permissions from active modules
                        if (isset($modulePermissions) && count($modulePermissions) > 0) {
                            foreach ($modulePermissions as $moduleDisplayName => $moduleData) {
                                $moduleKey = $moduleData['module'];
                                
                                // Add to moduleNames
                                if (!isset($moduleNames[$moduleKey])) {
                                    $moduleNames[$moduleKey] = $moduleDisplayName;
                                }
                                
                                // Get ALL permissions from database that match this module pattern
                                // Not just the ones in registerPermissions(), but all that exist
                                $allModulePermissions = \Spatie\Permission\Models\Permission::where('guard_name', 'web')
                                    ->where('name', 'like', $moduleKey . '.%')
                                    ->get();
                                
                                // Also include the ones from registerPermissions (in case they don't exist yet)
                                foreach ($moduleData['permissions'] as $permName) {
                                    $existing = $allModulePermissions->firstWhere('name', $permName);
                                    if (!$existing) {
                                        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(
                                            ['name' => $permName, 'guard_name' => 'web']
                                        );
                                        $allModulePermissions->push($permission);
                                    }
                                }
                                
                                // Process all module permissions
                                foreach ($allModulePermissions as $permission) {
                                    $permName = $permission->name;
                                    
                                    // Parse permission name (e.g., "skillmatching.vacancies.view")
                                    $parts = explode('.', $permName);
                                    if (count($parts) >= 3 && $parts[0] === $moduleKey) {
                                        $action = $parts[2]; // "view", "create", etc.
                                        $resource = $parts[1]; // "vacancies", "matches", etc.
                                        
                                        // Use resource as module key for grouping (e.g., "vacancies" under "skillmatching")
                                        // But we want to show them grouped by the actual module
                                        $displayModuleKey = $moduleKey . '-' . $resource;
                                        
                                        // Add to moduleNames
                                        if (!isset($moduleNames[$displayModuleKey])) {
                                            $moduleNames[$displayModuleKey] = ucfirst($resource);
                                        }
                                        
                                        // Add to permission map
                                        if (!isset($permissionMap[$displayModuleKey])) {
                                            $permissionMap[$displayModuleKey] = [];
                                        }
                                        $permissionMap[$displayModuleKey][$action] = $permission;
                                        
                                        // Add to allPermissions (if not already there)
                                        if (!$allPermissions->contains('id', $permission->id)) {
                                            $allPermissions->push($permission);
                                        }
                                        
                                        // Note: permissionModules will be built later from allPermissions
                                    }
                                }
                            }
                        }
                        
                        // Build resource to module mapping first
                        $resourceToModuleMap = [];
                        if (isset($modulePermissions) && is_array($modulePermissions)) {
                            foreach ($modulePermissions as $moduleDisplayName => $moduleData) {
                                $moduleKey = $moduleData['module'];
                                // Get all permissions for this module to find resources
                                $allModulePerms = \Spatie\Permission\Models\Permission::where('guard_name', 'web')
                                    ->where('name', 'like', $moduleKey . '.%')
                                    ->get();
                                
                                foreach ($allModulePerms as $perm) {
                                    $parts = explode('.', $perm->name);
                                    if (count($parts) >= 3 && $parts[0] === $moduleKey) {
                                        $resource = $parts[1];
                                        // Map resource to module (e.g., "matches" -> "skillmatching")
                                        if (!isset($resourceToModuleMap[$resource])) {
                                            $resourceToModuleMap[$resource] = $moduleKey;
                                        }
                                    }
                                }
                            }
                        }
                        
                        // NOW build the structure AFTER all permissions are collected
                        // Parse permissions: can be either "action-module" (e.g., "view-users") or "module.resource.action" (e.g., "skillmatching.vacancies.view")
                        // Group by module (the part after the action)
                        $permissionModules = $allPermissions->groupBy(function($permission) use ($resourceToModuleMap) {
                            $name = $permission->name;
                            
                            // Check if it's module format (module.resource.action)
                            if (strpos($name, '.') !== false) {
                                $parts = explode('.', $name);
                                if (count($parts) >= 3) {
                                    // Format: module.resource.action -> group by "module-resource"
                                    return $parts[0] . '-' . $parts[1];
                                }
                            }
                            
                            // Old format: action-module
                            $parts = explode('-', $name);
                            if (count($parts) > 1) {
                                $action = array_shift($parts);
                                $resource = implode('-', $parts);
                                
                                // Check if this resource belongs to an active module
                                if (isset($resourceToModuleMap[$resource])) {
                                    // Group under module-resource (e.g., "skillmatching-matches")
                                    return $resourceToModuleMap[$resource] . '-' . $resource;
                                }
                                
                                return $resource;
                            }
                            return 'other';
                        });
                        
                        // Create structure to group by main module for ordering and display
                        $permissionsByMainModule = [];
                        $moduleOrder = [];
                        
                        // Get list of active module keys
                        $activeModuleKeys = [];
                        if (isset($modulePermissions) && is_array($modulePermissions)) {
                            foreach ($modulePermissions as $modDisplayName => $modData) {
                                if (isset($modData['module'])) {
                                    $activeModuleKeys[] = $modData['module'];
                                }
                            }
                        }
                        
                        foreach ($permissionModules as $moduleKey => $perms) {
                            $mainModule = 'other';
                            $resource = null;
                            
                            // Check if it's module format (module.resource.action)
                            if (strpos($moduleKey, '-') !== false) {
                                $parts = explode('-', $moduleKey, 2);
                                $mainModule = $parts[0];
                                $resource = $parts[1] ?? null;
                            } else {
                                // This is old format (e.g., "matches", "vacancies")
                                // Check if this resource belongs to an active module
                                $resource = $moduleKey;
                                if (isset($resourceToModuleMap[$resource])) {
                                    $mainModule = $resourceToModuleMap[$resource];
                                } else {
                                    // Try to find module from permission names
                                    $firstPerm = $perms->first();
                                    if ($firstPerm) {
                                        $name = $firstPerm->name;
                                        if (strpos($name, '.') !== false) {
                                            $nameParts = explode('.', $name);
                                            if (count($nameParts) >= 3) {
                                                $mainModule = $nameParts[0];
                                                $resource = $nameParts[1];
                                            }
                                        }
                                    }
                                }
                            }
                            
                            // Only add if module is active or is 'other' (non-module permissions)
                            // Skip if this is a module that is not active
                            if ($mainModule !== 'other' && !in_array($mainModule, $activeModuleKeys)) {
                                continue; // Skip inactive modules
                            }
                            
                            if (!isset($permissionsByMainModule[$mainModule])) {
                                $permissionsByMainModule[$mainModule] = [];
                                $moduleOrder[] = $mainModule;
                            }
                            
                            // If this is an old format permission for a module resource, merge with module key
                            $displayKey = $moduleKey;
                            if ($mainModule !== 'other' && strpos($moduleKey, '-') === false) {
                                // This is old format, but belongs to a module - use module-resource format
                                $displayKey = $mainModule . '-' . $resource;
                                
                                // Merge with existing module permissions if they exist
                                $existingIndex = null;
                                foreach ($permissionsByMainModule[$mainModule] as $idx => $group) {
                                    if ($group['key'] === $displayKey) {
                                        $existingIndex = $idx;
                                        break;
                                    }
                                }
                                
                                if ($existingIndex !== null) {
                                    // Merge permissions
                                    $permissionsByMainModule[$mainModule][$existingIndex]['permissions'] = 
                                        $permissionsByMainModule[$mainModule][$existingIndex]['permissions']
                                        ->merge($perms)
                                        ->unique('id');
                                } else {
                                    // Add new group
                                    $permissionsByMainModule[$mainModule][] = [
                                        'key' => $displayKey,
                                        'resource' => $resource,
                                        'permissions' => $perms
                                    ];
                                }
                            } else {
                                // Normal case - add as is
                                $permissionsByMainModule[$mainModule][] = [
                                    'key' => $displayKey,
                                    'resource' => $resource,
                                    'permissions' => $perms
                                ];
                            }
                        }

                        // Get all unique actions
                        $allActions = $allPermissions->map(function($permission) {
                            $name = $permission->name;
                            
                            // Check if it's module format (module.resource.action)
                            if (strpos($name, '.') !== false) {
                                $parts = explode('.', $name);
                                if (count($parts) >= 3) {
                                    return $parts[2]; // action is last part
                                }
                            }
                            
                            // Old format: action-module
                            $parts = explode('-', $name);
                            return $parts[0] ?? 'other';
                        })->unique()->sort()->values();

                        // Create a map of module => [permissions by action]
                        $permissionMap = [];
                        foreach ($allPermissions as $permission) {
                            $name = $permission->name;
                            $action = 'other';
                            $module = 'other';
                            $mainModuleKey = 'other';
                            
                            // Check if it's module format (module.resource.action)
                            if (strpos($name, '.') !== false) {
                                $parts = explode('.', $name);
                                if (count($parts) >= 3) {
                                    $action = $parts[2];
                                    $module = $parts[0] . '-' . $parts[1];
                                    $mainModuleKey = $parts[0];
                                }
                            } else {
                                // Old format: action-module
                                $parts = explode('-', $name);
                                $action = $parts[0] ?? 'other';
                                array_shift($parts);
                                $oldModule = implode('-', $parts) ?: 'other';
                                
                                // Check if this old format permission belongs to an active module
                                if (isset($resourceToModuleMap[$oldModule])) {
                                    // Map to module-resource format (e.g., "matches" -> "skillmatching-matches")
                                    $module = $resourceToModuleMap[$oldModule] . '-' . $oldModule;
                                    $mainModuleKey = $resourceToModuleMap[$oldModule];
                                } else {
                                    $module = $oldModule;
                                    $mainModuleKey = 'other';
                                }
                            }
                            
                            // Only add if module is active or is 'other' (non-module permissions)
                            if ($mainModuleKey !== 'other' && !in_array($mainModuleKey, $activeModuleKeys)) {
                                continue; // Skip inactive modules
                            }
                            
                            if (!isset($permissionMap[$module])) {
                                $permissionMap[$module] = [];
                            }
                            // Only add if not already exists (prefer new format over old if both exist)
                            if (!isset($permissionMap[$module][$action])) {
                                $permissionMap[$module][$action] = $permission;
                            }
                        }

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

                    <table class="kt-table kt-table-border align-middle text-sm w-full" data-required-checkbox-group="permissions[]">
                        <thead>
                            <tr>
                                <th class="min-w-[250px] text-left text-secondary-foreground font-normal">Module / Resource</th>
                                @foreach($allActions as $action)
                                    <th class="min-w-[100px] text-center text-secondary-foreground font-normal">
                                        {{ $actionNames[$action] ?? ucfirst($action) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Sort modules: first active modules (from $modulePermissions), then others
                                $sortedModuleOrder = [];
                                if (isset($modulePermissions) && is_array($modulePermissions)) {
                                    foreach ($modulePermissions as $modDisplayName => $modData) {
                                        $modKey = $modData['module'];
                                        if (isset($permissionsByMainModule[$modKey])) {
                                            $sortedModuleOrder[] = $modKey;
                                        }
                                    }
                                }
                                // Add remaining modules
                                foreach ($moduleOrder as $modKey) {
                                    if (!in_array($modKey, $sortedModuleOrder)) {
                                        $sortedModuleOrder[] = $modKey;
                                    }
                                }
                            @endphp
                            
                            @foreach($sortedModuleOrder as $mainModule)
                                @if(isset($permissionsByMainModule[$mainModule]))
                                    @php
                                        $mainModuleDisplayName = $moduleNames[$mainModule] ?? ucfirst(str_replace(['-', '_'], ' ', $mainModule));
                                        
                                        // Check if this module is in modulePermissions
                                        $moduleInfo = null;
                                        $isActiveModule = false;
                                        if (isset($modulePermissions) && is_array($modulePermissions)) {
                                            foreach ($modulePermissions as $modDisplayName => $modData) {
                                                if (isset($modData['module']) && $modData['module'] === $mainModule) {
                                                    $moduleInfo = $modData;
                                                    $mainModuleDisplayName = $modDisplayName;
                                                    $isActiveModule = true;
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        // Determine display name
                                        if (!$isActiveModule && $mainModule === 'other') {
                                            $headerDisplayName = 'Algemeen';
                                        } else {
                                            $headerDisplayName = $mainModuleDisplayName;
                                        }
                                    @endphp
                                    
                                    {{-- Module Header Row --}}
                                    <tr class="bg-muted/30">
                                        <td colspan="{{ count($allActions) + 1 }}" class="py-2 px-4">
                                            @if($isActiveModule)
                                                <span class="font-semibold text-foreground text-sm flex items-center gap-1">
                                                    <x-heroicon-s-puzzle-piece class="w-4 h-4 text-primary flex-shrink-0" />
                                                    {{ $headerDisplayName }}
                                                </span>
                                            @else
                                                <span class="font-semibold text-foreground text-sm">
                                                    {{ $headerDisplayName }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    
                                    @foreach($permissionsByMainModule[$mainModule] as $moduleGroup)
                                        @php
                                            $moduleKey = $moduleGroup['key'];
                                            $resource = $moduleGroup['resource'];
                                        @endphp
                                        <tr>
                                            <td class="text-foreground pl-6">
                                                <span class="font-medium text-sm">
                                                    {{ $moduleNames[$moduleKey] ?? ucfirst(str_replace(['-', '_'], ' ', $resource ?? $moduleKey)) }}
                                                </span>
                                            </td>
                                            @foreach($allActions as $action)
                                                <td class="text-center">
                                                    @if(isset($permissionMap[$moduleKey][$action]))
                                                        @php
                                                            $permission = $permissionMap[$moduleKey][$action];
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
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 mb-3 mr-3">
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fix permission set button text wrapping
    const fixButtonTextWrapping = () => {
        const buttons = document.querySelectorAll('.permission-set-btn');
        buttons.forEach(button => {
            const desc = button.querySelector('span.line-clamp-2');
            if (desc) {
                desc.style.display = '-webkit-box';
                desc.style.webkitLineClamp = '2';
                desc.style.webkitBoxOrient = 'vertical';
                desc.style.overflow = 'hidden';
                desc.style.textOverflow = 'ellipsis';
                desc.style.whiteSpace = 'normal';
                desc.style.wordWrap = 'break-word';
                desc.style.maxWidth = '100%';
            }
        });
    };

    // Apply fixes immediately
    fixButtonTextWrapping();

    // Also apply after a short delay to ensure DOM is ready
    setTimeout(fixButtonTextWrapping, 100);
    
    // Function to check permissions and show/hide validation message
    function checkPermissionsValidation() {
        const allCheckboxes = document.querySelectorAll('input[name="permissions[]"]');
        const checkedCount = Array.from(allCheckboxes).filter(cb => cb.checked).length;
        const feedbackElement = document.querySelector('.field-feedback[data-field="permissions"]');
        
        if (feedbackElement) {
            if (checkedCount === 0) {
                // Show message with fade-in - ensure fade-out and hidden are removed first
                feedbackElement.classList.remove('fade-out', 'hidden');
                feedbackElement.textContent = 'Selecteer minimaal één recht.';
                feedbackElement.style.display = 'block';
                // Force reflow and then set opacity to ensure fade-in works
                void feedbackElement.offsetHeight; // Force reflow
                // Reset opacity to ensure it's visible
                feedbackElement.style.opacity = '';
                setTimeout(() => {
                    feedbackElement.style.opacity = '1';
                }, 10);
            } else {
                // Fade out before hiding
                if (!feedbackElement.classList.contains('fade-out')) {
                    feedbackElement.classList.add('fade-out');
                    feedbackElement.style.opacity = '0';
                    setTimeout(() => {
                        feedbackElement.classList.add('hidden');
                        feedbackElement.style.display = 'none';
                    }, 300); // Match transition duration
                }
            }
        }
    }
    
    // Check permissions on page load
    checkPermissionsValidation();
    
    // Listen for checkbox changes
    const allPermissionCheckboxes = document.querySelectorAll('input[name="permissions[]"]');
    allPermissionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            checkPermissionsValidation();
        });
    });

    // Permission Sets functionality
    const permissionSetButtons = document.querySelectorAll('.permission-set-btn');
    const clearAllBtn = document.getElementById('clear-all-permissions');

    permissionSetButtons.forEach(button => {
        button.addEventListener('click', function() {
            const permissions = JSON.parse(this.getAttribute('data-permissions'));
            const allCheckboxes = document.querySelectorAll('input[name="permissions[]"]');

            // First, clear ALL checkboxes
            allCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event('change'));
            });

            // Then, check only the checkboxes that match the set permissions
            allCheckboxes.forEach(checkbox => {
                if (permissions.includes(checkbox.value)) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change'));
                }
            });

            // Check validation after setting permissions - use setTimeout to ensure DOM is updated
            setTimeout(() => {
                checkPermissionsValidation();
            }, 10);

            // Show feedback
            const setName = this.querySelector('.font-medium').textContent;
            showTemporaryFeedback(`Permission set "${setName}" toegepast!`, 'success');
        });
    });

    // Clear all permissions
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function() {
            const allCheckboxes = document.querySelectorAll('input[name="permissions[]"]');
            allCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event('change'));
            });
            // Check validation after clearing - use setTimeout to ensure DOM is updated
            setTimeout(() => {
                checkPermissionsValidation();
            }, 10);
            showTemporaryFeedback('Alle rechten gewist', 'danger');
        });
    }

    // Helper function to show temporary feedback
    function showTemporaryFeedback(message, type = 'info') {
        let alertClass, iconClass;
        if (type === 'success') {
            alertClass = 'kt-alert-success';
            iconClass = 'ki-check-circle';
        } else if (type === 'danger') {
            alertClass = 'kt-alert-danger';
            iconClass = 'ki-cross-circle';
        } else {
            alertClass = 'kt-alert-info';
            iconClass = 'ki-information-5';
        }
        
        const feedback = document.createElement('div');
        feedback.className = `kt-alert ${alertClass} mb-4 mx-5`;
        feedback.innerHTML = `<i class="ki-filled ${iconClass} me-2"></i>${message}`;

        const permissionsCard = document.querySelector('[data-required-checkbox-group="permissions[]"]');
        if (permissionsCard && permissionsCard.parentElement) {
            permissionsCard.parentElement.insertBefore(feedback, permissionsCard);

            setTimeout(() => {
                feedback.style.transition = 'opacity 0.3s ease-out';
                feedback.style.opacity = '0';
                setTimeout(() => feedback.remove(), 300);
            }, 2000);
        }
    }
});
</script>
@endpush

@endsection
