<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ai_upload_id',
    'ai_job_id',
    'output_type',
    'title',
    'content_text',
    'content_json',
    'storage_disk',
    'storage_path',
    'metadata',
])]
class AiOutput extends Model
{
    protected function casts(): array
    {
        return [
            'content_json' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<AiUpload, $this>
     */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(AiUpload::class, 'ai_upload_id');
    }

    /**
     * @return BelongsTo<AiJob, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(AiJob::class, 'ai_job_id');
    }
}
