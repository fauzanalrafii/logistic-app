<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $statuses = Cache::remember('audit_statuses', 3600, function () {
            return DB::table('vl_status')->pluck('name', 'id');
        });

        $planners = Cache::remember('audit_planners', 3600, function () {
            return User::whereNotNull('Name')->pluck('Name', 'ID');
        });

        $targetOptions = Cache::remember('audit_targets', 3600, function () {
            return AuditLog::select('table_name')->distinct()->whereNotNull('table_name')->pluck('table_name');
        });

        $actionOptions = Cache::remember('audit_actions', 3600, function () {
            return AuditLog::select('action')->distinct()->whereNotNull('action')->pluck('action');
        });

        $projects = Cache::remember('audit_projects', 300, function () {
            return DB::table('vl_projects')->pluck('name', 'id');
        });

        $roles = Cache::remember('audit_roles', 3600, function () {
            return Role::pluck('name', 'id');
        });


        $permissions = Cache::remember('audit_permissions', 3600, function () {
            return Permission::pluck('name', 'id');
        });


        $query = AuditLog::with('user');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('target')) {
            $query->where('table_name', $request->target);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $keywords = explode(' ', strtolower($request->search));
            $keywords = array_filter($keywords, fn($value) => !is_null($value) && $value !== '');

            $query->where(function ($mainQuery) use ($keywords, $statuses, $planners, $projects) {
                foreach ($keywords as $word) {
                    $mainQuery->where(function ($subQuery) use ($word, $statuses, $planners, $projects) {

                        // 1. Cari Text Biasa
                        $subQuery->orWhere('record_id', 'like', "%{$word}%")
                            ->orWhere('table_name', 'like', "%{$word}%")
                            ->orWhere('action', 'like', "%{$word}%");

                        // 2. Cari di JSON
                        $subQuery->orWhere('old_values', 'like', "%{$word}%")
                            ->orWhere('new_values', 'like', "%{$word}%");

                        // 3. Cari User
                        $subQuery->orWhereHas('user', function ($userQuery) use ($word) {
                            $userQuery->where('Name', 'like', "%{$word}%")
                                ->orWhere('UserID', 'like', "%{$word}%");
                        });

                        // 4. Cari STATUS ID
                        $matchingStatusIds = $statuses->filter(function ($name) use ($word) {
                            return str_contains(strtolower($name), $word);
                        })->keys()->toArray();

                        if (!empty($matchingStatusIds)) {
                            foreach ($matchingStatusIds as $id) {
                                $subQuery->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(new_values, '$.status_id')) = ?", [(string)$id])
                                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(old_values, '$.status_id')) = ?", [(string)$id]);
                            }
                        }

                        // 5. Cari PLANNER ID
                        $matchingPlannerIds = $planners->filter(function ($name) use ($word) {
                            return str_contains(strtolower($name), $word);
                        })->keys()->toArray();

                        if (!empty($matchingPlannerIds)) {
                            foreach ($matchingPlannerIds as $id) {
                                $subQuery->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(new_values, '$.planner_id')) = ?", [(string)$id])
                                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(old_values, '$.planner_id')) = ?", [(string)$id]);
                            }
                        }

                        // 6. Cari PROJECT ID
                        $matchingProjectIds = $projects->filter(function ($name) use ($word) {
                            return str_contains(strtolower($name), $word);
                        })->keys()->toArray();

                        if (!empty($matchingProjectIds)) {
                            foreach ($matchingProjectIds as $id) {
                                $subQuery->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(new_values, '$.project_id')) = ?", [(string)$id])
                                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(old_values, '$.project_id')) = ?", [(string)$id]);
                            }
                        }
                    });
                }
            });
        }

        $logs = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('AuditLogs/Index', [
            'logs'          => $logs,
            'filters'       => $request->only(['search', 'action', 'target', 'date_from', 'date_to']),
            'statuses'      => $statuses,
            'targetOptions' => $targetOptions,
            'actionOptions' => $actionOptions,
            'planners'      => $planners,
            'projects'      => $projects,
            'roles'         => $roles,
            'permissions'   => $permissions,
        ]);
    }
}
