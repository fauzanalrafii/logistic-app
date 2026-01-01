<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Traits\AppActivityLoggable;

class ApprovalStep extends Model
{
    use Auditable;
    use AppActivityLoggable;

    protected $table = 'vl_approval_steps';

    protected $fillable = [
        'uuid',
        'approval_flow_id',
        'step_order',
        'name',
        'required_role_id',
        'sla_hours',
    ];

    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    public function requiredRole()
    {
        return $this->belongsTo(Role::class, 'required_role_id');
    }

    public function actions()
    {
        return $this->hasMany(ApprovalAction::class, 'approval_step_id');
    }
}
