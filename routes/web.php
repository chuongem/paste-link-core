<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'paste-link-core',
        'docs' => url('/docs'),
        'health' => url('/api/v1/health'),
    ]);
});

Route::get('/docs', function () {
    return view('swagger');
});

Route::get('/openapi.yml', function () {
    return response()->file(base_path('openapi.yml'), [
        'Content-Type' => 'application/yaml',
    ]);
});
