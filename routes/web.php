<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| easyai.local — Tenant App
|--------------------------------------------------------------------------
*/
Route::domain('easyai.local')->group(function () {

    // Root redirect
    Route::get('/', fn () => redirect()->route('login'));

    // Guest only
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register']);
    });

    // Auth required
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Tenant routes (auth + tenant middleware)
        Route::middleware('tenant')->group(function () {
            Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
        });
    });

});

/*
|--------------------------------------------------------------------------
| admin.easyai.local — Admin Panel
|--------------------------------------------------------------------------
*/
Route::domain('admin.easyai.local')->group(function () {

    // Guest login for admin
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('admin.login');
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Superadmin protected
    Route::middleware(['auth', 'superadmin'])->group(function () {
        Route::get('/', fn () => Inertia::render('Admin/Dashboard'))->name('admin.dashboard');
    });

});