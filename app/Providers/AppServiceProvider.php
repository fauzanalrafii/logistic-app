<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Inertia::share('auth', function () {
            $user = Auth::user();

            return [
                'user' => $user ? [
                    'id' => $user->getKey(),
                    'name' => $user->Name ?? $user->name ?? '-',
                    'userid' => $user->UserID ?? null,
                ] : null,

                'roles' => $user ? $user->getRoleNames()->values()->toArray() : [],
                'permissions' => $user ? $user->getAllPermissions()->pluck('name')->values()->toArray() : [],
            ];
        });
    }
}
