<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminPermissionController extends Controller
{


    public function index()
    {
        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(function ($permission) {
                // Group permissions by their prefix (e.g., 'view-', 'create-', 'edit-', 'delete-')
                $parts = explode('-', $permission->name);
                return $parts[0] ?? 'other';
            });

        $roles = Role::where('guard_name', 'web')->get();

        return view('admin.permissions.index', compact('permissions', 'roles'));
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
            'group' => 'required|string|max:100'
        ]);

        Permission::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'description' => $request->description,
            'group' => $request->group
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Recht succesvol aangemaakt.');
    }

    public function show(Permission $permission)
    {
        $permission->load('roles');
        $roles = Role::where('guard_name', 'web')->get();

        return view('admin.permissions.show', compact('permission', 'roles'));
    }

    public function edit(Permission $permission)
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
            'description' => 'nullable|string|max:500',
            'group' => 'required|string|max:100'
        ]);

        $permission->update([
            'name' => $request->name,
            'description' => $request->description,
            'group' => $request->group
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Recht succesvol bijgewerkt.');
    }

    public function destroy(Permission $permission)
    {
        // Check if permission is assigned to any roles
        if ($permission->roles()->count() > 0) {
            return back()->with('error', 'Dit recht is toegewezen aan rollen en kan niet worden verwijderd.');
        }

        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Recht succesvol verwijderd.');
    }

    public function assignToRole(Request $request, Permission $permission)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name'
        ]);

        $roles = Role::whereIn('name', $request->roles)->get();
        $permission->syncRoles($roles);

        return back()->with('success', 'Rechten succesvol toegewezen aan rollen.');
    }

    public function bulkCreate()
    {
        return view('admin.permissions.bulk-create');
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'module' => 'required|string|max:100',
            'actions' => 'required|array',
            'actions.*' => 'in:view,create,edit,delete,approve,schedule,send'
        ]);

        $module = $request->module;
        $actions = $request->actions;
        $createdPermissions = [];

        foreach ($actions as $action) {
            $permissionName = $action . '-' . $module;
            
            if (!Permission::where('name', $permissionName)->exists()) {
                $permission = Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'description' => ucfirst($action) . ' ' . ucfirst($module),
                    'group' => $module
                ]);
                $createdPermissions[] = $permission;
            }
        }

        $message = count($createdPermissions) > 0 
            ? count($createdPermissions) . ' rechten succesvol aangemaakt.'
            : 'Geen nieuwe rechten aangemaakt (alleen bestaande rechten gevonden).';

        return redirect()->route('admin.permissions.index')
            ->with('success', $message);
    }
}
