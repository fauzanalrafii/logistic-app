<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function index(): Response
    {
        $user = Auth::user();

        // Get user roles with their specific permissions
        $rolesWithPermissions = [];
        if (method_exists($user, 'roles')) {
            $user->load(['roles.permissions']);
            $rolesWithPermissions = $user->roles->map(function ($role) {
                return [
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->toArray()
                ];
            });
        }

        // Keep all permissions for referenced if needed, or just handle direct permissions
        $allPermissions = method_exists($user, 'getAllPermissions')
            ? $user->getAllPermissions()->pluck('name')->toArray()
            : [];

        return Inertia::render('Profile/Index', [
            'profile' => [
                'id' => $user->getKey(),
                'user_id' => $user->UserID ?? null,
                'name' => $user->Name ?? $user->name ?? '-',
                'position' => $user->Position ?? 'Guest',
                'location' => $user->Location ?? '-',
                'email' => $user->Email ?? '-',
            ],
            'roles' => $rolesWithPermissions,
            'permissions' => $allPermissions,
            'sessions' => $this->getSessions($user->getAuthIdentifier()),
        ]);
    }

    /**
     * Get user active sessions.
     */
    private function getSessions($userId)
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        $sessions = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $userId)
            ->orderBy('last_activity', 'desc')
            ->get();

        return collect($sessions)->map(function ($session) {
            return (object) [
                'agent' => $this->createAgent($session),
                'ip_address' => $session->ip_address,
                'is_current_device' => $session->id === session()->getId(),
                'last_active' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
            ];
        })->toArray();
    }

    /**
     * Create a human-readable agent string.
     */
    private function createAgent($session)
    {
        $agent = $session->user_agent;
        $os = 'Unknown OS';
        $browser = 'Unknown Browser';
        $icon = 'Monitor'; // Default to desktop

        if (preg_match('/Windows/i', $agent)) {
            $os = 'Windows';
        } elseif (preg_match('/Macintosh/i', $agent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $agent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/i', $agent)) {
            $os = 'Android';
            $icon = 'Smartphone';
        } elseif (preg_match('/iPhone|iPad/i', $agent)) {
            $os = 'iOS';
            $icon = 'Smartphone';
        }

        if (preg_match('/Edg|Edge/i', $agent)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome/i', $agent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox/i', $agent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/i', $agent)) {
            $browser = 'Safari';
        }

        return (object) [
            'os' => $os,
            'browser' => $browser,
            'icon' => $icon,
            'raw' => $agent
        ];
    }
}
