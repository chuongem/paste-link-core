<?php

namespace App\Http\Requests;

class AiUploadRequest extends ApiRequest
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
                'max:'.(int) ((int) config('ai.max_upload_bytes', 300 * 1024 * 1024) / 1024),
            ],
        ];
    }
}
