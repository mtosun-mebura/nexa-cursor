<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminPermissionController extends Controller
{


    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-permissions')) {
            abort(403, 'Je hebt geen rechten om permissies te bekijken.');
        }
        
        // Start query
        $query = Permission::where('guard_name', 'web')
            ->with('roles')
            ->withCount('roles');
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $driver = DB::connection()->getDriverName();
            $query->where(function($q) use ($search, $driver) {
                if ($driver === 'pgsql') {
                    // PostgreSQL: use ILIKE for case-insensitive search
                    $q->whereRaw("name ILIKE ?", ["%{$search}%"])
                      ->orWhereRaw("description ILIKE ?", ["%{$search}%"]);
                } else {
                    // MySQL and others: use LIKE (case-insensitive by default in most collations)
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                }
            });
        }
        
        // Apply module filter
        if ($request->filled('module')) {
            $module = $request->module;
            $driver = DB::connection()->getDriverName();
            $query->where(function($q) use ($module, $driver) {
                // Check if module matches the group field
                $q->where('group', $module)
                  // Or check if module is in the permission name (action-module format)
                  ->orWhere('name', 'like', "%-{$module}")
                  ->orWhere('name', 'like', "%-{$module}-%");
                
                // Database-specific: get last part after dash
                if ($driver === 'pgsql') {
                    // PostgreSQL: use SPLIT_PART
                    $q->orWhereRaw("SPLIT_PART(name, '-', -1) = ?", [$module]);
                } elseif ($driver === 'mysql') {
                    // MySQL: use SUBSTRING_INDEX
                    $q->orWhereRaw("SUBSTRING_INDEX(name, '-', -1) = ?", [$module]);
                } else {
                    // Fallback: check if name ends with the module
                    $q->orWhere('name', 'like', "%-{$module}");
                }
            });
        }
        
        // Apply assigned filter
        if ($request->filled('assigned')) {
            if ($request->assigned === 'yes') {
                $query->has('roles');
            } elseif ($request->assigned === 'no') {
                $query->doesntHave('roles');
            }
        }
        
        // Apply sorting
        $sortBy = $request->get('sort');
        $sortDirection = $request->get('direction');
        
        if ($sortBy && in_array($sortBy, ['name'])) {
            // Set default direction based on sort field
            if (!$sortDirection || !in_array($sortDirection, ['asc', 'desc'])) {
                $sortDirection = 'asc'; // Default to alphabetical
            }
            $query->orderBy($sortBy, $sortDirection)->orderBy('id', 'asc');
        } else {
            // Default sort: order by name
            $query->orderBy('name', 'asc');
        }
        
        // Get all permissions
        $allPermissions = $query->get();

        $roles = Role::where('guard_name', 'web')->get();
        
        // Get unique modules for filter
        // First get modules from group field
        $modulesFromGroup = Permission::where('guard_name', 'web')
            ->whereNotNull('group')
            ->distinct()
            ->pluck('group')
            ->unique()
            ->values();
        
        // Then get modules from permission names (action-module format)
        // Use database-agnostic approach: get all permissions and extract modules in PHP
        $driver = \DB::connection()->getDriverName();
        $permissionsForModules = Permission::where('guard_name', 'web')
            ->pluck('name');
        
        $modulesFromName = collect();
        foreach ($permissionsForModules as $permissionName) {
            $parts = explode('-', $permissionName);
            if (count($parts) > 1) {
                // Get everything after the first part (action) as module
                array_shift($parts);
                $module = implode('-', $parts);
                if ($module) {
                    $modulesFromName->push($module);
                }
            }
        }
        
        // Combine and sort
        $allModules = $modulesFromGroup->merge($modulesFromName)
            ->unique()
            ->sort()
            ->values();
        
        // Filter modules based on user permissions (only show modules user can access)
        $accessibleModules = collect();
        
        // Module to permission mapping (based on sidebar menu items)
        $modulePermissionMap = [
            'users' => 'view-users',
            'vacancies' => 'view-vacancies',
            'matches' => 'view-matches',
            'interviews' => 'view-interviews',
            'agenda' => 'view-agenda',
            'notifications' => 'view-notifications',
            'email-templates' => 'view-email-templates',
            'email_templates' => 'view-email-templates',
            'companies' => 'view-companies',
            'branches' => 'view-branches',
            'roles' => 'view-roles',
            'permissions' => 'view-permissions',
            'job-configurations' => 'view-job-configurations',
            'job_configurations' => 'view-job-configurations',
            'dashboard' => null, // Dashboard is always accessible
        ];
        
        // Check each module if user has access
        foreach ($allModules as $module) {
            // Settings/Configuraties: only for super-admin
            if (in_array($module, ['settings', 'instellingen', 'configuraties'])) {
                if (auth()->user()->hasRole('super-admin')) {
                    $accessibleModules->push($module);
                }
            } elseif ($module === 'dashboard') {
                // Dashboard is always accessible
                $accessibleModules->push($module);
            } elseif (isset($modulePermissionMap[$module])) {
                $permission = $modulePermissionMap[$module];
                if ($permission === null || auth()->user()->hasRole('super-admin') || auth()->user()->can($permission)) {
                    $accessibleModules->push($module);
                }
            } else {
                // For other modules without mapping, include them (might be custom permissions)
                // But only if user is super-admin or has view-permissions
                if (auth()->user()->hasRole('super-admin') || auth()->user()->can('view-permissions')) {
                    $accessibleModules->push($module);
                }
            }
        }
        
        // Always add Configuraties for super-admin, even if no permissions exist for it
        if (auth()->user()->hasRole('super-admin')) {
            $accessibleModules->push('configuraties');
            $accessibleModules->push('settings');
            $accessibleModules->push('instellingen');
        }
        
        $modules = $accessibleModules->unique()->sort()->values();

        // Statistieken voor dashboard
        $viewCount = Permission::where('guard_name', 'web')->where('name', 'like', 'view-%')->count();
        $createCount = Permission::where('guard_name', 'web')->where('name', 'like', 'create-%')->count();
        $editCount = Permission::where('guard_name', 'web')->where('name', 'like', 'edit-%')->count();
        $deleteCount = Permission::where('guard_name', 'web')->where('name', 'like', 'delete-%')->count();
        $totalCount = Permission::where('guard_name', 'web')->count();
        $otherCount = $totalCount - $viewCount - $createCount - $editCount - $deleteCount;

        $stats = [
            'total_permissions' => $totalCount,
            'permissions_by_group' => Permission::where('guard_name', 'web')
                ->selectRaw('"group", count(*) as count')
                ->groupBy('group')
                ->orderBy('count', 'desc')
                ->get(),
            'permissions_by_type' => [
                'view' => $viewCount,
                'create' => $createCount,
                'edit' => $editCount,
                'delete' => $deleteCount,
                'other' => $otherCount
            ],
            'assigned_permissions' => Permission::where('guard_name', 'web')
                ->whereHas('roles')
                ->count(),
            'unassigned_permissions' => Permission::where('guard_name', 'web')
                ->whereDoesntHave('roles')
                ->count(),
            'most_used_permissions' => Permission::where('guard_name', 'web')
                ->withCount('roles')
                ->orderBy('roles_count', 'desc')
                ->take(5)
                ->get()
        ];

        return view('admin.permissions.index', compact('allPermissions', 'roles', 'stats', 'modules'));
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500'
        ]);

        Permission::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'description' => $request->description
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissie succesvol aangemaakt.');
    }

    public function show(Permission $permission)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-permissions')) {
            abort(403, 'Je hebt geen rechten om permissies te bekijken.');
        }
        
        $permission->load(['roles.users', 'users']);

        return view('admin.permissions.show', compact('permission'));
    }

    public function edit(Permission $permission)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-permissions')) {
            abort(403, 'Je hebt geen rechten om permissies te bewerken.');
        }
        
        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
            'description' => 'nullable|string|max:500'
        ]);

        $permission->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissie succesvol bijgewerkt.');
    }

    public function destroy(Permission $permission)
    {
        // Check if permission is assigned to any roles
        if ($permission->roles()->count() > 0) {
            return back()->with('error', 'Deze permissie is toegewezen aan rollen en kan niet worden verwijderd.');
        }

        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissie succesvol verwijderd.');
    }

    public function assignToRole(Request $request, Permission $permission)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name'
        ]);

        $roles = Role::whereIn('name', $request->roles)->get();
        $permission->syncRoles($roles);

        return back()->with('success', 'Permissies succesvol toegewezen aan rollen.');
    }

    public function bulkCreate()
    {
        // Get unique modules dynamically from database
        // First get modules from group field
        $modulesFromGroup = Permission::where('guard_name', 'web')
            ->whereNotNull('group')
            ->distinct()
            ->pluck('group')
            ->unique()
            ->values();
        
        // Then get modules from permission names (action-module format)
        $permissionsForModules = Permission::where('guard_name', 'web')
            ->pluck('name');
        
        $modulesFromName = collect();
        foreach ($permissionsForModules as $permissionName) {
            $parts = explode('-', $permissionName);
            if (count($parts) > 1) {
                // Get everything after the first part (action) as module
                array_shift($parts);
                $module = implode('-', $parts);
                if ($module) {
                    $modulesFromName->push($module);
                }
            }
        }
        
        // Combine and get unique modules
        $allModules = $modulesFromGroup->merge($modulesFromName)
            ->unique()
            ->sort()
            ->values();
        
        // Also extract modules from permission names that don't follow action-module format
        // For example: "Permissie Mailserver" should be recognized as module "instellingen"
        $allPermissionNames = Permission::where('guard_name', 'web')->pluck('name');
        foreach ($allPermissionNames as $permissionName) {
            // Check if it's not in action-module format
            $parts = explode('-', $permissionName);
            if (count($parts) === 1) {
                // Single word permission - check if it contains keywords
                $lowerName = strtolower($permissionName);
                if (strpos($lowerName, 'mailserver') !== false || 
                    strpos($lowerName, 'mail') !== false ||
                    strpos($lowerName, 'instelling') !== false ||
                    strpos($lowerName, 'setting') !== false) {
                    if (!$allModules->contains('instellingen')) {
                        $allModules->push('instellingen');
                    }
                }
            }
        }
        
        // Re-sort after adding new modules and convert to array
        $allModules = $allModules->unique()->sort()->values()->toArray();
        
        // Create module display names mapping
        // Use exact names from sidebar menu items
        $baseModuleNames = [
            'users' => 'Gebruikers',
            'vacancies' => 'Vacatures',
            'matches' => 'Matches',
            'interviews' => 'Interviews',
            'notifications' => 'Notificaties',
            'email-templates' => 'E-mail Templates',
            'email_templates' => 'E-mail Templates',
            'tenant-dashboard' => 'Dashboard',
            'tenant_dashboard' => 'Dashboard',
            'agenda' => 'Agenda',
            'companies' => 'Bedrijven',
            'branches' => 'Branches',
            'roles' => 'Rollen en Permissies',
            'permissions' => 'Permissies',
            'dashboard' => 'Dashboard',
            'instellingen' => 'Configuraties',
            'settings' => 'Configuraties',
            'payments' => 'Betalingen',
            'invoices' => 'Facturen',
            'payment-providers' => 'Betalingsproviders',
            'payment_providers' => 'Betalingsproviders',
        ];
        
        // Build moduleNames array with all found modules
        $moduleNames = [];
        foreach ($allModules as $module) {
            $moduleNames[$module] = $baseModuleNames[$module] ?? ucfirst(str_replace(['-', '_'], ' ', $module));
        }
        
        // Sort by display name for better UX
        asort($moduleNames);
        
        // Extract all unique actions from existing permissions
        $allActions = collect();
        foreach ($allPermissionNames as $permissionName) {
            $parts = explode('-', $permissionName);
            if (count($parts) > 1) {
                $action = $parts[0];
                if (!empty($action)) {
                    $allActions->push($action);
                }
            } else {
                // For single-word permissions, try to extract action-like words
                $lowerName = strtolower($permissionName);
                // Check if it starts with common action words
                $actionWords = ['view', 'create', 'edit', 'delete', 'manage', 'access', 'configure', 'use'];
                foreach ($actionWords as $actionWord) {
                    if (strpos($lowerName, $actionWord) === 0) {
                        $allActions->push($actionWord);
                        break;
                    }
                }
            }
        }
        
        // Add standard actions if not already present
        $standardActions = ['view', 'create', 'edit', 'delete', 'approve', 'assign', 'publish', 'schedule', 'send'];
        foreach ($standardActions as $action) {
            if (!$allActions->contains($action)) {
                $allActions->push($action);
            }
        }
        
        // Also check for custom actions in permission names
        // For example: "Permissie Mailserver" should be added as a custom action
        foreach ($allPermissionNames as $permissionName) {
            // If permission doesn't follow action-module format, it might be a custom permission
            $parts = explode('-', $permissionName);
            if (count($parts) === 1) {
                // This is a custom permission - extract the action name
                $lowerName = strtolower($permissionName);
                
                // Remove common prefixes like "permissie", "permission", etc.
                $cleanName = $permissionName;
                $prefixes = ['permissie', 'permission', 'recht', 'right'];
                foreach ($prefixes as $prefix) {
                    if (stripos($lowerName, $prefix) === 0) {
                        $cleanName = trim(substr($permissionName, strlen($prefix)));
                        break;
                    }
                }
                
                // If clean name is not empty and not already in actions, add it
                if (!empty($cleanName)) {
                    $cleanNameLower = strtolower($cleanName);
                    // Convert to slug-like format for consistency
                    $actionKey = str_replace(' ', '-', $cleanNameLower);
                    if (!$allActions->contains($actionKey)) {
                        $allActions->push($actionKey);
                    }
                }
            }
        }
        
        $uniqueActions = $allActions->unique()->sort()->values()->toArray();
        
        // Create action display names
        $actionNames = [
            'view' => 'View - Bekijken van items',
            'create' => 'Create - Nieuwe items aanmaken',
            'edit' => 'Edit - Bestaande items bewerken',
            'delete' => 'Delete - Items verwijderen',
            'approve' => 'Approve - Items goedkeuren',
            'assign' => 'Assign - Items toewijzen',
            'publish' => 'Publish - Items publiceren',
            'schedule' => 'Schedule - Items inplannen',
            'send' => 'Send - Items versturen',
        ];
        
        // Build actions array with display names
        $actions = [];
        foreach ($uniqueActions as $action) {
            if (isset($actionNames[$action])) {
                $actions[$action] = $actionNames[$action];
            } else {
                // For custom actions, create a display name
                $displayName = ucwords(str_replace(['-', '_'], ' ', $action));
                $actions[$action] = $displayName . ' - ' . $displayName . ' actie';
            }
        }
        
        return view('admin.permissions.bulk-create', compact('moduleNames', 'actions'));
    }

    public function bulkStore(Request $request)
    {
        // Get all valid actions dynamically from existing permissions
        $allPermissionNames = Permission::where('guard_name', 'web')->pluck('name');
        $validActions = collect();
        foreach ($allPermissionNames as $permissionName) {
            $parts = explode('-', $permissionName);
            if (count($parts) > 1) {
                $action = $parts[0];
                if (!empty($action)) {
                    $validActions->push($action);
                }
            } else {
                // Handle custom permissions (single word)
                $lowerName = strtolower($permissionName);
                $cleanName = $permissionName;
                $prefixes = ['permissie', 'permission', 'recht', 'right'];
                foreach ($prefixes as $prefix) {
                    if (stripos($lowerName, $prefix) === 0) {
                        $cleanName = trim(substr($permissionName, strlen($prefix)));
                        break;
                    }
                }
                if (!empty($cleanName)) {
                    $actionKey = strtolower(str_replace(' ', '-', $cleanName));
                    $validActions->push($actionKey);
                }
            }
        }
        // Add standard actions
        $standardActions = ['view', 'create', 'edit', 'delete', 'approve', 'assign', 'publish', 'schedule', 'send'];
        foreach ($standardActions as $action) {
            $validActions->push($action);
        }
        $validActions = $validActions->unique()->values()->toArray();
        
        $request->validate([
            'modules' => 'required|array|min:1',
            'modules.*' => 'string|max:100',
            'actions' => 'required|array|min:1',
            'actions.*' => 'in:' . implode(',', $validActions)
        ]);

        $modules = $request->modules;
        $actions = $request->actions;
        $createdPermissions = [];
        
        // Get all modules dynamically from database
        $modulesFromGroup = Permission::where('guard_name', 'web')
            ->whereNotNull('group')
            ->distinct()
            ->pluck('group')
            ->unique()
            ->values();
        
        $permissionsForModules = Permission::where('guard_name', 'web')
            ->pluck('name');
        
        $modulesFromName = collect();
        foreach ($permissionsForModules as $permissionName) {
            $parts = explode('-', $permissionName);
            if (count($parts) > 1) {
                array_shift($parts);
                $module = implode('-', $parts);
                if ($module) {
                    $modulesFromName->push($module);
                }
            }
        }
        
        $allModulesFromDb = $modulesFromGroup->merge($modulesFromName)
            ->unique()
            ->values()
            ->toArray();
        
        // Base module display names mapping
        // Use exact names from sidebar menu items
        $baseModuleNames = [
            'users' => 'Gebruikers',
            'vacancies' => 'Vacatures',
            'matches' => 'Matches',
            'interviews' => 'Interviews',
            'notifications' => 'Notificaties',
            'email-templates' => 'E-mail Templates',
            'email_templates' => 'E-mail Templates',
            'tenant-dashboard' => 'Dashboard',
            'tenant_dashboard' => 'Dashboard',
            'agenda' => 'Agenda',
            'companies' => 'Bedrijven',
            'branches' => 'Branches',
            'roles' => 'Rollen en Permissies',
            'permissions' => 'Permissies',
            'dashboard' => 'Dashboard',
            'instellingen' => 'Configuraties',
            'settings' => 'Configuraties',
            'payments' => 'Betalingen',
            'invoices' => 'Facturen',
            'payment-providers' => 'Betalingsproviders',
            'payment_providers' => 'Betalingsproviders',
        ];
        
        // Build moduleNames array with all found modules
        $moduleNames = [];
        foreach ($allModulesFromDb as $module) {
            $moduleNames[$module] = $baseModuleNames[$module] ?? ucfirst(str_replace(['-', '_'], ' ', $module));
        }
        $moduleNames['algemeen'] = 'Algemeen';
        
        // If "algemeen" is selected, create permissions for all modules
        if (in_array('algemeen', $modules)) {
            $allModules = array_keys($moduleNames);
            $allModules = array_filter($allModules, function($m) {
                return $m !== 'algemeen';
            });
            $modules = array_merge($modules, $allModules);
            $modules = array_unique($modules);
            // Remove "algemeen" from the array as it's not a real module
            $modules = array_filter($modules, function($m) {
                return $m !== 'algemeen';
            });
        }

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissionName = $action . '-' . $module;
                
                if (!Permission::where('name', $permissionName)->exists()) {
                    $moduleDisplayName = $moduleNames[$module] ?? ucfirst(str_replace(['-', '_'], ' ', $module));
                    $permission = Permission::create([
                        'name' => $permissionName,
                        'guard_name' => 'web',
                        'description' => ucfirst($action) . ' ' . $moduleDisplayName,
                        'group' => $module
                    ]);
                    $createdPermissions[] = $permission;
                }
            }
        }

        $message = count($createdPermissions) > 0 
            ? count($createdPermissions) . ' permissies succesvol aangemaakt.'
            : 'Geen nieuwe permissies aangemaakt (alleen bestaande permissies gevonden).';

        return redirect()->route('admin.permissions.index')
            ->with('success', $message);
    }

    public function bulkEdit(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-permissions')) {
            abort(403, 'Je hebt geen rechten om permissies te bewerken.');
        }
        
        $selectedModule = $request->get('module'); // Single module selected from dropdown
        
        // Get all modules dynamically from database (same as bulkCreate)
        $modulesFromGroup = Permission::where('guard_name', 'web')
            ->whereNotNull('group')
            ->distinct()
            ->pluck('group')
            ->unique()
            ->values();
        
        $permissionsForModules = Permission::where('guard_name', 'web')
            ->pluck('name');
        
        $modulesFromName = collect();
        foreach ($permissionsForModules as $permissionName) {
            $parts = explode('-', $permissionName);
            if (count($parts) > 1) {
                array_shift($parts);
                $module = implode('-', $parts);
                if ($module) {
                    $modulesFromName->push($module);
                }
            }
        }
        
        $allModules = $modulesFromGroup->merge($modulesFromName)
            ->unique()
            ->sort()
            ->values();
        
        // Also extract modules from permission names that don't follow action-module format
        foreach ($permissionsForModules as $permissionName) {
            $parts = explode('-', $permissionName);
            if (count($parts) === 1) {
                $lowerName = strtolower($permissionName);
                if (strpos($lowerName, 'mailserver') !== false || 
                    strpos($lowerName, 'mail') !== false ||
                    strpos($lowerName, 'instelling') !== false ||
                    strpos($lowerName, 'setting') !== false) {
                    if (!$allModules->contains('instellingen')) {
                        $allModules->push('instellingen');
                    }
                }
            }
        }
        
        $allModules = $allModules->unique()->sort()->values()->toArray();
        
        // Base module display names mapping
        // Use exact names from sidebar menu items
        $baseModuleNames = [
            'users' => 'Gebruikers',
            'vacancies' => 'Vacatures',
            'matches' => 'Matches',
            'interviews' => 'Interviews',
            'notifications' => 'Notificaties',
            'email-templates' => 'E-mail Templates',
            'email_templates' => 'E-mail Templates',
            'tenant-dashboard' => 'Dashboard',
            'tenant_dashboard' => 'Dashboard',
            'agenda' => 'Agenda',
            'companies' => 'Bedrijven',
            'branches' => 'Branches',
            'roles' => 'Rollen en Permissies',
            'permissions' => 'Permissies',
            'dashboard' => 'Dashboard',
            'instellingen' => 'Configuraties',
            'settings' => 'Configuraties',
            'payments' => 'Betalingen',
            'invoices' => 'Facturen',
            'payment-providers' => 'Betalingsproviders',
            'payment_providers' => 'Betalingsproviders',
        ];
        
        // Build moduleNames array with all found modules
        $moduleNames = [];
        foreach ($allModules as $module) {
            $moduleNames[$module] = $baseModuleNames[$module] ?? ucfirst(str_replace(['-', '_'], ' ', $module));
        }
        asort($moduleNames);
        
        // Determine which modules and actions are selected
        $selectedModules = [];
        $selectedActions = [];
        
        if ($selectedModule) {
            // If a specific module is selected, only show that module
            $selectedModules = [$selectedModule];
            
            // Get all actions that exist for this module
            $modulePermissions = Permission::where('guard_name', 'web')
                ->where(function($query) use ($selectedModule) {
                    $query->where('group', $selectedModule)
                          ->orWhere('name', 'like', "%-{$selectedModule}")
                          ->orWhere('name', 'like', "%-{$selectedModule}-%");
                })
                ->get();
            
            foreach ($modulePermissions as $permission) {
                $parts = explode('-', $permission->name);
                if (count($parts) > 1) {
                    $action = $parts[0];
                    if (!in_array($action, $selectedActions)) {
                        $selectedActions[] = $action;
                    }
                }
            }
        } else {
            // If no module selected, show all modules and actions that exist
            $allPermissions = Permission::where('guard_name', 'web')->get();
            
            foreach ($allPermissions as $permission) {
                $parts = explode('-', $permission->name);
                if (count($parts) > 1) {
                    $action = $parts[0];
                    array_shift($parts);
                    $module = implode('-', $parts);
                    
                    if ($module && isset($moduleNames[$module])) {
                        if (!in_array($module, $selectedModules)) {
                            $selectedModules[] = $module;
                        }
                        if (!in_array($action, $selectedActions)) {
                            $selectedActions[] = $action;
                        }
                    }
                }
            }
        }
        
        return view('admin.permissions.bulk-edit', compact('moduleNames', 'selectedModules', 'selectedActions', 'selectedModule'));
    }

    public function bulkUpdate(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-permissions')) {
            abort(403, 'Je hebt geen rechten om permissies te bewerken.');
        }

        // Get all valid actions dynamically from existing permissions
        $allPermissionNames = Permission::where('guard_name', 'web')->pluck('name');
        $validActions = collect();
        foreach ($allPermissionNames as $permissionName) {
            $parts = explode('-', $permissionName);
            if (count($parts) > 1) {
                $action = $parts[0];
                if (!empty($action)) {
                    $validActions->push($action);
                }
            }
        }
        // Add standard actions
        $standardActions = ['view', 'create', 'edit', 'delete', 'approve', 'assign', 'publish', 'schedule', 'send'];
        foreach ($standardActions as $action) {
            $validActions->push($action);
        }
        $validActions = $validActions->unique()->values()->toArray();
        
        $request->validate([
            'modules' => 'required|array|min:1',
            'modules.*' => 'string|max:100',
            'actions' => 'required|array|min:1',
            'actions.*' => 'in:' . implode(',', $validActions)
        ]);

        $modules = $request->modules;
        $actions = $request->actions;
        
        // Get all modules dynamically from database
        $modulesFromGroup = Permission::where('guard_name', 'web')
            ->whereNotNull('group')
            ->distinct()
            ->pluck('group')
            ->unique()
            ->values();
        
        $permissionsForModules = Permission::where('guard_name', 'web')
            ->pluck('name');
        
        $modulesFromName = collect();
        foreach ($permissionsForModules as $permissionName) {
            $parts = explode('-', $permissionName);
            if (count($parts) > 1) {
                array_shift($parts);
                $module = implode('-', $parts);
                if ($module) {
                    $modulesFromName->push($module);
                }
            }
        }
        
        $allModulesFromDb = $modulesFromGroup->merge($modulesFromName)
            ->unique()
            ->values()
            ->toArray();
        
        // Base module display names mapping
        // Use exact names from sidebar menu items
        $baseModuleNames = [
            'users' => 'Gebruikers',
            'vacancies' => 'Vacatures',
            'matches' => 'Matches',
            'interviews' => 'Interviews',
            'notifications' => 'Notificaties',
            'email-templates' => 'E-mail Templates',
            'email_templates' => 'E-mail Templates',
            'tenant-dashboard' => 'Dashboard',
            'tenant_dashboard' => 'Dashboard',
            'agenda' => 'Agenda',
            'companies' => 'Bedrijven',
            'branches' => 'Branches',
            'roles' => 'Rollen en Permissies',
            'permissions' => 'Permissies',
            'dashboard' => 'Dashboard',
            'instellingen' => 'Configuraties',
            'settings' => 'Configuraties',
            'payments' => 'Betalingen',
            'invoices' => 'Facturen',
            'payment-providers' => 'Betalingsproviders',
            'payment_providers' => 'Betalingsproviders',
        ];
        
        // Build moduleNames array with all found modules
        $moduleNames = [];
        foreach ($allModulesFromDb as $module) {
            $moduleNames[$module] = $baseModuleNames[$module] ?? ucfirst(str_replace(['-', '_'], ' ', $module));
        }
        $moduleNames['algemeen'] = 'Algemeen';
        
        // If "algemeen" is selected, create permissions for all modules
        if (in_array('algemeen', $modules)) {
            $allModules = array_keys($moduleNames);
            $allModules = array_filter($allModules, function($m) {
                return $m !== 'algemeen';
            });
            $modules = array_merge($modules, $allModules);
            $modules = array_unique($modules);
            // Remove "algemeen" from the array as it's not a real module
            $modules = array_filter($modules, function($m) {
                return $m !== 'algemeen';
            });
        }

        // Determine which permissions should exist
        $expectedPermissions = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissionName = $action . '-' . $module;
                $expectedPermissions[] = $permissionName;
            }
        }

        // Get all existing permissions
        $allExistingPermissions = Permission::where('guard_name', 'web')->get();
        
        $created = 0;
        $deleted = 0;
        $skipped = 0;

        // Create missing permissions
        foreach ($expectedPermissions as $permissionName) {
            if (!Permission::where('name', $permissionName)->exists()) {
                $parts = explode('-', $permissionName);
                $action = $parts[0];
                array_shift($parts);
                $module = implode('-', $parts);
                
                $moduleDisplayName = $moduleNames[$module] ?? ucfirst(str_replace(['-', '_'], ' ', $module));
                Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'description' => ucfirst($action) . ' ' . $moduleDisplayName,
                    'group' => $module
                ]);
                $created++;
            }
        }

        // Delete permissions that are not in the expected list (only if not assigned to roles)
        foreach ($allExistingPermissions as $permission) {
            if (!in_array($permission->name, $expectedPermissions)) {
                // Only delete if not assigned to any roles
                if ($permission->roles()->count() === 0) {
                    $permission->delete();
                    $deleted++;
                } else {
                    $skipped++;
                }
            }
        }

        $message = [];
        if ($created > 0) {
            $message[] = $created . ' permissie' . ($created > 1 ? 's' : '') . ' aangemaakt';
        }
        if ($deleted > 0) {
            $message[] = $deleted . ' permissie' . ($deleted > 1 ? 's' : '') . ' verwijderd';
        }
        if ($skipped > 0) {
            $message[] = $skipped . ' permissie' . ($skipped > 1 ? 's' : '') . ' overgeslagen (toegewezen aan rollen)';
        }
        
        $finalMessage = count($message) > 0 
            ? implode(', ', $message) . '.'
            : 'Geen wijzigingen doorgevoerd.';

        return redirect()->route('admin.permissions.index')
            ->with('success', $finalMessage);
    }

    public function bulkDelete(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-permissions')) {
            abort(403, 'Je hebt geen rechten om permissies te verwijderen.');
        }

        $request->validate([
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $permissionIds = $request->permissions;
        $permissions = Permission::where('guard_name', 'web')
            ->whereIn('id', $permissionIds)
            ->withCount('roles')
            ->get();

        $deleted = 0;
        $skipped = 0;
        
        foreach ($permissions as $permission) {
            // Only delete if permission is not assigned to any roles
            if ($permission->roles_count == 0) {
                $permission->delete();
                $deleted++;
            } else {
                $skipped++;
            }
        }

        if ($deleted > 0) {
            $message = $deleted . ' permissie' . ($deleted > 1 ? 's' : '') . ' succesvol verwijderd.';
            if ($skipped > 0) {
                $message .= ' ' . $skipped . ' permissie' . ($skipped > 1 ? 's' : '') . ' overgeslagen (toegewezen aan rollen).';
            }
            return redirect()->route('admin.permissions.index')
                ->with('success', $message);
        } else {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Geen permissies verwijderd. Alle geselecteerde permissies zijn toegewezen aan rollen.');
        }
    }
}
