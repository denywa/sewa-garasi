<?php

use App\Http\Controllers\Admin\ExtensionController;
use App\Http\Controllers\Admin\GarageController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Tenant\RentalController;
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

        // UC-06. {tenant} = tenants.user_id (primary key Tenant).
        Route::get('/verifikasi', [VerificationController::class, 'index'])->name('verifications.index');
        Route::patch('/verifikasi/{tenant}/approve', [VerificationController::class, 'approve'])->name('verifications.approve');
        Route::patch('/verifikasi/{tenant}/reject', [VerificationController::class, 'reject'])->name('verifications.reject');

        // UC-05.
        Route::get('/garasi/create', [GarageController::class, 'create'])->name('garages.create');
        Route::post('/garasi', [GarageController::class, 'store'])->name('garages.store');

        // UC-07 + UC-08.
        Route::get('/perpanjang', [ExtensionController::class, 'index'])->name('extensions.index');
        Route::get('/perpanjang/{rental}', [ExtensionController::class, 'edit'])->name('extensions.edit');
        Route::put('/perpanjang/{rental}', [ExtensionController::class, 'update'])->name('extensions.update');

        // UC-09.
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    });

    Route::middleware('role:tenant')->prefix('penyewa')->name('tenant.')->group(function () {
        Route::view('/', 'tenant.dashboard')->name('dashboard');

        // UC-04 + UC-08.
        Route::get('/sewa/baru', [RentalController::class, 'create'])->name('rentals.create');
        Route::get('/sewa/baru/garasi', [RentalController::class, 'choose'])->name('rentals.choose');
        Route::post('/sewa', [RentalController::class, 'store'])->name('rentals.store');

        // UC-10 (pelengkap). Didaftarkan setelah /sewa/baru agar "baru" tidak dianggap {rental}.
        Route::get('/sewa', [RentalController::class, 'index'])->name('rentals.index');
        Route::get('/sewa/{rental}', [RentalController::class, 'show'])->name('rentals.show');
    });
});
