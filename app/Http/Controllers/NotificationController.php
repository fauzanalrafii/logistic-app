<?php

namespace App\Http\Controllers;

use App\Models\ApprovalInstance;
use App\Models\Project;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $limit = (int) ($request->get('limit', 50));
        $limit = $limit > 0 ? min($limit, 200) : 50;

        $items = [];

        // SLA selalu tampil untuk semua role (yang login)
        $items = array_merge($items, $this->slaOverdueNotifications($user, $limit));

        // Approval hanya untuk role yang punya approval.view
        if ($user && $user->can('approval.view')) {
            $items = array_merge($items, $this->approvalNotifications($user, $limit));
            // SLA Approval notifications (H-4 jam warning & overdue)
            $items = array_merge($items, $this->approvalSlaNotifications($user, $limit));
        }

        return Inertia::render('notifications/Index', [
            'items' => $items,
        ]);
    }

    private function approvalNotifications($user, int $limit = 50): array
    {
        if (!$user) return [];

        // ✅ konsisten: ambil role ids dari relasi roles (seperti di middleware kamu)
        $roleIds = $user->roles()
            ->pluck('vl_roles.id')
            ->map(fn($v) => (int) $v)
            ->values()
            ->all();

        if (empty($roleIds)) return [];

        $instTable    = (new ApprovalInstance())->getTable(); // vl_approval_instances
        $statusTable  = (new Status())->getTable();           // vl_status
        $projectTable = (new Project())->getTable();          // vl_projects

        $actionTable = 'vl_approval_actions';
        $stepTable   = 'vl_approval_steps';

        $pendingNames = ['PENDING', 'IN_REVIEW', 'SUBMITTED'];

        $actionsCountSub = DB::table($actionTable)
            ->selectRaw('approval_instance_id, COUNT(*) AS actions_count')
            ->groupBy('approval_instance_id');

        $rows = DB::table($instTable . ' as ai')
            ->join($statusTable . ' as st', 'st.id', '=', 'ai.status_id')
            ->join($projectTable . ' as p', 'p.id', '=', 'ai.project_id')
            ->leftJoinSub($actionsCountSub, 'ac', function ($join) {
                $join->on('ac.approval_instance_id', '=', 'ai.id');
            })
            ->join($stepTable . ' as step', function ($join) {
                $join->on('step.approval_flow_id', '=', 'ai.approval_flow_id')
                    ->whereRaw('step.step_order = COALESCE(ac.actions_count, 0) + 1');
            })
            ->whereIn(DB::raw('UPPER(st.name)'), $pendingNames)
            ->whereIn('step.required_role_id', $roleIds)
            ->orderByDesc(DB::raw('COALESCE(ai.started_at, ai.created_at)'))
            ->limit($limit)
            ->select([
                'ai.id',
                'ai.related_type',
                'ai.started_at',
                'ai.created_at',
                'p.code as project_code',
                'p.name as project_name',
                'step.step_order',
                'step.name as step_name',
            ])
            ->get();

        return $rows->map(function ($r) {
            $type = strtoupper((string) ($r->related_type ?? 'APPROVAL'));
            $typeLabel = $type === 'PLANNING' ? 'Planning' : 'Approval';

            $projectLine = trim(($r->project_code ?? '-') . ' — ' . ($r->project_name ?? '-'));

            return [
                'id'    => (int) $r->id,
                'kind'  => 'approval',

                // ✅ ini yang jadi teks utama
                'title' => $projectLine,

                // ✅ teks kecil penjelas
                'desc'  => $typeLabel . ' menunggu tindakan Anda',

                'meta'  => 'Step ' . ($r->step_order ?? '-') . ' • ' . ($r->step_name ?? '-'),

                'time'  => $r->started_at ?? $r->created_at ?? null,

                'href'  => '/approval/' . $r->id,
                'severity' => 'warning',
            ];
        })->values()->all();
    }

    /**
     * SLA Notifications for Approval Steps
     * - H-4 jam sebelum deadline: warning
     * - Overdue: danger (with recurring 24-hour reminders)
     */
    private function approvalSlaNotifications($user, int $limit = 50): array
    {
        if (!$user) return [];

        $roleIds = $user->roles()
            ->pluck('vl_roles.id')
            ->map(fn($v) => (int) $v)
            ->values()
            ->all();

        if (empty($roleIds)) return [];

        $instTable    = (new ApprovalInstance())->getTable();
        $statusTable  = (new Status())->getTable();
        $projectTable = (new Project())->getTable();
        $actionTable  = 'vl_approval_actions';
        $stepTable    = 'vl_approval_steps';

        $pendingNames = ['PENDING', 'IN_REVIEW', 'SUBMITTED'];

        $actionsCountSub = DB::table($actionTable)
            ->selectRaw('approval_instance_id, COUNT(*) AS actions_count')
            ->groupBy('approval_instance_id');

        // Query approvals with SLA
        $rows = DB::table($instTable . ' as ai')
            ->join($statusTable . ' as st', 'st.id', '=', 'ai.status_id')
            ->join($projectTable . ' as p', 'p.id', '=', 'ai.project_id')
            ->leftJoinSub($actionsCountSub, 'ac', function ($join) {
                $join->on('ac.approval_instance_id', '=', 'ai.id');
            })
            ->join($stepTable . ' as step', function ($join) {
                $join->on('step.approval_flow_id', '=', 'ai.approval_flow_id')
                    ->whereRaw('step.step_order = COALESCE(ac.actions_count, 0) + 1');
            })
            ->whereIn(DB::raw('UPPER(st.name)'), $pendingNames)
            ->whereIn('step.required_role_id', $roleIds)
            ->whereNotNull('step.sla_hours')
            ->whereNotNull('ai.started_at')
            ->limit($limit)
            ->select([
                'ai.id',
                'ai.started_at',
                'p.code as project_code',
                'p.name as project_name',
                'step.step_order',
                'step.name as step_name',
                'step.sla_hours',
            ])
            // Add last action timestamp for step start calculation
            ->selectSub(
                DB::table('vl_approval_actions')
                    ->whereColumn('approval_instance_id', 'ai.id')
                    ->orderByDesc('acted_at')
                    ->limit(1)
                    ->select('acted_at'),
                'last_action_at'
            )
            ->get();

        $now = now();
        $results = [];

        foreach ($rows as $r) {
            // Step start time: for step 1 use started_at, for step 2+ use last action
            $stepStartedAt = $r->last_action_at
                ? \Carbon\Carbon::parse($r->last_action_at)
                : \Carbon\Carbon::parse($r->started_at);
            $deadline = $stepStartedAt->copy()->addHours($r->sla_hours);
            $hoursRemaining = $now->diffInHours($deadline, false);

            $projectLine = trim(($r->project_code ?? '-') . ' — ' . ($r->project_name ?? '-'));

            // Only show if overdue OR within 4 hours of deadline
            if ($hoursRemaining > 4) {
                continue;
            }

            if ($hoursRemaining < 0) {
                // OVERDUE
                $totalMinutes = abs($now->diffInMinutes($deadline));
                $days = floor($totalMinutes / 1440);
                $hours = floor(($totalMinutes % 1440) / 60);
                $minutes = $totalMinutes % 60;

                $timeStr = '';
                if ($days > 0) $timeStr .= $days . ' hari ';
                if ($hours > 0) $timeStr .= $hours . ' jam ';
                if ($minutes > 0 && $days == 0) $timeStr .= $minutes . ' menit';
                $timeStr = trim($timeStr) ?: '0 menit';

                $results[] = [
                    'id'       => 'sla-approval:' . $r->id,
                    'kind'     => 'approval_sla_overdue',
                    'title'    => $projectLine,
                    'desc'     => 'Approval SLA Overdue!',
                    'meta'     => 'Lewat ' . $timeStr . ' • Step ' . ($r->step_order ?? '-'),
                    'time'     => $deadline->toDateTimeString(),
                    'href'     => '/approval/' . $r->id,
                    'severity' => 'danger',
                ];
            } else {
                // WARNING: H-4 jam
                $totalMinutes = $now->diffInMinutes($deadline);
                $hours = floor($totalMinutes / 60);
                $minutes = $totalMinutes % 60;

                $timeStr = '';
                if ($hours > 0) $timeStr .= $hours . ' jam ';
                if ($minutes > 0) $timeStr .= $minutes . ' menit';
                $timeStr = trim($timeStr) ?: '0 menit';

                $results[] = [
                    'id'       => 'sla-warning:' . $r->id,
                    'kind'     => 'approval_sla_warning',
                    'title'    => $projectLine,
                    'desc'     => 'Deadline segera!',
                    'meta'     => $timeStr . ' lagi • Step ' . ($r->step_order ?? '-'),
                    'time'     => $deadline->toDateTimeString(),
                    'href'     => '/approval/' . $r->id,
                    'severity' => 'warning',
                ];
            }
        }

        return $results;
    }

    private function slaOverdueNotifications($user, int $limit = 50): array
    {
        if (!$user) return [];

        $today = \Carbon\Carbon::today();

        $rows = \App\Models\Project::query()
            ->select(['id', 'name', 'code', 'target_completion_date', 'status_id'])
            ->with(['status:id,name'])
            ->whereNotNull('target_completion_date')
            ->whereDate('target_completion_date', '<', $today)
            ->whereHas('status', function ($q) {
                $q->whereRaw("UPPER(name) NOT IN ('CLOSED')");
            })
            ->orderBy('target_completion_date', 'asc') // yang paling telat muncul dulu
            ->limit($limit)
            ->get();

        return $rows->map(function ($p) use ($today) {
            $due = \Carbon\Carbon::parse($p->target_completion_date)->startOfDay();
            $overdueDays = $due->diffInDays($today);

            return [
                'id' => 'sla:' . $p->id,
                'kind' => 'sla_overdue',

                // ✅ tampil nama project aja
                'title' => $p->name ?? '-',

                // opsional: kalau mau ada teks kecil penjelas juga
                'desc' => 'SLA Overdue',

                'meta' => 'Lewat ' . $overdueDays . ' hari • Target ' . $due->format('d M Y'),

                // time boleh targetnya
                'time' => $due->toDateTimeString(),

                // pilih salah satu:
                'href' => '/projects/sla-overdue',
                // 'href' => '/projects/project-detail?id=' . $p->id,

                'severity' => 'danger',
            ];
        })->values()->all();
    }
}
