@echo off
setlocal enabledelayedexpansion
title SILAMET - Lab SIJA SMKN 1 Cibinong
cd /d "%~dp0"

echo =========================================================================
echo    SILAMET - SISTEM INVENTARISASI TERPADU LAB SIJA SMKN 1 CIBINONG
echo =========================================================================
echo  [1] Laravel Backend Server  : http://127.0.0.1:8000
echo  [2] Vite Frontend Watcher   : Asset Bundler ^& Hot Reload
echo  [3] Gate Security Daemon    : UHF RFID (COM2) + Arduino Relay (COM4)
echo  [4] Queue Worker            : Pengiriman Notifikasi Telegram Async
echo  [5] Task Scheduler          : Cek Overdue, Jadwal Preventif ^& Ringkasan
echo =========================================================================
echo.
echo Menjalankan seluruh service dalam 1 konsol (tanpa perlu membuka banyak terminal)...
echo Tekan [Ctrl + C] untuk menghentikan seluruh service sekaligus.
echo =========================================================================
echo.

if not exist "public\storage" (
    call php artisan storage:link >nul 2>&1
)

if "%1"=="--sim" (
    echo [MODE SIMULASI DIAKTIFKAN]
    call npm run start:sim
) else (
    call npm run start:all
)

if errorlevel 1 (
    echo.
    echo [PERINGATAN] Terjadi kendala saat menjalankan service.
    pause
)

