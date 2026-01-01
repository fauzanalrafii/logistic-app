<?php

namespace App\Http\Requests;

use App\Services\DocumentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class ReplaceDocumentFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:' . DocumentService::MAX_FILE_SIZE_KB,

                // ✅ custom validation: boleh kalau MIME ok ATAU ekstensi ok
                function (string $attribute, $value, \Closure $fail) {
                    if (!($value instanceof UploadedFile)) {
                        $fail('Upload harus berupa file.');
                        return;
                    }

                    // MIME dari server (lebih valid)
                    $serverMime = strtolower((string) $value->getMimeType());

                    // MIME dari client (kadang membantu)
                    $clientMime = strtolower((string) $value->getClientMimeType());

                    // ekstensi dari nama file
                    $ext = strtolower((string) $value->getClientOriginalExtension());

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
            'file.required' => 'File wajib diisi.',
            'file.max'      => 'Ukuran file maksimal ' . (DocumentService::MAX_FILE_SIZE_KB / 1024) . ' MB.',
        ];
    }
}
