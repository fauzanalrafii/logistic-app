<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Project;
use App\Models\BoqHeader;
use App\Models\BepProjection;
use Illuminate\Support\Str;


class BoqOnDeskDocumentController extends Controller
{
    public function generate(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|integer|exists:vl_projects,id',
            'mitra'      => 'required|string|max:255',
            'background' => 'nullable|string',
            'objective'  => 'nullable|string',
            'benefit'    => 'nullable|string',
        ]);

        $project = Project::findOrFail($data['project_id']);

        // ===== CEK STATUS PLANNING =====
        $planning = DB::table('vl_approval_instances as ai')
            ->join('vl_status as s', 's.id', '=', 'ai.status_id')
            ->where('ai.project_id', $project->id)
            ->where('ai.related_type', 'planning')
            ->select('s.name')
            ->orderByDesc('ai.created_at')
            ->first();

        if (!$planning) {
            return response()->json([
                'message' => 'Planning belum disubmit.'
            ], 422);
        }

        // ❌ TIDAK BOLEH GENERATE
        if (in_array($planning->name, ['DRAFT', 'REJECTED'])) {
            return response()->json([
                'message' => 'Planning belum bisa generate dokumen.'
            ], 422);
        }

        // ===== BOQ =====
        $boq = BoqHeader::with('items')
            ->where('project_id', $project->id)
            ->where('type', 'ON_DESK')
            ->latest()
            ->first();

        if (!$boq) {
            return response()->json(['message' => 'BOQ On Desk belum dibuat'], 422);
        }

        // ===== BEP =====
        $bep = BepProjection::where('project_id', $project->id)->latest()->first();

        if (!$bep) {
            return response()->json(['message' => 'BEP belum dibuat'], 422);
        }
        
        // ===== CEK DOKUMEN SUDAH PERNAH DIGENERATE =====
        $document = DB::table('vl_documents')
            ->where('project_id', $project->id)
            ->where('document_type', 'KELENGKAPAN_SPK_SURVEY')
            ->orderByDesc('created_at')
            ->first();

        if ($document && $planning->name !== 'REJECTED') {
            return response()->json([
                'message' => 'Dokumen kelengkapan sudah pernah digenerate.'
            ], 422);
        }

        // ===== PEMOHON =====
        $pemohonNama = '-';
        $pemohonJabatan = '-';

        if ($project->planner_id) {
            $user = DB::connection('mysql_user')
                ->table('user_l')
                ->where('ID', $project->planner_id)
                ->first();

            if ($user) {
                $pemohonNama = $user->Name ?? '-';
                $pemohonJabatan = $user->Position ?? '-';
            }
        }

        // ===== NOMOR SURAT =====
        $nomorSurat = sprintf(
            '%04d/AC/%s/%s',
            1,
            strtoupper(now()->format('M')),
            now()->year
        );

        // ===== PDF =====
        $filename = 'BOQ-OnDesk-' . $project->code . '.pdf';
        $path = 'documents/boq-ondesk/' . $filename;

        $pdf = Pdf::loadView('documents.boq_ondesk', [
            'project'        => $project,
            'boqItems'       => $boq->items,
            'bep'            => $bep,
            'mitra'          => $data['mitra'],
            'background'     => $data['background'],
            'objective'      => $data['objective'],
            'benefit'        => $data['benefit'],
            'nomorSurat'     => $nomorSurat,
            'pemohonNama'    => $pemohonNama,
            'pemohonJabatan' => $pemohonJabatan,
        ]);

        Storage::disk('public')->put($path, $pdf->output());

        $statusId = DB::table('vl_status')
            ->where('type', 'document')
            ->where('name', 'GENERATED')
            ->value('id') ?? 1;

        // ===== SIMPAN LOG DOKUMEN =====
        DB::table('vl_documents')->insert([
            'uuid'          => (string) Str::uuid(),
            'project_id'    => $project->id,
            'related_type'  => 'planning',
            'related_id'    => $project->id,
            'document_type' => 'KELENGKAPAN_SPK_SURVEY',
            'file_path'     => $path,
            'file_name'     => $filename,
            'file_size'     => Storage::disk('public')->size($path),
            'mime_type'     => 'application/pdf',
            'status_id'     => $statusId,
            'uploaded_by'   => auth()->id() ?? null,
            'uploaded_at'   => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return response()->json([
            'pdf_url' => url('/storage/' . $path),
        ]);
    }
}
