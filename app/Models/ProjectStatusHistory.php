<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'vl_project_status_histories';

    protected $fillable = [
        'uuid',
        'project_id',
        'old_status',
        'new_status',
        'changed_by',
        'changed_at',
        'note',
    ];

    public $timestamps = false;

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    // ✅ field ini akan ikut terkirim ke Vue
    protected $appends = [
        'old_status_label',
        'new_status_label',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    // ✅ User kamu pakai ID dan Name
    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by', 'ID');
    }

    // ✅ Relasi default jika data history menyimpan ID
    public function oldStatusData()
    {
        return $this->belongsTo(Status::class, 'old_status', 'id');
    }

    public function newStatusData()
    {
        return $this->belongsTo(Status::class, 'new_status', 'id');
    }

    // ==========================
    // ✅ ACCESSOR LABEL (MIX SAFE)
    // ==========================
    public function getOldStatusLabelAttribute()
    {
        return $this->resolveStatusLabel($this->old_status);
    }

    public function getNewStatusLabelAttribute()
    {
        return $this->resolveStatusLabel($this->new_status);
    }

    private function resolveStatusLabel($value)
    {
        if (!$value) return null;

        // Jika numeric => anggap ID
        if (is_numeric($value)) {
            return Status::where('id', (int) $value)->value('name');
        }

        // Jika string => anggap name
        $name = Status::where('name', $value)->value('name');

        // fallback terakhir: tampilkan nilai aslinya
        return $name ?? $value;
    }
}
