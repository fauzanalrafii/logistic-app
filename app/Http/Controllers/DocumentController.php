<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\ReplaceDocumentFileRequest;
use App\Models\Document;
use App\Models\Status;
use App\Models\Project;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'project_id',
            'document_type',
            'date_from',
            'date_to',
            'q',
            'status_id',
            'per_page',
        ]);

        $perPage = (int) ($request->get('per_page', 10));
        if ($perPage <= 0) $perPage = 10;

        $query = $this->documentService
            ->buildSearchQuery($filters)
            ->with(['project', 'status']);

        $documents = $query->paginate($perPage)->withQueryString();

        $docTypes = collect(DocumentService::DOC_TYPES)
            ->map(fn ($cfg, $code) => [
                'code'  => $code,
                'label' => $cfg['label'],
            ])
            ->values()
            ->all();

        $projectOptions = Project::orderBy('name')->get(['id', 'name', 'code']);

        $statusOptions = Status::document()
            ->orderBy('no')
            ->get(['id', 'name', 'description']);

        return Inertia::render('documents/index', [
            'documents'      => $documents,
            'filters'        => [
                ...$filters,
                'per_page' => $perPage,
            ],
            'docTypes'       => $docTypes,
            'projectOptions' => $projectOptions,
            'statusOptions'  => $statusOptions,
        ]);
    }

    public function create(): Response
    {
        $docTypeOptions = collect(DocumentService::DOC_TYPES)
            ->map(fn ($cfg, $code) => [
                'code'  => $code,
                'label' => $cfg['label'],
            ])
            ->values()
            ->all();

        $projectOptions = Project::orderBy('name')->get(['id', 'name', 'code']);

        $statusOptions = Status::document()
            ->orderBy('no')
            ->get(['id', 'name', 'description']);

        return Inertia::render('documents/create', [
            'docTypeOptions' => $docTypeOptions,
            'projectOptions' => $projectOptions,
            'statusOptions'  => $statusOptions,
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $file = $request->file('file');

        if (!empty($data['doc_code'])) {
            $this->documentService->storeByCode($file, $data['doc_code'], $data);
        } else {
            $data['document_type'] = $data['document_type'] ?? 'LAINNYA';
            $this->documentService->storeDocument($file, $data);
        }

        return redirect()
            ->route('documents.index')
            ->with('success', 'Dokumen berhasil diunggah.');
    }

    /**
     * ✅ Form edit dokumen (buat ganti file / update data)
     */
    public function edit(Document $document): Response
    {
        $docTypeOptions = collect(DocumentService::DOC_TYPES)
            ->map(fn ($cfg, $code) => [
                'code'  => $code,
                'label' => $cfg['label'],
            ])
            ->values()
            ->all();

        $projectOptions = Project::orderBy('name')->get(['id', 'name', 'code']);

        $statusOptions = Status::document()
            ->orderBy('no')
            ->get(['id', 'name', 'description']);

        return Inertia::render('documents/edit', [
            'document' => $document->load(['project', 'status']),
            'docTypeOptions' => $docTypeOptions,
            'projectOptions' => $projectOptions,
            'statusOptions'  => $statusOptions,
        ]);
    }

    /**
     * ✅ Update dokumen: bisa ganti file + update field lain
     */

    public function replaceFile(Document $document): Response
    {
        // kalau mau pakai access rule yang sama:
        $auth   = Auth::user();
        $userId = $auth?->ID ?? $auth?->id ?? null;
        $roles  = [];

        if (!$this->documentService->userCanAccess($document, $userId, $roles)) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah dokumen ini.');
        }

        return Inertia::render('documents/replace-file', [
            'document' => [
                'id'            => $document->id,
                'document_type' => $document->document_type,
                'file_name'     => $document->file_name,
            ],
        ]);
    }

    public function updateFile(ReplaceDocumentFileRequest $request, Document $document): RedirectResponse
    {
        $auth   = Auth::user();
        $userId = $auth?->ID ?? $auth?->id ?? null;
        $roles  = [];

        if (!$this->documentService->userCanAccess($document, $userId, $roles)) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah dokumen ini.');
        }

        $file = $request->file('file');

        $this->documentService->replaceFileOnly($document, $file);

        return redirect()
            ->route('documents.index')
            ->with('success', 'File dokumen berhasil diganti.');
    }

    
    public function download(Document $document, Request $request)
    {
        $auth   = Auth::user();
        $userId = $auth?->ID ?? $auth?->id ?? null;
        $roles  = [];

        if (!$this->documentService->userCanAccess($document, $userId, $roles)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        return $this->documentService->download($document);
    }

    /**
     * View dokumen (inline di browser).
     */
    public function view(Document $document, Request $request)
    {
        $auth   = Auth::user();
        $userId = $auth?->ID ?? $auth?->id ?? null;
        $roles  = [];

        if (!$this->documentService->userCanAccess($document, $userId, $roles)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        // Return file untuk preview di browser (inline)
        $path = storage_path('app/public/' . $document->file_path);

        if (!file_exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return response()->file($path, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"'
        ]);
    }

    /**
     * Hapus dokumen.
     */
    public function destroy(Document $document, Request $request): RedirectResponse
    {
        $auth   = Auth::user();
        $userId = $auth?->ID ?? $auth?->id ?? null;
        $roles  = [];

        if (!$this->documentService->userCanAccess($document, $userId, $roles)) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus dokumen ini.');
        }

        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()
            ->back()
            ->with('success', 'Dokumen berhasil dihapus.');
    }
}
