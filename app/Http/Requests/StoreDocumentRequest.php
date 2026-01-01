<?php

namespace App\Http\Requests;

use App\Services\DocumentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:vl_projects,id'],
            'related_type'  => ['nullable', 'string', 'max:100'],
            'related_id'    => ['nullable', 'integer'],

            'doc_code'      => [
                'nullable',
                'string',
                'max:100',
                Rule::in(array_keys(DocumentService::DOC_TYPES)),
            ],

            'document_type' => ['nullable', 'string', 'max:100'],

            // status_id: hanya boleh ambil dari vl_status type = 'document'
            'status_id'     => [
                'nullable',
                'integer',
                Rule::exists('vl_status', 'id')
                    ->where(fn ($q) => $q->where('type', 'document')),
            ],

            'file' => [
                'required',
                'file',
                'max:' . DocumentService::MAX_FILE_SIZE_KB,

                // ✅ LULUS kalau: MIME allowed ATAU extension allowed
                function (string $attribute, $value, \Closure $fail) {
                    if (!($value instanceof UploadedFile)) {
                        $fail('Upload harus berupa file.');
                        return;
                    }

                    $serverMime = strtolower((string) $value->getMimeType());
                    $clientMime = strtolower((string) $value->getClientMimeType());
                    $ext        = strtolower((string) $value->getClientOriginalExtension());

                    $mimeAllowed =
                        in_array($serverMime, DocumentService::ALLOWED_MIME_TYPES, true) ||
                        in_array($clientMime, DocumentService::ALLOWED_MIME_TYPES, true);

                    $extAllowed = in_array($ext, DocumentService::ALLOWED_EXTENSIONS, true);

                    if (!$mimeAllowed && !$extAllowed) {
                        $fail('Tipe file tidak diizinkan.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Project wajib dipilih.',
            'project_id.exists'   => 'Project tidak ditemukan.',
            'file.required'       => 'File wajib diisi.',
            'file.max'            => 'Ukuran file maksimal ' . (DocumentService::MAX_FILE_SIZE_KB / 1024) . ' MB.',
            'doc_code.in'         => 'Kode jenis dokumen tidak dikenal.',
            'status_id.exists'    => 'Status tidak valid. Pilih salah satu status dokumen yang tersedia.',
        ];
    }
}
