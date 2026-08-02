<?php

return [
    'provider' => env('AI_PROVIDER', 'local'),
    'model' => env('AI_MODEL', 'local-analysis'),
    'uploads_disk' => env('AI_UPLOADS_DISK', env('FILESYSTEM_UPLOADS_DISK', 'public_root')),
    'max_upload_bytes' => env('AI_MAX_UPLOAD_BYTES', 300 * 1024 * 1024),

    'supported_extensions' => [
        'text' => ['txt', 'md', 'json'],
        'document' => ['pdf', 'docx'],
        'audio' => ['mp3', 'wav', 'm4a'],
        'video' => ['mp4', 'mov', 'webm'],
    ],
];
