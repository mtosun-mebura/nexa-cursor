<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminRoleController extends Controller
{


    public function index()
    {
        $roles = Role::with('permissions')
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
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
