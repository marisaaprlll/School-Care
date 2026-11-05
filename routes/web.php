<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Peta Halaman School Care
|--------------------------------------------------------------------------
| Setiap baris di bawah ini = satu halaman. Kalau kamu mau ubah tampilan
| suatu halaman, cari nama controller & view-nya di sini, lalu buka:
|   - Controller-nya di app/Http/Controllers/
|   - Tampilannya di resources/views/
|--------------------------------------------------------------------------
*/

// ---------- Halaman Login (belum login) ----------
Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ---------- Halaman setelah login ----------
Route::middleware('auth')->group(function () {

    // Ringkasan (Dashboard) — sama untuk Pelapor & Admin, kontennya beda lewat role
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Laporan Saya (khusus Pelapor)
    Route::get('/laporan-saya', [ReportController::class, 'index'])->name('reports.index');

    // Ajukan Laporan (khusus Pelapor)
    Route::get('/laporan/ajukan', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/laporan', [ReportController::class, 'store'])->name('reports.store');

    // Detail satu laporan (Pelapor lihat miliknya, Admin bisa lihat semua)
    Route::get('/laporan/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/laporan/{report}/bukti', [ReportController::class, 'evidence'])->name('reports.evidence');
    Route::get('/laporan/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    Route::delete('/laporan/{report}', [ReportController::class, 'destroy'])->name('reports.destroy');

    // Profil (Pelapor & Admin)
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');

    // ---------- Khusus Admin / Petugas Sarpras ----------
    Route::middleware('admin')->group(function () {
        Route::get('/buat-akun', [ProfileController::class, 'accounts'])->name('accounts.index');
        Route::post('/buat-akun', [ProfileController::class, 'createUser'])->name('accounts.store');

        // Antrean Laporan
        Route::get('/antrean-laporan', [ReportController::class, 'queue'])->name('reports.queue');
        Route::patch('/laporan/{report}/status', [ReportController::class, 'updateStatus'])->name('reports.updateStatus');

        // Data Master (Lokasi & Kategori)
        Route::get('/data-master', [MasterDataController::class, 'index'])->name('master.index');
        Route::post('/data-master/lokasi', [MasterDataController::class, 'storeLocation'])->name('master.location.store');
        Route::delete('/data-master/lokasi/{location}', [MasterDataController::class, 'destroyLocation'])->name('master.location.destroy');
        Route::post('/data-master/kategori', [MasterDataController::class, 'storeCategory'])->name('master.category.store');
        Route::delete('/data-master/kategori/{category}', [MasterDataController::class, 'destroyCategory'])->name('master.category.destroy');
    });
});
