<?php

// FILE: routes/api.php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\ExportController;
use App\Http\Controllers\Api\V1\FileUploadController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\PromptTemplateController;
use App\Http\Controllers\Api\V1\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Public ─────────────────────────────────────────────────────────────
    Route::post('auth/login',    [AuthController::class, 'login']);
    Route::post('auth/register', [AuthController::class, 'register']);

    // ── Protected (Sanctum token + Tenant) ────────────────────────────────
    Route::middleware(['auth:sanctum', \App\Http\Middleware\TenantMiddleware::class])
        ->group(function () {

        // ── Auth ───────────────────────────────────────────────────────────
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me',      [AuthController::class, 'me']);

        // ── Tenant ─────────────────────────────────────────────────────────
        Route::get('tenant',       [TenantController::class, 'show']);
        Route::get('tenant/usage', [TenantController::class, 'usage']);

        // ── Templates ──────────────────────────────────────────────────────
        Route::get('templates',                     [PromptTemplateController::class, 'index']);
        Route::post('templates',                    [PromptTemplateController::class, 'store']);
        Route::put('templates/{promptTemplate}',    [PromptTemplateController::class, 'update']);
        Route::delete('templates/{promptTemplate}', [PromptTemplateController::class, 'destroy']);

        // ── Projects ───────────────────────────────────────────────────────
        Route::apiResource('projects', ProjectController::class);

        Route::delete('projects/{project}/memory',
            [ProjectController::class, 'clearMemory']);
        Route::patch('projects/{project}/memory',
            [ProjectController::class, 'updateMemory']);

        // ── Chats + Messages + Uploads (nested under projects) ─────────────
        Route::prefix('projects/{project}')->group(function () {

            // Chats
            Route::apiResource('chats', ChatController::class)->except(['update']);
            Route::post('chats/{chat}/close', [ChatController::class, 'close']);

            // Messages (nested under chat)
            Route::prefix('chats/{chat}')->group(function () {
                Route::get('messages',        [MessageController::class, 'index']);
                Route::post('messages',       [MessageController::class, 'store']);
                Route::get('messages/status', [MessageController::class, 'status']);

                // File upload
                Route::post('upload', [FileUploadController::class, 'store']);
            });

            // Export
            Route::get('chats/{chat}/export/pdf',      [ExportController::class, 'exportPdf']);
            Route::get('chats/{chat}/export/markdown',  [ExportController::class, 'exportMarkdown']);

        });

        // ── Attachment delete (standalone) ─────────────────────────────────
        Route::delete('attachments/{attachment}', [FileUploadController::class, 'destroy']);

        // ── Billing ────────────────────────────────────────────────────────
        Route::get('billing/plans',                       [BillingController::class, 'plans']);
        Route::get('billing/current-plan',                [BillingController::class, 'currentPlan']);
        Route::get('billing/invoices',                    [BillingController::class, 'invoices']);
        Route::get('billing/invoices/{payment}/download', [BillingController::class, 'downloadInvoice']);

    }); // end auth:sanctum middleware

}); // end v1 prefix