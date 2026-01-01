<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Traits\AppActivityLoggable;
use Illuminate\Support\Str;

class BoqItem extends Model
{
    use Auditable;
    use AppActivityLoggable;
    protected $table = 'vl_boq_items';

    public $timestamps = false; // This table doesn't have timestamps

    protected $fillable = [
        'uuid',
        'boq_header_id',
        'material_code',
        'material_description',
        'uom',
        'qty',
        'unit_price_estimate',
        'remarks',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'unit_price_estimate' => 'decimal:2',
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

    public function boqHeader()
    {
        return $this->belongsTo(BoqHeader::class, 'boq_header_id');
    }

    /**
     * Get subtotal (qty * unit_price_estimate)
     */
    public function getSubtotalAttribute(): float
    {
        return (float) $this->qty * (float) $this->unit_price_estimate;
    }
}
