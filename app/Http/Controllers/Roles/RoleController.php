<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
class RoleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->per_page ?? 10;

        $query = Role::with('permissions');

        // GLOBAL SEARCH
        if ($request->search) {
            $search = strtolower($request->search);

            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%$search%"])->orWhereHas('permissions', function ($perm) use ($search) {
                    $perm->whereRaw('LOWER(name) LIKE ?', ["%$search%"]);
                });
            });
        }

        // FILTER BY PERMISSION
        if ($request->permission) {
            $query->whereHas('permissions', function ($q) use ($request) {
                $q->where('name', $request->permission);
            });
        }

        // PAGINATION
        $roles = $query->orderBy('created_at', 'asc')->paginate($perPage);

        // MODIFY OUTPUT → only send permission names (string array)
        $roles->getCollection()->transform(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ];
        });

        return Inertia::render('ManageRoles/RoleIndex', [
            'roles' => $roles,
            'permissions' => Permission::orderBy('name')->get(['id', 'name']),
            'filters' => $request->all(),
            'currentPage' => 'roles.index',
        ]);
    }

    /**
     * Show the form for creating a new role
     */
    public function create()
    {
        $permissions = Permission::all()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
            ];
        });

        return Inertia::render('ManageRoles/RoleCreate', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:vl_roles,name',
            'permissions' => 'array|nullable',
            'permissions.*' => 'string',
        ]);

        // Create role
        $role = Role::create(['name' => $validated['name']]);

        // Sync permissions if provided
        if (!empty($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return redirect()->route('roles.index')->with('success', 'Role created successfully');
    }

    /**
     * Show the form for editing the specified role
     */
    public function edit(Role $role)
    {
        $role->load('permissions');

        $permissions = Permission::all()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
            ];
        });

        return Inertia::render('ManageRoles/RoleEdit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ],
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update the specified role in storage
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:vl_roles,name,' . $role->id,
            'permissions' => 'array|nullable',
            'permissions.*' => 'string',
        ]);

        $role->update(['name' => $validated['name']]);

        // Sync permissions if provided
        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return redirect()->route('roles.index')->with('success', 'Role updated successfully');
    }

    /**
     * Remove the specified role from storage
     */
    public function destroy(Role $role)
    {
        $role->delete();
        // DB::Connection('mysql')->table('vl_users')->where('role_id', $role->id)->delete();

        // DB::Connection('mysql')->table('vl_roles')->where('id', $role->id)->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully');
    }
}
