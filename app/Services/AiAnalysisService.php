<?php

namespace App\Services;

use App\Ai\Providers\AiProviderManager;
use App\Models\AiJob;
use App\Models\AiOutput;
use App\Models\AiUpload;

class AiAnalysisService
{
    public function __construct(
        private readonly AiProviderManager $providerManager,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function createJob(AiUpload $upload, string $jobType, array $options = []): AiJob
    {
        $job = AiJob::query()->create([
            'ai_upload_id' => $upload->id,
            'job_type' => $jobType,
            'provider' => (string) config('ai.provider', 'local'),
            'model' => (string) config('ai.model', 'local-analysis'),
            'status' => 'processing',
            'input_options' => $options,
            'credit_cost' => $this->creditCost($jobType, $upload->file_kind),
            'started_at' => now(),
        ]);

        $output = $this->providerManager
            ->provider($job->provider)
            ->process($upload, $jobType, $options);

        $job->update([
            'status' => 'completed',
            'output_text' => $output['content_text'],
            'output_json' => $output['content_json'],
            'completed_at' => now(),
        ]);

        $upload->update(['status' => 'completed']);

        AiOutput::query()->create([
            'ai_upload_id' => $upload->id,
            'ai_job_id' => $job->id,
            'output_type' => $output['output_type'],
            'title' => $output['title'],
            'content_text' => $output['content_text'],
            'content_json' => $output['content_json'],
            'metadata' => [
                'provider' => $job->provider,
                'model' => $job->model,
                'file_kind' => $upload->file_kind,
            ],
        ]);

        return $job->refresh();
    }

    private function creditCost(string $jobType, string $fileKind): int
    {
        return match ($jobType) {
            'transcribe' => 300,
            'summarize', 'translate' => 100,
            'extract' => 150,
            'analyze' => match ($fileKind) {
                'audio' => 300,
                'video' => 400,
                default => 100,
            },
            default => 0,
        };
    }
}
