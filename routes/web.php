<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PromptTemplateController;
use App\Models\UsageLog;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| easyai.local — Tenant App
|--------------------------------------------------------------------------
*/
Route::domain('easyai.local')->group(function () {

    Route::get('/', fn () => redirect()->route('login'));

    // ── Guest ──────────────────────────────────────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('/login',     [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login',    [AuthController::class, 'login']);
        Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register']);
    });

    // ── Auth ───────────────────────────────────────────────────────
    Route::middleware('auth')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // ── Auth + Tenant ──────────────────────────────────────────
        Route::middleware('tenant')->group(function () {

            // Dashboard
            Route::get('/dashboard', fn () => Inertia::render('Dashboard'))
                ->name('dashboard');

            // Projects
            Route::resource('projects', ProjectController::class);

            // Project memory
Route::delete('/projects/{project}/memory',
    [\App\Http\Controllers\ProjectController::class, 'clearMemory'])
    ->name('projects.memory.clear');

Route::patch('/projects/{project}/memory',
    [\App\Http\Controllers\ProjectController::class, 'updateMemory'])
    ->name('projects.memory.update');


            // Chats (nested under project)
            Route::prefix('/projects/{project}/chats')->group(function () {
                Route::post('/',         [ChatController::class, 'store'])  ->name('projects.chats.store');
                Route::get('/{chat}',    [ChatController::class, 'show'])   ->name('projects.chats.show');
                Route::delete('/{chat}', [ChatController::class, 'destroy'])->name('projects.chats.destroy');

                // Messages (nested under chat)
                Route::prefix('/{chat}/messages')->group(function () {
                    Route::post('/', [MessageController::class, 'store'])->name('projects.chats.messages.store');
                    Route::get('/',  [MessageController::class, 'index'])->name('projects.chats.messages.index');
                });
            });

            // Templates
            Route::get('/templates',
                [PromptTemplateController::class, 'index'])->name('templates.index');
            Route::post('/templates',
                [PromptTemplateController::class, 'store'])->name('templates.store');
            Route::put('/templates/{promptTemplate}',
                [PromptTemplateController::class, 'update'])->name('templates.update');
            Route::delete('/templates/{promptTemplate}',
                [PromptTemplateController::class, 'destroy'])->name('templates.destroy');


                // Export
Route::get('/projects/{project}/chats/{chat}/export/pdf',
    [\App\Http\Controllers\ExportController::class, 'exportPdf'])
    ->name('chats.export.pdf');

Route::get('/projects/{project}/chats/{chat}/export/markdown',
    [\App\Http\Controllers\ExportController::class, 'exportMarkdown'])
    ->name('chats.export.markdown');


    // Billing
Route::get('/billing',
    [\App\Http\Controllers\BillingController::class, 'index'])
    ->name('billing.index');

Route::get('/billing/plans',
    [\App\Http\Controllers\BillingController::class, 'plans'])
    ->name('billing.plans');

Route::get('/billing/plans/{plan}/select',
    [\App\Http\Controllers\BillingController::class, 'selectPlan'])
    ->name('billing.select');

Route::post('/billing/cod/{plan}',
    [\App\Http\Controllers\BillingController::class, 'processCod'])
    ->name('billing.cod');

Route::post('/billing/sslcommerz/{plan}',
    [\App\Http\Controllers\BillingController::class, 'processSslcommerz'])
    ->name('billing.sslcommerz');

Route::get('/billing/sslcommerz/success',
    [\App\Http\Controllers\BillingController::class, 'sslSuccess'])
    ->name('billing.sslcommerz.success');

Route::get('/billing/sslcommerz/fail',
    [\App\Http\Controllers\BillingController::class, 'sslFail'])
    ->name('billing.sslcommerz.fail');

Route::get('/billing/sslcommerz/cancel',
    [\App\Http\Controllers\BillingController::class, 'sslCancel'])
    ->name('billing.sslcommerz.cancel');

Route::post('/billing/sslcommerz/ipn',
    [\App\Http\Controllers\BillingController::class, 'sslIpn'])
    ->name('billing.sslcommerz.ipn');

Route::post('/billing/stripe/{plan}',
    [\App\Http\Controllers\BillingController::class, 'processStripe'])
    ->name('billing.stripe');

Route::get('/billing/stripe/success',
    [\App\Http\Controllers\BillingController::class, 'stripeSuccess'])
    ->name('billing.stripe.success');

Route::get('/billing/invoice/{payment}',
    [\App\Http\Controllers\BillingController::class, 'downloadInvoice'])
    ->name('billing.invoice.download');



            // Usage (MIS placeholder)
            Route::get('/usage', function () {
                $tenant = app('tenant');
                $logs   = UsageLog::where('tenant_id', $tenant->id)
                    ->latest()
                    ->take(50)
                    ->get();
                return Inertia::render('Usage/Index', ['logs' => $logs]);
            })->name('usage.index');

        });
    });

});



Route::domain('easyai.local')->post(
    '/stripe/webhook',
    [\App\Http\Controllers\BillingController::class, 'stripeWebhook']
)->name('stripe.webhook');


/*
|--------------------------------------------------------------------------
| admin.easyai.local — Admin Panel
|--------------------------------------------------------------------------
*/
Route::domain('admin.easyai.local')->group(function () {

    // ── Guest ──────────────────────────────────────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('/login',  [AuthController::class, 'showLogin'])->name('admin.login');
        Route::post('/login', [AuthController::class, 'login']);
    });

    // ── Auth + Superadmin ──────────────────────────────────────────
    Route::middleware(['auth', 'superadmin'])->group(function () {

        Route::get('/',
            [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
            ->name('admin.dashboard');

        // Tenants
        Route::get('/tenants',
            [\App\Http\Controllers\Admin\TenantController::class, 'index'])
            ->name('admin.tenants.index');
        Route::get('/tenants/{tenant}',
            [\App\Http\Controllers\Admin\TenantController::class, 'show'])
            ->name('admin.tenants.show');
        Route::put('/tenants/{tenant}/plan',
            [\App\Http\Controllers\Admin\TenantController::class, 'updatePlan'])
            ->name('admin.tenants.plan');
        Route::put('/tenants/{tenant}/status',
            [\App\Http\Controllers\Admin\TenantController::class, 'updateStatus'])
            ->name('admin.tenants.status');

        // Plans
        Route::get('/plans',
            [\App\Http\Controllers\Admin\PlanController::class, 'index'])
            ->name('admin.plans.index');
        Route::post('/plans',
            [\App\Http\Controllers\Admin\PlanController::class, 'store'])
            ->name('admin.plans.store');
        Route::put('/plans/{plan}',
            [\App\Http\Controllers\Admin\PlanController::class, 'update'])
            ->name('admin.plans.update');
        Route::delete('/plans/{plan}',
            [\App\Http\Controllers\Admin\PlanController::class, 'destroy'])
            ->name('admin.plans.destroy');

        // Payments
        Route::get('/payments',
            [\App\Http\Controllers\Admin\PaymentController::class, 'index'])
            ->name('admin.payments.index');
        Route::put('/payments/{id}/approve',
            [\App\Http\Controllers\Admin\PaymentController::class, 'approveCod'])
            ->name('admin.payments.approve');

        // Usage
        Route::get('/usage',
            [\App\Http\Controllers\Admin\UsageController::class, 'index'])
            ->name('admin.usage.index');

    });

});