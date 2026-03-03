<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| easyai.local — Tenant App
|--------------------------------------------------------------------------
*/
Route::domain('easyai.local')->group(function () {

    Route::get('/', fn () => redirect()->route('login'));

    Route::middleware('guest')->group(function () {
        Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login',   [AuthController::class, 'login']);
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register',[AuthController::class, 'register']);
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::middleware('tenant')->group(function () {

            Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

            // Projects
            Route::resource('projects', ProjectController::class);

            // Chats (nested under projects)
            Route::post(
                '/projects/{project}/chats',
                [ChatController::class, 'store']
            )->name('projects.chats.store');

            Route::get(
                '/projects/{project}/chats/{chat}',
                [ChatController::class, 'show']
            )->name('projects.chats.show');

            Route::delete(
                '/projects/{project}/chats/{chat}',
                [ChatController::class, 'destroy']
            )->name('projects.chats.destroy');

        });
    });

});

/*
|--------------------------------------------------------------------------
| admin.easyai.local — Admin Panel
|--------------------------------------------------------------------------
*/
Route::domain('admin.easyai.local')->group(function () {

    Route::middleware('guest')->group(function () {
        Route::get('/login',  [AuthController::class, 'showLogin'])->name('admin.login');
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware(['auth', 'superadmin'])->group(function () {
        Route::get('/', fn () => Inertia::render('Admin/Dashboard'))->name('admin.dashboard');
    });

});