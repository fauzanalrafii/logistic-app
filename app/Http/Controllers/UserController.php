<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redis;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Tampilkan profil user dari Redis.
     */
    public function show(string $id): View
    {
        // Ambil data mentah dari Redis (string JSON)
        $rawUser = Redis::get('user:profile:' . $id);

        // Kalau tidak ada datanya
        if (! $rawUser) {
            abort(404, 'User tidak ditemukan di Redis');
        }

        // Decode JSON ke array/objek
        $user = json_decode($rawUser, true);

        return view('user.profile', [
            'user' => $user,
        ]);
    }
}
