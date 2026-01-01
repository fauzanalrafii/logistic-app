<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Logging\AppLogger;

class DocumentService
{
    // max 10MB
    public const MAX_FILE_SIZE_KB = 10240;

    /**
     * ✅ ekstensi allowed (fallback kalau MIME kebaca aneh, misal application/octet-stream)
     */
    public const ALLOWED_EXTENSIONS = [
        'pdf',
        'jpeg', 'jpg', 'png', 'webp',
        'txt',
        'xls', 'xlsx',
        'doc', 'docx',
    ];

    /**
     * ✅ MIME types allowed (deteksi server bisa beda-beda tergantung OS / php-fileinfo)
     */
    public const ALLOWED_MIME_TYPES = [
        // PDF (variasi yang sering muncul)
        'application/pdf',
        'application/x-pdf',
        'application/acrobat',
        'applications/vnd.pdf',
        'text/pdf',

        // Images
        'image/jpeg',
        'image/png',
        'image/webp',

        // Text
        'text/plain',

        // Excel
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

        // Word
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    // mapping jenis dokumen deployment
    public const DOC_TYPES = [
        'kelengkapan_spk_survey' => [
            'label'  => 'KELENGKAPAN DOKUMEN SPK SURVEY',
            'folder' => 'spk_survey_kelengkapan',
        ],
        'spk_survey_ttd' => [
            'label'  => 'DOKUMEN SPK SURVEY YANG SUDAH DI TTD',
            'folder' => 'spk_survey_ttd',
        ],
        'kelengkapan_spk_implementasi' => [
            'label'  => 'KELENGKAPAN DOKUMEN SPK IMPLEMENTASI',
            'folder' => 'spk_implementasi_kelengkapan',
        ],
        'spk_implementasi_ttd' => [
            'label'  => 'DOKUMEN SPK IMPLEMENTASI YANG SUDAH DI TTD',
            'folder' => 'spk_implementasi_ttd',
        ],
        'ba_lop' => [
            'label'  => 'BA LOP',
            'folder' => 'ba_lop',
        ],
        'kelengkapan_bast' => [
            'label'  => 'DOKUMEN KELENGKAPAN BAST',
            'folder' => 'bast_kelengkapan',
        ],
    ];

    /**
     * Ambil default status dokumen (DRAFT).
     */
    protected function defaultDocumentStatusId(): ?int
    {
        return Status::where('type', 'document')
            ->where('name', 'DRAFT')
            ->value('id');
    }

    /**
     * Upload generic (document_type bebas).
     */
    public function storeDocument(UploadedFile $file, array $data): Document
    {
        $documentType = $data['document_type'] ?? 'LAINNYA';

        $statusId = $data['status_id'] ?? null;
        if (!$statusId) {
            $statusId = $this->defaultDocumentStatusId();
        }

        $docTypeSlug = Str::slug($documentType, '_');
        $path        = $file->store("documents/{$docTypeSlug}", 'public');

        $uploadedBy  = $this->resolveUploadedBy($data['uploaded_by'] ?? null);

        return Document::create([
            'project_id'    => $data['project_id']    ?? null,
            'related_type'  => $data['related_type']  ?? null,
            'related_id'    => $data['related_id']    ?? null,
            'document_type' => $documentType,
            'file_path'     => $path,
            'file_name'     => $file->getClientOriginalName(),
            'file_size'     => $file->getSize(),
            'mime_type'     => $file->getClientMimeType(),
            'status_id'     => $statusId,
            'uploaded_by'   => $uploadedBy,
            'uploaded_at'   => now(),
        ]);
    }

    /**
     * Upload berdasarkan code di DOC_TYPES.
     */
    public function storeByCode(UploadedFile $file, string $code, array $data = []): Document
    {
        if (!isset(self::DOC_TYPES[$code])) {
            throw new \InvalidArgumentException("Unknown document code: {$code}");
        }

        $config       = self::DOC_TYPES[$code];
        $documentType = $config['label'];
        $folder       = $config['folder'];

        $statusId = $data['status_id'] ?? null;
        if (!$statusId) {
            $statusId = $this->defaultDocumentStatusId();
        }

        $path       = $file->store("documents/{$folder}", 'public');
        $uploadedBy = $this->resolveUploadedBy($data['uploaded_by'] ?? null);

        return Document::create([
            'project_id'    => $data['project_id']    ?? null,
            'related_type'  => $data['related_type']  ?? null,
            'related_id'    => $data['related_id']    ?? null,
            'document_type' => $documentType,
            'file_path'     => $path,
            'file_name'     => $file->getClientOriginalName(),
            'file_size'     => $file->getSize(),
            'mime_type'     => $file->getClientMimeType(),
            'status_id'     => $statusId,
            'uploaded_by'   => $uploadedBy,
            'uploaded_at'   => now(),
        ]);
    }

    /**
     * Ganti file dokumen yang sudah ada.
     */
    public function replaceFileOnly(Document $document, UploadedFile $file): Document
    {
        // 1) hapus file lama
        if (!empty($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        // 2) tentukan folder berdasarkan document_type (label) -> DOC_TYPES
        $folder = null;

        foreach (self::DOC_TYPES as $code => $cfg) {
            if (($cfg['label'] ?? null) === $document->document_type) {
                $folder = $cfg['folder'] ?? null;
                break;
            }
        }

        // fallback kalau tidak ketemu di mapping
        if (!$folder) {
            $folder = Str::slug($document->document_type ?: 'lainnya', '_');
        }

        // 3) simpan file baru
        $path = $file->store("documents/{$folder}", 'public');

        // 4) update metadata dokumen
        $document->update([
            'file_path'   => $path,
            'file_name'   => $file->getClientOriginalName(),
            'file_size'   => $file->getSize(),
            'mime_type'   => $file->getClientMimeType(),
            'uploaded_at' => now(),
        ]);

        return $document->refresh();
    }

    /**
     * Download dokumen dari storage.
     */
    public function download(Document $document)
    {
        AppLogger::activity('document_downloaded', [
            'document_id'   => $document->id,
            'document_type' => $document->document_type,
            'file_name'     => $document->file_name,
        ]);

        return Storage::disk('public')->download(
            $document->file_path,
            $document->file_name
        );
    }

    /**
     * Query untuk pencarian/filter dokumen.
     */
    public function buildSearchQuery(array $filters): Builder
    {
        $query = Document::query();

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['document_type'])) {
            $query->where('document_type', $filters['document_type']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('uploaded_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('uploaded_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['q'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('file_name', 'like', '%'.$filters['q'].'%')
                  ->orWhere('document_type', 'like', '%'.$filters['q'].'%');
            });
        }

        if (!empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        return $query->orderByDesc('uploaded_at');
    }

    /**
     * Kontrol akses sederhana.
     */
    public function userCanAccess(Document $document, ?int $userId, array $roles = []): bool
    {
        if (!$userId) {
            return true;
        }

        if (in_array('admin', $roles, true)) {
            return true;
        }

        if ($document->uploaded_by === $userId) {
            return true;
        }

        return false;
    }

    /**
     * Cari siapa uploader-nya.
     */
    protected function resolveUploadedBy(?int $explicit = null): ?int
    {
        if ($explicit) {
            return $explicit;
        }

        if (Auth::check()) {
            $auth = Auth::user();
            return $auth?->ID ?? $auth?->id ?? null;
        }

        $sessionUser = Session::get('user');
        if (is_array($sessionUser) && isset($sessionUser['id'])) {
            return $sessionUser['id'];
        }

        return null;
    }
}
