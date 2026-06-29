<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadFileRequest;
use App\Http\Requests\UploadFilesRequest;
use App\Models\File;
use App\Services\FileUploadService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class FileController extends Controller
{
    public function __construct(
        private readonly FileUploadService $fileUploadService,
    ) {}

    /**
     * Upload one file for the authenticated user.
     */
    public function store(UploadFileRequest $request): JsonResponse
    {
        // Resolve the authenticated owner for the uploaded file record.
        $user = $request->user();

        $file = $this->fileUploadService->store($user, $request->file('file'));

        return ApiResponse::success(
            data: $this->filePayload($file),
            message: 'File uploaded successfully.',
            status: 201,
        );
    }

    /**
     * Upload multiple files for the authenticated user.
     */
    public function storeMany(UploadFilesRequest $request): JsonResponse
    {
        // Resolve the authenticated owner for all uploaded file records.
        $user = $request->user();

        // Keep the files as an ordered list for stable response ordering.
        $uploadedFiles = $request->file('files', []);
        $files = $this->fileUploadService->storeMany($user, $uploadedFiles);

        return ApiResponse::success(
            data: array_map(fn (File $file): array => $this->filePayload($file), $files),
            message: 'Files uploaded successfully.',
            status: 201,
            meta: ['count' => count($files)],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function filePayload(File $file): array
    {
        return [
            'id' => $file->id,
            'disk' => $file->disk,
            'path' => $file->path,
            'url' => $this->fileUploadService->url($file),
            'original_name' => $file->original_name,
            'stored_name' => $file->stored_name,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'created_at' => $file->created_at,
        ];
    }
}
