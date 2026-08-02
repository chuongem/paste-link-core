<?php

use App\Http\Controllers\Api\V1\AiJobController;
use App\Http\Controllers\Api\V1\AiOutputController;
use App\Http\Controllers\Api\V1\AiUploadController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FileController;
use App\Http\Controllers\Api\V1\ShareLinkController;
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

    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/google/redirect', [AuthController::class, 'redirectToGoogle']);
        Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
        Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    });

    Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
        return ApiResponse::success($request->user());
    });

    Route::middleware('auth:sanctum')->prefix('files')->group(function (): void {
        Route::get('/', [FileController::class, 'index']);
        Route::post('/', [FileController::class, 'store']);
        Route::post('/{id}/share-links', [ShareLinkController::class, 'store'])->whereNumber('id');
        Route::post('/{id}/send-to-ai', [FileController::class, 'sendToAi'])->whereNumber('id');
        Route::get('/{id}', [FileController::class, 'show'])->whereNumber('id');
        Route::delete('/{id}', [FileController::class, 'destroy'])->whereNumber('id');
    });

    Route::middleware('auth:sanctum')->prefix('ai')->group(function (): void {
        Route::get('/uploads', [AiUploadController::class, 'index']);
        Route::post('/uploads', [AiUploadController::class, 'store']);
        Route::get('/uploads/{id}', [AiUploadController::class, 'show'])->whereNumber('id');
        Route::delete('/uploads/{id}', [AiUploadController::class, 'destroy'])->whereNumber('id');
        Route::post('/uploads/{id}/analyze', [AiUploadController::class, 'analyze'])->whereNumber('id');
        Route::post('/uploads/{id}/transcribe', [AiUploadController::class, 'transcribe'])->whereNumber('id');
        Route::post('/uploads/{id}/summarize', [AiUploadController::class, 'summarize'])->whereNumber('id');
        Route::post('/uploads/{id}/translate', [AiUploadController::class, 'translate'])->whereNumber('id');
        Route::post('/uploads/{id}/extract', [AiUploadController::class, 'extract'])->whereNumber('id');
        Route::get('/uploads/{id}/outputs', [AiUploadController::class, 'outputs'])->whereNumber('id');
        Route::get('/outputs/{id}', [AiOutputController::class, 'show'])->whereNumber('id');
        Route::get('/jobs/{id}', [AiJobController::class, 'show'])->whereNumber('id');
        Route::get('/jobs/{id}/result', [AiJobController::class, 'result'])->whereNumber('id');
    });

    Route::middleware('auth:sanctum')->prefix('share-links')->group(function (): void {
        Route::patch('/{id}', [ShareLinkController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [ShareLinkController::class, 'destroy'])->whereNumber('id');
        Route::post('/{id}/regenerate', [ShareLinkController::class, 'regenerate'])->whereNumber('id');
    });

    Route::get('/share-links/{code}', [ShareLinkController::class, 'show']);
    Route::post('/share-links/{code}/verify-password', [ShareLinkController::class, 'verifyPassword']);
    Route::get('/f/{code}', [ShareLinkController::class, 'publicFile']);
    Route::get('/f/{code}/download', [ShareLinkController::class, 'download']);
    Route::get('/f/{code}/qr-code', [ShareLinkController::class, 'qrCode']);
});
