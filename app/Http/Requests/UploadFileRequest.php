<?php

namespace App\Http\Requests;

use App\Services\FileUploadService;

class UploadFileRequest extends ApiRequest
{
    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:'.(int) (FileUploadService::MAX_UPLOAD_SESSION_BYTES / 1024),
            ],
        ];
    }
}
