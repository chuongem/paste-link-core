<?php

namespace App\Ai\Providers;

use App\Models\AiUpload;

interface AiProvider
{
    /**
     * @param  array<string, mixed>  $options
     * @return array{output_type: string, title: string, content_text: string, content_json: array<string, mixed>}
     */
    public function process(AiUpload $upload, string $jobType, array $options = []): array;
}
