<?php

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function () {
        return ApiResponse::success([
            'status' => 'ok',
            'service' => 'paste-link-core',
        ]);
    });

    Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
        return ApiResponse::success($request->user());
    });
});
