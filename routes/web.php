<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProjectController;
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

            // Chats (nested under project)
            Route::prefix('/projects/{project}/chats')->group(function () {
                Route::post('/',
                    [ChatController::class, 'store'])
                    ->name('projects.chats.store');

                Route::get('/{chat}',
                    [ChatController::class, 'show'])
                    ->name('projects.chats.show');

                Route::delete('/{chat}',
                    [ChatController::class, 'destroy'])
                    ->name('projects.chats.destroy');

                // Messages (nested under chat)
                Route::prefix('/{chat}/messages')->group(function () {
                    Route::post('/',
                        [MessageController::class, 'store'])
                        ->name('projects.chats.messages.store');

                    Route::get('/',
                        [MessageController::class, 'index'])
                        ->name('projects.chats.messages.index');
                });
            });

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
        Route::get('/', fn () => Inertia::render('Admin/Dashboard'))
            ->name('admin.dashboard');
    });

});