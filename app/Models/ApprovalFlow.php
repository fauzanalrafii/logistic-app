<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Traits\AppActivityLoggable;

class ApprovalFlow extends Model
{
    use Auditable;
    use AppActivityLoggable;

    protected $table = 'vl_approval_flows';

    protected $fillable = [
        'uuid',
        'name',
        'process_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function steps()
    {
        return $this->hasMany(ApprovalStep::class, 'approval_flow_id')
            ->orderBy('step_order');
    }

    public function instances()
    {
        return $this->hasMany(ApprovalInstance::class, 'approval_flow_id');
    }

    /**
     * Get approval flow by process type
     * Note: is_active filter removed - workflow should always be available
     */
    public static function getByProcessType(string $processType): ?self
    {
        return static::where('process_type', $processType)->first();
    }

    /**
     * Check if this flow has any pending (not yet completed) instances
     */
    public function hasPendingInstances(): bool
    {
        return $this->instances()
            ->whereHas('status', function ($q) {
                // Instance is pending if status is NOT APPROVED and NOT REJECTED
                $q->whereNotIn('name', ['APPROVED', 'REJECTED']);
            })
            ->whereHas('project', fn($q) => $q->where('is_deleted', false))
            ->exists();
    }

    /**
     * Sync is_active status based on pending instances
     */
    public function syncActiveStatus(): void
    {
        $this->is_active = $this->hasPendingInstances();
        $this->saveQuietly(); // Use saveQuietly to avoid triggering events
    }
}
