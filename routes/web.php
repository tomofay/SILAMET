<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\GateLogController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\PreventiveScheduleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Mode Kiosk Layanan Mandiri (Siswa) — Fullscreen, tanpa login web admin
Route::get('/', [KioskController::class, 'index'])->name('kiosk.home');
Route::get('/kiosk', [KioskController::class, 'index'])->name('kiosk.index');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:teknisi|kabeng|kaprog'])
    ->name('dashboard');

// Modules — Protected Auth Routes (dashboard & modul web khusus staff non-siswa)
Route::middleware(['auth', 'role:teknisi|kabeng|kaprog'])->group(function () {

    // Master Aset / Items
    Route::get('items/barcodes', [MasterController::class, 'printBarcodes'])->name('items.barcodes');
    Route::resource('items', MasterController::class)->names([
        '' => 'items.index',
        'create' => 'items.create',
        'store' => 'items.store',
        'show' => 'items.show',
        'edit' => 'items.edit',
        'update' => 'items.update',
        'destroy' => 'items.destroy',
    ]);

    // Buku Stok Bahan Habis Pakai (Stock Ledger)
    Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
    Route::post('stock-movements', [StockMovementController::class, 'store'])->name('stock-movements.store');

    // Peminjaman / Loans
    Route::get('loans/{loan}/photo', [LoanController::class, 'photo'])->name('loans.photo');
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
        Route::get('/feed', [GateLogController::class, 'feed'])->name('gate-logs.feed');
        Route::get('/{id}/photo', [GateLogController::class, 'photo'])->name('gate-logs.photo');
        Route::get('/{id}', [GateLogController::class, 'show'])->name('gate-logs.show');
    });

    // Maintenance & Repair — khusus teknisi & kabeng
    Route::middleware('role:teknisi|kabeng')->group(function () {
        // Jadwal Perawatan Preventif (didaftarkan sebelum resource maintenance agar tidak tertangkap {maintenance})
        Route::prefix('maintenance/schedules')->name('maintenance.schedules.')->group(function () {
            Route::get('/', [PreventiveScheduleController::class, 'index'])->name('index');
            Route::get('/create', [PreventiveScheduleController::class, 'create'])->name('create');
            Route::post('/', [PreventiveScheduleController::class, 'store'])->name('store');
            Route::get('/{schedule}/edit', [PreventiveScheduleController::class, 'edit'])->name('edit');
            Route::put('/{schedule}', [PreventiveScheduleController::class, 'update'])->name('update');
            Route::post('/{schedule}/mark-serviced', [PreventiveScheduleController::class, 'markServiced'])->name('mark-serviced');
            Route::delete('/{schedule}', [PreventiveScheduleController::class, 'destroy'])->name('destroy');
        });

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

    // Manajemen User — khusus kabeng (superadmin)
    Route::middleware('role:kabeng')->group(function () {
        Route::post('users/import', [UserController::class, 'import'])->name('users.import');
        Route::get('users/download-template', [UserController::class, 'downloadTemplate'])->name('users.download-template');
        Route::get('users/cards', [UserController::class, 'printCards'])->name('users.cards');
        Route::resource('users', UserController::class)->except(['show']);
    });
});

// Profile — setiap user terautentikasi dapat mengelola profilnya sendiri
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
