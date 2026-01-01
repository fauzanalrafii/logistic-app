<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Traits\AppActivityLoggable;
use Illuminate\Support\Str;

class ApprovalAction extends Model
{
    use Auditable;
    use AppActivityLoggable;

    protected $table = 'vl_approval_actions';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'approval_instance_id',
        'approval_step_id',
        'user_id',
        'action',
        'comment',
        'acted_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->acted_at)) {
                $model->acted_at = now();
            }
        });
    }

    public function instance()
    {
        return $this->belongsTo(ApprovalInstance::class, 'approval_instance_id');
    }

    public function step()
    {
        return $this->belongsTo(ApprovalStep::class, 'approval_step_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    /**
     * Check if action is an approval
     */
    public function isApprove(): bool
    {
        return $this->action === 'APPROVE';
    }

    /**
     * Check if action is a rejection
     */
    public function isReject(): bool
    {
        return $this->action === 'REJECT';
    }
}
