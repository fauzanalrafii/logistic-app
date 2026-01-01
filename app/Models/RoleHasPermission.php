<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Traits\Auditable;
use App\Traits\AppActivityLoggable;

class RoleHasPermission extends Pivot
{
    use Auditable;
    use AppActivityLoggable;

    protected $table = 'vl_role_has_permissions';

    public $timestamps = false; // Pivot tables usually don't have timestamps unless added manually

    protected $fillable = [
        'permission_id',
        'role_id',
    ];
}
