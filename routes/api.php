<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\PromptTemplateController;
use App\Http\Controllers\Api\V1\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Public ────────────────────────────────────────────────────
    Route::post('auth/login',    [AuthController::class, 'login']);
    Route::post('auth/register', [AuthController::class, 'register']);

    // ── Protected ─────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', \App\Http\Middleware\TenantMiddleware::class])
        ->group(function () {

        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me',      [AuthController::class, 'me']);

        // Tenant
        Route::get('tenant',       [TenantController::class, 'show']);
        Route::get('tenant/usage', [TenantController::class, 'usage']);

        // Templates
        Route::get('templates',                    [PromptTemplateController::class, 'index']);
        Route::post('templates',                   [PromptTemplateController::class, 'store']);
        Route::put('templates/{promptTemplate}',   [PromptTemplateController::class, 'update']);
        Route::delete('templates/{promptTemplate}',[PromptTemplateController::class, 'destroy']);

        // Export
Route::get('projects/{project}/chats/{chat}/export/pdf',
    [\App\Http\Controllers\Api\V1\ExportController::class, 'exportPdf']);
Route::get('projects/{project}/chats/{chat}/export/markdown',
    [\App\Http\Controllers\Api\V1\ExportController::class, 'exportMarkdown']);



    // Billing
Route::get('billing/plans',        [\App\Http\Controllers\Api\V1\BillingController::class, 'plans']);
Route::get('billing/current-plan', [\App\Http\Controllers\Api\V1\BillingController::class, 'currentPlan']);
Route::get('billing/invoices',     [\App\Http\Controllers\Api\V1\BillingController::class, 'invoices']);
Route::get('billing/invoices/{payment}/download',
    [\App\Http\Controllers\Api\V1\BillingController::class, 'downloadInvoice']);

    
        // Projects
        Route::apiResource('projects', ProjectController::class);

        // Project memory
Route::delete('projects/{project}/memory',
    [\App\Http\Controllers\Api\V1\ProjectController::class, 'clearMemory']);
Route::patch('projects/{project}/memory',
    [\App\Http\Controllers\Api\V1\ProjectController::class, 'updateMemory']);


        // Chats + Messages (nested under projects)
        Route::prefix('projects/{project}')->group(function () {
            Route::apiResource('chats', ChatController::class)->except(['update']);
            Route::post('chats/{chat}/close', [ChatController::class, 'close']);

            Route::prefix('chats/{chat}')->group(function () {
                Route::get('messages',        [MessageController::class, 'index']);
                Route::post('messages',       [MessageController::class, 'store']);
                Route::get('messages/status', [MessageController::class, 'status']);
            });
        });

    });
});