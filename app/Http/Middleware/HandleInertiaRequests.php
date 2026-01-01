<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ApprovalAction;
use App\Models\ApprovalInstance;
use App\Models\ApprovalStep;
use App\Models\Status;
use App\Models\Project;
use Inertia\Middleware;
use Carbon\Carbon;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        $role = 'Guest';
        if ($user) {
            $role = $user->roles
                ->sortByDesc(fn ($r) => $r->permissions->count())
                ->first()?->name ?? 'Guest';
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user
                    ? [
                        'name'  => $user->Name,
                        'email' => $user->UserID,
                        'id'    => $user->ID,
                        'role'  => $role,
                    ]
                    : null,
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
            ],

            // ✅ FLASH (WAJIB string, bukan closure)
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],

            // fallback lama
            'approval' => [
                'inbox_count' => $this->approvalInboxCount(),
            ],

            // notif dropdown
            'notifications' => $this->notificationsPayload(5),
        ]);
    }

    // ============================================================
    //  APPROVAL: COUNT
    // ============================================================
    private function approvalInboxCount(): int
    {
        $user = auth()->user();
        if (!$user) return 0;

        // ⚠️ kalau kamu pakai vl_users sebagai pivot, ini aman:
        $roleIds = DB::table('vl_users')
            ->where('user_l_ID', $user->ID)
            ->pluck('role_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($roleIds)) return 0;

        $pendingNames = ['PENDING', 'IN_REVIEW', 'SUBMITTED'];

        $instTable   = (new ApprovalInstance())->getTable();
        $statusTable = (new Status())->getTable();
        $actionTable = (new ApprovalAction())->getTable();
        $stepTable   = (new ApprovalStep())->getTable();

        $actionsCountSub = DB::table($actionTable)
            ->selectRaw('approval_instance_id, COUNT(*) AS actions_count')
            ->groupBy('approval_instance_id');

        $count = DB::table($instTable)
            ->join($statusTable, "$statusTable.id", '=', "$instTable.status_id")
            ->leftJoinSub($actionsCountSub, 'ac', function ($join) use ($instTable) {
                $join->on('ac.approval_instance_id', '=', "$instTable.id");
            })
            ->join("$stepTable as st", function ($join) use ($instTable) {
                $join->on('st.approval_flow_id', '=', "$instTable.approval_flow_id")
                    ->whereRaw('st.step_order = COALESCE(ac.actions_count, 0) + 1');
            })
            ->whereIn(DB::raw("UPPER($statusTable.name)"), $pendingNames)
            ->whereIn('st.required_role_id', $roleIds)
            ->distinct("$instTable.id")
            ->count("$instTable.id");

        return (int) $count;
    }

    // ============================================================
    //  NOTIFICATIONS: PAYLOAD (APPROVAL + SLA OVERDUE)
    // ============================================================
    private function notificationsPayload(int $limit = 5): array
    {
        $approval = $this->approvalNotifications($limit);
        $sla = $this->slaOverdueSummaryNotification();

        $items = $approval['items'];
        if ($sla) $items[] = $sla;

        usort($items, function ($a, $b) {
            $ta = !empty($a['time']) ? strtotime($a['time']) : 0;
            $tb = !empty($b['time']) ? strtotime($b['time']) : 0;
            return $tb <=> $ta;
        });

        $count = (int) ($approval['count'] ?? 0) + ($sla ? 1 : 0);

        return [
            'count' => $count,
            'items' => array_slice($items, 0, $limit),
        ];
    }

    // ============================================================
    //  APPROVAL: NOTIF ITEMS
    // ============================================================
    private function approvalNotifications(int $limit = 5): array
    {
        $user = auth()->user();
        if (!$user) return ['count' => 0, 'items' => []];

        $roleIds = DB::table('vl_users')
            ->where('user_l_ID', $user->ID)
            ->pluck('role_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($roleIds)) return ['count' => 0, 'items' => []];

        $pendingNames = ['PENDING', 'IN_REVIEW', 'SUBMITTED'];

        $instTable   = (new ApprovalInstance())->getTable();
        $statusTable = (new Status())->getTable();
        $actionTable = (new ApprovalAction())->getTable();
        $stepTable   = (new ApprovalStep())->getTable();
        $projTable   = (new Project())->getTable();

        $actionsCountSub = DB::table($actionTable)
            ->selectRaw('approval_instance_id, COUNT(*) AS actions_count')
            ->groupBy('approval_instance_id');

        $rows = DB::table($instTable)
            ->join($statusTable, "$statusTable.id", '=', "$instTable.status_id")
            ->join($projTable, "$projTable.id", '=', "$instTable.project_id")
            ->leftJoinSub($actionsCountSub, 'ac', function ($join) use ($instTable) {
                $join->on('ac.approval_instance_id', '=', "$instTable.id");
            })
            ->join("$stepTable as st", function ($join) use ($instTable) {
                $join->on('st.approval_flow_id', '=', "$instTable.approval_flow_id")
                    ->whereRaw('st.step_order = COALESCE(ac.actions_count, 0) + 1');
            })
            ->whereIn(DB::raw("UPPER($statusTable.name)"), $pendingNames)
            ->whereIn('st.required_role_id', $roleIds)
            ->orderByDesc("$instTable.updated_at")
            ->limit($limit)
            ->get([
                "$instTable.id as id",
                "$projTable.name as project_name",
                "st.step_order as step_order",
                "st.name as step_name",
                "$instTable.updated_at as updated_at",
            ]);

        $count = $this->approvalInboxCount();

        $items = $rows->map(function ($r) {
            return [
                'id'       => 'approval:' . $r->id,
                'kind'     => 'approval',
                'title'    => (string) ($r->project_name ?? '-'),
                'subtitle' => 'Menunggu approval • Step ' . ($r->step_order ?? '-') . ' • ' . ($r->step_name ?? '-'),
                'time'     => $r->updated_at,
                'href'     => "/approval/{$r->id}",
            ];
        })->values()->all();

        return [
            'count' => (int) $count,
            'items' => $items,
        ];
    }

    // ============================================================
    //  SLA OVERDUE: SUMMARY
    // ============================================================
    private function slaOverdueSummaryNotification(): ?array
    {
        $user = auth()->user();
        if (!$user) return null;

        $today = Carbon::today();

        $projectTable = (new Project())->getTable();
        $statusTable  = (new Status())->getTable();

        $count = DB::table($projectTable . ' as p')
            ->join($statusTable . ' as st', 'st.id', '=', 'p.status_id')
            ->whereNotNull('p.target_completion_date')
            ->whereDate('p.target_completion_date', '<', $today)
            ->whereRaw("UPPER(st.name) <> 'CLOSED'")
            ->count('p.id');

        if ((int) $count <= 0) return null;

        return [
            'id'       => 'sla_overdue:summary',
            'kind'     => 'sla_overdue',
            'title'    => 'SLA Overdue',
            'subtitle' => "Ada {$count} project melewati target completion",
            'time'     => $today->toDateTimeString(),
            'href'     => '/projects/sla-overdue',
        ];
    }
}
