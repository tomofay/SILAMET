<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-neutral-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>SILAMET</title>

    <!-- Google Fonts: Inter, Outfit & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }

        /* Typography logo wordmark */
        .brand-wordmark {
            font-family: 'Outfit', 'Inter', sans-serif;
            letter-spacing: 0.1em;
        }

        /* Pulse ring animation untuk target scanner Kiosk */
        @keyframes scanPulse {
            0% { transform: scale(0.96); opacity: 0.75; }
            70% { transform: scale(1.06); opacity: 0; }
            100% { transform: scale(0.96); opacity: 0; }
        }
        .scanner-beacon {
            animation: scanPulse 2.2s infinite cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Precision optical laser scanline */
        @keyframes scanLine {
            0% { top: 10%; opacity: 0; }
            15% { opacity: 1; }
            85% { opacity: 1; }
            100% { top: 90%; opacity: 0; }
        }
        .scan-laser {
            position: absolute;
            left: 10%;
            right: 10%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #4F46E5 25%, #818CF8 50%, #4F46E5 75%, transparent);
            animation: scanLine 2.2s infinite ease-in-out;
        }
        .scan-laser::after {
            content: '';
            position: absolute;
            left: 10%;
            right: 10%;
            top: -10px;
            height: 20px;
            background: linear-gradient(180deg, transparent, rgba(79, 70, 229, 0.14), transparent);
        }

        /* Subtle sweep bar untuk dark scanner strip */
        @keyframes scanSweep {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(250%); }
        }
        .scan-sweep-bar {
            animation: scanSweep 2.6s infinite ease-in-out;
        }

        /* Success check pop animation */
        @keyframes successPop {
            0% { transform: scale(0.85); opacity: 0.4; }
            60% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        .success-pop {
            animation: successPop 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>
<body class="h-full font-sans antialiased text-neutral-900 select-none overflow-x-hidden" x-data="kioskApp()" x-init="initKiosk()" x-cloak>

    <!-- ========================================================================= -->
    <!-- HEADER BAR (Typographic Wordmark & Jam Realtime) -->
    <!-- ========================================================================= -->
    <header class="bg-neutral-900 text-white border-b border-neutral-800 px-4 sm:px-6 py-3.5 flex items-center justify-between sticky top-0 z-40">
        <div class="flex items-center">
            <a href="{{ route('kiosk.home') }}" class="focus:outline-none">
                <h1 class="brand-wordmark text-2xl sm:text-[26px] font-extrabold text-white leading-none uppercase">
                    SILAMET
                </h1>
            </a>
        </div>

        <div class="flex items-center gap-3 sm:gap-4">
            <!-- Clock -->
            <div class="hidden sm:block text-right bg-neutral-950 border border-neutral-800 px-3.5 py-1.5 rounded-lg">
                <div class="text-sm font-bold font-mono tracking-wider text-neutral-100 tabular-nums" x-text="currentTime">--:--:--</div>
                <div class="text-[11px] font-mono text-neutral-400" x-text="currentDate">Memuat tanggal...</div>
            </div>

            <!-- Standby / Reset button jika sedang dalam transaksi -->
            <template x-if="screen !== 'standby'">
                <button 
                    @click="resetToStandby()"
                    type="button" 
                    class="min-h-[44px] min-w-[44px] bg-neutral-800 hover:bg-neutral-700 text-white border border-neutral-700 px-4 py-2 rounded-lg text-xs font-semibold flex items-center gap-2 transition-colors"
                >
                    <svg class="w-4 h-4 text-neutral-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Batal / Mulai Ulang</span>
                </button>
            </template>

            <!-- Tautan login web admin -->
            <template x-if="screen === 'standby'">
                <a 
                    href="{{ route('login') }}" 
                    class="min-h-[44px] min-w-[44px] text-xs font-semibold text-neutral-200 hover:text-white px-3.5 py-2 rounded-lg border border-neutral-700 hover:border-neutral-600 bg-neutral-800 hover:bg-neutral-700 transition-colors flex items-center gap-2"
                >
                    <svg class="w-4 h-4 text-primary-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span>Login Petugas</span>
                </a>
            </template>
        </div>
    </header>

    <!-- Global Toast Alert -->
    <div 
        x-show="toast.visible" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed top-20 left-1/2 -translate-x-1/2 z-50 max-w-xl w-full px-4"
    >
        <div 
            :class="{
                'bg-emerald-900 text-white border-emerald-700': toast.type === 'success',
                'bg-rose-900 text-white border-rose-700': toast.type === 'error',
                'bg-neutral-900 text-white border-neutral-700': toast.type === 'info'
            }"
            class="p-4 rounded-xl border flex items-center gap-3.5"
        >
            <div class="shrink-0">
                <template x-if="toast.type === 'success'">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
                <template x-if="toast.type === 'error'">
                    <svg class="w-6 h-6 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </template>
                <template x-if="toast.type !== 'success' && toast.type !== 'error'">
                    <svg class="w-6 h-6 text-primary-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-bold text-xs tracking-wider uppercase" x-text="toast.title"></div>
                <div class="text-sm text-neutral-100 mt-0.5" x-text="toast.message"></div>
            </div>
            <button @click="toast.visible = false" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-neutral-400 hover:text-white rounded-lg transition-colors" aria-label="Tutup peringatan">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MAIN CONTAINER -->
    <!-- ========================================================================= -->
    <main class="max-w-6xl mx-auto p-4 sm:p-6 flex flex-col justify-center min-h-[calc(100vh-76px)]">

        <!-- ===================================================================== -->
        <!-- STEPPER PROGRESS BAR -->
        <!-- ===================================================================== -->
        <div class="mb-8 max-w-xl mx-auto w-full px-4" x-show="screen !== 'return_screen' && screen !== 'success'">
            <div class="flex items-center justify-between relative">
                <!-- Track Background -->
                <div class="absolute left-6 right-6 top-5 -translate-y-1/2 h-0.5 bg-neutral-200 -z-0"></div>
                <!-- Track Active -->
                <div class="absolute left-6 top-5 -translate-y-1/2 h-0.5 bg-primary -z-0 transition-all duration-300"
                    :style="screen === 'standby' ? 'width: 0%' : (screen === 'scan_item' ? 'width: 50%' : 'width: calc(100% - 48px)')"></div>

                <!-- Step 1 -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-mono font-bold text-sm border transition-colors"
                        :class="screen === 'standby' ? 'bg-primary text-white border-primary' : 'bg-emerald-600 text-white border-emerald-600'">
                        <template x-if="screen === 'standby'"><span>01</span></template>
                        <template x-if="screen !== 'standby'">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </template>
                    </div>
                    <span class="text-xs font-semibold mt-2" :class="screen === 'standby' ? 'text-primary-700' : 'text-neutral-700'">Scan Kartu</span>
                </div>

                <!-- Step 2 -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-mono font-bold text-sm border transition-colors"
                        :class="screen === 'scan_item' ? 'bg-primary text-white border-primary' : (screen === 'snapshot_confirm' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-neutral-400 border-neutral-300')">
                        <template x-if="screen === 'snapshot_confirm'">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </template>
                        <template x-if="screen !== 'snapshot_confirm'"><span>02</span></template>
                    </div>
                    <span class="text-xs font-semibold mt-2" :class="screen === 'scan_item' ? 'text-primary-700' : 'text-neutral-500'">Pilih Alat</span>
                </div>

                <!-- Step 3 -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-mono font-bold text-sm border transition-colors"
                        :class="screen === 'snapshot_confirm' ? 'bg-primary text-white border-primary' : 'bg-white text-neutral-400 border-neutral-300'">
                        <span>03</span>
                    </div>
                    <span class="text-xs font-semibold mt-2" :class="screen === 'snapshot_confirm' ? 'text-primary-700' : 'text-neutral-500'">Foto & Selesai</span>
                </div>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- LAYAR 1: STANDBY / IDLE SCREEN -->
        <!-- ===================================================================== -->
        <section
            x-show="screen === 'standby'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="flex flex-col items-center justify-center text-center py-4"
        >
            
            <!-- Precision Scanner Viewfinder with Active Beacon & Laser Sweep -->
            <div class="relative w-56 h-56 mb-6 flex items-center justify-center">
                <!-- Outer Pulse Ring -->
                <div class="absolute inset-1 rounded-2xl border-2 border-primary/35 scanner-beacon pointer-events-none"></div>

                <!-- Target Box Scanner -->
                <div class="relative w-52 h-52 rounded-xl bg-white border border-neutral-200 flex flex-col items-center justify-center p-6 overflow-hidden">
                    <!-- Technical Corner Brackets -->
                    <span class="absolute top-2.5 left-2.5 w-4 h-4 border-t-2 border-l-2 border-primary"></span>
                    <span class="absolute top-2.5 right-2.5 w-4 h-4 border-t-2 border-r-2 border-primary"></span>
                    <span class="absolute bottom-2.5 left-2.5 w-4 h-4 border-b-2 border-l-2 border-primary"></span>
                    <span class="absolute bottom-2.5 right-2.5 w-4 h-4 border-b-2 border-r-2 border-primary"></span>

                    <!-- Active Optical Laser Sweep -->
                    <div class="scan-laser pointer-events-none z-20"></div>

                    <div class="relative z-10 p-3.5 rounded-xl bg-primary-50 text-primary-600 mb-3 border border-primary-100">
                        <svg class="w-11 h-11" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                        </svg>
                    </div>
                    <span class="relative z-10 font-mono text-[11px] font-bold uppercase tracking-wider text-primary-700 bg-primary-50 border border-primary-100 px-2.5 py-0.5 rounded-md">SCANNER SIAGA</span>
                </div>
            </div>

            <h2 class="text-2xl sm:text-4xl font-extrabold text-neutral-900 tracking-tight mb-2.5 font-heading">
                Tempelkan Barcode Kartu Pelajar Anda
            </h2>
            <p class="text-sm sm:text-base text-neutral-600 max-w-xl mb-6 leading-relaxed">
                Arahkan barcode <span class="font-mono font-semibold text-neutral-900">NIS Kartu Siswa</span> ke pemindai barcode meja untuk memulai peminjaman mandiri alat praktikum.
            </p>

            <!-- Input Barcode Listener (Hidden Background Listener khusus Scanner USB) -->
            <form @submit.prevent="handleNisSubmit()" class="w-full max-w-md mb-6">
                <input 
                    x-ref="nisInput"
                    x-model="scanInputs.nis"
                    type="text" 
                    autocomplete="off"
                    autofocus
                    class="opacity-0 absolute pointer-events-none -z-50 h-0 w-0"
                    @blur="keepFocus($event)"
                />
                <div class="inline-flex items-center gap-2.5 px-5 py-3 rounded-xl bg-white border border-neutral-200">
                    <span class="relative flex h-2.5 w-2.5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <span class="text-sm font-semibold text-neutral-800">Scanner USB Siaga</span>
                    <span class="text-neutral-300">|</span>
                    <span class="text-xs text-neutral-500">Dekatkan kartu ke sensor</span>
                </div>
            </form>

            <!-- Alternatif Cepat: Tombol Pengembalian Mandiri -->
            <div class="pt-6 border-t border-neutral-200 w-full max-w-md flex items-center justify-center">
                <button 
                    @click="goToReturnScreen()"
                    type="button" 
                    class="min-h-[52px] min-w-[44px] w-full bg-white hover:bg-neutral-50 text-neutral-900 border-2 border-neutral-300 hover:border-primary font-bold px-6 py-3.5 rounded-xl flex items-center justify-center gap-3 transition-colors text-base"
                >
                    <div class="p-1.5 rounded-lg bg-primary-50 text-primary-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <span>Pengembalian Barang Mandiri</span>
                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </section>

        <!-- ===================================================================== -->
        <!-- LAYAR 2: SCAN BARANG & PILIHAN DURASI PEMAKAIAN -->
        <!-- ===================================================================== -->
        <section
            x-show="screen === 'scan_item'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="py-2 space-y-4"
        >
            
            <!-- User Header Card -->
            <div class="bg-white rounded-xl p-4 sm:p-5 border border-neutral-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-700 flex items-center justify-center font-bold text-xl border border-primary-100 shrink-0">
                        <span x-text="currentUser.name ? currentUser.name.charAt(0).toUpperCase() : '?'"></span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-lg font-bold text-neutral-900 tracking-tight" x-text="currentUser.name">Nama Siswa</h2>
                            <span class="bg-neutral-100 text-neutral-800 font-mono text-xs px-2.5 py-0.5 rounded-md font-semibold border border-neutral-200" x-text="'NIS: ' + currentUser.nis_nip"></span>
                        </div>
                        <div class="text-xs sm:text-sm mt-1 flex items-center gap-2">
                            <span class="text-neutral-500">Kontak WhatsApp:</span>
                            <template x-if="currentUser.phone">
                                <span class="font-mono font-semibold text-emerald-700 inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span x-text="currentUser.phone"></span>
                                </span>
                            </template>
                            <template x-if="!currentUser.phone">
                                <span class="font-semibold text-rose-700 inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Belum Terdaftar (Wajib Lapor Admin)
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                <button 
                    @click="resetToStandby()" 
                    type="button"
                    class="min-h-[44px] min-w-[44px] text-neutral-700 hover:text-neutral-900 bg-neutral-100 hover:bg-neutral-200 border border-neutral-200 text-xs font-semibold px-4 py-2 rounded-lg transition-colors"
                >
                    Batalkan Transaksi
                </button>
            </div>

            <!-- Peringatan Jika Nomor Telepon Kosong -->
            <template x-if="!currentUser.phone">
                <div class="bg-rose-50 border border-rose-300 text-rose-900 p-5 rounded-xl flex items-start gap-3.5">
                    <div class="p-2 rounded-lg bg-rose-100 text-rose-700 shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base mb-1">Nomor Kontak / WhatsApp Belum Terdaftar</h3>
                        <p class="text-sm text-rose-800">
                            Sesuai aturan operasional laboratorium, peminjaman hanya dapat diproses jika nomor kontak siswa telah terdaftar untuk administrasi peminjaman alat.
                        </p>
                        <p class="text-sm font-semibold mt-2 text-rose-900">
                            Silakan temui Teknisi atau Kepala Bengkel Lab SIJA untuk melengkapi data kontak Anda.
                        </p>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4" x-show="currentUser.phone">
                
                <!-- Kolom Kiri: Scanner USB & Daftar Barang Terpilih -->
                <div class="lg:col-span-6 flex flex-col gap-4">
                    
                    <!-- USB Scanner Card -->
                    <div class="relative overflow-hidden bg-neutral-900 border border-neutral-800 rounded-xl p-5 text-white flex flex-col items-center text-center">
                        <!-- Subtle Top Sweep Bar -->
                        <div class="absolute top-0 left-0 right-0 h-0.5 bg-neutral-800 overflow-hidden">
                            <div class="h-full w-1/3 bg-gradient-to-r from-transparent via-primary-light to-transparent scan-sweep-bar"></div>
                        </div>

                        <form @submit.prevent="handleItemScan()">
                            <input 
                                x-ref="itemInput"
                                x-model="scanInputs.itemCode"
                                type="text"
                                autocomplete="off"
                                autofocus
                                class="opacity-0 absolute pointer-events-none -z-50 h-0 w-0"
                                @blur="keepFocus($event)"
                            />
                        </form>

                        <div class="relative p-3 rounded-lg bg-neutral-800 border border-neutral-700 text-info mb-3 overflow-hidden">
                            <div class="scan-laser opacity-70 pointer-events-none"></div>
                            <svg class="w-7 h-7 relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>

                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-emerald-950 border border-emerald-800/60 text-emerald-400 font-mono text-[11px] font-semibold mb-2">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span>SCANNER BARCODE SIAGA</span>
                        </div>

                        <h4 class="text-base font-bold text-white tracking-tight">Arahkan Barcode / QR Alat ke Scanner</h4>
                        <p class="text-xs text-neutral-400 mt-1 max-w-sm">
                            Dekatkan label barcode pada fisik alat ke sensor scanner USB. Alat otomatis masuk ke daftar di bawah.
                        </p>
                    </div>

                    <!-- Panel Daftar Barang Terpilih -->
                    <div class="bg-white rounded-xl p-5 border border-neutral-200 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-4 border-b border-neutral-100 pb-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="p-2 rounded-lg bg-neutral-100 text-neutral-600">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-neutral-900">Daftar Alat yang Discan</h4>
                                        <p class="text-[11px] text-neutral-500">Barang yang akan Anda pinjam</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-md font-mono text-xs font-bold border" :class="selectedItems.length > 0 ? 'bg-primary-50 text-primary-700 border-primary-100' : 'bg-neutral-100 text-neutral-500 border-neutral-200'">
                                        <span x-text="selectedItems.length">0</span> Item
                                    </span>
                                    <button 
                                        x-show="selectedItems.length > 0" 
                                        type="button" 
                                        @click="selectedItems = []" 
                                        class="min-h-[44px] px-2.5 text-xs text-rose-700 hover:bg-rose-50 rounded-lg font-semibold transition-colors"
                                    >
                                        Hapus Semua
                                    </button>
                                </div>
                            </div>

                            <!-- List Barang Terpilih -->
                            <div x-show="selectedItems.length > 0" class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                                <template x-for="(item, idx) in selectedItems" :key="item.id">
                                    <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200 space-y-2">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <span class="w-7 h-7 rounded-lg bg-primary text-white font-mono text-xs font-bold flex items-center justify-center shrink-0" x-text="idx + 1"></span>
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <span class="text-sm font-bold text-neutral-900 truncate" x-text="item.name"></span>
                                                        <span x-show="item.type === 'bahan_habis_pakai'" class="px-1.5 py-0.2 rounded font-mono text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">BHP</span>
                                                    </div>
                                                    <div class="text-xs text-neutral-500 font-mono mt-0.5">
                                                        <span class="font-semibold text-neutral-800" x-text="item.item_code"></span> · Rak: <span x-text="item.location"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <button 
                                                type="button" 
                                                @click="removeItem(item.id)" 
                                                class="min-h-[44px] min-w-[44px] rounded-lg bg-white hover:bg-rose-50 text-neutral-500 hover:text-rose-700 flex items-center justify-center border border-neutral-200 hover:border-rose-200 transition-colors shrink-0 cursor-pointer" 
                                                title="Hapus dari daftar"
                                                aria-label="Hapus barang dari daftar"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <template x-if="item.type === 'bahan_habis_pakai'">
                                            <div class="pt-2 border-t border-neutral-200 flex items-center justify-between text-xs">
                                                <div>
                                                    <span class="font-bold text-neutral-800">Jumlah konektor/unit dipakai:</span>
                                                    <p class="text-[10px] text-neutral-500">Isi jumlah pcs yang diambil</p>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <button type="button" @click="item.quantity = Math.max(1, (item.quantity || 1) - 1)" class="w-7 h-7 rounded-lg bg-neutral-200 hover:bg-neutral-300 text-neutral-800 font-bold flex items-center justify-center cursor-pointer">-</button>
                                                    <input type="number" x-model.number="item.quantity" min="1" max="10000" class="w-16 text-center font-mono font-bold text-xs py-1 rounded-lg border border-neutral-300">
                                                    <button type="button" @click="item.quantity = (item.quantity || 1) + 1" class="w-7 h-7 rounded-lg bg-neutral-200 hover:bg-neutral-300 text-neutral-800 font-bold flex items-center justify-center cursor-pointer">+</button>
                                                    <span class="font-mono text-[11px] text-neutral-600 font-semibold">pcs</span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <!-- Empty State -->
                            <div x-show="selectedItems.length === 0" class="py-10 px-4 text-center border border-dashed border-neutral-300 rounded-xl bg-neutral-50/50">
                                <div class="p-2.5 mx-auto w-fit rounded-lg bg-neutral-100 text-neutral-500 mb-2.5">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div class="text-sm font-semibold text-neutral-800">Belum Ada Alat yang Discan</div>
                                <p class="text-xs text-neutral-500 mt-1 max-w-xs mx-auto">
                                    Pindai barcode pada fisik alat menggunakan USB scanner untuk menambahkannya ke peminjaman.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-neutral-100 flex items-center gap-2 text-xs text-neutral-500">
                            <svg class="w-4 h-4 text-primary-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Hanya menerima input dari USB Barcode Scanner fisik Kiosk.</span>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Pilihan Durasi Pemakaian (Touch Friendly) -->
                <div class="lg:col-span-6 flex flex-col">
                    <div class="bg-white rounded-xl p-5 border border-neutral-200 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="p-2 rounded-lg bg-primary/10 text-primary-600">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-bold text-neutral-900">Pilih Durasi Pemakaian</h3>
                                </div>
                                <span class="text-xs font-medium text-neutral-500">*Sentuh salah satu</span>
                            </div>
                            <p class="text-xs text-neutral-500 mb-4">
                                Batas waktu pengembalian otomatis dihitung dan dipantau sistem.
                            </p>

                            <!-- Duration Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
                                
                                <!-- Card 1: Pulang Sekolah -->
                                <button 
                                    type="button"
                                    @click="setDuration('pulang_sekolah')"
                                    :class="selectedDuration === 'pulang_sekolah' ? 'border-primary bg-primary-50/70' : 'border-neutral-200 hover:border-neutral-300 bg-white'"
                                    class="min-h-[84px] p-4 rounded-xl border-2 text-left transition-colors flex flex-col justify-between"
                                >
                                    <div class="flex items-center justify-between w-full">
                                        <span class="font-bold text-sm sm:text-base text-neutral-900">Pulang Sekolah</span>
                                        <span class="w-5 h-5 rounded-full border-2 border-primary flex items-center justify-center shrink-0" x-show="selectedDuration === 'pulang_sekolah'">
                                            <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                                        </span>
                                    </div>
                                    <span class="text-xs font-mono text-neutral-500">Hari ini, 16:00 WIB</span>
                                </button>

                                <!-- Card 2: 1 Hari -->
                                <button 
                                    type="button"
                                    @click="setDuration('1_hari')"
                                    :class="selectedDuration === '1_hari' ? 'border-primary bg-primary-50/70' : 'border-neutral-200 hover:border-neutral-300 bg-white'"
                                    class="min-h-[84px] p-4 rounded-xl border-2 text-left transition-colors flex flex-col justify-between"
                                >
                                    <div class="flex items-center justify-between w-full">
                                        <span class="font-bold text-sm sm:text-base text-neutral-900">1 Hari</span>
                                        <span class="w-5 h-5 rounded-full border-2 border-primary flex items-center justify-center shrink-0" x-show="selectedDuration === '1_hari'">
                                            <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                                        </span>
                                    </div>
                                    <span class="text-xs font-mono text-neutral-500">Besok, 23:59 WIB</span>
                                </button>

                                <!-- Card 3: 2 Hari -->
                                <button 
                                    type="button"
                                    @click="setDuration('2_hari')"
                                    :class="selectedDuration === '2_hari' ? 'border-primary bg-primary-50/70' : 'border-neutral-200 hover:border-neutral-300 bg-white'"
                                    class="min-h-[84px] p-4 rounded-xl border-2 text-left transition-colors flex flex-col justify-between"
                                >
                                    <div class="flex items-center justify-between w-full">
                                        <span class="font-bold text-sm sm:text-base text-neutral-900">2 Hari</span>
                                        <span class="w-5 h-5 rounded-full border-2 border-primary flex items-center justify-center shrink-0" x-show="selectedDuration === '2_hari'">
                                            <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                                        </span>
                                    </div>
                                    <span class="text-xs font-mono text-neutral-500">+2 Hari, 23:59 WIB</span>
                                </button>

                                <!-- Card 4: 3 Hari -->
                                <button 
                                    type="button"
                                    @click="setDuration('3_hari')"
                                    :class="selectedDuration === '3_hari' ? 'border-primary bg-primary-50/70' : 'border-neutral-200 hover:border-neutral-300 bg-white'"
                                    class="min-h-[84px] p-4 rounded-xl border-2 text-left transition-colors flex flex-col justify-between"
                                >
                                    <div class="flex items-center justify-between w-full">
                                        <span class="font-bold text-sm sm:text-base text-neutral-900">3 Hari</span>
                                        <span class="w-5 h-5 rounded-full border-2 border-primary flex items-center justify-center shrink-0" x-show="selectedDuration === '3_hari'">
                                            <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                                        </span>
                                    </div>
                                    <span class="text-xs font-mono text-neutral-500">+3 Hari, 23:59 WIB</span>
                                </button>

                                <!-- Card 5: 1 Minggu (Span 2) -->
                                <button 
                                    type="button"
                                    @click="setDuration('1_minggu')"
                                    :class="selectedDuration === '1_minggu' ? 'border-primary bg-primary-50/70' : 'border-neutral-200 hover:border-neutral-300 bg-white'"
                                    class="sm:col-span-2 min-h-[84px] p-4 rounded-xl border-2 text-left transition-colors flex flex-col justify-between"
                                >
                                    <div class="flex items-center justify-between w-full">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-sm sm:text-base text-neutral-900">1 Minggu</span>
                                            <span class="text-[11px] bg-neutral-100 text-neutral-700 border border-neutral-200 px-2 py-0.5 rounded-md font-semibold">Proyek Khusus</span>
                                        </div>
                                        <span class="w-5 h-5 rounded-full border-2 border-primary flex items-center justify-center shrink-0" x-show="selectedDuration === '1_minggu'">
                                            <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                                        </span>
                                    </div>
                                    <span class="text-xs font-mono text-neutral-500">7 Hari Kalender, 23:59 WIB</span>
                                </button>
                            </div>

                            <!-- Estimasi Due At Box -->
                            <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200 mb-5">
                                <div class="text-[11px] font-semibold uppercase text-neutral-500 tracking-wider">Estimasi Batas Pengembalian</div>
                                <div class="text-sm sm:text-base font-bold font-mono text-neutral-900 mt-1 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-primary-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span x-text="estimatedDueText">Pilih durasi pemakaian...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Next Button -->
                        <div>
                            <button 
                                @click="proceedToSnapshotScreen()"
                                :disabled="selectedItems.length === 0"
                                type="button"
                                class="min-h-[52px] w-full py-3.5 px-5 rounded-xl font-bold text-base text-white border transition-colors flex items-center justify-center gap-2.5"
                                :class="selectedItems.length > 0 ? 'bg-primary hover:bg-primary-700 border-primary/40 cursor-pointer' : 'bg-neutral-200 border-neutral-300 cursor-not-allowed text-neutral-500'"
                            >
                                <span>Lanjutkan ke Foto Wajah</span>
                                <span x-show="selectedItems.length > 0" class="font-mono text-xs bg-white text-primary-700 px-2 py-0.5 rounded-md font-bold" x-text="selectedItems.length + ' Item'"></span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- ===================================================================== -->
        <!-- LAYAR 3: SNAPSHOT KAMERA & KONFIRMASI PEMINJAMAN -->
        <!-- ===================================================================== -->
        <section
            x-show="screen === 'snapshot_confirm'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="py-2"
        >
            
            <div class="max-w-4xl mx-auto bg-white rounded-xl p-5 sm:p-6 border border-neutral-200">
                <div class="text-center mb-6 pb-4 border-b border-neutral-100">
                    <span class="font-mono bg-primary-50 text-primary-700 text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-md border border-primary-100">
                        Tahap Akhir Verifikasi
                    </span>
                    <h2 class="text-xl sm:text-2xl font-bold text-neutral-900 tracking-tight mt-2">
                        Pratinjau Foto Wajah Siswa
                    </h2>
                    <p class="text-xs sm:text-sm text-neutral-600 mt-0.5">
                        Posisikan wajah menghadap kamera Kiosk untuk dokumentasi keamanan laboratorium.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                    
                    <!-- WebRTC / Shared Daemon Camera Preview -->
                    <div class="md:col-span-7 flex flex-col items-center">
                        <div class="relative w-full aspect-video rounded-xl overflow-hidden bg-neutral-950 border-2 border-neutral-800 flex items-center justify-center">
                            
                            <!-- Shared Gate & Kiosk Camera Stream (MJPEG) -->
                            <img
                                x-show="cameraMode === 'daemon' && daemonStreamUrl !== ''"
                                x-ref="daemonVideo"
                                :src="daemonStreamUrl"
                                alt="Pratinjau kamera Kiosk"
                                x-on:load="onDaemonStreamLoad()"
                                x-on:error="onDaemonStreamError()"
                                class="w-full h-full object-cover"
                            >

                            <!-- Video Element (WebRTC Fallback) -->
                            <video 
                                x-show="cameraMode === 'webrtc'"
                                x-ref="videoElement" 
                                autoplay 
                                playsinline 
                                muted 
                                class="w-full h-full object-cover"
                            ></video>

                            <!-- Hidden Canvas for Capture -->
                            <canvas x-ref="captureCanvas" class="hidden"></canvas>

                            <!-- Clean Face Frame Overlay -->
                            <div class="absolute inset-8 border border-dashed border-white/50 rounded-lg pointer-events-none flex items-end justify-center pb-3">
                                <span class="font-mono text-[11px] font-semibold tracking-wider text-white bg-neutral-900/80 border border-neutral-700 px-2.5 py-0.5 rounded">
                                    AREA WAJAH
                                </span>
                            </div>

                            <!-- Loading/Error Indicator -->
                            <div x-show="cameraStatus !== 'ready'" class="absolute inset-0 bg-neutral-900 text-white flex flex-col items-center justify-center p-6 text-center">
                                <div class="p-3 rounded-lg bg-neutral-800 border border-neutral-700 mb-3">
                                    <svg class="w-7 h-7 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-medium" x-text="cameraStatusMessage">Menghubungkan webcam...</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 mt-2.5 text-xs text-neutral-500">
                            <span class="relative flex h-2 w-2" x-show="cameraStatus === 'ready'">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500" x-show="cameraStatus === 'pending'"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500" x-show="cameraStatus === 'error'"></span>
                            <span x-text="cameraStatus === 'ready' ? (cameraMode === 'daemon' ? 'Kamera aktif (Kamera Terpadu Gate & Kiosk)' : 'Kamera aktif (WebRTC Camera Kiosk)') : cameraStatusMessage"></span>
                        </div>
                    </div>

                    <!-- Ringkasan Transaksi & Tombol Konfirmasi -->
                    <div class="md:col-span-5 flex flex-col justify-between h-full space-y-4">
                        
                        <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200 space-y-3">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-neutral-500">Ringkasan Peminjaman</div>
                            
                            <div class="border-b border-neutral-200 pb-2.5">
                                <div class="text-xs text-neutral-500">Peminjam</div>
                                <div class="font-bold text-neutral-900 text-sm mt-0.5" x-text="currentUser.name"></div>
                                <div class="text-xs text-neutral-600 font-mono" x-text="'NIS: ' + currentUser.nis_nip"></div>
                            </div>

                            <div class="border-b border-neutral-200 pb-2.5">
                                <div class="text-xs text-neutral-500 mb-1.5">
                                    Daftar Barang (<span class="font-mono font-bold text-neutral-900" x-text="selectedItems.length"></span> item)
                                </div>
                                <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                                    <template x-for="(item, idx) in selectedItems" :key="item.id">
                                        <div class="flex items-center justify-between text-xs bg-white p-2 rounded-lg border border-neutral-200">
                                            <div class="min-w-0 pr-2">
                                                <div class="font-semibold text-neutral-900 truncate" x-text="(idx + 1) + '. ' + item.name"></div>
                                                <div class="text-[11px] text-neutral-500 font-mono" x-text="item.item_code"></div>
                                            </div>
                                            <span class="font-mono text-[11px] text-neutral-500 shrink-0" x-text="item.location"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-neutral-500">Batas Waktu Pengembalian</div>
                                <div class="font-mono font-bold text-rose-700 text-xs sm:text-sm mt-0.5" x-text="estimatedDueText"></div>
                            </div>
                        </div>

                        <div class="space-y-2.5">
                            <!-- Confirm Button -->
                            <button 
                                @click="confirmLoan()"
                                :disabled="isSubmitting"
                                type="button" 
                                class="min-h-[54px] w-full bg-emerald-700 hover:bg-emerald-600 text-white font-bold text-base py-3.5 px-5 rounded-xl border border-emerald-600 transition-colors flex items-center justify-center gap-2.5 cursor-pointer disabled:opacity-50"
                            >
                                <template x-if="!isSubmitting">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span>Konfirmasi Peminjaman</span>
                                    </div>
                                </template>
                                <template x-if="isSubmitting">
                                    <div class="flex items-center gap-2">
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <span>Memproses Data...</span>
                                    </div>
                                </template>
                            </button>

                            <button 
                                @click="stopWebcam(); screen = 'scan_item'; $nextTick(() => focusActiveScanner())" 
                                type="button" 
                                class="min-h-[44px] w-full text-neutral-700 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-200 rounded-lg text-xs font-semibold py-2 flex items-center justify-center gap-1.5 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                <span>Kembali Ubah Data</span>
                            </button>
                        </div>

                    </div>

                </div>
            </div>

        </section>

        <!-- ===================================================================== -->
        <!-- LAYAR SUKSES TRANSAKSI -->
        <!-- ===================================================================== -->
        <section
            x-show="screen === 'success'"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="flex flex-col items-center justify-center text-center py-10"
        >
            <div class="w-20 h-20 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center mb-5 success-pop animate-bounce">
                <svg class="w-11 h-11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 tracking-tight mb-2" x-text="successMessage.title">
                Peminjaman Berhasil!
            </h2>
            <p class="text-sm sm:text-base text-neutral-600 max-w-lg mb-5" x-text="successMessage.body">
                Barang siap digunakan untuk kegiatan praktikum laboratorium.
            </p>
            <div class="bg-white border border-neutral-200 rounded-xl px-6 py-4 mb-6">
                <div class="text-[11px] uppercase font-bold tracking-wider text-neutral-500">Informasi Batas Pengembalian</div>
                <div class="text-lg font-mono font-bold text-neutral-900 mt-1" x-text="successMessage.dueAt"></div>
            </div>
            <div class="flex items-center gap-2 text-xs sm:text-sm text-neutral-500">
                <span>Layar akan kembali ke menu awal dalam</span>
                <span class="font-mono font-bold text-primary-700 px-1.5 py-0.5 rounded bg-primary-50 border border-primary-100" x-text="countdown">3</span>
                <span>detik</span>
            </div>
        </section>

        <!-- ===================================================================== -->
        <!-- LAYAR 4: PENGEMBALIAN MANDIRI MULTI-BARANG -->
        <!-- ===================================================================== -->
        <section
            x-show="screen === 'return_screen'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="py-2"
        >
            <div class="max-w-4xl mx-auto bg-white rounded-xl p-4 sm:p-6 border border-neutral-200">
                
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 border-b border-neutral-200 pb-4">
                    <div>
                        <span class="font-mono text-[11px] font-bold uppercase tracking-wider text-primary-700">Layanan Mandiri</span>
                        <h2 class="text-xl font-bold text-neutral-900 tracking-tight">Pengembalian Barang</h2>
                        <p class="text-xs text-neutral-500 mt-0.5">Hanya peminjam yang dapat mengembalikan barang miliknya.</p>
                    </div>
                    <button @click="resetToStandby()" type="button" class="min-h-[44px] min-w-[44px] inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-neutral-100 hover:bg-neutral-200 border border-neutral-200 text-xs font-semibold text-neutral-700 hover:text-neutral-900 transition-colors self-start sm:self-auto cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Kembali ke Beranda</span>
                    </button>
                </div>

                <!-- SUB-STEP 1: SCAN NIS PEMINJAM -->
                <template x-if="returnFlow.step === 1">
                    <div class="max-w-xl mx-auto py-4 sm:py-6 flex flex-col items-center text-center px-2">
                        <div class="p-3.5 rounded-xl bg-primary-50 text-primary-600 border border-primary-100 flex items-center justify-center mb-4">
                            <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                        </div>

                        <h3 class="text-xl sm:text-2xl font-bold text-neutral-900 tracking-tight mb-2">Scan QR Code / Kartu Siswa</h3>
                        <p class="text-xs sm:text-sm text-neutral-600 mb-6 max-w-md">
                            Pindai QR code atau barcode pada kartu pelajar peminjam ke scanner untuk memverifikasi daftar pinjaman aktif.
                        </p>

                        <!-- Hidden Scanner Input -->
                        <form @submit.prevent="verifyReturnNis()" class="w-full max-w-md">
                            <input 
                                x-ref="returnNisInput"
                                x-model="returnFlow.nis"
                                type="text" 
                                autocomplete="off"
                                autofocus
                                class="opacity-0 absolute pointer-events-none -z-50 h-0 w-0"
                                @blur="keepFocus($event)"
                            />
                        </form>

                        <!-- Card Visual Scanner USB Siaga -->
                        <div class="relative overflow-hidden w-full max-w-md p-5 rounded-xl bg-neutral-900 text-white border border-neutral-800 flex flex-col items-center text-center">
                            <div class="absolute top-0 left-0 right-0 h-0.5 bg-neutral-800 overflow-hidden">
                                <div class="h-full w-1/3 bg-gradient-to-r from-transparent via-primary-light to-transparent scan-sweep-bar"></div>
                            </div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-emerald-950 border border-emerald-800/60 text-emerald-400 font-mono text-[11px] font-semibold mb-2.5">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span>SCANNER USB SIAGA</span>
                            </div>
                            <div class="text-sm sm:text-base font-bold text-white">Arahkan QR Code Kartu ke Scanner</div>
                            <p class="text-xs text-neutral-400 mt-1 max-w-xs">
                                Dekatkan QR Code pada kartu ke sensor scanner untuk membuka daftar pinjaman aktif.
                            </p>
                        </div>
                    </div>
                </template>

                <!-- SUB-STEP 2: SCAN ALAT & DAFTAR ALAT YANG DIKEMBALIKAN -->
                <template x-if="returnFlow.step === 2">
                    <div class="space-y-4">
                        
                        <!-- Profile Peminjam Card -->
                        <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-11 h-11 rounded-xl bg-primary text-white font-bold text-lg flex items-center justify-center shrink-0">
                                    <span x-text="returnFlow.user ? returnFlow.user.name.charAt(0).toUpperCase() : '?'"></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="font-bold text-neutral-900 text-sm sm:text-base break-words" x-text="returnFlow.user ? returnFlow.user.name : ''"></h4>
                                        <span class="text-xs bg-white text-neutral-800 px-2 py-0.5 rounded-md border border-neutral-200 font-mono font-semibold shrink-0" x-text="'NIS: ' + (returnFlow.user ? returnFlow.user.nis_nip : '')"></span>
                                    </div>
                                    <div class="text-xs text-neutral-500 mt-0.5">
                                        Total alat sedang dipinjam: <span class="font-mono font-bold text-primary-700" x-text="returnFlow.activeLoans.length">0</span> barang
                                    </div>
                                </div>
                            </div>
                            <button 
                                @click="resetReturnNis()" 
                                type="button" 
                                class="min-h-[44px] min-w-[44px] px-3.5 py-2 text-xs text-neutral-700 hover:text-rose-700 bg-white hover:bg-rose-50 border border-neutral-200 rounded-lg font-semibold self-start sm:self-center shrink-0 cursor-pointer transition-colors"
                            >
                                Ganti Siswa
                            </button>
                        </div>

                        <!-- USB Scanner Status Card -->
                        <div>
                            <form @submit.prevent="addScannedReturnItem()">
                                <input 
                                    x-ref="returnItemInput"
                                    x-model="returnFlow.itemCode"
                                    type="text" 
                                    autocomplete="off"
                                    autofocus
                                    class="opacity-0 absolute pointer-events-none -z-50 h-0 w-0"
                                    @blur="keepFocus($event)"
                                />
                            </form>

                            <div class="relative overflow-hidden rounded-xl p-4 bg-neutral-900 text-white border border-neutral-800 flex items-center gap-3.5">
                                <div class="absolute top-0 left-0 right-0 h-0.5 bg-neutral-800 overflow-hidden">
                                    <div class="h-full w-1/3 bg-gradient-to-r from-transparent via-primary-light to-transparent scan-sweep-bar"></div>
                                </div>
                                <div class="relative p-2.5 rounded-lg bg-neutral-800 border border-neutral-700 text-info shrink-0 overflow-hidden">
                                    <div class="scan-laser opacity-70 pointer-events-none"></div>
                                    <svg class="w-6 h-6 relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-emerald-950 border border-emerald-800/60 text-emerald-400 font-mono text-[10px] font-semibold mb-1">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                        </span>
                                        <span>SCANNER USB SIAGA</span>
                                    </div>
                                    <h5 class="text-sm font-bold text-white">Scan Barcode / QR Alat yang Dikembalikan</h5>
                                    <p class="text-xs text-neutral-400 mt-0.5">Pindai fisik alat satu per satu atau ketuk kartu pinjaman aktif di bawah.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Daftar Pinjaman Aktif Siswa -->
                        <div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-2.5">
                                <h4 class="text-sm font-bold text-neutral-900">
                                    Daftar Pinjaman Aktif <span x-text="returnFlow.user ? '(' + returnFlow.user.name + ')' : ''"></span>
                                </h4>
                                <span class="text-xs text-neutral-500">Pindai kode fisik alat atau ketuk kartu untuk memilih</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-1">
                                <template x-for="loan in returnFlow.activeLoans" :key="loan.loan_id">
                                    <div 
                                        @click="toggleItemInReturnList(loan)"
                                        :class="isItemInReturnList(loan.item_code) ? 'border-emerald-600 bg-emerald-50/50' : 'border-neutral-200 bg-white hover:border-neutral-300'"
                                        class="min-h-[64px] p-3.5 rounded-xl border-2 transition-colors flex items-center justify-between gap-3 cursor-pointer select-none"
                                    >
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div 
                                                :class="isItemInReturnList(loan.item_code) ? 'bg-emerald-600 text-white' : 'bg-neutral-100 text-neutral-500'"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                                            >
                                                <svg x-show="isItemInReturnList(loan.item_code)" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <svg x-show="!isItemInReturnList(loan.item_code)" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-sm text-neutral-900 truncate" x-text="loan.item_name"></div>
                                                <div class="text-xs text-neutral-500 font-mono mt-0.5" x-text="loan.item_code"></div>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span 
                                                x-show="isItemInReturnList(loan.item_code)" 
                                                class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1"
                                            >
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Discan</span>
                                            </span>
                                            <span 
                                                x-show="!isItemInReturnList(loan.item_code)" 
                                                class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-neutral-100 text-neutral-600 border border-neutral-200 inline-block"
                                            >
                                                Scan Fisik
                                            </span>
                                            <span 
                                                x-show="loan.is_overdue" 
                                                class="block font-mono text-[10px] uppercase font-bold text-rose-700 mt-1"
                                            >
                                                TERLAMBAT
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Keranjang Alat yang Akan Dikembalikan -->
                        <div class="pt-4 border-t border-neutral-200">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-bold text-neutral-900 flex items-center gap-2">
                                    <span>Alat yang Akan Dikembalikan</span>
                                    <span class="bg-primary text-white font-mono text-xs px-2 py-0.5 rounded-md font-bold" x-text="returnFlow.selectedItems.length">0</span>
                                </h4>
                                <button 
                                    x-show="returnFlow.selectedItems.length > 0"
                                    @click="returnFlow.selectedItems = []"
                                    type="button" 
                                    class="min-h-[44px] px-2.5 text-xs text-neutral-600 hover:text-rose-700 font-semibold cursor-pointer rounded-lg hover:bg-rose-50 transition-colors"
                                >
                                    Kosongkan
                                </button>
                            </div>

                            <template x-if="returnFlow.selectedItems.length === 0">
                                <div class="p-6 rounded-xl border border-dashed border-neutral-300 bg-neutral-50 text-center text-neutral-500 text-xs sm:text-sm">
                                    Belum ada alat yang dipilih untuk dikembalikan. Pindai barcode alat atau pilih dari daftar di atas.
                                </div>
                            </template>

                            <div class="space-y-3">
                                <template x-for="(item, index) in returnFlow.selectedItems" :key="item.item_code">
                                    <div class="rounded-xl border transition-colors overflow-hidden"
                                        :class="item.condition_in === 'rusak' ? 'bg-rose-50/40 border-rose-300' : 'bg-neutral-50 border-neutral-200'"
                                    >
                                        <div class="p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-8 h-8 rounded-lg bg-white border border-neutral-200 flex items-center justify-center font-mono font-bold text-neutral-800 shrink-0 text-xs">
                                                    <span x-text="index + 1"></span>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="font-bold text-neutral-900 text-sm truncate" x-text="item.item_name"></div>
                                                    <div class="text-xs text-neutral-500 font-mono mt-0.5" x-text="item.item_code"></div>
                                                </div>
                                            </div>

                                            <!-- Pilihan Kondisi & Tombol Hapus (Min 44px Touch Target) -->
                                            <div class="flex items-center justify-between sm:justify-end gap-2 w-full sm:w-auto pt-2 sm:pt-0 border-t sm:border-t-0 border-neutral-200 shrink-0">
                                                <div class="flex items-center bg-white rounded-lg p-1 border border-neutral-200 flex-1 sm:flex-initial justify-center gap-1">
                                                    <button 
                                                        type="button" 
                                                        @click="item.condition_in = 'bagus'; item.issue_description = ''"
                                                        :class="item.condition_in === 'bagus' ? 'bg-emerald-600 text-white' : 'text-neutral-600 hover:text-neutral-900'"
                                                        class="min-h-[44px] flex-1 sm:flex-initial px-3.5 py-2 rounded-md text-xs font-bold transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        <span>Bagus</span>
                                                    </button>
                                                    <button 
                                                        type="button" 
                                                        @click="item.condition_in = 'rusak'"
                                                        :class="item.condition_in === 'rusak' ? 'bg-rose-600 text-white' : 'text-neutral-600 hover:text-rose-700'"
                                                        class="min-h-[44px] flex-1 sm:flex-initial px-3.5 py-2 rounded-md text-xs font-bold transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                        <span>Rusak</span>
                                                    </button>
                                                </div>

                                                <button 
                                                    @click="removeItemFromReturnList(item.item_code)"
                                                    type="button" 
                                                    class="min-h-[44px] min-w-[44px] text-neutral-500 hover:text-rose-700 p-2 rounded-lg border border-neutral-200 bg-white hover:bg-rose-50 hover:border-rose-200 transition-colors cursor-pointer shrink-0 flex items-center justify-center"
                                                    title="Hapus dari daftar kembali"
                                                    aria-label="Hapus dari daftar pengembalian"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Deskripsi Kerusakan Wajib Diisi Jika Kondisi Rusak -->
                                        <template x-if="item.condition_in === 'rusak'">
                                            <div class="px-3 sm:px-4 pb-4 pt-3 bg-rose-50 border-t border-rose-200 space-y-2">
                                                <div class="flex items-center justify-between">
                                                    <label class="text-xs font-bold text-rose-800 flex items-center gap-1.5 uppercase tracking-wide">
                                                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        <span>Catatan Kerusakan Alat (Wajib Diisi)*</span>
                                                    </label>
                                                    <span class="font-mono text-[11px] font-semibold text-rose-700">Min. 3 karakter</span>
                                                </div>
                                                <textarea 
                                                    x-model="item.issue_description"
                                                    rows="2"
                                                    placeholder="Jelaskan kondisi kerusakan alat (contoh: kabel putus, port mati, tombol macet)..."
                                                    :class="(!item.issue_description || item.issue_description.trim().length < 3) ? 'border-rose-400 focus:ring-rose-500 bg-white' : 'border-neutral-300 focus:ring-primary bg-white'"
                                                    class="w-full text-xs sm:text-sm border rounded-lg p-3 focus:outline-none focus:ring-1 text-neutral-900 placeholder-neutral-400"
                                                ></textarea>
                                                <p x-show="!item.issue_description || item.issue_description.trim().length < 3" class="text-xs text-rose-700 font-medium flex items-center gap-1.5">
                                                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    <span>Catatan kerusakan wajib diisi agar tiket perbaikan teknisi dapat segera dibuat.</span>
                                                </p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Tombol Konfirmasi Selesai Pengembalian -->
                        <div class="pt-4 border-t border-neutral-200 flex flex-col sm:flex-row gap-3">
                            <button 
                                @click="resetReturnNis()" 
                                type="button" 
                                class="min-h-[48px] w-full sm:w-1/3 py-3 bg-neutral-100 hover:bg-neutral-200 border border-neutral-200 text-neutral-800 font-semibold rounded-xl transition-colors text-center text-sm cursor-pointer"
                            >
                                Batal / Ganti Siswa
                            </button>
                            <button 
                                @click="submitReturn()"
                                :disabled="returnFlow.selectedItems.length === 0 || returnFlow.isSubmitting"
                                type="button" 
                                class="min-h-[48px] w-full sm:w-2/3 bg-primary hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-base py-3 rounded-xl border border-primary/40 transition-colors flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <span x-show="!returnFlow.isSubmitting">
                                    Konfirmasi Pengembalian (<span class="font-mono" x-text="returnFlow.selectedItems.length">0</span> Barang)
                                </span>
                                <span x-show="returnFlow.isSubmitting">Memproses Pengembalian...</span>
                            </button>
                        </div>

                    </div>
                </template>

            </div>
        </section>

    </main>

    <!-- ========================================================================= -->
    <!-- LOGIC APPLICATION (Alpine.js Framework) -->
    <!-- ========================================================================= -->
    <script>
        function kioskApp() {
            return {
                screen: 'standby', // 'standby' | 'scan_item' | 'snapshot_confirm' | 'success' | 'return_screen'
                currentTime: '',
                currentDate: '',
                clockInterval: null,
                countdown: 3,
                countdownInterval: null,
                isSubmitting: false,

                availableItems: @json($availableItems ?? []),

                scanInputs: {
                    nis: '',
                    itemCode: ''
                },

                currentUser: {
                    id: null,
                    name: '',
                    nis_nip: '',
                    phone: '',
                    token: ''
                },
                selectedItems: [],
                selectedDuration: 'pulang_sekolah',
                estimatedDueText: '',

                returnFlow: {
                    step: 1,
                    nis: '',
                    user: null,
                    activeLoans: [],
                    selectedItems: [],
                    itemCode: '',
                    isLoading: false,
                    isSubmitting: false
                },

                cameraStream: null,
                cameraMode: 'detecting', // 'detecting' | 'daemon' | 'webrtc'
                daemonBaseUrl: 'http://127.0.0.1:8765',
                daemonStreamUrl: '',
                cameraProbeSeq: 0,
                cameraStatus: 'pending',
                cameraStatusMessage: 'Menyiapkan kamera Kiosk...',

                toast: {
                    visible: false,
                    type: 'info',
                    title: '',
                    message: ''
                },
                toastTimeout: null,

                successMessage: {
                    title: 'Peminjaman Berhasil!',
                    body: 'Barang siap digunakan untuk kegiatan praktikum laboratorium.',
                    dueAt: ''
                },

                initKiosk() {
                    this.updateClock();
                    this.clockInterval = setInterval(() => this.updateClock(), 1000);
                    this.calculateEstimatedDue();
                    
                    this.$nextTick(() => {
                        this.focusActiveScanner();
                    });

                    window.addEventListener('click', (e) => {
                        if (e.target && (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT' || (e.target.tagName === 'INPUT' && !e.target.classList.contains('pointer-events-none')))) {
                            return;
                        }
                        setTimeout(() => this.focusActiveScanner(), 80);
                    });

                    window.addEventListener('beforeunload', () => {
                        this.stopWebcam();
                    });
                },

                playAudioTone(type) {
                    try {
                        const AudioContext = window.AudioContext || window.webkitAudioContext;
                        if (!AudioContext) return;
                        const ctx = new AudioContext();

                        if (type === 'beep') {
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(880, ctx.currentTime);
                            gain.gain.setValueAtTime(0.15, ctx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.12);
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start();
                            osc.stop(ctx.currentTime + 0.12);
                        } else if (type === 'success') {
                            const notes = [587.33, 880];
                            notes.forEach((freq, idx) => {
                                const osc = ctx.createOscillator();
                                const gain = ctx.createGain();
                                osc.type = 'sine';
                                osc.frequency.setValueAtTime(freq, ctx.currentTime + (idx * 0.1));
                                gain.gain.setValueAtTime(0.2, ctx.currentTime + (idx * 0.1));
                                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + (idx * 0.1) + 0.2);
                                osc.connect(gain);
                                gain.connect(ctx.destination);
                                osc.start(ctx.currentTime + (idx * 0.1));
                                osc.stop(ctx.currentTime + (idx * 0.1) + 0.2);
                            });
                        } else if (type === 'error') {
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.type = 'sawtooth';
                            osc.frequency.setValueAtTime(220, ctx.currentTime);
                            gain.gain.setValueAtTime(0.2, ctx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start();
                            osc.stop(ctx.currentTime + 0.25);
                        }
                    } catch (e) {
                        console.warn('Audio tone error:', e);
                    }
                },

                showToast(type, title, message) {
                    this.toast.type = type;
                    this.toast.title = title;
                    this.toast.message = message;
                    this.toast.visible = true;

                    clearTimeout(this.toastTimeout);
                    this.toastTimeout = setTimeout(() => {
                        this.toast.visible = false;
                    }, 4000);
                },

                updateClock() {
                    const now = new Date();
                    this.currentTime = now.toLocaleTimeString('id-ID', { hour12: false });
                    this.currentDate = now.toLocaleDateString('id-ID', { 
                        weekday: 'long', 
                        day: 'numeric', 
                        month: 'long', 
                        year: 'numeric' 
                    });
                },

                focusActiveScanner() {
                    if (document.activeElement && (document.activeElement.tagName === 'TEXTAREA' || (document.activeElement.tagName === 'INPUT' && !document.activeElement.classList.contains('pointer-events-none')))) {
                        return;
                    }

                    if (this.screen === 'standby') {
                        if (this.$refs.nisInput) this.$refs.nisInput.focus();
                    } else if (this.screen === 'scan_item') {
                        if (this.$refs.itemInput) this.$refs.itemInput.focus();
                    } else if (this.screen === 'return_screen') {
                        if (this.returnFlow.step === 1 && this.$refs.returnNisInput) {
                            this.$refs.returnNisInput.focus();
                        } else if (this.returnFlow.step === 2 && this.$refs.returnItemInput) {
                            this.$refs.returnItemInput.focus();
                        }
                    }
                },

                focusNisInput() {
                    this.focusActiveScanner();
                },

                keepFocus(event) {
                    if (event && event.relatedTarget && (event.relatedTarget.tagName === 'TEXTAREA' || (event.relatedTarget.tagName === 'INPUT' && !event.relatedTarget.classList.contains('pointer-events-none')))) {
                        return;
                    }
                    if (document.activeElement && (document.activeElement.tagName === 'TEXTAREA' || (document.activeElement.tagName === 'INPUT' && !document.activeElement.classList.contains('pointer-events-none')))) {
                        return;
                    }
                    setTimeout(() => this.focusActiveScanner(), 100);
                },

                async handleNisSubmit() {
                    const nis = this.scanInputs.nis.trim();
                    this.scanInputs.nis = '';
                    if (this.$refs.nisInput) {
                        this.$refs.nisInput.value = '';
                    }
                    if (!nis) return;

                    try {
                        const response = await fetch('/api/v1/auth/login', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ nis_nip: nis })
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            this.playAudioTone('error');
                            this.showToast('error', 'Identifikasi Gagal', result.message || 'Siswa dengan NIS tersebut tidak ditemukan.');
                            this.scanInputs.nis = '';
                            this.focusNisInput();
                            return;
                        }

                        this.playAudioTone('beep');
                        this.currentUser = {
                            id: result.data.user.id,
                            name: result.data.user.name,
                            nis_nip: result.data.user.nis_nip,
                            phone: result.data.user.phone || '',
                            token: result.data.token
                        };

                        this.screen = 'scan_item';
                        this.scanInputs.nis = '';
                        this.$nextTick(() => {
                            if (this.$refs.itemInput) {
                                this.$refs.itemInput.value = '';
                                this.$refs.itemInput.focus();
                            }
                        });

                    } catch (error) {
                        this.playAudioTone('error');
                        this.showToast('error', 'Koneksi Bermasalah', 'Gagal berkomunikasi dengan server Kiosk.');
                    }
                },

                setDuration(duration) {
                    this.selectedDuration = duration;
                    this.calculateEstimatedDue();
                },

                calculateEstimatedDue() {
                    const now = new Date();
                    let due = new Date(now);

                    if (this.selectedDuration === 'pulang_sekolah') {
                        due.setHours(16, 0, 0, 0);
                        if (now.getHours() >= 16) {
                            due.setDate(due.getDate() + 1);
                        }
                    } else if (this.selectedDuration === '1_hari') {
                        due.setDate(due.getDate() + 1);
                        due.setHours(23, 59, 59, 0);
                    } else if (this.selectedDuration === '2_hari') {
                        due.setDate(due.getDate() + 2);
                        due.setHours(23, 59, 59, 0);
                    } else if (this.selectedDuration === '3_hari') {
                        due.setDate(due.getDate() + 3);
                        due.setHours(23, 59, 59, 0);
                    } else if (this.selectedDuration === '1_minggu') {
                        due.setDate(due.getDate() + 7);
                        due.setHours(23, 59, 59, 0);
                    }

                    const options = { 
                        weekday: 'short', 
                        day: 'numeric', 
                        month: 'short', 
                        year: 'numeric', 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    };
                    this.estimatedDueText = due.toLocaleDateString('id-ID', options) + ' WIB';
                },

                async handleItemScan() {
                    const code = this.scanInputs.itemCode.trim();
                    this.scanInputs.itemCode = '';
                    if (this.$refs.itemInput) {
                        this.$refs.itemInput.value = '';
                    }
                    if (!code) return;

                    try {
                        const response = await fetch(`/api/v1/items/${encodeURIComponent(code)}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            this.playAudioTone('error');
                            this.showToast('error', 'Barang Tidak Ditemukan', 'Kode QR barang tidak terdaftar di database.');
                            this.scanInputs.itemCode = '';
                            return;
                        }

                        const item = result.data;
                        if (item.status !== 'tersedia') {
                            this.playAudioTone('error');
                            this.showToast('error', 'Barang Tidak Tersedia', `Barang "${item.name}" saat ini berstatus "${item.status}".`);
                            this.scanInputs.itemCode = '';
                            return;
                        }

                        const exists = this.selectedItems.some(i => i.id === item.id);
                        if (exists) {
                            this.playAudioTone('error');
                            this.showToast('info', 'Sudah Dipilih', `Alat "${item.name}" sudah ada di daftar peminjaman.`);
                            this.scanInputs.itemCode = '';
                            return;
                        }

                        item.quantity = item.quantity || 1;
                        this.playAudioTone('beep');
                        this.selectedItems.push(item);
                        this.scanInputs.itemCode = '';
                        this.showToast('success', 'Barang Ditambahkan', `"${item.name}" masuk ke daftar (${this.selectedItems.length} barang).`);

                    } catch (error) {
                        this.playAudioTone('error');
                        this.showToast('error', 'Error', 'Gagal memverifikasi status barang.');
                    }
                },

                selectItemDirectly(item) {
                    const exists = this.selectedItems.some(i => i.id === item.id);
                    if (exists) {
                        this.removeItem(item.id);
                        this.showToast('info', 'Barang Dihapus', `"${item.name}" dihapus dari daftar.`);
                        return;
                    }
                    item.quantity = item.quantity || 1;
                    this.selectedItems.push(item);
                    this.playAudioTone('beep');
                    this.showToast('success', 'Barang Ditambahkan', `"${item.name}" masuk ke daftar (${this.selectedItems.length} barang).`);
                },

                removeItem(itemId) {
                    this.selectedItems = this.selectedItems.filter(i => i.id !== itemId);
                },

                proceedToSnapshotScreen() {
                    if (this.selectedItems.length === 0) return;
                    this.screen = 'snapshot_confirm';
                    this.startWebcam();
                },

                async startWebcam() {
                    this.stopWebcam();
                    const seq = ++this.cameraProbeSeq;
                    this.cameraStatus = 'pending';
                    this.cameraStatusMessage = 'Menghubungkan kamera terpadu SILAMET...';

                    const isLocalKiosk = ['127.0.0.1', 'localhost', '::1'].includes(window.location.hostname);

                    if (isLocalKiosk) {
                        const daemonOk = await this.probeDaemonCamera();
                        if (seq !== this.cameraProbeSeq || this.screen !== 'snapshot_confirm') {
                            return;
                        }

                        if (daemonOk) {
                            this.cameraMode = 'daemon';
                            this.daemonStreamUrl = `${this.daemonBaseUrl}/stream.mjpg?t=${Date.now()}`;
                            this.cameraStatus = 'ready';
                            return;
                        }
                    }

                    await this.startWebRtcWebcam(seq);
                },

                async probeDaemonCamera() {
                    const controller = new AbortController();
                    const timer = setTimeout(() => controller.abort(), 800);

                    try {
                        const response = await fetch(`${this.daemonBaseUrl}/health`, {
                            signal: controller.signal,
                            cache: 'no-store'
                        });
                        if (!response.ok) return false;
                        const data = await response.json();
                        return Boolean(data && data.camera_ready);
                    } catch (err) {
                        return false;
                    } finally {
                        clearTimeout(timer);
                    }
                },

                onDaemonStreamLoad() {
                    if (this.cameraMode === 'daemon' && this.daemonStreamUrl !== '') {
                        this.cameraStatus = 'ready';
                    }
                },

                async onDaemonStreamError() {
                    if (this.cameraMode !== 'daemon' || this.daemonStreamUrl === '') {
                        return;
                    }
                    const seq = this.cameraProbeSeq;
                    this.daemonStreamUrl = '';
                    await this.startWebRtcWebcam(seq);
                },

                async startWebRtcWebcam(seq) {
                    if (seq !== this.cameraProbeSeq || this.screen !== 'snapshot_confirm') {
                        return;
                    }

                    this.cameraMode = 'webrtc';
                    this.cameraStatus = 'pending';
                    this.cameraStatusMessage = 'Mengakses kamera Kiosk (WebRTC)...';

                    try {
                        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                            throw new Error('Browser tidak mendukung akses kamera WebRTC.');
                        }

                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                width: { ideal: 640 },
                                height: { ideal: 480 },
                                facingMode: 'user'
                            },
                            audio: false
                        });

                        if (seq !== this.cameraProbeSeq || this.screen !== 'snapshot_confirm') {
                            stream.getTracks().forEach(track => track.stop());
                            return;
                        }

                        this.cameraStream = stream;
                        if (this.$refs.videoElement) {
                            this.$refs.videoElement.srcObject = stream;
                        }
                        this.cameraStatus = 'ready';
                    } catch (err) {
                        console.warn('Webcam error:', err);
                        this.cameraStatus = 'error';
                        this.cameraStatusMessage = 'Kamera tidak tersedia atau sedang dipakai proses lain.';
                    }
                },

                stopWebcam() {
                    this.cameraProbeSeq++;
                    this.daemonStreamUrl = '';
                    if (this.$refs.daemonVideo) {
                        this.$refs.daemonVideo.removeAttribute('src');
                    }
                    if (this.cameraStream) {
                        this.cameraStream.getTracks().forEach(track => track.stop());
                        this.cameraStream = null;
                    }
                    if (this.$refs.videoElement) {
                        this.$refs.videoElement.srcObject = null;
                    }
                    this.cameraMode = 'detecting';
                },

                async captureSnapshot() {
                    if (this.cameraMode === 'daemon' && this.cameraStatus === 'ready') {
                        const controller = new AbortController();
                        const timer = setTimeout(() => controller.abort(), 2000);
                        try {
                            const res = await fetch(`${this.daemonBaseUrl}/snapshot`, {
                                signal: controller.signal,
                                cache: 'no-store'
                            });
                            if (res.ok) {
                                const data = await res.json();
                                if (data && data.success && data.photo) {
                                    return data.photo;
                                }
                            }
                        } catch (err) {
                            console.warn('Daemon snapshot fallback error:', err);
                        } finally {
                            clearTimeout(timer);
                        }
                        return null;
                    }

                    if (!this.$refs.videoElement || this.cameraStatus !== 'ready') {
                        return null;
                    }
                    const video = this.$refs.videoElement;
                    const canvas = this.$refs.captureCanvas;
                    canvas.width = video.videoWidth || 640;
                    canvas.height = video.videoHeight || 480;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    return canvas.toDataURL('image/jpeg', 0.85);
                },

                async confirmLoan() {
                    if (this.isSubmitting || this.selectedItems.length === 0) return;
                    this.isSubmitting = true;

                    const photoBase64 = await this.captureSnapshot();
                    const itemIds = this.selectedItems.map(i => i.id);
                    const quantities = {};
                    this.selectedItems.forEach(i => {
                        quantities[i.id] = i.quantity || 1;
                    });

                    try {
                        const response = await fetch('/api/v1/loans', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${this.currentUser.token}`
                            },
                            body: JSON.stringify({
                                user_id: this.currentUser.id,
                                item_ids: itemIds,
                                quantities: quantities,
                                usage_duration: this.selectedDuration,
                                photo: photoBase64
                            })
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            this.isSubmitting = false;
                            this.playAudioTone('error');
                            this.showToast('error', 'Peminjaman Gagal', result.message || 'Terjadi kesalahan sistem.');
                            return;
                        }

                        this.playAudioTone('success');
                        this.stopWebcam();

                        this.successMessage = {
                            title: 'Peminjaman Berhasil!',
                            body: `${this.selectedItems.length} Peralatan (${result.data.item_name}) berhasil dipinjam atas nama ${this.currentUser.name}.`,
                            dueAt: this.estimatedDueText
                        };

                        this.screen = 'success';
                        this.selectedItems = [];
                        this.isSubmitting = false;
                        this.startSuccessCountdown();

                    } catch (error) {
                        this.isSubmitting = false;
                        this.playAudioTone('error');
                        this.showToast('error', 'Koneksi Gagal', 'Gagal memproses transaksi peminjaman.');
                    }
                },

                startSuccessCountdown() {
                    this.countdown = 3;
                    clearInterval(this.countdownInterval);
                    this.countdownInterval = setInterval(() => {
                        this.countdown--;
                        if (this.countdown <= 0) {
                            clearInterval(this.countdownInterval);
                            this.resetToStandby();
                        }
                    }, 1000);
                },

                goToReturnScreen() {
                    this.screen = 'return_screen';
                    this.returnFlow = {
                        step: 1,
                        nis: '',
                        user: null,
                        activeLoans: [],
                        selectedItems: [],
                        itemCode: '',
                        isLoading: false,
                        isSubmitting: false
                    };
                    this.$nextTick(() => {
                        if (this.$refs.returnNisInput) {
                            this.$refs.returnNisInput.value = '';
                            this.$refs.returnNisInput.focus();
                        }
                    });
                },

                async verifyReturnNis() {
                    const nis = this.returnFlow.nis.trim();
                    this.returnFlow.nis = '';
                    if (this.$refs.returnNisInput) {
                        this.$refs.returnNisInput.value = '';
                    }

                    if (!nis || this.returnFlow.isLoading) return;

                    this.returnFlow.isLoading = true;

                    try {
                        const response = await fetch(`/api/v1/loans/active-by-nis?nis=${encodeURIComponent(nis)}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const result = await response.json();

                        this.returnFlow.isLoading = false;

                        if (!response.ok || !result.success) {
                            this.playAudioTone('error');
                            this.showToast('error', 'Siswa Tidak Ditemukan', result.message || 'NIS kartu tidak terdaftar.');
                            this.returnFlow.nis = '';
                            this.$nextTick(() => {
                                if (this.$refs.returnNisInput) {
                                    this.$refs.returnNisInput.value = '';
                                    this.$refs.returnNisInput.focus();
                                }
                            });
                            return;
                        }

                        if (!result.data.active_loans || result.data.active_loans.length === 0) {
                            this.playAudioTone('error');
                            this.showToast('info', 'Tidak Ada Pinjaman Aktif', `${result.data.user.name} saat ini tidak memiliki tanggungan peminjaman barang.`);
                            this.returnFlow.nis = '';
                            this.$nextTick(() => {
                                if (this.$refs.returnNisInput) {
                                    this.$refs.returnNisInput.value = '';
                                    this.$refs.returnNisInput.focus();
                                }
                            });
                            return;
                        }

                        this.playAudioTone('beep');
                        this.returnFlow.user = result.data.user;
                        this.returnFlow.activeLoans = result.data.active_loans;
                        this.returnFlow.step = 2;
                        this.returnFlow.nis = '';
                        this.returnFlow.itemCode = '';

                        this.$nextTick(() => {
                            if (this.$refs.returnItemInput) {
                                this.$refs.returnItemInput.value = '';
                                this.$refs.returnItemInput.focus();
                            }
                        });

                    } catch (error) {
                        this.returnFlow.isLoading = false;
                        this.returnFlow.nis = '';
                        this.playAudioTone('error');
                        this.showToast('error', 'Error Sistem', 'Gagal memverifikasi NIS siswa.');
                        this.$nextTick(() => {
                            if (this.$refs.returnNisInput) {
                                this.$refs.returnNisInput.value = '';
                                this.$refs.returnNisInput.focus();
                            }
                        });
                    }
                },

                resetReturnNis() {
                    this.returnFlow.step = 1;
                    this.returnFlow.nis = '';
                    this.returnFlow.user = null;
                    this.returnFlow.activeLoans = [];
                    this.returnFlow.selectedItems = [];
                    this.returnFlow.itemCode = '';
                    this.returnFlow.isLoading = false;
                    this.returnFlow.isSubmitting = false;
                    this.$nextTick(() => {
                        if (this.$refs.returnNisInput) {
                            this.$refs.returnNisInput.value = '';
                            this.$refs.returnNisInput.focus();
                        }
                    });
                },

                isItemInReturnList(itemCode) {
                    return this.returnFlow.selectedItems.some(i => i.item_code === itemCode);
                },

                toggleItemInReturnList(loan) {
                    if (this.isItemInReturnList(loan.item_code)) {
                        this.removeItemFromReturnList(loan.item_code);
                    } else {
                        this.returnFlow.selectedItems.push({
                            loan_id: loan.loan_id,
                            item_code: loan.item_code,
                            item_name: loan.item_name,
                            condition_in: 'bagus',
                            issue_description: ''
                        });
                        this.playAudioTone('beep');
                    }
                },

                removeItemFromReturnList(itemCode) {
                    this.returnFlow.selectedItems = this.returnFlow.selectedItems.filter(i => i.item_code !== itemCode);
                },

                async addScannedReturnItem() {
                    const rawInput = this.returnFlow.itemCode.trim();
                    this.returnFlow.itemCode = '';
                    if (this.$refs.returnItemInput) {
                        this.$refs.returnItemInput.value = '';
                    }

                    if (!rawInput) return;

                    const matchedLoan = this.returnFlow.activeLoans.find(loan => 
                        (loan.barcode_token && loan.barcode_token.toLowerCase() === rawInput.toLowerCase()) ||
                        loan.item_code.toLowerCase() === rawInput.toLowerCase() || 
                        String(loan.item_id) === rawInput
                    );

                    if (!matchedLoan) {
                        this.playAudioTone('error');
                        this.showToast('error', 'Barang Tidak Sesuai', `Barang '${rawInput}' tidak terdaftar sebagai pinjaman aktif milik ${this.returnFlow.user.name}. Hanya peminjam yang boleh mengembalikan.`);
                        this.returnFlow.itemCode = '';
                        this.$nextTick(() => {
                            if (this.$refs.returnItemInput) {
                                this.$refs.returnItemInput.value = '';
                                this.$refs.returnItemInput.focus();
                            }
                        });
                        return;
                    }

                    if (this.isItemInReturnList(matchedLoan.item_code)) {
                        this.playAudioTone('error');
                        this.showToast('info', 'Sudah Ditambahkan', `Barang '${matchedLoan.item_name}' sudah ada di daftar pengembalian.`);
                        this.returnFlow.itemCode = '';
                        this.$nextTick(() => {
                            if (this.$refs.returnItemInput) {
                                this.$refs.returnItemInput.value = '';
                                this.$refs.returnItemInput.focus();
                            }
                        });
                        return;
                    }

                    this.returnFlow.selectedItems.push({
                        loan_id: matchedLoan.loan_id,
                        item_code: matchedLoan.item_code,
                        item_name: matchedLoan.item_name,
                        condition_in: 'bagus',
                        issue_description: ''
                    });

                    this.playAudioTone('beep');
                    this.showToast('success', 'Barang Ditambahkan', `${matchedLoan.item_name} berhasil dimasukkan ke daftar pengembalian.`);
                    this.returnFlow.itemCode = '';

                    this.$nextTick(() => {
                        if (this.$refs.returnItemInput) {
                            this.$refs.returnItemInput.value = '';
                            this.$refs.returnItemInput.focus();
                        }
                    });
                },

                async submitReturn() {
                    if (this.returnFlow.selectedItems.length === 0 || this.returnFlow.isSubmitting) return;

                    const invalidDamagedItem = this.returnFlow.selectedItems.find(
                        item => item.condition_in === 'rusak' && (!item.issue_description || item.issue_description.trim().length < 3)
                    );

                    if (invalidDamagedItem) {
                        this.playAudioTone('error');
                        this.showToast(
                            'error', 
                            'Catatan Kerusakan Wajib Diisi', 
                            `Silakan isi catatan kerusakan untuk alat "${invalidDamagedItem.item_name}" minimal 3 karakter.`
                        );
                        return;
                    }

                    this.returnFlow.isSubmitting = true;

                    try {
                        const payload = {
                            nis: this.returnFlow.user.nis_nip,
                            items: this.returnFlow.selectedItems.map(item => ({
                                item_code: item.item_code,
                                condition_in: item.condition_in,
                                issue_description: item.condition_in === 'rusak' ? item.issue_description : null
                            }))
                        };

                        const response = await fetch('/api/v1/loans/return-kiosk', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            this.returnFlow.isSubmitting = false;
                            this.playAudioTone('error');
                            this.showToast('error', 'Pengembalian Gagal', result.message || 'Terjadi kesalahan sistem.');
                            return;
                        }

                        this.playAudioTone('success');
                        this.successMessage = {
                            title: 'Pengembalian Berhasil!',
                            body: result.message,
                            dueAt: `${result.data.total_returned} barang telah dikembalikan ke laboratorium`
                        };

                        this.screen = 'success';
                        this.returnFlow.isSubmitting = false;
                        this.startSuccessCountdown();

                    } catch (error) {
                        this.returnFlow.isSubmitting = false;
                        this.playAudioTone('error');
                        this.showToast('error', 'Error', 'Gagal memproses pengembalian barang.');
                    }
                },

                resetToStandby() {
                    this.stopWebcam();
                    clearInterval(this.countdownInterval);
                    this.screen = 'standby';
                    this.scanInputs.nis = '';
                    this.scanInputs.itemCode = '';
                    this.returnFlow.nis = '';
                    this.returnFlow.itemCode = '';
                    if (this.$refs.nisInput) this.$refs.nisInput.value = '';
                    if (this.$refs.itemInput) this.$refs.itemInput.value = '';
                    if (this.$refs.returnNisInput) this.$refs.returnNisInput.value = '';
                    if (this.$refs.returnItemInput) this.$refs.returnItemInput.value = '';
                    this.currentUser = { id: null, name: '', nis_nip: '', phone: '', token: '' };
                    this.selectedItems = [];
                    this.selectedDuration = 'pulang_sekolah';
                    this.calculateEstimatedDue();
                    this.isSubmitting = false;
                    this.$nextTick(() => {
                        this.focusNisInput();
                    });
                }
            };
        }
    </script>
</body>
</html>
