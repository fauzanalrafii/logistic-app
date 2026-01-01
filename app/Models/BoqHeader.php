<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Traits\AppActivityLoggable;
use Illuminate\Support\Str;

class BoqHeader extends Model
{
    use Auditable;
    use AppActivityLoggable;
    protected $table = 'vl_boq_headers';

    protected $fillable = [
        'uuid',
        'project_id',
        'type',
        'version',
        'status_id',
        'total_cost_estimate',
        'created_by',
        'submitted_at',
    ];

    protected $casts = [
        'total_cost_estimate' => 'decimal:2',
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

    public function items()
    {
        return $this->hasMany(BoqItem::class, 'boq_header_id');
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
     * Check if BOQ is submitted
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
     * Calculate total from items
     */
    public function calculateTotal(): float
    {
        return $this->items->sum(function ($item) {
            return $item->qty * $item->unit_price_estimate;
        });
    }
}
