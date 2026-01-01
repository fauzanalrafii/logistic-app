<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Support\Str;
use App\Traits\AppActivityLoggable;

class ApprovalInstance extends Model
{
    use Auditable;
    use AppActivityLoggable;

    protected $table = 'vl_approval_instances';

    protected $fillable = [
        'uuid',
        'approval_flow_id',
        'project_id',
        'related_type',
        'related_id',
        'status_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        // When instance is created, mark the flow as active
        static::created(function ($model) {
            if ($model->flow) {
                $model->flow->update(['is_active' => true]);
            }
        });

        // When instance status changes, sync flow's active status
        static::updated(function ($model) {
            if ($model->isDirty('status_id') && $model->flow) {
                // Check if status changed to APPROVED or REJECTED
                $statusName = $model->status?->name ?? '';
                if (in_array($statusName, ['APPROVED', 'REJECTED'])) {
                    $model->flow->syncActiveStatus();
                }
            }
        });
    }

    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function actions()
    {
        return $this->hasMany(ApprovalAction::class, 'approval_instance_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * Get the current pending step
     */
    public function getCurrentStep(): ?ApprovalStep
    {
        $completedSteps = $this->actions()
            ->where('action', 'APPROVE')
            ->pluck('approval_step_id')
            ->toArray();

        return $this->flow->steps()
            ->whereNotIn('id', $completedSteps)
            ->orderBy('step_order')
            ->first();
    }

    /**
     * Check if there's a next step after the given step ID
     * (used to detect if current step is the last one BEFORE creating the action)
     */
    public function getNextStepAfterCurrent(int $currentStepId): ?ApprovalStep
    {
        $completedSteps = $this->actions()
            ->where('action', 'APPROVE')
            ->pluck('approval_step_id')
            ->toArray();

        // Add the current step to completed (simulating after approval)
        $completedSteps[] = $currentStepId;

        return $this->flow->steps()
            ->whereNotIn('id', $completedSteps)
            ->orderBy('step_order')
            ->first();
    }

    /**
     * Get current step order number
     */
    public function getCurrentStepOrder(): int
    {
        $currentStep = $this->getCurrentStep();
        return $currentStep ? $currentStep->step_order : 0;
    }

    /**
     * Get total steps count
     */
    public function getTotalSteps(): int
    {
        return $this->flow->steps()->count();
    }

    /**
     * Check if all steps are approved
     */
    public function isFullyApproved(): bool
    {
        $statusName = $this->status?->name ?? '';
        return $statusName === 'APPROVED';
    }

    /**
     * Check if rejected
     */
    public function isRejected(): bool
    {
        $statusName = $this->status?->name ?? '';
        return $statusName === 'REJECTED';
    }

    /**
     * Get approval progress as string
     */
    public function getProgressLabel(): string
    {
        if ($this->isFullyApproved()) {
            return 'Approved';
        }
        if ($this->isRejected()) {
            return 'Rejected';
        }

        $currentStep = $this->getCurrentStep();
        if ($currentStep) {
            return "Step {$currentStep->step_order}/{$this->getTotalSteps()}: {$currentStep->name}";
        }

        return 'Pending';
    }
}
