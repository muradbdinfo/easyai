<?php

// FILE: routes/web.php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\Admin\UsageController as AdminUsageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ChatController;
use App\Models\UsageLog;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PromptTemplateController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| easyai.local — Tenant App
|--------------------------------------------------------------------------
*/
Route::domain('easyai.local')->group(function () {

    Route::get('/', fn () => redirect()->route('login'));

    // ── Guest ──────────────────────────────────────────────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('/login',     [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login',    [AuthController::class, 'login']);
        Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register']);

        Route::get('/forgot-password',        [PasswordResetController::class, 'showForgotForm'])->name('password.request');
        Route::post('/forgot-password',       [PasswordResetController::class, 'sendResetLink'])->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
        Route::post('/reset-password',        [PasswordResetController::class, 'reset'])->name('password.update');
    });

    // ── Invitations (public, no auth required) ────────────────────────────
    Route::get('/invitation/{token}',          [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('/invitation/{token}/accept',  [InvitationController::class, 'accept'])->name('invitation.accept');
    Route::post('/invitation/{token}/decline', [InvitationController::class, 'decline'])->name('invitation.decline');

    // ── Authenticated ──────────────────────────────────────────────────────
    Route::middleware('auth')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // ── Auth + Tenant ──────────────────────────────────────────────────
        Route::middleware('tenant')->group(function () {

        // Standalone new chat (uses General project)
Route::get('/chat/new', [ChatController::class, 'createQuick'])->name('chat.new');

            // Dashboard
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            // Notifications
            Route::get('/notifications',            [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
            Route::post('/notifications/read-all',  [NotificationController::class, 'markAllRead'])->name('notifications.readAll');


// Project-level Knowledge Base
Route::prefix('projects/{project}/knowledge')->group(function () {
    Route::get('/',                   [KnowledgeBaseController::class, 'index'])->name('kb.index');
    Route::post('/',                  [KnowledgeBaseController::class, 'store'])->name('kb.store');
    Route::post('/documents',         [KnowledgeBaseController::class, 'uploadDocument'])->name('kb.upload');
    Route::delete('/documents/{document}', [KnowledgeBaseController::class, 'destroyDocument'])->name('kb.document.destroy');
});

// Chat-level Knowledge Base
Route::prefix('projects/{project}/chats/{chat}/knowledge')->group(function () {
    Route::get('/',           [KnowledgeBaseController::class, 'chatIndex'])->name('kb.chat.index');
    Route::post('/',          [KnowledgeBaseController::class, 'chatStore'])->name('kb.chat.store');
    Route::post('/documents', [KnowledgeBaseController::class, 'uploadDocument'])->name('kb.chat.upload');
});


            // ── Projects ───────────────────────────────────────────────────
            Route::resource('projects', ProjectController::class);

            Route::delete('/projects/{project}/memory',
                [ProjectController::class, 'clearMemory'])
                ->name('projects.memory.clear');

            Route::patch('/projects/{project}/memory',
                [ProjectController::class, 'updateMemory'])
                ->name('projects.memory.update');

            // ── Chats (nested under project) ───────────────────────────────
            Route::prefix('/projects/{project}/chats')->group(function () {

                Route::post('/',         [ChatController::class, 'store'])  ->name('projects.chats.store');
                Route::get('/{chat}',    [ChatController::class, 'show'])   ->name('projects.chats.show');
                Route::delete('/{chat}', [ChatController::class, 'destroy'])->name('projects.chats.destroy');

                // Messages
                Route::prefix('/{chat}/messages')->group(function () {
                    Route::post('/', [MessageController::class, 'store'])->name('projects.chats.messages.store');
                    Route::get('/',  [MessageController::class, 'index'])->name('projects.chats.messages.index');
                });

                // ── SSE Streaming (M19) ────────────────────────────────────
                Route::get('/{chat}/stream', [StreamController::class, 'stream'])
                    ->name('projects.chats.stream');

                // File upload
                Route::post('/{chat}/upload',
                    [FileUploadController::class, 'store'])
                    ->name('chats.upload');
            });

            // Attachment delete
            Route::delete('/attachments/{attachment}',
                [FileUploadController::class, 'destroy'])
                ->name('attachments.destroy');

            // ── Templates ──────────────────────────────────────────────────
            Route::get('/templates',
                [PromptTemplateController::class, 'index'])->name('templates.index');
            Route::post('/templates',
                [PromptTemplateController::class, 'store'])->name('templates.store');
            Route::put('/templates/{promptTemplate}',
                [PromptTemplateController::class, 'update'])->name('templates.update');
            Route::delete('/templates/{promptTemplate}',
                [PromptTemplateController::class, 'destroy'])->name('templates.destroy');

            // ── Export ─────────────────────────────────────────────────────
            Route::get('/projects/{project}/chats/{chat}/export/pdf',
                [ExportController::class, 'exportPdf'])
                ->name('chats.export.pdf');

            Route::get('/projects/{project}/chats/{chat}/export/markdown',
                [ExportController::class, 'exportMarkdown'])
                ->name('chats.export.markdown');

            // ── Billing ────────────────────────────────────────────────────
            Route::get('/billing',
                [BillingController::class, 'index'])
                ->name('billing.index');

            Route::get('/billing/plans',
                [BillingController::class, 'plans'])
                ->name('billing.plans');

            Route::get('/billing/plans/{plan}/select',
                [BillingController::class, 'selectPlan'])
                ->name('billing.select');

            Route::post('/billing/cod/{plan}',
                [BillingController::class, 'processCod'])
                ->name('billing.cod');

            Route::post('/billing/sslcommerz/{plan}',
                [BillingController::class, 'processSslcommerz'])
                ->name('billing.sslcommerz');

            Route::get('/billing/sslcommerz/success',
                [BillingController::class, 'sslSuccess'])
                ->name('billing.sslcommerz.success');

            Route::get('/billing/sslcommerz/fail',
                [BillingController::class, 'sslFail'])
                ->name('billing.sslcommerz.fail');

            Route::get('/billing/sslcommerz/cancel',
                [BillingController::class, 'sslCancel'])
                ->name('billing.sslcommerz.cancel');

            Route::post('/billing/sslcommerz/ipn',
                [BillingController::class, 'sslIpn'])
                ->name('billing.sslcommerz.ipn');

            Route::post('/billing/stripe/{plan}',
                [BillingController::class, 'processStripe'])
                ->name('billing.stripe');

            Route::get('/billing/stripe/success',
                [BillingController::class, 'stripeSuccess'])
                ->name('billing.stripe.success');

            Route::get('/billing/invoice/{payment}',
                [BillingController::class, 'downloadInvoice'])
                ->name('billing.invoice.download');

            // ── Usage / MIS ────────────────────────────────────────────────
            Route::get('/usage',        [\App\Http\Controllers\UsageController::class, 'index'])->name('usage.index');
            Route::get('/usage/export', [\App\Http\Controllers\UsageController::class, 'exportCsv'])->name('usage.export.csv');

            // ── Team Management ───────────────────────────────────────────────────
            Route::get('/team',                                  [TeamController::class, 'index'])->name('team.index');
            Route::post('/team/invite',                          [TeamController::class, 'invite'])->name('team.invite');
            Route::post('/team/invitations/{invitation}/resend', [TeamController::class, 'resendInvite'])->name('team.invitation.resend');
            Route::delete('/team/invitations/{invitation}',      [TeamController::class, 'cancelInvite'])->name('team.invitation.cancel');
Route::put('/team/members/{member}/role',              [TeamController::class, 'updateRole'])->name('team.member.role');
Route::put('/team/members/{member}/status',            [TeamController::class, 'toggleStatus'])->name('team.member.status');
Route::delete('/team/members/{member}',                [TeamController::class, 'removeMember'])->name('team.member.remove');

            // ── Project Members ───────────────────────────────────────────────────
            Route::get('/projects/{project}/members',             [ProjectMemberController::class, 'index'])->name('project.members.index');
            Route::post('/projects/{project}/members',            [ProjectMemberController::class, 'add'])->name('project.members.add');
            Route::put('/projects/{project}/members/{member}',    [ProjectMemberController::class, 'updateRole'])->name('project.members.role');
            Route::delete('/projects/{project}/members/{member}', [ProjectMemberController::class, 'remove'])->name('project.members.remove');
            Route::put('/projects/{project}/restricted',          [ProjectMemberController::class, 'toggleRestricted'])->name('project.restricted.toggle');

        }); // end tenant middleware
    }); // end auth middleware

}); // end easyai.local domain

// ── Stripe webhook (no auth, no CSRF) ─────────────────────────────────────────
Route::domain('easyai.local')
    ->post('/stripe/webhook', [BillingController::class, 'stripeWebhook'])
    ->name('stripe.webhook');

/*
|--------------------------------------------------------------------------
| admin.easyai.local — Admin Panel
|--------------------------------------------------------------------------
*/
Route::domain('admin.easyai.local')->group(function () {

    // ── Guest ──────────────────────────────────────────────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('/login',  [AuthController::class, 'showLogin'])->name('admin.login');
        Route::post('/login', [AuthController::class, 'login']);
    });

    // ── Auth + Superadmin ──────────────────────────────────────────────────
    Route::middleware(['auth', 'superadmin'])->group(function () {

     // ── ADD THIS LINE ──────────────────────────────────────────────────
        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');


        Route::get('/', [AdminDashboardController::class, 'index'])
            ->name('admin.dashboard');

        // Tenants
        Route::get('/tenants',
            [AdminTenantController::class, 'index'])
            ->name('admin.tenants.index');

        Route::get('/tenants/{tenant}',
            [AdminTenantController::class, 'show'])
            ->name('admin.tenants.show');

        Route::put('/tenants/{tenant}/plan',
            [AdminTenantController::class, 'updatePlan'])
            ->name('admin.tenants.plan');

        Route::put('/tenants/{tenant}/status',
            [AdminTenantController::class, 'updateStatus'])
            ->name('admin.tenants.status');

        // Plans
        Route::get('/plans',
            [AdminPlanController::class, 'index'])
            ->name('admin.plans.index');

        Route::post('/plans',
            [AdminPlanController::class, 'store'])
            ->name('admin.plans.store');

        Route::put('/plans/{plan}',
            [AdminPlanController::class, 'update'])
            ->name('admin.plans.update');

        Route::delete('/plans/{plan}',
            [AdminPlanController::class, 'destroy'])
            ->name('admin.plans.destroy');

        // Payments
        Route::get('/payments',
            [AdminPaymentController::class, 'index'])
            ->name('admin.payments.index');

        Route::put('/payments/{id}/approve',
            [AdminPaymentController::class, 'approveCod'])
            ->name('admin.payments.approve');

        // Usage
        Route::get('/usage',
            [AdminUsageController::class, 'index'])
            ->name('admin.usage.index');
        Route::get('/usage/export', [AdminUsageController::class, 'exportCsv'])->name('admin.usage.export');

    }); // end superadmin middleware

}); // end admin.easyai.local domain