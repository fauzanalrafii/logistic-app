<?php

namespace App\Traits;

use App\Jobs\StoreAuditLogJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function (Model $model) {
            self::logAudit('CREATE', $model);
        });

        static::updated(function (Model $model) {
            self::logAudit('UPDATE', $model);
        });

        static::deleted(function (Model $model) {
            self::logAudit('DELETE', $model);
        });
    }

    protected static function logAudit(string $action, Model $model): void
    {
        try {
            $oldValues = [];
            $newValues = [];

            $ignoredColumns = ['created_at', 'updated_at', 'deleted_at'];

            if ($action === 'CREATE') {
                $newValues = $model->makeHidden($ignoredColumns)->toArray();
            } elseif ($action === 'DELETE') {
                $oldValues = $model->makeHidden($ignoredColumns)->toArray();
            } elseif ($action === 'UPDATE') {
                $oldValues = Arr::except($model->getOriginal(), $ignoredColumns);
                $newValues = $model->makeHidden($ignoredColumns)->toArray();
            }

            // ⚠️ Kamu pakai user key "ID", jadi jangan pakai Auth::id() mentah
            $authUser = Auth::user();
            $userId = null;

            if ($authUser) {
                // prioritas field ID milikmu
                $userId = $authUser->ID ?? $authUser->getAuthIdentifier();
            }

            // request() bisa tidak ada kalau dipanggil dari CLI/seeder
            $ip = null;
            $ua = null;
            try {
                $ip = request()?->ip();
                $ua = request()?->userAgent();
            } catch (\Throwable $e) {
                $ip = null;
                $ua = null;
            }

            $payload = [
                'user_id'    => $userId,
                'action'     => $action,
                'table_name' => preg_replace('/^.*\./', '', $model->getTable()),
                'record_id'  => (string) $model->getKey(),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'created_at' => now(), // biar timestamp tetap konsisten
            ];

            /**
             * ✅ Penting:
             * dispatch hanya setelah transaksi sukses commit.
             * Kalau rollback, job tidak akan pernah dikirim.
             */
            DB::afterCommit(function () use ($payload) {
                try {
                    StoreAuditLogJob::dispatch($payload)
                        ->onConnection('rabbitmq')
                        ->onQueue('default');
                } catch (\Throwable $e) {
                    Log::error("GAGAL DISPATCH AUDIT LOG JOB: " . $e->getMessage());
                }
            });
        } catch (\Throwable $e) {
            Log::error("GAGAL SIAPKAN AUDIT LOG: " . $e->getMessage());
        }
    }
}
