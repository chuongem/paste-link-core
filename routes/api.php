<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'paste-link-core',
        ]);
    });

    Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
        return $request->user();
    });
});
