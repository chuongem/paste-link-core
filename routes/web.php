<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'paste-link-core',
        'docs' => url('/swagger'),
        'health' => url('/api/v1/health'),
    ]);
});

Route::redirect('/swagger', '/api/documentation');
