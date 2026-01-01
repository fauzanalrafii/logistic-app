<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Project;
use App\Models\ApprovalInstance;
use App\Models\Status;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // ==========================
        // STAT COUNT PER STATUS
        // ==========================
        $rawCounts = Project::query()
            ->notDeleted()
            ->select('status_id', DB::raw('COUNT(*) as total'))
            ->groupBy('status_id')
            ->with(['status:id,name'])
            ->get();

        $statCounts = [];
        foreach ($rawCounts as $row) {
            $name = strtoupper($row->status?->name ?? 'UNKNOWN');
            $statCounts[$name] = (int) $row->total;
        }

        // ==========================
        // PROYEK TERBARU
        // ==========================
        $recentProjects = Project::query()
            ->notDeleted()
            ->latest('created_at')
            ->with([
                'status:id,name',
                'planner:ID,Name',
            ])
            ->take(5)
            ->get()
            ->map(function ($p) {
                return [
                    'id'         => $p->id,
                    'name'       => $p->name,
                    'code'       => $p->code,
                    'source'     => $p->source,
                    'status'     => $p->status?->name,
                    'planner'    => $p->planner?->Name,
                    'created_at' => $p->created_at,
                ];
            });

        // ==========================
        // SLA OVERDUE
        // ==========================
        $today = Carbon::today();
        $slaOverdueCount = Project::query()
            ->notDeleted()
            ->whereNotNull('target_completion_date')
            ->whereDate('target_completion_date', '<', $today)
            ->whereHas('status', fn($q) => $q->where('name', '!=', 'CLOSED'))
            ->count();

        // ==========================
        // QUICK STATS
        // ==========================
        // 1. Total Active Projects (not HANDOVER/CLOSED)
        $activeProjects = Project::query()
            ->notDeleted()
            ->whereHas('status', fn($q) => $q->whereNotIn('name', ['HANDOVER', 'CLOSED']))
            ->count();

        // 2. Target This Month (target_completion_date in current month)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $monthlyTarget = Project::query()
            ->notDeleted()
            ->whereNotNull('target_completion_date')
            ->whereBetween('target_completion_date', [$startOfMonth, $endOfMonth])
            ->count();

        // 3. On Track vs Delayed
        $totalOngoingProjects = Project::query()
            ->notDeleted()
            ->whereNotNull('target_completion_date')
            ->whereHas('status', fn($q) => $q->whereNotIn('name', ['HANDOVER', 'CLOSED']))
            ->count();

        // 3. Waiting Approval (Action Items)
        // We reuse logic from getApprovalDashboardList but with higher limit to get accurate count
        // Ideally checking 'count' directly in DB is better, but the logic is complex.
        $fullApprovalList = $this->getApprovalDashboardList($user, 100);
        $approvalWaiting = count($fullApprovalList);

        // Limit display list to 5
        $approvalList = array_slice($fullApprovalList, 0, 5);

        // ==========================
        // ANALYTICS DATA
        // ==========================
        // ... (existing analytics logic) ...
        // 1. Trend Data (Last 12 Months - to support frontend filtering)
        $trendData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->startOfMonth()->subMonths($i);
            $monthName = $date->format('M');
            $year = $date->year;
            $month = $date->month;

            $createdCount = Project::query()
                ->notDeleted()
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();

            $completedCount = Project::query()
                ->notDeleted()
                ->whereHas('status', fn($q) => $q->whereIn('name', ['HANDOVER', 'CLOSED']))
                ->whereYear('updated_at', $year) // Assumption: updated_at is completion time
                ->whereMonth('updated_at', $month)
                ->count();

            $trendData[] = [
                'month' => $monthName,
                'created' => $createdCount,
                'completed' => $completedCount,
            ];
        }

        // 2. Region Data (Top 5 regions)
        // If 'region' is mostly null, we might want to fallback or just show what's there.
        // 2a. Region Data (For Bar Chart - Top 5)
        $regionDataRaw = Project::query()
            ->notDeleted()
            ->select('region', DB::raw('count(*) as count'))
            ->whereNotNull('region')
            ->where('region', '!=', '')
            ->groupBy('region')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $regionColors = ['bg-blue-500', 'bg-indigo-600', 'bg-emerald-400', 'bg-amber-400', 'bg-rose-500'];
        $regionData = $regionDataRaw->map(function ($item, $index) use ($regionColors) {
            return [
                'name' => $item->region,
                'count' => $item->count,
                // Color handled in frontend, but we keep structure if needed
            ];
        });

        // 2b. City Data (For Map - Top 100)
        $cityDataRaw = Project::query()
            ->notDeleted()
            ->select('city', 'province', DB::raw('count(*) as count'))
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->groupBy('city', 'province')
            ->orderByDesc('count')
            ->limit(100)
            ->get();

        // Fetch detailed projects for these cities (Top 5 per city)
        $cityNames = $cityDataRaw->pluck('city');
        $projectsByCity = Project::query()
            ->notDeleted()
            ->whereIn('city', $cityNames)
            ->with('status:id,name') // Ensure 'status' relationship exists or column 'status'
            ->latest('created_at')
            ->get(['id', 'code', 'name', 'city', 'status_id', 'created_at']);

        $projectsGrouped = $projectsByCity->groupBy('city');

        $cityData = $cityDataRaw->map(function ($item) use ($projectsGrouped) {
            $cityProjects = $projectsGrouped->get($item->city, collect());
            return [
                'name' => $item->city,
                'province' => $item->province ?? '',
                'count' => $item->count,
                'projects' => $cityProjects->take(5)->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name, // truncate on frontend
                        'status' => $p->status ? $p->status->name : '-',
                    ];
                })->values()
            ];
        });

        // Fallback dummy if no region data
        if ($regionData->isEmpty()) {
            $regionData = collect([
                ['name' => 'No Data', 'count' => 0]
            ]);
        }

        $userRole = $user->roles
            ->sortByDesc(fn($r) => $r->permissions->count())
            ->first()?->name ?? 'User';

        return Inertia::render('Dashboard', [
            'userName'         => $user->Name,
            'userRole'         => $userRole,
            'statCounts'       => $statCounts,
            'recentProjects'   => $recentProjects,
            'approvalList'     => $approvalList,
            'slaOverdueCount'  => $slaOverdueCount,
            'quickStats'       => [
                'activeProjects'    => $activeProjects,
                'monthlyTarget'     => $monthlyTarget,
                'approvalWaiting'   => $approvalWaiting,
            ],
            'analytics' => [
                'trendData'  => $trendData,
                'regionData' => $regionData,
                'cityData'   => $cityData,
            ],
        ]);
    }

    /**
     * Ambil approval yang sedang menunggu tindakan user berdasarkan role_id di vl_users.
     * - Status instance: PENDING / IN_REVIEW / SUBMITTED
     * - Step aktif dihitung dari jumlah actions + 1
     * - Step aktif harus required_role_id in (role user)
     */
    private function getApprovalDashboardList($user, int $limit = 5): array
    {
        // role_id user dari tabel vl_users (sesuai log kamu)
        $roleIds = DB::table('vl_users')
            ->where('user_l_ID', $user->ID)
            ->pluck('role_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($roleIds)) return [];

        // nama tabel (lebih aman)
        $instTable   = (new ApprovalInstance())->getTable(); // vl_approval_instances
        $statusTable = (new Status())->getTable();           // vl_status
        $projectTable = (new Project())->getTable();         // vl_projects

        // penting: pakai nama tabel yang benar (bukan approval_steps)
        $actionTable = 'vl_approval_actions';
        $stepTable   = 'vl_approval_steps';

        $pendingNames = ['PENDING', 'IN_REVIEW', 'SUBMITTED'];

        // subquery: total aksi per instance (berapa step sudah diproses)
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
            ->orderByDesc('ai.started_at')
            ->limit($limit)
            ->select([
                'ai.id',
                'ai.related_type',
                'ai.started_at',
                'p.code as project_code',
                'p.name as project_name',
                'step.step_order',
                'step.name as step_name',
                'step.sla_hours',
            ])
            // Add last action timestamp
            ->selectSub(
                DB::table('vl_approval_actions')
                    ->whereColumn('approval_instance_id', 'ai.id')
                    ->orderByDesc('acted_at')
                    ->limit(1)
                    ->select('acted_at'),
                'last_action_at'
            )
            ->get();

        return $rows->map(function ($r) {
            $type = strtoupper((string)($r->related_type ?? 'APPROVAL'));
            $typeLabel = $type === 'PLANNING' ? 'Planning' : 'Approval';

            // Calculate SLA deadline and status - use last_action_at for step 2+
            $slaHours = $r->sla_hours;
            $deadline = null;
            $isOverdue = false;
            $hoursRemaining = null;

            if ($slaHours) {
                // Step start time: for step 1 use started_at, for step 2+ use last action
                $stepStartedAt = $r->last_action_at ? Carbon::parse($r->last_action_at) : ($r->started_at ? Carbon::parse($r->started_at) : null);

                if ($stepStartedAt) {
                    $deadline = $stepStartedAt->copy()->addHours($slaHours);
                    $isOverdue = now()->gt($deadline);
                    $hoursRemaining = now()->diffInHours($deadline, false);
                }
            }

            return [
                'id'        => (int) $r->id,
                'name'      => $r->project_name,
                'code'      => $r->project_code,
                'date'      => $r->started_at,
                'type'      => $typeLabel,
                'step'      => 'Step ' . ($r->step_order ?? '-') . ' • ' . ($r->step_name ?? '-'),
                'href'      => '/approval/' . $r->id,
                'typeClass' => $type === 'PLANNING'
                    ? 'bg-yellow-50 text-yellow-700 border-yellow-200'
                    : 'bg-blue-50 text-blue-700 border-blue-200',
                'sla' => [
                    'hours' => $slaHours,
                    'deadline' => $deadline?->toDateTimeString(),
                    'is_overdue' => $isOverdue,
                    'hours_remaining' => $hoursRemaining,
                ],
            ];
        })->values()->all();
    }
}
