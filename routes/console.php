<?php

use Illuminate\Support\Facades\Schedule;

// Cek peminjaman melewati jatuh tempo setiap 15 menit & kirim pengingat Telegram + lama terlambat.
Schedule::command('loans:check-overdue')
    ->everyFifteenMinutes()
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Cek jadwal perawatan preventif (H-7, H-3, H-1, Hari-H, Overdue) setiap jam di jam kerja lab.
Schedule::command('maintenance:check-due')
    ->hourly()
    ->between('07:00', '16:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Ringkasan operasional harian Laboratorium SIJA ke Grup Telegram.
Schedule::command('lab:daily-summary')
    ->dailyAt('17:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Sinkronisasi perintah /daftar <NIS_NIP> dari user ke bot Telegram secara lokal tanpa webhook publik.
Schedule::command('telegram:sync-updates')
    ->everyFiveMinutes()
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Pembersihan otomatis file foto snapshot gate & peminjaman yang lebih tua dari 60 hari setiap awal bulan pukul 02:00 WIB.
Schedule::command('snapshots:prune --days=60')
    ->monthlyOn(1, '02:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();
