<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    use TenantFilter;
    
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-users')) {
            abort(403, 'Je hebt geen rechten om gebruikers te bekijken.');
        }
        
        $query = User::with(['company', 'roles']);
        $this->applyTenantFilter($query);
        
        // Filter op rol
        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }
        
        // Filter op bedrijf
        if ($request->filled('company')) {
            $query->where('company_id', $request->company);
        }
        
        // Filter op status (actief/inactief)
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->status === 'inactive') {
                $query->whereNull('email_verified_at');
            }
        }
        
        // Sortering
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('order', 'desc');
        
        if ($sortField === 'roles') {
            // Speciale sortering voor rollen - sorteren op de eerste rol naam
            $query->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                  ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
                  ->orderBy('roles.name', $sortDirection)
                  ->select('users.*'); // Zorg ervoor dat we alleen user kolommen selecteren
        } else {
            $query->orderBy($sortField, $sortDirection);
        }
        
        // Paginering
        $perPage = $request->get('per_page', 25);
        $users = $query->paginate($perPage);
        
        // Voeg query parameters toe aan pagination links
        $users->appends($request->query());
        
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-users')) {
            abort(403, 'Je hebt geen rechten om gebruikers aan te maken.');
        }
        
        $user = auth()->user();
        
        // Filter bedrijven op basis van gebruiker rechten
        if ($user->hasRole('super-admin')) {
            $companies = Company::all();
        } else {
            $companies = Company::where('id', $user->company_id)->get();
        }
        
        // Filter rollen op basis van gebruiker rechten
        if ($user->hasRole('super-admin')) {
            $roles = Role::all();
        } else {
            $roles = Role::where('name', '!=', 'super-admin')->get();
        }
        
        return view('admin.users.create', compact('companies', 'roles'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-users')) {
            abort(403, 'Je hebt geen rechten om gebruikers aan te maken.');
        }
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'company_id' => 'nullable|exists:companies,id',
            'role' => 'required|string|exists:roles,name',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date'
        ]);

        $userData = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
        ];
        
        // Als Super Admin en tenant geselecteerd, gebruik die tenant
        if (auth()->user()->hasRole('super-admin') && session('selected_tenant')) {
            $userData['company_id'] = session('selected_tenant');
        } else {
            $userData['company_id'] = $request->company_id;
        }
        
        $user = User::create($userData);

        $user->assignRole($request->role);

        return redirect()->route('admin.users.index')->with('success', 'Gebruiker succesvol aangemaakt.');
    }

    public function show(User $user)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-users')) {
            abort(403, 'Je hebt geen rechten om gebruikers te bekijken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($user)) {
            abort(403, 'Je hebt geen toegang tot deze gebruiker.');
        }
        
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-users')) {
            abort(403, 'Je hebt geen rechten om gebruikers te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($user)) {
            abort(403, 'Je hebt geen toegang tot deze gebruiker.');
        }
        
        $currentUser = auth()->user();
        
        // Filter bedrijven op basis van gebruiker rechten
        if ($currentUser->hasRole('super-admin')) {
            $companies = Company::all();
        } else {
            $companies = Company::where('id', $currentUser->company_id)->get();
        }
        
        // Filter rollen op basis van gebruiker rechten
        if ($currentUser->hasRole('super-admin')) {
            $roles = Role::all();
        } else {
            $roles = Role::where('name', '!=', 'super-admin')->get();
        }
        
        return view('admin.users.edit', compact('user', 'companies', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-users')) {
            abort(403, 'Je hebt geen rechten om gebruikers te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($user)) {
            abort(403, 'Je hebt geen toegang tot deze gebruiker.');
        }
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8',
            'company_id' => 'nullable|exists:companies,id',
            'role' => 'required|string|exists:roles,name',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date'
        ]);

        $userData = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
        ];
        
        // Als Super Admin en tenant geselecteerd, gebruik die tenant
        if (auth()->user()->hasRole('super-admin') && session('selected_tenant')) {
            $userData['company_id'] = session('selected_tenant');
        } else {
            $userData['company_id'] = $request->company_id;
        }

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);
        $user->syncRoles([$request->role]);

        return redirect()->route('admin.users.index')->with('success', 'Gebruiker succesvol bijgewerkt.');
    }

    public function destroy(User $user)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-users')) {
            abort(403, 'Je hebt geen rechten om gebruikers te verwijderen.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($user)) {
            abort(403, 'Je hebt geen toegang tot deze gebruiker.');
        }
        
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Je kunt jezelf niet verwijderen.');
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Gebruiker succesvol verwijderd.');
    }

    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array'
        ]);

        $user->syncRoles($request->roles);
        return back()->with('success', 'Rollen succesvol toegewezen.');
    }
}
