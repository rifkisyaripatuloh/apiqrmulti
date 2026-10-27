<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PublicQrController;
use App\Http\Controllers\Api\V1\PublicGeneratorController;
use App\Http\Controllers\Api\V1\QrLinkController;
use App\Http\Controllers\Api\V1\QrPageController;
use App\Http\Controllers\Api\V1\QrStatsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/ping', function () {
        return response()->json([
            'message' => 'QR Multi-Link API aktif',
            'service' => 'Laravel 13',
        ]);
    });
    Route::get('/public/qr/{code}', [PublicQrController::class, 'show'])->middleware('throttle:120,1');
    Route::post('/public/generate', [PublicGeneratorController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware(['web', 'throttle:5,1']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::apiResource('qr-pages', QrPageController::class)->except(['create', 'edit']);
        Route::get('/qr-pages/{qrPage}/stats', [QrStatsController::class, 'show']);
        Route::put('/qr-pages/{qrPage}/links/reorder', [QrLinkController::class, 'reorder']);
        Route::post('/qr-pages/{qrPage}/links', [QrLinkController::class, 'store']);
        Route::patch('/qr-pages/{qrPage}/links/{link}', [QrLinkController::class, 'update'])->scopeBindings();
        Route::delete('/qr-pages/{qrPage}/links/{link}', [QrLinkController::class, 'destroy'])->scopeBindings();
    });
});

