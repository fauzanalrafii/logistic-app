<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use App\Traits\Auditable;
use App\Models\User;
use App\Traits\AppActivityLoggable;

class Permission extends SpatiePermission
{
    use Auditable;
    use AppActivityLoggable;
    protected $connection = 'mysql';
    protected $table = 'vl_permissions';
    protected $guard_name = 'web';

    public function users(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        // Specify the pivot table with database name to ensure correct connection usage
        $pivotTable = config('database.connections.mysql.database') ? config('database.connections.mysql.database') . '.vl_model_has_permissions' : 'vl_model_has_permissions';

        return $this->morphedByMany(User::class, 'model', $pivotTable, 'permission_id', 'model_id');
    }
}
