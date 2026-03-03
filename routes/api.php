<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Public ────────────────────────────────────────────────────
    Route::post('auth/login',    [AuthController::class, 'login']);
    Route::post('auth/register', [AuthController::class, 'register']);

    // ── Protected ─────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me',      [AuthController::class, 'me']);

        // Tenant
        Route::get('tenant',       [TenantController::class, 'show']);
        Route::get('tenant/usage', [TenantController::class, 'usage']);

        // Projects
        Route::apiResource('projects', ProjectController::class);

        // Chats + Messages (nested under projects)
        Route::prefix('projects/{project}')->group(function () {
            Route::apiResource('chats', ChatController::class)->except(['update']);
            Route::post('chats/{chat}/close', [ChatController::class, 'close']);

            // Messages
            Route::prefix('chats/{chat}')->group(function () {
                Route::get('messages',        [MessageController::class, 'index']);
                Route::post('messages',       [MessageController::class, 'store']);
                Route::get('messages/status', [MessageController::class, 'status']);
            });
        });

    });

});