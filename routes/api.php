<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\ExportController;
use App\Http\Controllers\Api\V1\FileUploadController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\PromptTemplateController;
use App\Http\Controllers\Api\V1\TeamController as ApiTeamController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\AddonController as ApiAddonController;
use App\Http\Controllers\Api\V1\AgentController as ApiAgentController;

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Public (strict throttle: 10 req/min per IP) ───────────────────────
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('auth/login',    [AuthController::class, 'login']);
        Route::post('auth/register', [AuthController::class, 'register']);
    });

    // ── Protected (Sanctum token + tenant scope + 60 req/min) ────────────
    Route::middleware([
        'auth:sanctum',
        \App\Http\Middleware\TenantMiddleware::class,
        'throttle:60,1',
    ])->group(function () {


    // ── Add-on API ────────────────────────────────────────────────────────────
Route::get('addons', [ApiAddonController::class, 'index']);
Route::post('addons/{addon}/purchase', [ApiAddonController::class, 'purchase']);
Route::delete('addons/{addon}/cancel', [ApiAddonController::class, 'cancel']);

// ── Agent API (requires agent-ai addon) ──────────────────────────────────
Route::middleware('addon:agent-ai')->group(function () {
    Route::post('projects/{project}/chats/{chat}/agent/run', [ApiAgentController::class, 'run']);
    Route::get('projects/{project}/chats/{chat}/agent/{agentRun}/steps', [ApiAgentController::class, 'steps']);
    Route::post('projects/{project}/chats/{chat}/agent/{agentRun}/stop', [ApiAgentController::class, 'stop']);
});


        // ── Auth ──────────────────────────────────────────────────────────
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me',      [AuthController::class, 'me']);

        // ── Tenant ────────────────────────────────────────────────────────
        Route::get('tenant',       [TenantController::class, 'show']);
        Route::get('tenant/usage', [TenantController::class, 'usage']);


        // Knowledge Base
        Route::prefix('projects/{project}/knowledge')->group(function () {
            Route::get('/',                    [\App\Http\Controllers\Api\V1\KnowledgeBaseController::class, 'show']);
            Route::post('/',                   [\App\Http\Controllers\Api\V1\KnowledgeBaseController::class, 'store']);
            Route::post('/documents',          [\App\Http\Controllers\Api\V1\KnowledgeBaseController::class, 'uploadDocument']);
            Route::delete('/documents/{document}', [\App\Http\Controllers\Api\V1\KnowledgeBaseController::class, 'destroyDocument']);
        });

        // ── Projects ──────────────────────────────────────────────────────
        Route::apiResource('projects', ProjectController::class);
        Route::delete('projects/{project}/memory', [ProjectController::class, 'clearMemory']);
        Route::patch('projects/{project}/memory',  [ProjectController::class, 'updateMemory']);

        // ── Chats + Messages + File Upload ────────────────────────────────
        Route::prefix('projects/{project}')->group(function () {

            Route::apiResource('chats', ChatController::class)->except(['update']);
            Route::post('chats/{chat}/close', [ChatController::class, 'close']);

            Route::prefix('chats/{chat}')->group(function () {

                // Messages
                Route::get('messages',        [MessageController::class, 'index']);
                Route::post('messages',       [MessageController::class, 'store']);
                Route::get('messages/status', [MessageController::class, 'status']);

                // File Upload
                Route::post('upload', [FileUploadController::class, 'store'])
                    ->name('api.chats.upload');
            });
        });

        // ── Attachment delete (standalone, not nested under chat) ─────────
        Route::delete('attachments/{attachment}', [FileUploadController::class, 'destroy'])
            ->name('api.attachments.destroy');

        // ── Templates ─────────────────────────────────────────────────────
        Route::get('templates',                     [PromptTemplateController::class, 'index']);
        Route::post('templates',                    [PromptTemplateController::class, 'store']);
        Route::put('templates/{promptTemplate}',    [PromptTemplateController::class, 'update']);
        Route::delete('templates/{promptTemplate}', [PromptTemplateController::class, 'destroy']);

        // ── Export (higher rate limit — file downloads) ───────────────────
        Route::middleware('throttle:20,1')->group(function () {
            Route::get('projects/{project}/chats/{chat}/export/pdf',
                [ExportController::class, 'exportPdf']);
            Route::get('projects/{project}/chats/{chat}/export/markdown',
                [ExportController::class, 'exportMarkdown']);
        });

        // ── Billing ───────────────────────────────────────────────────────
        Route::get('billing/plans',                       [BillingController::class, 'plans']);
        Route::get('billing/current-plan',                [BillingController::class, 'currentPlan']);
        Route::get('billing/invoices',                    [BillingController::class, 'invoices']);
        Route::get('billing/invoices/{payment}/download', [BillingController::class, 'downloadInvoice']);

        // ── Team ──────────────────────────────────────────────────────────
        Route::get('team',                             [ApiTeamController::class, 'index']);
        Route::post('team/invite',                     [ApiTeamController::class, 'invite']);
        Route::delete('team/invitations/{invitation}', [ApiTeamController::class, 'cancelInvite']);
Route::put('team/members/{member}/role',         [ApiTeamController::class, 'updateRole']);
Route::put('team/members/{member}/status',       [ApiTeamController::class, 'toggleStatus']);
Route::delete('team/members/{member}',           [ApiTeamController::class, 'removeMember']);

    });

});