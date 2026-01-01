<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    protected $connection = 'mysql';
    protected $table = 'vl_model_has_roles';

    protected $fillable = [
        'role_id',
        'model_id',
        'model_type',
    ];

    public $timestamps = false;

    public function scopeForUser($query)
    {
        return $query->where('model_type', User::class);
    }
}
