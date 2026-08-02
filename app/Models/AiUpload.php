<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'source_file_id',
    'source_type',
    'original_name',
    'display_name',
    'mime_type',
    'extension',
    'size_bytes',
    'storage_disk',
    'storage_path',
    'checksum',
    'file_kind',
    'status',
    'detected_language',
    'metadata',
])]
class AiUpload extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function sourceFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'source_file_id');
    }

    /**
     * @return HasMany<AiJob, $this>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(AiJob::class);
    }

    /**
     * @return HasMany<AiOutput, $this>
     */
    public function outputs(): HasMany
    {
        return $this->hasMany(AiOutput::class);
    }
}
