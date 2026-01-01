<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $u = Auth::user();

        $name = $u->name ?? $u->Name ?? 'User';

        // ✅ ambil email dari kolom manapun yang ada di tabel user kamu
        $email = $u->email ?? $u->Email ?? $u->EMAIL ?? '-';

        // ✅ role (kalau kamu punya role di props, sesuaikan)
        $role = $u->role ?? $u->Role ?? 'User';

        return Inertia::render('settings/index', [
            'userInfo' => [
                'name'  => $name,
                'email' => $email,
                'role'  => $role,
            ],
        ]);
    }
}
