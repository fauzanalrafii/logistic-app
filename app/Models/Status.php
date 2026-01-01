<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Status extends Model
{
    protected $table = 'vl_status';

    protected $fillable = [
        'no',
        'name',
        'type',
        'description',
    ];

    // biar enak dipanggil: Status::document()->get()
    public function scopeDocument($query)
    {
        return $query->where('type', 'document');
    }

    // Scope for project statuses
    public function scopeProject($query)
    {
        return $query->where('type', 'project');
    }
}
