<?php

use App\Http\Controllers\Api\GateApiController;
use App\Http\Controllers\Api\LoanApiController;
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
    Route::post('/loans', [LoanApiController::class, 'store']);
    Route::post('/loans/{id}/return', [LoanApiController::class, 'return']);

    // Gate Security — dipanggil HW-VX6330 reader saat RFID tag melintasi pintu
    Route::post('/gate/check-tag', [GateApiController::class, 'checkTag']);
});
