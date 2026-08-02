<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiActionRequest;
use App\Http\Requests\AiUploadRequest;
use App\Models\AiJob;
use App\Models\AiOutput;
use App\Models\AiUpload;
use App\Services\AiAnalysisService;
use App\Services\AiUploadService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiUploadController extends Controller
{
    public function __construct(
        private readonly AiUploadService $aiUploadService,
        private readonly AiAnalysisService $aiAnalysisService,
    ) {}

    public function store(AiUploadRequest $request): JsonResponse
    {
        $upload = $this->aiUploadService->store($request->user(), $request->file('file'));

        return ApiResponse::success(
            data: $this->uploadPayload($upload),
            message: 'AI upload created successfully.',
            status: 201,
        );
    }

    public function index(Request $request): JsonResponse
    {
        $uploads = AiUpload::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return ApiResponse::success(
            data: $uploads->map(fn (AiUpload $upload): array => $this->uploadPayload($upload))->all(),
            message: 'AI uploads retrieved successfully.',
            meta: ['count' => $uploads->count()],
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $upload = $this->findOwnedUpload($request, $id);

        if (! $upload instanceof AiUpload) {
            return $this->uploadNotFoundResponse();
        }

        return ApiResponse::success(
            data: $this->uploadPayload($upload),
            message: 'AI upload retrieved successfully.',
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $upload = $this->findOwnedUpload($request, $id);

        if (! $upload instanceof AiUpload) {
            return $this->uploadNotFoundResponse();
        }

        $this->aiUploadService->deletePhysicalFile($upload);
        $upload->delete();

        return ApiResponse::success(message: 'AI upload deleted successfully.');
    }

    public function analyze(AiActionRequest $request, int $id): JsonResponse
    {
        return $this->createJobResponse($request, $id, 'analyze');
    }

    public function transcribe(AiActionRequest $request, int $id): JsonResponse
    {
        return $this->createJobResponse($request, $id, 'transcribe');
    }

    public function summarize(AiActionRequest $request, int $id): JsonResponse
    {
        return $this->createJobResponse($request, $id, 'summarize');
    }

    public function translate(AiActionRequest $request, int $id): JsonResponse
    {
        return $this->createJobResponse($request, $id, 'translate');
    }

    public function extract(AiActionRequest $request, int $id): JsonResponse
    {
        return $this->createJobResponse($request, $id, 'extract');
    }

    public function outputs(Request $request, int $id): JsonResponse
    {
        $upload = $this->findOwnedUpload($request, $id);

        if (! $upload instanceof AiUpload) {
            return $this->uploadNotFoundResponse();
        }

        $outputs = $upload->outputs()->latest()->get();

        return ApiResponse::success(
            data: $outputs->map(fn (AiOutput $output): array => $this->outputPayload($output))->all(),
            message: 'AI outputs retrieved successfully.',
            meta: ['count' => $outputs->count()],
        );
    }

    private function createJobResponse(AiActionRequest $request, int $id, string $jobType): JsonResponse
    {
        $upload = $this->findOwnedUpload($request, $id);

        if (! $upload instanceof AiUpload) {
            return $this->uploadNotFoundResponse();
        }

        $job = $this->aiAnalysisService->createJob($upload, $jobType, $request->validated());

        return ApiResponse::success(
            data: $this->jobPayload($job),
            message: 'AI job completed successfully.',
            status: 201,
        );
    }

    private function findOwnedUpload(Request $request, int $id): ?AiUpload
    {
        return AiUpload::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function uploadPayload(AiUpload $upload): array
    {
        return [
            'id' => $upload->id,
            'source_file_id' => $upload->source_file_id,
            'source_type' => $upload->source_type,
            'original_name' => $upload->original_name,
            'display_name' => $upload->display_name,
            'mime_type' => $upload->mime_type,
            'extension' => $upload->extension,
            'size_bytes' => $upload->size_bytes,
            'storage_disk' => $upload->storage_disk,
            'storage_path' => $upload->storage_path,
            'checksum' => $upload->checksum,
            'file_kind' => $upload->file_kind,
            'status' => $upload->status,
            'detected_language' => $upload->detected_language,
            'metadata' => $upload->metadata,
            'created_at' => $upload->created_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function jobPayload(AiJob $job): array
    {
        return [
            'id' => $job->id,
            'ai_upload_id' => $job->ai_upload_id,
            'job_type' => $job->job_type,
            'provider' => $job->provider,
            'model' => $job->model,
            'status' => $job->status,
            'input_options' => $job->input_options,
            'output_text' => $job->output_text,
            'output_json' => $job->output_json,
            'error_message' => $job->error_message,
            'credit_cost' => $job->credit_cost,
            'started_at' => $job->started_at,
            'completed_at' => $job->completed_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function outputPayload(AiOutput $output): array
    {
        return [
            'id' => $output->id,
            'ai_upload_id' => $output->ai_upload_id,
            'ai_job_id' => $output->ai_job_id,
            'output_type' => $output->output_type,
            'title' => $output->title,
            'content_text' => $output->content_text,
            'content_json' => $output->content_json,
            'storage_disk' => $output->storage_disk,
            'storage_path' => $output->storage_path,
            'metadata' => $output->metadata,
            'created_at' => $output->created_at,
        ];
    }

    private function uploadNotFoundResponse(): JsonResponse
    {
        return ApiResponse::error(
            message: 'AI upload not found.',
            status: 404,
            code: 'AI_UPLOAD_NOT_FOUND',
        );
    }
}
