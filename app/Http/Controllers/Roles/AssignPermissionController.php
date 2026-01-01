<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserPermission;
use App\Models\User;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class AssignPermissionController extends Controller
{
    public function index(Request $request)
    {
        $search  = strtolower($request->search ?? '');
        $perPage = $request->per_page ?? 10;
        $page    = $request->page ?? 1;

        $userPermissions = UserPermission::forUser()->get();
        $userIds = $userPermissions->pluck('model_id')->unique();

        $users = User::on('mysql_user')
            ->whereIn('ID', $userIds)
            ->get();

        $permissions = Permission::pluck('name', 'id');

        $assignments = $users->map(function ($u) use ($userPermissions, $permissions) {
            $permissionNames = $userPermissions
                ->where('model_id', $u->ID)
                ->pluck('permission_id')
                ->map(fn ($pid) => $permissions[$pid] ?? null)
                ->filter();

            return [
                'id' => $u->ID,
                'name' => $u->UserID,
                'permissions' => $permissionNames->values(),
            ];
        });

        if ($search) {
            $assignments = $assignments->filter(fn ($item) =>
                str_contains(strtolower($item['name']), $search) ||
                $item['permissions']->contains(
                    fn ($p) => str_contains(strtolower($p), $search)
                )
            );
        }

        $total = $assignments->count();

        return Inertia::render('ManageRoles/AssignPermissionIndex', [
            'assignments' => [
                'data' => $assignments->forPage($page, $perPage)->values(),
                'total' => $total,
                'per_page' => (int) $perPage,
                'current_page' => (int) $page,
                'last_page' => (int) ceil($total / $perPage),
            ],
            'filters' => $request->all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('ManageRoles/AssignPermissionCreate', [
            'users' => User::on('mysql_user')
                ->select('ID', 'UserID')
                ->orderBy('UserID')
                ->get()
                ->map(fn ($u) => [
                    'label' => $u->UserID,
                    'value' => $u->ID,
                ]),
            'permissions' => Permission::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'permissions' => 'required|array',
        ]);

        $user = User::on('mysql_user')->findOrFail($request->user_id);
        $user->syncPermissions($request->permissions);

        return redirect()->route('assign-permissions.index');
    }

    public function edit($id)
    {
        return Inertia::render('ManageRoles/AssignPermissionEdit', [
            'user' => User::on('mysql_user')->findOrFail($id),
            'permissions' => Permission::orderBy('name')->get(),
            'assigned' => UserPermission::forUser()
                ->where('model_id', $id)
                ->pluck('permission_id'),
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['permissions' => 'required|array']);

        $user = User::on('mysql_user')->findOrFail($id);
        $user->syncPermissions($request->permissions);

        return redirect()->route('assign-permissions.index');
    }

    public function destroy($id)
    {
        User::on('mysql_user')->findOrFail($id)->syncPermissions([]);
        return back();
    }
}
