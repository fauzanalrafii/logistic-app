<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserRole;
use App\Models\Role;
use App\Models\User;
use Inertia\Inertia;

class AssignRoleController extends Controller
{
    public function index(Request $request)
    {
        $search = strtolower($request->search ?? '');
        $perPage = $request->per_page ?? 10;
        $page = $request->page ?? 1;

        /**
         * 1️⃣ Ambil semua mapping user → role_id
         */
        $userRoles = UserRole::forUser()->get();

        /**
         * 2️⃣ Ambil user_id unik
         */
        $userIds = $userRoles->pluck('model_id')->unique();

        /**
         * 3️⃣ Ambil user dari DB user
         */
        $users = User::on('mysql_user')->whereIn('ID', $userIds)->get();

        /**
         * 4️⃣ Ambil role list
         */
        $roles = Role::pluck('name', 'id');

        /**
         * 5️⃣ Map user → role name
         */
        $assignments = $users->map(function ($u) use ($userRoles, $roles) {
            $roleNames = $userRoles->where('model_id', $u->ID)->pluck('role_id')->map(fn($rid) => $roles[$rid] ?? null)->filter();

            return [
                'id' => $u->ID,
                'name' => $u->UserID,
                'roles' => $roleNames->values(),
            ];
        });

        /**
         * 6️⃣ Search
         */
        if ($search) {
            $assignments = $assignments->filter(fn($item) => str_contains(strtolower($item['name']), $search) || $item['roles']->contains(fn($r) => str_contains(strtolower($r), $search)));
        }

        $total = $assignments->count();

        return Inertia::render('ManageRoles/AssignRoleIndex', [
            'assignments' => [
                'data' => $assignments->forPage($page, $perPage)->values(),
                'total' => $total,
                'per_page' => (int) $perPage,
                'current_page' => (int) $page,
                'last_page' => (int) ceil($total / $perPage),
            ],
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'filters' => $request->all(),
        ]);
    }

    private function paginationLinks($page, $total, $perPage)
    {
        $last = ceil($total / $perPage);
        $links = [];

        for ($i = 1; $i <= $last; $i++) {
            $links[] = [
                'label' => (string) $i,
                'url' => "?page=$i",
                'active' => $i == $page,
            ];
        }

        return $links;
    }

    public function create()
    {
        $users = User::select('ID', 'UserID')->orderBy('UserID')->get()->map(
            fn($u) => [
                'label' => $u->UserID,
                'value' => $u->ID,
            ],
        );

        $roles = Role::orderBy('name')->get(['id', 'name']);

        return Inertia::render('ManageRoles/AssignRoleCreate', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'roles' => 'required|array',
        ]);

        $user = User::findOrFail($request->user_id);

        $user->syncRoles($request->roles);

        return redirect()->route('assign-roles.index')->with('success', 'Role berhasil diassign!');
    }
    
    public function edit($id)
    {
        $user = User::on('mysql_user')->findOrFail($id);

        $assigned = UserRole::forUser()->where('model_id', $id)->pluck('role_id');

        $roles = Role::orderBy('name')->get();

        return Inertia::render('ManageRoles/AssignRoleEdit', [
            'user' => [
                'id' => $user->ID,
                'name' => $user->UserID,
            ],
            'roles' => $roles,
            'assigned' => $assigned,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'roles' => 'required|array',
        ]);

        $user = User::findOrFail($id);
        $user->syncRoles($request->roles);

        return redirect()->route('assign-roles.index')->with('success', 'Roles berhasil diupdate!');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->syncRoles([]);

        return redirect()->route('assign-roles.index')->with('success', 'Roles user berhasil dihapus!');
    }
}
