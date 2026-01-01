<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\AppActivityLoggable;
use App\Traits\Auditable;

class Project extends Model
{
    use Auditable;
    use HasFactory;
    use AppActivityLoggable;

    protected $table = 'vl_projects';

    protected $fillable = [
        'uuid',
        'code',
        'source',
        'oss_reference_id',
        'name',
        'description',
        'location',
        'area',

        // 🔥 SNAPSHOT KODEPOS (WAJIB)
        'kodepos_id',
        'zipcode',
        'province',
        'city',
        'district',
        'sub_district',
        'region',

        'project_type',
        'status_id',
        'planner_id',
        'target_completion_date',
        'is_deleted',
    ];

    public function planner()
    {
        return $this->belongsTo(User::class, 'planner_id', 'ID');
        // foreign key di vl_projects = planner_id
        // owner key di users_l = ID
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(ProjectStatusHistory::class, 'project_id')
            ->orderByDesc('changed_at');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'project_id', 'id');
    }

    public function boqHeaders()
    {
        return $this->hasMany(BoqHeader::class, 'project_id');
    }

    public function bepProjections()
    {
        return $this->hasMany(BepProjection::class, 'project_id');
    }

    public function approvalInstances()
    {
        return $this->hasMany(ApprovalInstance::class, 'project_id');
    }

    /**
     * Get the latest ON_DESK BOQ header
     */
    public function getLatestBoqOnDesk()
    {
        return $this->boqHeaders()
            ->where('type', 'ON_DESK')
            ->latest()
            ->first();
    }

    /**
     * Get the latest BEP projection
     */
    public function getLatestBepProjection()
    {
        return $this->bepProjections()
            ->latest()
            ->first();
    }

    /**
     * Get planning approval instance
     */
    public function getPlanningApprovalInstance()
    {
        return $this->approvalInstances()
            ->where('related_type', 'planning')
            ->latest()
            ->first();
    }

    /**
     * Check if planning is submitted
     */
    public function isPlanningSubmitted(): bool
    {
        $boq = $this->getLatestBoqOnDesk();
        $bep = $this->getLatestBepProjection();

        return ($boq && $boq->isSubmitted()) || ($bep && $bep->isSubmitted());
    }

    /**
     * Get planning approval status
     */
    public function getPlanningApprovalStatus(): string
    {
        $instance = $this->getPlanningApprovalInstance();
        if ($instance) {
            return $instance->getProgressLabel();
        }

        // Fallback: check if BOQ/BEP is already submitted without approval instance (legacy)
        if ($this->isPlanningSubmitted()) {
            return 'Submitted (Pending)';
        }

        return 'Belum Submit';
    }
    /**
     * Scope for applying global search
     */
    public function scopeApplyGlobalSearch($query, $search)
    {
        if (!$search) {
            return $query;
        }

        $search = strtolower($search);
        return $query->where(function ($q) use ($search) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%$search%"])
                ->orWhereRaw('LOWER(code) LIKE ?', ["%$search%"])
                ->orWhereRaw('LOWER(location) LIKE ?', ["%$search%"])
                ->orWhereRaw('LOWER(area) LIKE ?', ["%$search%"])
                ->orWhereRaw('LOWER(project_type) LIKE ?', ["%$search%"])
                ->orWhereRaw('LOWER(description) LIKE ?', ["%$search%"])
                ->orWhereRaw('LOWER(source) LIKE ?', ["%$search%"])
                ->orWhereRaw('LOWER(oss_reference_id) LIKE ?', ["%$search%"])
                ->orWhereRaw('LOWER(target_completion_date) LIKE ?', ["%$search%"])
                ->orWhereHas(
                    'planner',
                    fn($p) =>
                    $p->whereRaw('LOWER(Name) LIKE ?', ["%$search%"])
                )
                ->orWhereHas(
                    'status',
                    fn($s) =>
                    $s->whereRaw('LOWER(name) LIKE ?', ["%$search%"])
                );
        });
    }

    /**
     * Scope for applying common filters
     */
    public function scopeApplyFilters($query, array $filters)
    {
        if (isset($filters['status'])) {
            $query->whereHas('status', fn($q) => $q->where('name', $filters['status']));
        }

        if (isset($filters['area'])) {
            $query->where('area', $filters['area']);
        }

        if (isset($filters['project_type'])) {
            $query->where('project_type', $filters['project_type']);
        }

        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query;
    }

    /**
     * Scope for filtering non-deleted projects
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Check if project can be deleted (only if not yet submitted for approval)
     */
    public function canBeDeleted(): bool
    {
        return !$this->isPlanningSubmitted();
    }
}
