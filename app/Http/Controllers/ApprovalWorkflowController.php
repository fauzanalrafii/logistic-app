<?php

namespace App\Http\Controllers;

use App\Models\ApprovalFlow;
use App\Models\ApprovalStep;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ApprovalWorkflowController extends Controller
{
    /**
     * Display a listing of approval workflows.
     */
    public function index()
    {
        $workflows = ApprovalFlow::query()
            ->withCount(['steps', 'instances'])
            ->orderByDesc('id')
            ->get()
            ->map(function ($workflow) {
                // Get unique active project count (only those with pending approval - not APPROVED/REJECTED)
                $activeProjectCount = $workflow->instances()
                    ->whereHas('status', fn($q) => $q->whereNotIn('name', ['APPROVED', 'REJECTED']))
                    ->whereHas('project', fn($q) => $q->where('is_deleted', false))
                    ->distinct('project_id')
                    ->count('project_id');

                // Get status names that trigger this workflow (from process_type convention)
                // Format: process_type like "PLAN_ON_DESK_PLANNING" -> status "PLAN ON DESK"
                $triggerStatus = str_replace('_PLANNING', '', $workflow->process_type);
                $triggerStatus = str_replace('_', ' ', $triggerStatus);

                return [
                    'id' => $workflow->id,
                    'name' => $workflow->name,
                    'process_type' => $workflow->process_type,
                    'is_active' => $workflow->is_active,
                    'steps_count' => $workflow->steps_count ?? 0,
                    'project_count' => $activeProjectCount,
                    'trigger_status' => $triggerStatus,
                ];
            });

        return Inertia::render('ApprovalWorkflow/Index', [
            'workflows' => $workflows,
            'currentPage' => 'approval.workflow.index',
        ]);
    }

    /**
     * Show the form for creating a new workflow.
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get(['id', 'name']);

        return Inertia::render('ApprovalWorkflow/Create', [
            'roles' => $roles,
            'currentPage' => 'approval.workflow.index',
        ]);
    }

    /**
     * Store a newly created workflow in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.name' => ['required', 'string', 'max:255'],
            'steps.*.step_order' => ['required', 'integer', 'min:1'],
            'steps.*.required_role_id' => ['required', 'exists:vl_roles,id'],
            'steps.*.sla_hours' => ['nullable', 'integer', 'min:1'],
        ]);

        // Auto-generate process_type from name (UPPER_SNAKE_CASE)
        $processType = strtoupper(str_replace([' ', '-'], '_', $validated['name']));
        $processType = preg_replace('/[^A-Z0-9_]/', '', $processType);

        return DB::transaction(function () use ($validated, $processType) {
            // Create the workflow (is_active defaults to false, will be set true when project uses it)
            $workflow = ApprovalFlow::create([
                'uuid' => Str::uuid()->toString(),
                'name' => $validated['name'],
                'process_type' => $processType,
                'is_active' => false,
            ]);

            // Create steps
            foreach ($validated['steps'] as $stepData) {
                ApprovalStep::create([
                    'uuid' => Str::uuid()->toString(),
                    'approval_flow_id' => $workflow->id,
                    'name' => $stepData['name'],
                    'step_order' => $stepData['step_order'],
                    'required_role_id' => $stepData['required_role_id'],
                    'sla_hours' => $stepData['sla_hours'] ?? 24, // Default 24 jam
                ]);
            }

            return redirect()->route('approval.workflow.index')
                ->with('success', 'Workflow berhasil dibuat!');
        });
    }

    /**
     * Display the specified workflow (with diagram visualization).
     */
    public function show($id)
    {
        $workflow = ApprovalFlow::with(['steps.requiredRole:id,name'])
            ->findOrFail($id);

        $steps = $workflow->steps->map(function ($step) {
            return [
                'id' => $step->id,
                'uuid' => $step->uuid,
                'name' => $step->name,
                'step_order' => $step->step_order,
                'required_role_id' => $step->required_role_id,
                'role_name' => $step->requiredRole?->name ?? '-',
                'sla_hours' => $step->sla_hours,
                'created_at' => $step->created_at?->toDateTimeString(),
            ];
        })->sortBy('step_order')->values();

        return Inertia::render('ApprovalWorkflow/Show', [
            'workflow' => [
                'id' => $workflow->id,
                'uuid' => $workflow->uuid,
                'name' => $workflow->name,
                'process_type' => $workflow->process_type,
                'is_active' => $workflow->is_active,
                'created_at' => $workflow->created_at?->toDateTimeString(),
                'updated_at' => $workflow->updated_at?->toDateTimeString(),
            ],
            'steps' => $steps,
            'currentPage' => 'approval.workflow.index',
        ]);
    }

    /**
     * Show the form for editing the specified workflow.
     */
    public function edit($id)
    {
        $workflow = ApprovalFlow::with(['steps.requiredRole:id,name'])
            ->findOrFail($id);

        $roles = Role::orderBy('name')->get(['id', 'name']);

        $steps = $workflow->steps->map(function ($step) {
            return [
                'id' => $step->id,
                'uuid' => $step->uuid,
                'name' => $step->name,
                'step_order' => $step->step_order,
                'required_role_id' => $step->required_role_id,
                'role_name' => $step->requiredRole?->name ?? '-',
                'sla_hours' => $step->sla_hours,
            ];
        })->sortBy('step_order')->values();

        return Inertia::render('ApprovalWorkflow/Edit', [
            'workflow' => [
                'id' => $workflow->id,
                'uuid' => $workflow->uuid,
                'name' => $workflow->name,
                'process_type' => $workflow->process_type,
                'is_active' => $workflow->is_active,
            ],
            'steps' => $steps,
            'roles' => $roles,
            'currentPage' => 'approval.workflow.index',
        ]);
    }

    /**
     * Update the specified workflow in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.id' => ['nullable', 'exists:vl_approval_steps,id'],
            'steps.*.name' => ['required', 'string', 'max:255'],
            'steps.*.step_order' => ['required', 'integer', 'min:1'],
            'steps.*.required_role_id' => ['required', 'exists:vl_roles,id'],
            'steps.*.sla_hours' => ['nullable', 'integer', 'min:1'],
        ]);

        return DB::transaction(function () use ($id, $validated) {
            $workflow = ApprovalFlow::findOrFail($id);

            // Update workflow - process_type is NOT updated (set only on create)
            $workflow->update([
                'name' => $validated['name'],
            ]);

            // Get existing step IDs
            $existingStepIds = $workflow->steps()->pluck('id')->toArray();
            $submittedStepIds = collect($validated['steps'])
                ->pluck('id')
                ->filter()
                ->toArray();

            // Delete steps that are not in the submitted data
            $toDelete = array_diff($existingStepIds, $submittedStepIds);
            if (!empty($toDelete)) {
                // Use Eloquent delete to trigger Auditable trait
                ApprovalStep::whereIn('id', $toDelete)->get()->each->delete();
            }

            // Update or create steps
            foreach ($validated['steps'] as $stepData) {
                if (!empty($stepData['id'])) {
                    // Update existing step - use find() + update() to trigger Auditable
                    $step = ApprovalStep::find($stepData['id']);
                    if ($step) {
                        $step->update([
                            'name' => $stepData['name'],
                            'step_order' => $stepData['step_order'],
                            'required_role_id' => $stepData['required_role_id'],
                            'sla_hours' => $stepData['sla_hours'] ?? 24, // Default 24 jam
                        ]);
                    }
                } else {
                    // Create new step
                    ApprovalStep::create([
                        'uuid' => Str::uuid()->toString(),
                        'approval_flow_id' => $workflow->id,
                        'name' => $stepData['name'],
                        'step_order' => $stepData['step_order'],
                        'required_role_id' => $stepData['required_role_id'],
                        'sla_hours' => $stepData['sla_hours'] ?? 24, // Default 24 jam
                    ]);
                }
            }

            // NOTE: Status mapping feature disabled - StatusWorkflowMapping model removed

            return redirect()->route('approval.workflow.index')
                ->with('success', 'Workflow berhasil diperbarui!');
        });
    }

    /**
     * Remove the specified workflow from storage.
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $workflow = ApprovalFlow::findOrFail($id);

            // Check if workflow has any instances with ACTIVE projects (not soft-deleted)
            $activeInstancesCount = $workflow->instances()
                ->whereHas('project', function ($q) {
                    $q->where('is_deleted', false);
                })
                ->count();

            if ($activeInstancesCount > 0) {
                return back()->with('error', "Workflow tidak dapat dihapus karena masih digunakan oleh {$activeInstancesCount} project aktif.");
            }

            $workflow->delete(); // This will cascade delete steps

            return redirect()->route('approval.workflow.index')
                ->with('success', 'Workflow berhasil dihapus!');
        });
    }
}
