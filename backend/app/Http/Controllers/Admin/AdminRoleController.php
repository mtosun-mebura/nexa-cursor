<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminRoleController extends Controller
{


    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-roles')) {
            abort(403, 'Je hebt geen rechten om rollen te bekijken.');
        }
        
        $query = Role::with('permissions')
            ->where('guard_name', 'web');
        
        // Apply filters
        if ($request->filled('type')) {
            if ($request->type === 'system') {
                $query->whereIn('name', ['super-admin', 'company-admin', 'staff', 'candidate']);
            } elseif ($request->type === 'custom') {
                $query->whereNotIn('name', ['super-admin', 'company-admin', 'staff', 'candidate']);
            }
        }
        
        if ($request->filled('users')) {
            if ($request->users === 'with_users') {
                $query->whereHas('users');
            } elseif ($request->users === 'without_users') {
                $query->whereDoesntHave('users');
            }
        }
        
        if ($request->filled('permissions')) {
            if ($request->permissions === 'with_permissions') {
                $query->whereHas('permissions');
            } elseif ($request->permissions === 'without_permissions') {
                $query->whereDoesntHave('permissions');
            }
        }
        
        // Get per_page from request, default to 25
        $perPage = $request->get('per_page', 25);
        
        $roles = $query->orderBy('name')->paginate($perPage);

        // Statistieken voor dashboard
        $stats = [
            'total_roles' => Role::where('guard_name', 'web')->count(),
            'system_roles' => Role::where('guard_name', 'web')
                ->whereIn('name', ['super-admin', 'company-admin', 'staff', 'candidate'])
                ->count(),
            'custom_roles' => Role::where('guard_name', 'web')
                ->whereNotIn('name', ['super-admin', 'company-admin', 'staff', 'candidate'])
                ->count(),
            'roles_with_permissions' => Role::where('guard_name', 'web')
                ->whereHas('permissions')
                ->count(),
            'total_users_with_roles' => \App\Models\User::whereHas('roles')->count(),
            'roles_by_usage' => Role::where('guard_name', 'web')
                ->withCount('users')
                ->orderBy('users_count', 'desc')
                ->take(5)
                ->get()
        ];

        return view('admin.roles.index', compact('roles', 'stats'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-roles')) {
            abort(403, 'Je hebt geen rechten om rollen aan te maken.');
        }
        
        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(function ($permission) {
                // Group permissions by their prefix (e.g., 'view-', 'create-', 'edit-', 'delete-')
                $parts = explode('-', $permission->name);
                return $parts[0] ?? 'other';
            });

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-roles')) {
            abort(403, 'Je hebt geen rechten om rollen aan te maken.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'description' => $request->description
        ]);

        $role->syncPermissions($request->permissions);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rol succesvol aangemaakt.');
    }

    public function show(Role $role)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-roles')) {
            abort(403, 'Je hebt geen rechten om rollen te bekijken.');
        }
        
        $role->load(['permissions', 'users.company']);
        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(function ($permission) {
                $parts = explode('-', $permission->name);
                return $parts[0] ?? 'other';
            });

        return view('admin.roles.show', compact('role', 'permissions'));
    }

    public function edit(Role $role)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-roles')) {
            abort(403, 'Je hebt geen rechten om rollen te bewerken.');
        }
        
        $role->load('permissions');
        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(function ($permission) {
                $parts = explode('-', $permission->name);
                return $parts[0] ?? 'other';
            });

        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-roles')) {
            abort(403, 'Je hebt geen rechten om rollen te bewerken.');
        }
        
        // Check if this is a system role
        $isSystemRole = in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']);
        
        // Define validation rules
        $validationRules = [
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name'
        ];
        
        // Only validate name if it's not a system role
        if (!$isSystemRole) {
            $validationRules['name'] = 'required|string|max:255|unique:roles,name,' . $role->id;
        }
        
        $request->validate($validationRules);

        // Update role data
        $updateData = [
            'description' => $request->description
        ];
        
        // Only update name if it's not a system role
        if (!$isSystemRole) {
            $updateData['name'] = $request->name;
        }

        $role->update($updateData);
        $role->syncPermissions($request->permissions);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rol succesvol bijgewerkt.');
    }

    public function destroy(Role $role)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-roles')) {
            abort(403, 'Je hebt geen rechten om rollen te verwijderen.');
        }
        
        // Prevent deletion of system roles
        if (in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate'])) {
            return back()->with('error', 'Systeem rollen kunnen niet worden verwijderd.');
        }

        // Check if role is assigned to any users
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Deze rol is toegewezen aan gebruikers en kan niet worden verwijderd.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rol succesvol verwijderd.');
    }
}
