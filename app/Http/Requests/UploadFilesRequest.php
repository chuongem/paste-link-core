<?php

namespace App\Http\Requests;

use App\Services\FileUploadService;
use Illuminate\Validation\Validator;

class UploadFilesRequest extends ApiRequest
{
    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*' => [
                'required',
                'file',
                'max:'.(int) (FileUploadService::MAX_UPLOAD_SESSION_BYTES / 1024),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $totalSize = collect($this->file('files', []))
                    ->sum(fn ($file): int => $file->getSize() ?: 0);

                if ($totalSize > FileUploadService::MAX_UPLOAD_SESSION_BYTES) {
                    $validator->errors()->add('files', 'The upload session may not be greater than 300 megabytes.');
                }
            },
        ];
    }
}
