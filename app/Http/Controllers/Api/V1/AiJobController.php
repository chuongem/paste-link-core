<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AiJob;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiJobController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $job = AiJob::query()
            ->whereKey($id)
            ->whereHas('upload', fn ($query) => $query->where('user_id', $request->user()->id))
            ->first();

        if (! $job instanceof AiJob) {
            return ApiResponse::error('AI job not found.', 404, code: 'AI_JOB_NOT_FOUND');
        }

        return ApiResponse::success([
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
            'created_at' => $job->created_at,
        ], 'AI job retrieved successfully.');
    }

    public function result(Request $request, int $id): JsonResponse
    {
        $job = AiJob::query()
            ->whereKey($id)
            ->whereHas('upload', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with('outputs')
            ->first();

        if (! $job instanceof AiJob) {
            return ApiResponse::error('AI job not found.', 404, code: 'AI_JOB_NOT_FOUND');
        }

        return ApiResponse::success([
            'job_id' => $job->id,
            'status' => $job->status,
            'outputs' => $job->outputs->map(fn ($output): array => [
                'id' => $output->id,
                'output_type' => $output->output_type,
                'title' => $output->title,
                'content_text' => $output->content_text,
                'content_json' => $output->content_json,
                'metadata' => $output->metadata,
            ])->all(),
        ], 'AI job result retrieved successfully.');
    }
}
