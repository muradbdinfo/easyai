<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\ProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Public ────────────────────────────────────────────────────
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/register', [AuthController::class, 'register']);

    // ── Protected ─────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Tenant
        Route::get('tenant', [TenantController::class, 'show']);
        Route::get('tenant/usage', [TenantController::class, 'usage']);

        // Projects
        Route::apiResource('projects', ProjectController::class);
    });

});