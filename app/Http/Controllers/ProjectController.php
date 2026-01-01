<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectStatusHistory;
use App\Models\User;
use App\Models\Status;
use App\Models\ApprovalInstance;
use App\Models\ApprovalAction;
use App\Models\BoqHeader;
use App\Models\BepProjection;
use App\Models\Document;
use Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Jobs\StoreProjectStatusHistoryJob;

class ProjectController extends Controller
{
    /**
     * API endpoint for Command Palette search
     */
    public function searchApi(Request $request)
    {
        $search = trim($request->get('q', ''));

        if (strlen($search) < 2) {
            return response()->json(['projects' => [], 'approvals' => []]);
        }

        // Search Projects
        $projects = Project::query()
            ->notDeleted()
            ->with(['status:id,name'])
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'city' => $p->city,
                'region' => $p->region,
                'status' => $p->status?->name ?? '-',
            ]);

        // Search Approvals (pending items for current user)
        $user = Auth::user();
        $roleIds = $user->roles()->pluck('vl_roles.id')->map(fn($v) => (int)$v)->values()->all();

        $pendingStatusNames = ['PENDING', 'IN_REVIEW', 'SUBMITTED'];

        $approvals = ApprovalInstance::query()
            ->with([
                'project:id,code,name,city,area',
                'flow:id,name,process_type',
                'status:id,name',
            ])
            ->whereHas('status', function ($s) use ($pendingStatusNames) {
                $s->whereIn(DB::raw('UPPER(name)'), array_map('strtoupper', $pendingStatusNames));
            })
            ->whereHas('project', function ($p) use ($search) {
                $p->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->filter(function ($inst) use ($roleIds) {
                $currentStep = $inst->getCurrentStep();
                if (!$currentStep) return false;
                return in_array((int) $currentStep->required_role_id, $roleIds, true);
            })
            ->take(5)
            ->map(fn($inst) => [
                'id' => $inst->id,
                'project_id' => $inst->project?->id,
                'project_name' => $inst->project?->name ?? '-',
                'project_code' => $inst->project?->code ?? '-',
                'city' => $inst->project?->city ?? $inst->project?->area ?? '-',
                'flow_name' => $inst->flow?->name ?? '-',
                'status' => $inst->status?->name ?? '-',
            ])
            ->values();

        return response()->json([
            'projects' => $projects,
            'approvals' => $approvals,
        ]);
    }

    public function index(Request $request)
    {
        $query = Project::with(['planner:ID,Name', 'status:id,name'])
            ->notDeleted();

        // GLOBAL SEARCH
        $query->applyGlobalSearch($request->search);

        // FILTER
        $query->applyFilters($request->all());

        // ambil semua
        $projects = $query->orderBy('created_at', 'desc')->get();

        // map jadi array ringan (yang dipakai UI)
        $mapped = $projects->map(function ($p) {
            return [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'source' => $p->source, // INISIASI / OSS / dll
                'planner_name' => $p->planner?->Name ?? '-',
                'target_completion_date' => $p->target_completion_date
                    ? Carbon::parse($p->target_completion_date)->format('Y-m-d')
                    : null,
                'status_name' => $p->status?->name ?? 'NO STATUS',
            ];
        });

        // grouping untuk board berdasarkan status
        $grouped = $mapped->groupBy('status_name');

        return Inertia::render('projects/List', [
            'groupedProjects' => $grouped,
            'statusOptions' => Status::where('type', 'project')->orderBy('no')->get(),
            'filters' => $request->all(),
        ]);
    }


    public function create()
    {
        return Inertia::render('projects/Create', [
            'planners' => User::select('ID', 'Name')->get(),

            // 👇 TAMBAHKAN INI
            'authUser' => [
                'id' => Auth::user()->ID,
                'name' => Auth::user()->Name,
            ],

            'currentPage' => 'project.create',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',

            'kodepos_id' => [
                'required',
                function ($attr, $value, $fail) {
                    $exists = DB::connection('mysql_master')
                        ->table('master_kodepos_new')
                        ->where('ID', $value)
                        ->exists();

                    if (! $exists) {
                        $fail('Kodepos tidak valid.');
                    }
                }
            ],

            'planner_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value && !User::where('ID', $value)->exists()) {
                        $fail("The selected planner is invalid.");
                    }
                }
            ],
        ]);


        DB::beginTransaction();
        try {
            $initialStatus = Status::where('name', 'PLAN ON DESK')
                ->where('type', 'project')
                ->firstOrFail();

            $code = $this->generateProjectCode();
            
            $kodepos = DB::connection('mysql_master')
                ->table('master_kodepos_new')
                ->where('ID', $request->kodepos_id)
                ->first();

            if (!$kodepos) {
                throw new \Exception('Kodepos tidak ditemukan');
            }

            $project = Project::create([
                'uuid' => Str::uuid(),
                'code' => $code,
                'source' => 'INISIASI',
                'oss_reference_id' => null,
                'name' => $request->name,
                'description' => $request->description,

                // 🔥 AREA & LOCATION DARI KODEPOS
                'area' => "{$kodepos->ZipCode} - {$kodepos->Sub_District} - {$kodepos->City}",
                'location' => $request->location,

                // 🔥 SNAPSHOT LOKASI
                'kodepos_id'   => $kodepos->ID,
                'zipcode'      => $kodepos->ZipCode,
                'province'     => $kodepos->Province,
                'city'         => $kodepos->City,
                'district'     => $kodepos->District,
                'sub_district' => $kodepos->Sub_District,
                'region'       => $kodepos->Regional,

                'project_type' => $request->project_type,
                'status_id' => $initialStatus->id,
                'planner_id' => $request->planner_id,
                'target_completion_date' => $request->target_completion_date,
            ]);

            // Simpan histori status pertama (simpan NAMA status)
            DB::commit();

            /** 🔥 MASUK QUEUE RABBITMQ */
            StoreProjectStatusHistoryJob::dispatch(
                projectId: $project->id,
                oldStatus: null,
                newStatus: $initialStatus->id,
                changedBy: optional(Auth::user())->ID,
                note: 'Initial status'
            )->onQueue('default');

            return redirect()->route('projects.index')->with('success', 'Project berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors('error', $e->getMessage())
                ->withInput();
        }
    }

    public function edit(Project $project)
    {
        // Check if planning is submitted - block editing
        $isSubmitted = $project->isPlanningSubmitted();

        $project->load([
            'statusHistories' => function ($q) {
                $q->orderBy('changed_at', 'asc'); // urut dari paling awal → terbaru
            },
            'statusHistories.changer',
            'statusHistories.oldStatusData',
            'statusHistories.newStatusData',
        ]);

        return Inertia::render('projects/Edit', [
            'project' => $project,
            'planners' => User::select('ID', 'Name')->get(),
            'statusOptions' => Status::where('type', 'project')->orderBy('no')->get(),
            'currentPage' => 'project.list',
            'isSubmitted' => $isSubmitted,
        ]);
    }


    public function detail(Request $request)
    {
        $id = $request->id;

        if (!$id) {
            abort(404, 'Project ID is required.');
        }

        $project = Project::with([
            'planner',
            'status',
            'statusHistories.changer',
            'statusHistories.oldStatusData',
            'statusHistories.newStatusData',
        ])->findOrFail($id);

        // ==== PLANNING (BOQ/BEP terakhir) ====
        $boq = BoqHeader::query()
            ->with(['status:id,name', 'items'])
            ->where('project_id', $project->id)
            ->where('type', 'ON_DESK')
            ->orderByDesc('version')
            ->first();

        $bep = BepProjection::query()
            ->with(['status:id,name'])
            ->where('project_id', $project->id)
            ->orderByDesc('version')
            ->first();

        // ==== DOCUMENTS ====
        $documents = Document::query()
            ->where('project_id', $project->id)
            ->orderByDesc('uploaded_at')
            ->get()
            ->map(function ($doc) {
                // Get uploader name from vl_users
                $uploader = User::find($doc->uploaded_by);
                return [
                    'id' => $doc->id,
                    'uuid' => $doc->uuid,
                    'project_id' => $doc->project_id,
                    'document_type' => $doc->document_type,
                    'file_name' => $doc->file_name,
                    'file_path' => $doc->file_path,
                    'file_size' => $doc->file_size,
                    'mime_type' => $doc->mime_type,
                    'uploaded_by' => $uploader->Name ?? 'Unknown',
                    'uploaded_at' => $doc->uploaded_at?->toDateTimeString(),
                ];
            });

        // Check if planning is submitted
        $isSubmitted = $project->isPlanningSubmitted();

        // Get approval instance for revise check
        $planningApproval = ApprovalInstance::where('project_id', $project->id)
            ->where('related_type', 'planning')
            ->latest()
            ->first();
        $canRevise = $planningApproval?->isRejected() ?? false;

        // ==== SEMUA APPROVAL INSTANCES untuk project ini ====
        $allApprovals = ApprovalInstance::query()
            ->with([
                'status:id,name',
                'flow:id,name,process_type',
                'flow.steps:id,approval_flow_id,step_order,name,required_role_id',
                'flow.steps.requiredRole:id,name',
                'actions' => function ($q) {
                    $q->with([
                        'step:id,step_order,name',
                        'user:ID,Name',
                    ])->orderBy('acted_at', 'asc');
                },
            ])
            ->where('project_id', $project->id)
            ->orderByDesc('id')
            ->get();

        // Format semua approvals ke array dengan key = related_type
        $approvals = [];
        foreach ($allApprovals as $inst) {
            $currentStep = method_exists($inst, 'getCurrentStep') ? $inst->getCurrentStep() : null;
            $rejectAction = $inst->actions ? $inst->actions->firstWhere('action', 'REJECT') : null;

            $approvals[] = [
                'id' => $inst->id,
                'related_type' => $inst->related_type, // planning, survey-drm-approval, etc
                'process_type' => $inst->flow?->process_type ?? '-',
                'status' => $inst->status?->name ?? '-',
                'progress_label' => method_exists($inst, 'getProgressLabel') ? $inst->getProgressLabel() : null,
                'started_at' => $inst->started_at?->toDateTimeString(),
                'completed_at' => $inst->completed_at?->toDateTimeString(),

                'current_step' => $currentStep ? [
                    'id' => $currentStep->id,
                    'order' => (int) $currentStep->step_order,
                    'name' => $currentStep->name,
                    'role' => $currentStep->requiredRole?->name ?? '-',
                ] : null,

                'flow' => [
                    'name' => $inst->flow?->name ?? '-',
                    'process_type' => $inst->flow?->process_type ?? '-',
                    'steps' => $inst->flow?->steps
                        ? $inst->flow->steps->sortBy('step_order')->values()->map(fn($s) => [
                            'id' => $s->id,
                            'order' => (int) $s->step_order,
                            'name' => $s->name,
                            'role' => $s->requiredRole?->name ?? '-',
                        ])->values()
                        : [],
                ],

                'actions' => $inst->actions
                    ? $inst->actions->values()->map(function ($a) {
                        return [
                            'action' => $a->action,
                            'step_id' => $a->approval_step_id,
                            'step_order' => (int) ($a->step?->step_order ?? 0),
                            'step_name' => $a->step?->name ?? '-',
                            'user_name' => $a->user?->Name ?? '-',
                            'acted_at' => $a->acted_at?->toDateTimeString(),
                            'comment' => $a->comment,
                        ];
                    })->values()
                    : [],

                'rejected_by' => $rejectAction?->user?->Name ?? null,
                'rejection_reason' => $rejectAction?->comment ?? null,
            ];
        }

        // Planning = approval dengan related_type = 'planning' (untuk backward compat)
        $planning = collect($approvals)->firstWhere('related_type', 'planning');

        return Inertia::render('projects/ProjectDetail', [
            'project'       => $project,
            'statusOptions' => Status::where('type', 'project')->orderBy('no')->get(),
            'currentPage'   => 'project.detail',

            // Semua approvals (dinamis)
            'approvals' => $approvals,

            // Backward compat
            'planning' => $planning,
            'planningBoq' => $boq ? [
                'version' => (int) ($boq->version ?? 1),
                'status' => $boq->status?->name ?? '-',
                'submitted_at' => $boq->submitted_at?->toDateTimeString(),
            ] : null,
            'planningBep' => $bep ? [
                'version' => (int) ($bep->version ?? 1),
                'status' => $bep->status?->name ?? '-',
                'submitted_at' => $bep->submitted_at?->toDateTimeString(),
            ] : null,

            // BOQ & BEP data for inline editing
            'boqHeader' => $boq ? [
                'id' => $boq->id,
                'type' => $boq->type,
                'version' => $boq->version,
                'status' => $boq->status?->name ?? 'DRAFT',
                'total_cost_estimate' => $boq->total_cost_estimate,
                'items' => $boq->items->map(fn($item) => [
                    'id' => $item->id,
                    'material_code' => $item->material_code,
                    'material_description' => $item->material_description,
                    'uom' => $item->uom,
                    'qty' => (float) $item->qty,
                    'unit_price_estimate' => (float) $item->unit_price_estimate,
                    'remarks' => $item->remarks,
                ]),
            ] : null,
            'bepProjection' => $bep ? [
                'id' => $bep->id,
                'version' => $bep->version,
                'capex' => (float) $bep->capex,
                'opex_estimate' => (float) $bep->opex_estimate,
                'revenue_estimate' => (float) $bep->revenue_estimate,
                'bep_months' => $bep->bep_months,
                'status' => $bep->status?->name ?? 'DRAFT',
            ] : null,

            // Documents
            'documents' => $documents,
            'isSubmitted' => $isSubmitted,
            'canRevise' => $canRevise,
        ]);

        // Temporary debug
        // dd($documents);
    }

    public function update(Request $request, Project $project)
    {
        if ($project->isPlanningSubmitted()) {
            return back()->withErrors([
                'error' => 'Project tidak dapat diedit karena planning sudah di-submit.'
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'planner_id' => [
                'nullable',
                function ($attr, $value, $fail) {
                    if ($value && !User::where('ID', $value)->exists()) {
                        $fail('Planner tidak valid');
                    }
                }
            ],
            'kodepos_id' => [
                'nullable',
                function ($attr, $value, $fail) {
                    $exists = DB::connection('mysql_master')
                        ->table('master_kodepos_new')
                        ->where('ID', $value)
                        ->exists();

                    if (! $exists) {
                        $fail('Kodepos tidak valid.');
                    }
                }
            ],
        ]);

        DB::beginTransaction();
        try {
            // data dasar
            $data = [
                'name' => $request->name,
                'planner_id' => $request->planner_id,
                'target_completion_date' => $request->target_completion_date,
                'project_type' => $request->project_type,
                'location' => $request->location,
                'description' => $request->description,
            ];

            // 🔥 JIKA AREA DIGANTI
            if ($request->filled('kodepos_id')) {
                $kodepos = DB::connection('mysql_master')
                    ->table('master_kodepos_new')
                    ->where('ID', $request->kodepos_id)
                    ->first();

                $data['kodepos_id']   = $kodepos->ID;
                $data['zipcode']      = $kodepos->ZipCode;
                $data['province']     = $kodepos->Province;
                $data['city']         = $kodepos->City;
                $data['district']     = $kodepos->District;
                $data['sub_district'] = $kodepos->Sub_District;
                $data['region']       = $kodepos->Regional;

                // area SELALU hasil generate
                $data['area'] = "{$kodepos->ZipCode} - {$kodepos->Sub_District} - {$kodepos->City}";
            }

            $project->update($data);

            DB::commit();
            return redirect()
                ->route('projects.index')
                ->with('success', 'Project berhasil diupdate.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index');
    }

    /**
     * Simpan histori status
     * Kita simpan NAMA status ke old_status/new_status
     * supaya cocok ke kolom varchar & relasi ownerKey name.
     */
    private function saveStatusHistory(Project $project, ?int $oldStatus, int $newStatus, string $note)
    {
        ProjectStatusHistory::create([
            'uuid' => Str::uuid(),
            'project_id' => $project->id,
            'old_status' => $oldStatus, // ID vl_status
            'new_status' => $newStatus, // ID vl_status
            'changed_by' => optional(Auth::user())->ID,
            'changed_at' => now(),
            'note' => $note,
        ]);
    }


    private function generateProjectCode()
    {
        $year = date('Y');
        $last = Project::whereYear('created_at', $year)
            ->whereNotNull('code')
            ->orderBy('id', 'desc')
            ->first();

        $next = $last ? intval(substr($last->code, -6)) + 1 : 1;

        return 'PRJ-' . $year . '-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    public function planOnDesk(Request $request)
    {
        $perPage = (int)($request->per_page ?? 10);

        // hanya project dengan status PLAN ON DESK dan belum dihapus
        $query = Project::with(['planner', 'status'])
            ->notDeleted()
            ->whereHas('status', function ($q) {
                $q->where('name', 'PLAN ON DESK');
            });

        // GLOBAL SEARCH
        $query->applyGlobalSearch($request->search);

        // FILTER TAMBAHAN
        $query->applyFilters($request->except('status'));

        $projects = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // ===============================
        // Tambahan: ambil approver/rejector terakhir
        // ===============================
        $projectIds = $projects->getCollection()->pluck('id')->values();

        // latest approval instance per project (related_type = planning)
        $latestInstanceIds = ApprovalInstance::query()
            ->select(DB::raw('MAX(id) as id'))
            ->whereIn('project_id', $projectIds)
            ->where('related_type', 'planning')
            ->groupBy('project_id')
            ->pluck('id')
            ->filter()
            ->values();

        $instances = ApprovalInstance::query()
            ->whereIn('id', $latestInstanceIds)
            ->get()
            ->keyBy('project_id'); // key: project_id

        $instanceIds = $instances->pluck('id')->values();

        // ambil semua action terakhir (kita ambil paling atas per instance)
        $actions = ApprovalAction::query()
            ->with(['user:ID,Name', 'step:id,step_order,name'])
            ->whereIn('approval_instance_id', $instanceIds)
            ->orderBy('acted_at', 'desc')
            ->get()
            ->groupBy('approval_instance_id')
            ->map(fn($rows) => $rows->first()); // last action per instance

        // map paginator output
        $projects->setCollection(
            $projects->getCollection()->map(function ($project) use ($instances, $actions) {

                $approvalStatus = $project->getPlanningApprovalStatus();
                $isRejected = str_contains(strtolower($approvalStatus ?? ''), 'rejected');

                $inst = $instances->get($project->id);
                $lastAction = $inst ? ($actions->get($inst->id) ?? null) : null;

                return [
                    'id' => $project->id,
                    'code' => $project->code,
                    'name' => $project->name,
                    'planner' => $project->planner,
                    'source' => $project->source,
                    'area' => $project->area,
                    'is_submitted' => $project->isPlanningSubmitted(),
                    'is_rejected' => $isRejected,
                    'approval_status' => $approvalStatus,

                    // ✅ ini yang dipakai buat tampil nama approve/reject
                    'approval_last_action' => $lastAction ? [
                        'action' => $lastAction->action, // APPROVE / REJECT
                        'user_name' => $lastAction->user?->Name ?? '-',
                        'step_order' => (int)($lastAction->step?->step_order ?? 0),
                        'step_name' => $lastAction->step?->name ?? '-',
                        'acted_at' => $lastAction->acted_at?->toDateTimeString(),
                        'comment' => $lastAction->comment,
                    ] : null,
                ];
            })
        );

        return Inertia::render('projects/PlanOnDesk', [
            'projects' => $projects,
            'filters'  => $request->all(),
        ]);
    }


    public function slaOverdue(Request $request)
    {
        $perPage = (int) ($request->per_page ?? 10);
        $today = Carbon::today();

        $query = Project::query()
            ->notDeleted()
            ->with([
                'planner:ID,Name',
                'status:id,name',
            ])
            ->whereNotNull('target_completion_date')
            ->whereDate('target_completion_date', '<', $today);

        // GLOBAL SEARCH
        $query->applyGlobalSearch($request->search);

        // FILTERS
        $query->applyFilters($request->all());

        // Urutkan: yang paling lama overdue muncul dulu
        $projects = $query
            ->orderBy('target_completion_date', 'asc')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($p) use ($today) {
                $due = Carbon::parse($p->target_completion_date)->startOfDay();
                $overdueDays = $due->diffInDays($today);

                return [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'planner' => $p->planner ? ['Name' => $p->planner->Name] : null,
                    'target_completion_date' => $p->target_completion_date,
                    'overdue_days' => $overdueDays,
                    'source' => $p->source,
                    'area' => $p->area,
                ];
            });

        return Inertia::render('projects/SlaOverdue', [
            'projects' => $projects,
            'filters'  => $request->all(),
            'currentPage' => 'projects.slaOverdue',
        ]);
    }

    /**
     * Soft delete a project (only if not yet submitted for approval)
     */
    public function softDelete(Request $request, int $id)
    {
        $project = Project::findOrFail($id);

        if (!$project->canBeDeleted()) {
            return back()->withErrors(['error' => 'Proyek tidak dapat dihapus karena sudah di-submit untuk approval.']);
        }

        $oldStatusId = $project->status_id;

        $project->update(['is_deleted' => true]);

        // Catat ke history bahwa project dihapus (tanpa queue)
        ProjectStatusHistory::create([
            'uuid' => Str::uuid(),
            'project_id' => $project->id,
            'old_status' => $oldStatusId,
            'new_status' => $oldStatusId,
            'changed_by' => Auth::id(),
            'changed_at' => now(),
            'note' => 'Dihapus',
        ]);

        return back()->with('success', 'Proyek berhasil dihapus.');
    }
}
