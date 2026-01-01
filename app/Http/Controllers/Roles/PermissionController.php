<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->per_page ?? 10;

        $query = Permission::query();

        if ($request->search) {
            $search = strtolower($request->search);
            $query->whereRaw('LOWER(name) LIKE ?', ["%$search%"]);
        }

        $permissions = $query->orderBy('id', 'asc')->paginate($perPage)->through(
            fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ],
        );

        return inertia('ManageRoles/PermissionIndex', [
            'permissions' => $permissions,
            'filters' => $request->all(),
            'currentPage' => 'permissions.index',
        ]);
    }

    public function create()
    {
        return inertia('ManageRoles/PermissionCreate');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:vl_permissions,name',
        ]);

        Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return redirect()->route('permissions.index')->with('success', 'Permission created successfully!');
    }

    public function edit(Permission $permission)
    {
        return inertia('ManageRoles/PermissionEdit', [
            'permission' => $permission,
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $permission->update([
            'name' => $request->name,
        ]);

        return redirect()->route('permissions.index');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        // DB::Connection('mysql')->table('vl_role_has_permissions')->where('permission_id', $permission->id)->delete();

        // DB::Connection('mysql')->table('vl_permissions')->where('id', $permission->id)->delete();
        return redirect()->back();
    }
}
