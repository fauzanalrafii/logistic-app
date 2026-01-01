<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class OperationalUser extends Authenticatable
{
    use HasRoles;

    protected $connection = 'mysql';
    protected $table = 'velox_main.users';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'UserID',
        'Name'
    ];

    protected $guard_name = 'web';
}
