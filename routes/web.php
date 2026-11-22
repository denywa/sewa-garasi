<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Tamu (belum login). Yang sudah login diarahkan ke route "home" oleh middleware guest.
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

// Sudah login. Tamu diarahkan ke route "login" oleh middleware auth.
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Halaman utama: arahkan ke dashboard sesuai role.
    Route::get('/', fn (Request $request) => redirect()->route(
        $request->user()->isAdmin() ? 'admin.dashboard' : 'tenant.dashboard'
    ))->name('home');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::view('/', 'admin.dashboard')->name('dashboard');
    });

    Route::middleware('role:tenant')->prefix('penyewa')->name('tenant.')->group(function () {
        Route::view('/', 'tenant.dashboard')->name('dashboard');
    });
});
