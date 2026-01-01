<?php

namespace App\Logging;

use App\Jobs\WriteDailyJsonLogJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class AppLogger
{
    /**
     * Logging aktivitas umum aplikasi.
     * - Jika QUEUE_CONNECTION=sync => langsung tulis file (tanpa worker)
     * - Jika QUEUE_CONNECTION=rabbitmq/database/redis => dispatch job sesuai default connection
     */
    public static function activity(string $action, array $extra = [], ?Request $request = null): void
    {
        $request = $request ?? request();

        $authUser    = Auth::user();
        $sessionUser = Session::get('user');

        $userId = $userUid = $userName = null;

        if ($authUser) {
            $userId   = $authUser->ID ?? $authUser->id ?? null;
            $userUid  = $authUser->UserID ?? null;
            $userName = $authUser->Name ?? $authUser->name ?? null;
        } elseif (is_array($sessionUser)) {
            $userId   = $sessionUser['id'] ?? null;
            $userUid  = $sessionUser['uid'] ?? null;
            $userName = $sessionUser['name'] ?? null;
        }

        $base = [
            'action'     => $action,
            'user_id'    => $userId,
            'user_uid'   => $userUid,
            'user_name'  => $userName,
            'ip'         => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url'        => $request?->fullUrl(),
            'method'     => $request?->method(),
            'time'       => now()->toDateTimeString(),
        ];

        $data = array_merge($base, $extra);

        $queue = env('LOG_QUEUE', 'logs');            // queue khusus log (opsional)
        $conn  = config('queue.default');             // ini ngikut QUEUE_CONNECTION

        try {
            // ✅ kalau sync, langsung tulis ke file (tanpa worker)
            if ($conn === 'sync') {
                (new WriteDailyJsonLogJob('info', 'app_activity', $data))->handle();
                return;
            }

            // ✅ selain sync => dispatch job via default connection (.env)
            WriteDailyJsonLogJob::dispatch('info', 'app_activity', $data)
                ->onQueue($queue);

        } catch (\Throwable $e) {
            // fallback: tetap tulis langsung biar nggak hilang
            Log::channel('daily_json')->error('app_activity_fallback', [
                'reason'   => $e->getMessage(),
                'payload'  => $data,
            ]);
        }
    }

    public static function debug(string $message, array $context = []): void
    {
        $queue = env('LOG_QUEUE', 'logs');
        $conn  = config('queue.default');

        try {
            if ($conn === 'sync') {
                (new WriteDailyJsonLogJob('debug', $message, $context))->handle();
                return;
            }

            WriteDailyJsonLogJob::dispatch('debug', $message, $context)
                ->onQueue($queue);

        } catch (\Throwable $e) {
            Log::channel('daily_json')->debug($message, $context);
        }
    }
}
