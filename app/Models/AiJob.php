<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ai_upload_id',
    'job_type',
    'provider',
    'model',
    'status',
    'input_options',
    'output_text',
    'output_json',
    'error_message',
    'credit_cost',
    'started_at',
    'completed_at',
])]
class AiJob extends Model
{
    protected function casts(): array
    {
        return [
            'input_options' => 'array',
            'output_json' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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
     * @return HasMany<AiOutput, $this>
     */
    public function outputs(): HasMany
    {
        return $this->hasMany(AiOutput::class);
    }
}
