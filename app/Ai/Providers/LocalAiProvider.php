<?php

namespace App\Ai\Providers;

use App\Models\AiUpload;

class LocalAiProvider implements AiProvider
{
    /**
     * @param  array<string, mixed>  $options
     * @return array{output_type: string, title: string, content_text: string, content_json: array<string, mixed>}
     */
    public function process(AiUpload $upload, string $jobType, array $options = []): array
    {
        $title = match ($jobType) {
            'transcribe' => 'Transcript',
            'summarize' => 'Summary',
            'translate' => 'Translation',
            'extract' => 'Structured Extraction',
            default => 'AI Analysis',
        };

        $outputType = match ($jobType) {
            'transcribe' => 'transcript',
            'summarize' => 'summary',
            'translate' => 'translation',
            'extract' => 'extraction',
            default => 'analysis',
        };

        $message = "Local AI pipeline accepted {$upload->file_kind} file '{$upload->original_name}'. Configure OpenAI or Gemini provider to generate real {$outputType} content.";

        return [
            'output_type' => $outputType,
            'title' => $title,
            'content_text' => $message,
            'content_json' => [
                'status' => 'provider_not_configured',
                'file_kind' => $upload->file_kind,
                'source_type' => $upload->source_type,
                'options' => $options,
                'next_step' => 'Implement OpenAiProvider or GeminiProvider for this job type.',
            ],
        ];
    }
}
