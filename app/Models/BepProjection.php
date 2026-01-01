<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Support\Str;
use App\Traits\AppActivityLoggable;

class BepProjection extends Model
{
    use Auditable;
    use AppActivityLoggable;

    protected $table = 'vl_bep_projections';

    protected $fillable = [
        'uuid',
        'project_id',
        'version',
        'capex',
        'opex_estimate',
        'revenue_estimate',
        'bep_months',
        'status_id',
        'created_by',
        'submitted_at',
    ];

    protected $casts = [
        'capex' => 'decimal:2',
        'opex_estimate' => 'decimal:2',
        'revenue_estimate' => 'decimal:2',
        'submitted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'ID');
    }

    /**
     * Check if BEP is submitted
     */
    public function isSubmitted(): bool
    {
        // Check by status name if relationship loaded, otherwise check submitted_at
        if ($this->status) {
            return in_array($this->status->name, ['SUBMITTED', 'PENDING', 'APPROVED']);
        }
        return !empty($this->submitted_at);
    }

    /**
     * Calculate BEP months automatically
     */
    public function calculateBepMonths(): int
    {
        $net = (float) $this->revenue_estimate - (float) $this->opex_estimate;
        if ($net <= 0) {
            return 0;
        }
        return (int) ceil((float) $this->capex / $net);
    }
}
