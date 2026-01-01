<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use App\Traits\Auditable;
use App\Models\User;
use App\Traits\AppActivityLoggable;

class Role extends SpatieRole
{
    use Auditable;
    use AppActivityLoggable;

    protected $connection = 'mysql';
    protected $table = 'vl_roles';
    protected $guard_name = 'web';

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        // Specify the pivot table with database name to ensure correct connection usage
        $pivotTable = config('database.connections.mysql.database') ? config('database.connections.mysql.database') . '.vl_users' : 'vl_users';

        return $this->belongsToMany(
            User::class,
            $pivotTable, // Pivot table with DB prefix
            'role_id', // Foreign key on pivot table for Role
            'user_l_ID', // Foreign key on pivot table for User
        );
    }
}
