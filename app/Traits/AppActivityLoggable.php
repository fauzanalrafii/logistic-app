<?php

namespace App\Traits;

use App\Logging\AppLogger;

trait AppActivityLoggable
{
    public static function bootAppActivityLoggable(): void
    {
        static::created(function ($model) {
            AppLogger::activity(
                static::buildActionName($model, 'created'),
                static::buildPayload($model)
            );
        });

        static::updated(function ($model) {
            AppLogger::activity(
                static::buildActionName($model, 'updated'),
                static::buildPayload($model, true)
            );
        });

        static::deleted(function ($model) {
            AppLogger::activity(
                static::buildActionName($model, 'deleted'),
                static::buildPayload($model)
            );
        });
    }

    protected static function buildActionName($model, string $event): string
    {
        $base = (property_exists($model, 'activityLogName') && !empty($model->activityLogName))
            ? $model->activityLogName
            : strtolower(class_basename($model));

        return "{$base}_{$event}";
    }

    protected static function buildPayload($model, bool $onlyChanges = false): array
    {
        $id = $model->getKey();

        return [
            'model'      => get_class($model),
            'table'      => $model->getTable(),
            'record_id'  => $id,
            'attributes' => $onlyChanges ? $model->getChanges() : $model->getAttributes(),
        ];
    }
}
