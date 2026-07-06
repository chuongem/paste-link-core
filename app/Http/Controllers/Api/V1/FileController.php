<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadFilesRequest;
use App\Models\File;
use App\Services\FileUploadService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function __construct(
        private readonly FileUploadService $fileUploadService,
    ) {}

    /**
     * Upload one or more files for the authenticated user.
     */
    public function store(UploadFilesRequest $request): JsonResponse
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
            meta: [
                'count' => count($files),
                'source_file_count' => count($uploadedFiles),
            ],
        );
    }

    /**
     * List files owned by the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $files = File::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return ApiResponse::success(
            data: $files->map(fn (File $file): array => $this->filePayload($file))->all(),
            message: 'Files retrieved successfully.',
            meta: ['count' => $files->count()],
        );
    }

    /**
     * Show one file owned by the authenticated user.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $file = $this->findOwnedFile($request, $id);

        if (! $file instanceof File) {
            return $this->fileNotFoundResponse();
        }

        return ApiResponse::success(
            data: $this->filePayload($file),
            message: 'File retrieved successfully.',
        );
    }

    /**
     * Delete one file record and its physical storage object.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $file = $this->findOwnedFile($request, $id);

        if (! $file instanceof File) {
            return $this->fileNotFoundResponse();
        }

        $this->fileUploadService->deletePhysicalFile($file);
        $file->delete();

        return ApiResponse::success(message: 'File deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function filePayload(File $file): array
    {
        $fullPath = $this->fileUploadService->fullPath($file);

        return [
            'id' => $file->id,
            'disk' => $file->disk,
            'path' => $file->path,
            'url' => $this->fileUploadService->url($file),
            'full_path' => $fullPath,
            'download_url' => $fullPath,
            'original_name' => $file->original_name,
            'stored_name' => $file->stored_name,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'created_at' => $file->created_at,
        ];
    }

    private function findOwnedFile(Request $request, int $id): ?File
    {
        return File::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($id)
            ->first();
    }

    private function fileNotFoundResponse(): JsonResponse
    {
        return ApiResponse::error(
            message: 'File not found.',
            status: 404,
            code: 'FILE_NOT_FOUND',
        );
    }
}
