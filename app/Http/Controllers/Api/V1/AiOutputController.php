<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AiOutput;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiOutputController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $output = AiOutput::query()
            ->whereKey($id)
            ->whereHas('upload', fn ($query) => $query->where('user_id', $request->user()->id))
            ->first();

        if (! $output instanceof AiOutput) {
            return ApiResponse::error('AI output not found.', 404, code: 'AI_OUTPUT_NOT_FOUND');
        }

        return ApiResponse::success([
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
        ], 'AI output retrieved successfully.');
    }
}
