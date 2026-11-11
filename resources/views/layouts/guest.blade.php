<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SILAMET') }} — Login Petugas Lab</title>

        <!-- Google Fonts: Inter, Outfit & JetBrains Mono -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased bg-neutral-950 text-neutral-100 flex items-center justify-center p-4">

        <div class="w-full max-w-md space-y-6">
            <!-- Brand Header -->
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-primary text-white font-extrabold text-xl mb-3">
                    S
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-white font-heading">
                    SILAMET
                </h1>
                <p class="text-xs text-neutral-400 mt-1">
                    Sistem Inventarisasi Terintegrasi Gate Security UHF RFID
                </p>
                <div class="mt-2.5 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-mono bg-neutral-900 border border-neutral-800 text-neutral-300">
                    <span>LAB SIJA — SMKN 1 CIBINONG</span>
                </div>
            </div>

            <!-- Main Auth Card -->
            <div class="bg-neutral-900 border border-neutral-800 p-6 sm:p-8 rounded-xl">
                {{ $slot }}
            </div>

            <!-- Footer Return to Kiosk -->
            <div class="text-center">
                <a href="{{ route('kiosk.home') }}" class="inline-flex items-center gap-2 text-xs font-medium text-neutral-400 hover:text-white transition-colors py-2 px-3 rounded-lg hover:bg-neutral-900 border border-transparent hover:border-neutral-800">
                    <svg class="w-4 h-4 text-primary-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke Layanan Mandiri Kiosk Siswa</span>
                </a>
            </div>
        </div>

    </body>
</html>
