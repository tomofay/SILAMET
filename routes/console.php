<?php

use Illuminate\Support\Facades\Schedule;

// Cek peminjaman melewati jatuh tempo → kirim notifikasi WhatsApp.
// Pagi (08.00) menjangkau durasi "pulang sekolah" dari hari sebelumnya,
// malam (20.00) menjangkau sisa durasi harian yang terlewat.
Schedule::command('loans:check-overdue')
    ->dailyAt('08:00')
    ->timezone('Asia/Jakarta');

Schedule::command('loans:check-overdue')
    ->dailyAt('20:00')
    ->timezone('Asia/Jakarta');
