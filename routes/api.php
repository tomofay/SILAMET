<?php

use App\Http\Controllers\Api\GateApiController;
use App\Http\Controllers\Api\LoanApiController;
use App\Http\Controllers\Api\MaintenanceApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1/*
| Sanctum protected untuk Kiosk & Hardware Integration
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Auth (Kiosk flow — tanpa password, via NIS/NIP token)
    Route::post('/auth/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/auth/logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');

    // Public katalog items (untuk Kiosk tampilkan barang tersedia)
    Route::get('/items', [\App\Http\Controllers\Api\ItemApiController::class, 'index']);
    Route::get('/items/{id}', [\App\Http\Controllers\Api\ItemApiController::class, 'show']);

    // Loans — Kiosk flow
    // Endpoint pengembalian mandiri tanpa scan NIS (Layar 4 UI/UX Design)
    Route::post('/loans/return-kiosk', [LoanApiController::class, 'returnKiosk']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/loans', [LoanApiController::class, 'store']);
        Route::post('/loans/{id}/return', [LoanApiController::class, 'return']);
    });

    // Maintenance & Repair (M&R) — hanya teknisi, kabeng, kaprog
    Route::middleware(['auth:sanctum', 'role:teknisi|kabeng|kaprog'])->group(function () {
        Route::get('/maintenance/tickets', [MaintenanceApiController::class, 'index']);
        Route::get('/maintenance/tickets/{id}', [MaintenanceApiController::class, 'show']);
        Route::post('/maintenance/tickets', [MaintenanceApiController::class, 'store']);
        Route::post('/maintenance/tickets/{id}/start', [MaintenanceApiController::class, 'start']);
        Route::post('/maintenance/tickets/{id}/logs', [MaintenanceApiController::class, 'addLog']);
        Route::post('/maintenance/tickets/{id}/complete', [MaintenanceApiController::class, 'complete']);
    });

    // Gate Security — dipanggil HW-VX6330 reader saat RFID tag melintasi pintu
    Route::post('/gate/check-tag', [GateApiController::class, 'checkTag']);
});
