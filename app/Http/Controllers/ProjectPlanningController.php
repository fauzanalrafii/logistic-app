<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\BoqHeader;
use App\Models\BoqItem;
use App\Models\BepProjection;
use App\Models\ApprovalFlow;
use App\Models\ApprovalInstance;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProjectPlanningController extends Controller
{
    /**
     * Show planning page with existing BOQ/BEP data
     */
    public function index(Request $request)
    {
        $id = $request->integer('id');
        abort_unless($id, 404, 'Project ID tidak valid.');

        $project = Project::query()
            ->with(['planner:ID,Name'])
            ->findOrFail($id);

        // Get latest BOQ ON_DESK with items and status
        $boqHeader = BoqHeader::with(['items', 'status'])
            ->where('project_id', $project->id)
            ->where('type', 'ON_DESK')
            ->latest()
            ->first();

        // Get latest BEP projection with status
        $bepProjection = BepProjection::with('status')
            ->where('project_id', $project->id)
            ->latest()
            ->first();

        // Get approval instance if exists
        $approvalInstance = ApprovalInstance::with(['flow.steps', 'actions'])
            ->where('project_id', $project->id)
            ->where('related_type', 'planning')
            ->latest()
            ->first();

        $document = DB::table('vl_documents')
            ->where('project_id', $project->id)
            ->where('document_type', 'KELENGKAPAN_SPK_SURVEY')
            ->orderByDesc('created_at')
            ->first();

        return Inertia::render('projects/Planning', [
            'project' => [
                'id'      => $project->id,
                'code'    => $project->code,
                'name'    => $project->name,
                'planner' => $project->planner,
            ],
            'boqHeader' => $boqHeader ? [
                'id' => $boqHeader->id,
                'type' => $boqHeader->type,
                'version' => $boqHeader->version,
                'status' => $boqHeader->status?->name ?? 'DRAFT',
                'total_cost_estimate' => $boqHeader->total_cost_estimate,
                'items' => $boqHeader->items->map(fn($item) => [
                    'id' => $item->id,
                    'material_code' => $item->material_code,
                    'material_description' => $item->material_description,
                    'uom' => $item->uom,
                    'qty' => (float) $item->qty,
                    'unit_price_estimate' => (float) $item->unit_price_estimate,
                    'remarks' => $item->remarks,
                ]),
            ] : null,
            'bepProjection' => $bepProjection ? [
                'id' => $bepProjection->id,
                'version' => $bepProjection->version,
                'capex' => (float) $bepProjection->capex,
                'opex_estimate' => (float) $bepProjection->opex_estimate,
                'revenue_estimate' => (float) $bepProjection->revenue_estimate,
                'bep_months' => $bepProjection->bep_months,
                'status' => $bepProjection->status?->name ?? 'DRAFT',
            ] : null,
            'approvalInstance' => $approvalInstance ? [
                'id' => $approvalInstance->id,
                'status' => $approvalInstance->status?->name ?? 'PENDING',
                'progress' => $approvalInstance->getProgressLabel(),
                'current_step' => $approvalInstance->getCurrentStepOrder(),
                'total_steps' => $approvalInstance->getTotalSteps(),
                'is_rejected' => $approvalInstance->isRejected(),
                'rejection_reason' => $approvalInstance->isRejected()
                    ? $approvalInstance->actions()
                    ->where('action', 'REJECT')
                    ->latest('acted_at')
                    ->first()?->comment
                    : null,
            ] : null,
            'isSubmitted' => $project->isPlanningSubmitted(),
            'canRevise' => $approvalInstance?->isRejected() ?? false,
            'document' => $document,
        ]);
    }

    /**
     * Store BOQ header and items (draft)
     */
    public function storeBoq(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:vl_projects,id',
            'type' => 'required|in:ON_DESK,ON_SITE',
            'items' => 'required|array|min:1',
            'items.*.material_code' => 'nullable|string|max:100',
            'items.*.material_description' => 'required|string|max:255',
            'items.*.uom' => 'nullable|string|max:50',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.unit_price_estimate' => 'nullable|numeric|min:0',
            'items.*.remarks' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $project = Project::findOrFail($request->project_id);

            // Check if can edit - block if submitted AND not rejected
            $existingApproval = ApprovalInstance::where('project_id', $project->id)
                ->where('related_type', 'planning')
                ->latest()
                ->first();

            if ($project->isPlanningSubmitted() && !($existingApproval?->isRejected())) {
                return back()->withErrors(['error' => 'Planning sudah di-submit, tidak bisa diubah.']);
            }

            // Get draft status ID
            $draftStatus = Status::where('type', 'planning')->where('name', 'DRAFT')->first();
            $draftStatusId = $draftStatus?->id;

            // Always create new BOQ header for revision (to preserve history)
            // Get next version
            $lastVersion = BoqHeader::where('project_id', $project->id)
                ->where('type', $request->type)
                ->max('version') ?? 0;

            $boqHeader = BoqHeader::create([
                'uuid' => Str::uuid(),
                'project_id' => $project->id,
                'type' => $request->type,
                'version' => $lastVersion + 1,
                'status_id' => $draftStatusId,
                'created_by' => Auth::id(),
            ]);

            // Delete existing items and recreate
            $boqHeader->items()->delete();

            $totalCost = 0;
            $itemIndex = 0;
            foreach ($request->items as $itemData) {
                $itemIndex++;
                $subtotal = ($itemData['qty'] ?? 0) * ($itemData['unit_price_estimate'] ?? 0);
                $totalCost += $subtotal;

                // Auto-generate material code if not provided
                $materialCode = $itemData['material_code'] ?? null;
                if (empty($materialCode)) {
                    $materialCode = 'MAT-' . strtoupper(Str::random(4)) . '-' . str_pad($itemIndex, 3, '0', STR_PAD_LEFT);
                }

                BoqItem::create([
                    'uuid' => Str::uuid(),
                    'boq_header_id' => $boqHeader->id,
                    'material_code' => $materialCode,
                    'material_description' => $itemData['material_description'],
                    'uom' => $itemData['uom'] ?? 'Unit',
                    'qty' => $itemData['qty'] ?? 0,
                    'unit_price_estimate' => $itemData['unit_price_estimate'] ?? 0,
                    'remarks' => $itemData['remarks'] ?? null,
                ]);
            }

            // Update total cost
            $boqHeader->update(['total_cost_estimate' => $totalCost]);

            DB::commit();
            return back()->with('success', 'BOQ berhasil disimpan sebagai draft.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menyimpan BOQ: ' . $e->getMessage()]);
        }
    }

    /**
     * Store BEP projection (draft)
     */
    public function storeBep(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:vl_projects,id',
            'capex' => 'required|numeric|min:0',
            'opex_estimate' => 'required|numeric|min:0',
            'revenue_estimate' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $project = Project::findOrFail($request->project_id);

            // Check if can edit - block if submitted AND not rejected
            $existingApproval = ApprovalInstance::where('project_id', $project->id)
                ->where('related_type', 'planning')
                ->latest()
                ->first();

            if ($project->isPlanningSubmitted() && !($existingApproval?->isRejected())) {
                return back()->withErrors(['error' => 'Planning sudah di-submit, tidak bisa diubah.']);
            }

            // Get draft status ID
            $draftStatus = Status::where('type', 'planning')->where('name', 'DRAFT')->first();
            $draftStatusId = $draftStatus?->id;

            // Calculate BEP months
            $net = $request->revenue_estimate - $request->opex_estimate;
            $bepMonths = $net > 0 ? (int) ceil($request->capex / $net) : 0;

            // Always create new BEP for revision (to preserve history)
            // Get next version
            $lastVersion = BepProjection::where('project_id', $project->id)->max('version') ?? 0;

            BepProjection::create([
                'uuid' => Str::uuid(),
                'project_id' => $project->id,
                'version' => $lastVersion + 1,
                'capex' => $request->capex,
                'opex_estimate' => $request->opex_estimate,
                'revenue_estimate' => $request->revenue_estimate,
                'bep_months' => $bepMonths,
                'status_id' => $draftStatusId,
                'created_by' => Auth::id(),
            ]);

            DB::commit();
            return back()->with('success', 'BEP berhasil disimpan sebagai draft.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menyimpan BEP: ' . $e->getMessage()]);
        }
    }

    /**
     * Submit planning for approval (BOQ + BEP must be filled)
     */
    public function submit(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:vl_projects,id',
        ]);

        DB::beginTransaction();
        try {
            $project = Project::findOrFail($request->project_id);

            // Check for existing approval instance
            $existingApproval = ApprovalInstance::where('project_id', $project->id)
                ->where('related_type', 'planning')
                ->latest()
                ->first();

            // If already submitted and NOT rejected, block
            if ($project->isPlanningSubmitted() && !($existingApproval?->isRejected())) {
                return back()->withErrors(['error' => 'Planning sudah di-submit sebelumnya.']);
            }

            // If rejected, delete old approval instance (will create new one below)
            if ($existingApproval?->isRejected()) {
                $existingApproval->actions()->delete();
                $existingApproval->delete();
            }

            // *** IMPORTANT: Check approval flow FIRST before any status changes ***
            $approvalFlow = ApprovalFlow::getByProcessType('PLAN_ON_DESK_PLANNING');
            if (!$approvalFlow) {
                return back()->withErrors(['error' => 'Approval workflow belum dikonfigurasi. Hubungi admin untuk setup workflow "PLAN_ON_DESK_PLANNING".']);
            }

            // Check if flow has steps
            if ($approvalFlow->steps()->count() === 0) {
                return back()->withErrors(['error' => 'Approval workflow tidak memiliki step. Hubungi admin untuk menambahkan step approval.']);
            }

            // Get status IDs
            $draftStatus = Status::where('type', 'planning')->where('name', 'DRAFT')->first();
            $submittedStatus = Status::where('type', 'planning')->where('name', 'SUBMITTED')->first();
            $submittedStatusId = $submittedStatus?->id;

            // Get latest BOQ (prioritize DRAFT for revision case)
            $boqHeader = BoqHeader::where('project_id', $project->id)
                ->where('type', 'ON_DESK')
                ->latest()
                ->first();

            if (!$boqHeader || $boqHeader->items->isEmpty()) {
                return back()->withErrors(['error' => 'BOQ belum diisi. Harap isi minimal 1 item BOQ.']);
            }

            // Get latest BEP (prioritize DRAFT for revision case)
            $bepProjection = BepProjection::where('project_id', $project->id)
                ->latest()
                ->first();

            if (!$bepProjection || $bepProjection->capex <= 0) {
                return back()->withErrors(['error' => 'BEP belum diisi. Harap isi data CAPEX.']);
            }

            // Update BOQ status
            $boqHeader->update([
                'status_id' => $submittedStatusId,
                'submitted_at' => now(),
            ]);

            // Update BEP status
            $bepProjection->update([
                'status_id' => $submittedStatusId,
                'submitted_at' => now(),
            ]);

            // Create approval instance (flow is guaranteed to exist at this point)
            $pendingStatus = Status::where('type', 'approval')->where('name', 'PENDING')->first();

            ApprovalInstance::create([
                'uuid' => Str::uuid(),
                'approval_flow_id' => $approvalFlow->id,
                'project_id' => $project->id,
                'related_type' => 'planning',
                'related_id' => $boqHeader->id,
                'status_id' => $pendingStatus?->id,
                'started_at' => now(),
            ]);

            DB::commit();
            return redirect()->route('projects.plan_on_desk')
                ->with('success', 'Planning berhasil di-submit untuk approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal submit planning: ' . $e->getMessage()]);
        }
    }

    /**
     * Revise rejected planning - reset approval and allow re-editing
     */

    public function revise(Request $request)
    {
        $request->validate([
            'project_id' => ['required', 'integer'],
        ]);

        $projectId = (int) $request->project_id;

        // TODO: pastikan user ini planner project tsb (kalau perlu)
        // contoh: cek project.planner_id == Auth::id() atau sesuai struktur kamu

        return DB::transaction(function () use ($projectId) {

            // 1) ambil flow aktif
            $flow = \App\Models\ApprovalFlow::getByProcessType('PLAN_ON_DESK_PLANNING');
            if (!$flow) {
                abort(500, 'Approval flow PLAN_ON_DESK_PLANNING belum ada / tidak aktif.');
            }

            // 2) cek instance terakhir untuk project+flow
            $last = ApprovalInstance::with('status')
                ->where('project_id', $projectId)
                ->where('approval_flow_id', $flow->id)
                ->orderByDesc('id')
                ->first();

            if (!$last) {
                return back()->with('error', 'Belum ada approval sebelumnya untuk project ini.');
            }

            // 3) wajib REJECTED dulu baru boleh revise->resubmit
            $lastStatus = strtoupper((string)($last->status?->name ?? ''));
            if ($lastStatus !== 'REJECTED') {
                return back()->with('error', 'Hanya bisa revise/resubmit jika status terakhir REJECTED.');
            }

            // 4) pastikan tidak ada instance yang masih berjalan
            $runningNames = ['PENDING', 'IN_REVIEW', 'SUBMITTED'];
            $running = ApprovalInstance::where('project_id', $projectId)
                ->where('approval_flow_id', $flow->id)
                ->whereHas('status', function ($q) use ($runningNames) {
                    $q->whereIn(DB::raw('UPPER(name)'), $runningNames);
                })
                ->exists();

            if ($running) {
                return back()->with('error', 'Masih ada approval yang sedang berjalan untuk project ini.');
            }

            // 5) buat instance baru (cycle baru) -> otomatis step balik ke awal
            $pendingStatusId = Status::whereRaw('UPPER(name)=?', ['PENDING'])->value('id')
                ?? Status::whereRaw('UPPER(name)=?', ['IN_REVIEW'])->value('id')
                ?? Status::whereRaw('UPPER(name)=?', ['SUBMITTED'])->value('id');

            if (!$pendingStatusId) {
                abort(500, "Status PENDING/IN_REVIEW/SUBMITTED tidak ditemukan di vl_status.");
            }

            $new = ApprovalInstance::create([
                'approval_flow_id' => $flow->id,
                'project_id'       => $projectId,
                'related_type'     => $last->related_type,
                'related_id'       => $last->related_id,
                'status_id'        => $pendingStatusId,
                'started_at'       => now(),
                'completed_at'     => null,
            ]);

            return redirect()->route('approval.show', $new->id)
                ->with('success', 'Revisi berhasil dikirim. Approval ulang dimulai dari Step 1.');
        });
    }
}
