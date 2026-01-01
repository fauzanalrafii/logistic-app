<?php

namespace App\Http\Controllers;

use App\Models\ApprovalAction;
use App\Models\ApprovalInstance;
use App\Models\BoqHeader;
use App\Models\BepProjection;
use App\Models\ProjectStatusHistory;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    /**
     * INDEX (gabungan)
     * tab=inbox   => list task yang harus kamu approve (berdasarkan step aktif)
     * tab=history => list yang PERNAH kamu action (approve/reject), jadi Atasan Planning pasti tampil
     */
    public function index(Request $request)
    {
        $tab     = (string) ($request->tab ?? 'inbox'); // inbox | history
        $perPage = (int) ($request->per_page ?? 10);
        $search  = trim((string) ($request->search ?? ''));
        $page    = (int) ($request->page ?? 1);

        $user = Auth::user();
        $userId = (int) $user->getKey();

        // FIX ambiguous column id -> pakai vl_roles.id
        $roleIds = $user->roles()
            ->pluck('vl_roles.id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        // ==========================
        // TAB: HISTORY (MY ACTIONS)
        // ==========================
        if ($tab === 'history') {
            $q = ApprovalInstance::query()
                ->with([
                    'project.planner:ID,Name',
                    'project.status:id,name',
                    'flow:id,name,process_type',
                    'status:id,name',
                    // ambil actions milik user ini saja (biar history nya "aksi saya")
                    'actions' => function ($a) use ($userId) {
                        $a->with(['user:ID,Name', 'step:id,step_order,name'])
                            ->where('user_id', $userId)
                            ->whereIn('action', ['APPROVE', 'REJECT'])
                            ->orderBy('acted_at', 'desc');
                    },
                ])
                // kunci: instance yang pernah user ini action (walau belum final)
                ->whereHas('actions', function ($a) use ($userId) {
                    $a->where('user_id', $userId)
                        ->whereIn('action', ['APPROVE', 'REJECT']);
                })
                ->orderByDesc('id');

            if ($search !== '') {
                $s = strtolower($search);
                $q->whereHas('project', function ($p) use ($s) {
                    $p->whereRaw('LOWER(code) LIKE ?', ["%{$s}%"])
                        ->orWhereRaw('LOWER(name) LIKE ?', ["%{$s}%"])
                        ->orWhereRaw('LOWER(area) LIKE ?', ["%{$s}%"]);
                });
            }

            $p = $q->paginate($perPage)->withQueryString();

            $items = $p->getCollection()->map(function (ApprovalInstance $inst) {
                $myLast = $inst->actions->first(); // karena sudah order desc

                return [
                    'id' => $inst->id,
                    'status' => $inst->status?->name ?? '-',
                    'progress_label' => $inst->getProgressLabel(),
                    'completed_at' => $inst->completed_at?->toDateTimeString(),

                    'project' => [
                        'id' => $inst->project?->id,
                        'code' => $inst->project?->code,
                        'name' => $inst->project?->name,
                        'area' => $inst->project?->area,
                        'status' => $inst->project?->status?->name ?? '-',
                        'planner' => $inst->project?->planner ? ['Name' => $inst->project->planner->Name] : null,
                    ],

                    'flow' => [
                        'name' => $inst->flow?->name ?? '-',
                        'process_type' => $inst->flow?->process_type ?? '-',
                    ],

                    // history "aksi saya"
                    'my_action' => $myLast ? [
                        'action' => $myLast->action,
                        'user_name' => $myLast->user?->Name ?? '-',
                        'step_name' => $myLast->step?->name ?? '-',
                        'step_order' => (int) ($myLast->step?->step_order ?? 0),
                        'acted_at' => $myLast->acted_at?->toDateTimeString(),
                        'comment' => $myLast->comment,
                    ] : null,
                ];
            });

            return Inertia::render('Approval/Index', [
                'tab' => 'history',
                'items' => [
                    'data' => $items->values(),
                    'total' => $p->total(),
                    'per_page' => $p->perPage(),
                    'current_page' => $p->currentPage(),
                    'last_page' => $p->lastPage(),
                ],
                'filters' => $request->only(['search', 'per_page', 'tab']),
                'currentPage' => 'approval.index',
            ]);
        }

        // ==========================
        // TAB: INBOX (NEED ACTION)
        // ==========================
        $pendingStatusNames = ['PENDING', 'IN_REVIEW', 'SUBMITTED'];

        $q = ApprovalInstance::query()
            ->with([
                'project.planner:ID,Name',
                'project.status:id,name',
                'flow:id,name,process_type',
                'flow.steps.requiredRole:id,name',
                'status:id,name',
            ])
            ->whereHas('status', function ($s) use ($pendingStatusNames) {
                $s->whereIn(DB::raw('UPPER(name)'), array_map('strtoupper', $pendingStatusNames));
            })
            ->orderByDesc('id');

        if ($search !== '') {
            $s = strtolower($search);
            $q->whereHas('project', function ($p) use ($s) {
                $p->whereRaw('LOWER(code) LIKE ?', ["%{$s}%"])
                    ->orWhereRaw('LOWER(name) LIKE ?', ["%{$s}%"])
                    ->orWhereRaw('LOWER(area) LIKE ?', ["%{$s}%"]);
            });
        }

        // ambil dulu, filter by current step role (karena current step dihitung dari actions)
        $all = $q->get();

        $filtered = $all->filter(function (ApprovalInstance $inst) use ($roleIds) {
            $currentStep = $inst->getCurrentStep();
            if (!$currentStep) {
                return false;
            }

            return in_array((int) $currentStep->required_role_id, $roleIds, true);
        })->values();

        $total = $filtered->count();
        $slice = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

        $items = $slice->map(function (ApprovalInstance $inst) {
            $currentStep = $inst->getCurrentStep();

            // Calculate SLA deadline and overdue status
            $slaHours = $currentStep?->sla_hours;
            $deadline = null;
            $isOverdue = false;
            $hoursRemaining = null;

            if ($slaHours) {
                // Step start time: for step 1 use started_at, for step 2+ use last action's acted_at
                $lastAction = $inst->actions()->orderByDesc('acted_at')->first();
                $stepStartedAt = $lastAction?->acted_at ?? $inst->started_at;

                if ($stepStartedAt) {
                    $deadline = $stepStartedAt->copy()->addHours($slaHours);
                    $isOverdue = now()->gt($deadline);
                    $hoursRemaining = now()->diffInHours($deadline, false); // negative if overdue
                }
            }

            return [
                'id' => $inst->id,
                'status' => $inst->status?->name ?? '-',
                'progress_label' => $inst->getProgressLabel(),
                'started_at' => $inst->started_at?->toDateTimeString(),
                'project' => [
                    'id' => $inst->project?->id,
                    'code' => $inst->project?->code,
                    'name' => $inst->project?->name,
                    'area' => $inst->project?->area,
                    'status' => $inst->project?->status?->name ?? '-',
                    'planner' => $inst->project?->planner ? [
                        'Name' => $inst->project->planner->Name
                    ] : null,
                ],
                'step' => $currentStep ? [
                    'id' => $currentStep->id,
                    'step_order' => (int) $currentStep->step_order,
                    'name' => $currentStep->name,
                    'role_name' => $currentStep->requiredRole?->name ?? '-',
                    'sla_hours' => $slaHours,
                ] : null,
                'sla' => [
                    'hours' => $slaHours,
                    'deadline' => $deadline?->toDateTimeString(),
                    'is_overdue' => $isOverdue,
                    'hours_remaining' => $hoursRemaining,
                ],
                'flow' => [
                    'name' => $inst->flow?->name ?? '-',
                    'process_type' => $inst->flow?->process_type ?? '-',
                ],
            ];
        });

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => url('/approval'),
                'query' => $request->query(),
            ]
        );

        return Inertia::render('Approval/Index', [
            'tab' => 'inbox',
            'items' => [
                'data' => $paginator->items(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'filters' => $request->only(['search', 'per_page', 'tab']),
            'currentPage' => 'approval.index',
        ]);
    }

    /**
     * SHOW: detail instance + paket BOQ & BEP
     * + kirim flag can_act & already_acted untuk bantu UI disable tombol
     */
    public function show($id)
    {
        $id = (int) $id;

        $instance = ApprovalInstance::with([
            'project.planner:ID,Name',
            'project.status:id,name',
            'flow.steps.requiredRole:id,name',
            'actions.step:id,step_order,name,approval_flow_id,required_role_id',
            'actions.user:ID,Name',
            'status:id,name',
        ])
            ->findOrFail($id);

        $user = Auth::user();
        $roleIds = $user->roles()->pluck('vl_roles.id')->map(fn($v)=>(int)$v)->values()->all();

        $currentStep = $instance->getCurrentStep();

        // apakah step ini sudah pernah diproses (oleh siapapun) -> kalau iya tombol harus mati
        $alreadyActedOnStep = $currentStep
            ? ApprovalAction::where('approval_instance_id', $instance->id)
            ->where('approval_step_id', $currentStep->id)
            ->exists()
            : true;

        $canAct = $currentStep
            && in_array((int)$currentStep->required_role_id, $roleIds, true)
            && !$alreadyActedOnStep;

        // BOQ/BEP
        $boqHeader = class_exists(BoqHeader::class)
            ? BoqHeader::with('items')
            ->where('project_id', $instance->project_id)
            ->orderByDesc('version')
            ->first()
            : null;

        $bep = class_exists(BepProjection::class)
            ? BepProjection::query()
            ->where('project_id', $instance->project_id)
            ->orderByDesc('version')
            ->first()
            : null;

        return Inertia::render('Approval/Show', [
            'instance' => [
                'id' => $instance->id,
                'status' => $instance->status?->name ?? '-',
                'progress_label' => $instance->getProgressLabel(),

                'can_act' => (bool) $canAct,
                'already_acted' => (bool) $alreadyActedOnStep,

                'current_step' => $currentStep ? [
                    'id' => $currentStep->id,
                    'step_order' => (int) $currentStep->step_order,
                    'name' => $currentStep->name,
                    'required_role_id' => (int) $currentStep->required_role_id,
                    'role_name' => $currentStep->requiredRole?->name ?? '-',
                    'sla_hours' => $currentStep->sla_hours,
                ] : null,

                'sla' => (function () use ($currentStep, $instance) {
                    if (!$currentStep?->sla_hours) return null;

                    // Step start time: for step 1 use started_at, for step 2+ use last action's acted_at
                    $lastAction = $instance->actions()->orderByDesc('acted_at')->first();
                    $stepStartedAt = $lastAction?->acted_at ?? $instance->started_at;

                    if (!$stepStartedAt) return null;

                    $deadline = $stepStartedAt->copy()->addHours($currentStep->sla_hours);
                    return [
                        'hours' => $currentStep->sla_hours,
                        'deadline' => $deadline->toDateTimeString(),
                        'is_overdue' => now()->gt($deadline),
                        'hours_remaining' => now()->diffInHours($deadline, false),
                    ];
                })(),

                'flow' => [
                    'id' => $instance->flow?->id,
                    'name' => $instance->flow?->name,
                    'process_type' => $instance->flow?->process_type,
                    'steps' => $instance->flow?->steps?->map(fn($s) => [
                        'id' => $s->id,
                        'step_order' => (int) $s->step_order,
                        'name' => $s->name,
                        'role_name' => $s->requiredRole?->name ?? '-',
                        'sla_hours' => $s->sla_hours,
                    ])->values(),
                ],

                'project' => [
                    'id' => $instance->project?->id,
                    'code' => $instance->project?->code,
                    'name' => $instance->project?->name,
                    'area' => $instance->project?->area,
                    'location' => $instance->project?->location,
                    'project_type' => $instance->project?->project_type,
                    'source' => $instance->project?->source,
                    'target_completion_date' => $instance->project?->target_completion_date,
                    'status' => $instance->project?->status?->name ?? null,
                    'planner' => $instance->project?->planner ? ['Name' => $instance->project->planner->Name] : null,
                ],

                'actions' => $instance->actions
                    ->sortBy('acted_at')
                    ->values()
                    ->map(function ($a) {
                        return [
                            'action' => $a->action,
                            'comment' => $a->comment,
                            'step_order' => (int) ($a->step?->step_order ?? 0),
                            'step_name' => $a->step?->name ?? '-',
                            'acted_at' => $a->acted_at?->toDateTimeString(),
                            'user_name' => $a->user?->Name ?? '-',
                        ];
                    }),
            ],

            'boq' => $boqHeader ? [
                'id' => $boqHeader->id,
                'version' => (int) ($boqHeader->version ?? 1),
                'total_cost_estimate' => (float) ($boqHeader->total_cost_estimate ?? 0),
                'items' => $boqHeader->items->map(fn($i) => [
                    'item' => $i->material_description ?? '',
                    'spec' => $i->material_code ?? '',
                    'uom' => $i->uom ?? '',
                    'qty' => (float) ($i->qty ?? 0),
                    'unit_price' => (float) ($i->unit_price_estimate ?? 0),
                ])->values(),
            ] : null,

            'bep' => $bep ? [
                'id' => $bep->id,
                'version' => (int) ($bep->version ?? 1),
                'capex' => (float) ($bep->capex ?? 0),
                'opex_estimate' => (float) ($bep->opex_estimate ?? 0),
                'revenue_estimate' => (float) ($bep->revenue_estimate ?? 0),
                'bep_months' => (int) ($bep->bep_months ?? 0),
            ] : null,

            'currentPage' => 'approval.index',
        ]);
    }

    public function approve($instanceId)
    {
        $instanceId = (int) $instanceId;
        $user = Auth::user();

        $roleIds = $user->roles()->pluck('vl_roles.id')->map(fn($v)=>(int)$v)->values()->all();

        return DB::transaction(function () use ($instanceId, $user, $roleIds) {
            $instance = ApprovalInstance::lockForUpdate()
                ->with(['flow.steps', 'status', 'project'])
                ->findOrFail($instanceId);

            $currentStep = $instance->getCurrentStep();
            if (!$currentStep) {
                return back()->with('error', 'Tidak ada step aktif (mungkin sudah selesai).');
            }

            if (!in_array((int) $currentStep->required_role_id, $roleIds, true)) {
                return back()->with('error', 'Kamu tidak punya role untuk approve di step ini.');
            }

            $already = ApprovalAction::where('approval_instance_id', $instance->id)
                ->where('approval_step_id', $currentStep->id)
                ->exists();

            if ($already) {
                return back()->with('error', 'Step ini sudah diproses sebelumnya.');
            }

            $nextStep = $instance->getNextStepAfterCurrent($currentStep->id);
            $isLastStep = !$nextStep;

            // Buat approval action dengan comment jika step terakhir
            ApprovalAction::create([
                'approval_instance_id' => $instance->id,
                'approval_step_id' => $currentStep->id,
                'user_id' => (int) $user->getKey(),
                'action' => 'APPROVE',
                'comment' => $isLastStep ? 'Approval selesai (5/5)' : null,
                'acted_at' => now(),
            ]);

            // Refresh untuk cek next step yang sebenarnya
            $nextStepCheck = $instance->getCurrentStep();

            if (!$nextStepCheck) {
                // Semua step sudah selesai di-approve
                $instance->status_id = $this->statusId('APPROVED');
                $instance->completed_at = now();

                // ====== UPDATE STATUS PROJECT (GENERIC) ======
                // Otomatis pindah ke status berikutnya berdasarkan urutan
                $project = $instance->project;
                if ($project) {
                    $oldStatusId = $project->status_id;
                    $currentStatus = Status::find($oldStatusId);

                    if ($currentStatus) {
                        // Cari status project berikutnya berdasarkan urutan 'no'
                        $nextStatus = Status::where('type', 'project')
                            ->where('no', '>', $currentStatus->no)
                            ->orderBy('no')
                            ->first();

                        if ($nextStatus && $oldStatusId !== $nextStatus->id) {
                            // Update status project
                            $project->status_id = $nextStatus->id;
                            $project->save();
                            // Catat history perubahan status
                            ProjectStatusHistory::create([
                                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                                'project_id' => $project->id,
                                'old_status' => $oldStatusId,
                                'new_status' => $nextStatus->id,
                                'changed_by' => (int) $user->getKey(),
                                'changed_at' => now(),
                                'note' => 'Status berubah otomatis setelah approval ' . ($instance->flow?->name ?? '') . ' selesai',
                            ]);
                        }
                    }
                }
            } else {
                $instance->status_id = $this->statusIdIfExists(['IN_REVIEW', 'PENDING', 'SUBMITTED']) ?? $instance->status_id;
                $instance->completed_at = null;
                if (!$instance->started_at) {
                    $instance->started_at = now();
                }
            }

            $instance->save();

            $successMessage = !$nextStep
                ? 'Approve berhasil! Semua approval selesai, status project diperbarui.'
                : 'Approve berhasil, lanjut ke step berikutnya.';

            return redirect()->route('approval.show', $instance->id)
                ->with('success', $successMessage);
        });
    }

    public function reject(Request $request, $instanceId)
    {
        $instanceId = (int) $instanceId;

        $request->validate([
            'comment' => ['required', 'string', 'min:3'],
        ]);

        $user = Auth::user();
        $roleIds = $user->roles()->pluck('vl_roles.id')->map(fn($v)=>(int)$v)->values()->all();

        return DB::transaction(function () use ($request, $instanceId, $user, $roleIds) {
            $instance = ApprovalInstance::lockForUpdate()
                ->with(['flow.steps', 'status'])
                ->findOrFail($instanceId);

            $currentStep = $instance->getCurrentStep();
            if (!$currentStep) {
                return back()->with('error', 'Tidak ada step aktif (mungkin sudah selesai).');
            }

            if (!in_array((int) $currentStep->required_role_id, $roleIds, true)) {
                return back()->with('error', 'Kamu tidak punya role untuk reject di step ini.');
            }

            $already = ApprovalAction::where('approval_instance_id', $instance->id)
                ->where('approval_step_id', $currentStep->id)
                ->exists();

            if ($already) {
                return back()->with('error', 'Step ini sudah diproses sebelumnya.');
            }

            ApprovalAction::create([
                'approval_instance_id' => $instance->id,
                'approval_step_id' => $currentStep->id,
                'user_id' => (int) $user->getKey(),
                'action' => 'REJECT',
                'comment' => $request->comment,
                'acted_at' => now(),
            ]);

            $instance->status_id = $this->statusId('REJECTED');
            $instance->completed_at = now();
            if (!$instance->started_at) {
                $instance->started_at = now();
            }
            $instance->save();

            return redirect()->route('approval.show', $instance->id)
                ->with('success', 'Reject berhasil.');
        });
    }

    private function statusId(string $name): int
    {
        $id = Status::query()
            ->whereRaw('UPPER(name) = ?', [strtoupper($name)])
            ->value('id');

        if (!$id) {
            abort(500, "Status '{$name}' tidak ditemukan di vl_status.");
        }

        return (int) $id;
    }

    private function statusIdIfExists(array $names): ?int
    {
        foreach ($names as $n) {
            $id = Status::query()
                ->whereRaw('UPPER(name) = ?', [strtoupper($n)])
                ->value('id');
            if ($id) {
                return (int) $id;
            }
        }
        return null;
    }

    /**
     * OPTIONAL: kalau kamu masih punya route /approval/history
     * biar gak bikin view baru, arahkan ke inbox tab history
     */
    public function history(Request $request)
    {
        return redirect()->to('/approval?tab=history');
    }
}
