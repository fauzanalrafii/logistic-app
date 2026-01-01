<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; // Pastikan Model User ada method verify()
use Illuminate\Validation\ValidationException; // Penting buat error message di Vue
use Inertia\Inertia; // Wajib import Inertia
use App\Logging\AppLogger; // Logger lama kamu
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    // 1. Tampilkan Halaman Login
    public function create()
    {
        // Ganti 'view' jadi 'Inertia::render'
        return Inertia::render('auth/Login');
    }

    // 2. Proses Login
    public function store(Request $request)
    {
        // Validasi Input
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        // --- LOGIKA LAMA (User::verify) ---
        // Kita pakai method verify punya kamu
        $user = User::verify($request->username, $request->password);

        if (!$user) {
            // LOG: login gagal (tetap pakai logger lama)
            AppLogger::activity('login_failed', [
                'username' => $request->username,
            ], $request);

            // ERROR HANDLING UNTUK INERTIA/VUE
            // Jangan pakai back()->with('error'), tapi pakai ValidationException
            // Supaya teks merah muncul di bawah input username
            throw ValidationException::withMessages([
                'username' => 'Username atau password salah!',
            ]);
        }

        // Use Laravel Auth
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // LOG: login berhasil
        AppLogger::activity('login_success', [
            'user_db_id' => $user->ID,
            'user_name' => $user->Name,
            'user_uid'  => $user->UserID,
        ], $request);

        // Redirect ke dashboard
        return redirect()->intended('/dashboard');
    }

    // 3. Logout
    public function destroy(Request $request)
    {
        // LOG: logout
        AppLogger::activity('logout', [], $request);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect ke halaman login
        return redirect('/login');
    }
}
