<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckLogin
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('api/*')) {
            return $next($request);
        }
        
        $isLoggedIn = Auth::check();

        if ($isLoggedIn && $request->is('login')) {
            return redirect()->route('dashboard');
        }

        if (!$isLoggedIn && !$request->is('login') && !$request->is('login/*')) {
            return redirect()->route('login')
                ->with('error', 'Silakan login terlebih dahulu!');
        }

        return $next($request);
    }
}
