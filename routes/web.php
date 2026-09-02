<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\GateLogController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:teknisi|kabeng|kaprog'])
    ->name('dashboard');

// Modules — Protected Auth Routes (dashboard & modul web khusus staff non-siswa)
Route::middleware(['auth', 'role:teknisi|kabeng|kaprog'])->group(function () {

    // Master Aset / Items
    Route::resource('items', MasterController::class)->names([
        '' => 'items.index',
        'create' => 'items.create',
        'store' => 'items.store',
        'show' => 'items.show',
        'edit' => 'items.edit',
        'update' => 'items.update',
        'destroy' => 'items.destroy',
    ]);

    // Peminjaman / Loans
    Route::resource('loans', LoanController::class)->names([
        '' => 'loans.index',
        'create' => 'loans.create',
        'store' => 'loans.store',
        'show' => 'loans.show',
        'edit' => 'loans.edit',
        'update' => 'loans.update',
        'destroy' => 'loans.destroy',
    ]);

    // Gate Logs — Read Only
    Route::prefix('gate-logs')->group(function () {
        Route::get('/', [GateLogController::class, 'index'])->name('gate-logs.index');
        Route::get('/{id}', [GateLogController::class, 'show'])->name('gate-logs.show');
    });

    // Maintenance & Repair — khusus teknisi & kabeng
    Route::middleware('role:teknisi|kabeng')->group(function () {
        Route::resource('maintenance', MaintenanceController::class)->names([
            '' => 'maintenance.index',
            'create' => 'maintenance.create',
            'store' => 'maintenance.store',
            'show' => 'maintenance.show',
            'edit' => 'maintenance.edit',
            'update' => 'maintenance.update',
            'destroy' => 'maintenance.destroy',
        ]);
    });

    // Reports — kaprog dapat akses penuh laporan
    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/pdf/{type}', [ReportController::class, 'downloadPdf'])->name('reports.pdf');
        Route::get('/excel/{type}', [ReportController::class, 'downloadExcel'])->name('reports.excel');
    });
});

// Profile — setiap user terautentikasi dapat mengelola profilnya sendiri
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
