<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-neutral-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Kiosk Layanan Mandiri — SILAMET Lab SIJA</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        
        /* Pulse ring animation untuk target scanner */
        @keyframes scanPulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(94, 114, 228, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 24px rgba(94, 114, 228, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(94, 114, 228, 0); }
        }
        .scanner-beacon {
            animation: scanPulse 2s infinite cubic-bezier(0.4, 0, 0.6, 1);
        }

        /* Scanline animation */
        @keyframes scanLine {
            0% { top: 5%; opacity: 0; }
            50% { opacity: 1; }
            100% { top: 95%; opacity: 0; }
        }
        .scan-laser {
            position: absolute;
            left: 5%;
            right: 5%;
            height: 3px;
            background: linear-gradient(90deg, transparent, #FB6340, #5E72E4, transparent);
            box-shadow: 0 0 15px #5E72E4;
            animation: scanLine 2.2s infinite ease-in-out;
        }

        #camera-reader video {
            border-radius: 1rem;
            object-fit: cover;
            width: 100% !important;
        }
    </style>
</head>
<body class="h-full font-sans antialiased text-neutral-800 select-none overflow-x-hidden" x-data="kioskApp()" x-init="initKiosk()" x-cloak>

    <!-- ========================================================================= -->
    <!-- HEADER BAR KIOSK (Brand & Jam Realtime) -->
    <!-- ========================================================================= -->
    <header class="bg-primary-dark text-white shadow-md border-b border-white/10 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center font-black text-xl tracking-wider shadow-inner">
                S
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold tracking-tight">SILAMET</h1>
                    <span class="bg-primary/30 border border-primary text-xs px-2 py-0.5 rounded-full font-medium tracking-wide">
                        KIOSK MANDIRI
                    </span>
                </div>
                <p class="text-xs text-neutral-400">Laboratorium SIJA — SMK Negeri 1 Cibinong</p>
            </div>
        </div>

        <div class="flex items-center gap-6">
            <!-- Clock -->
            <div class="text-right">
                <div class="text-lg font-bold font-mono tracking-wider" x-text="currentTime">--:--:--</div>
                <div class="text-xs text-neutral-400" x-text="currentDate">Memuat tanggal...</div>
            </div>

            <!-- Standby / Reset button jika sedang dalam transaksi -->
            <template x-if="screen !== 'standby'">
                <button 
                    @click="resetToStandby()"
                    type="button" 
                    class="bg-neutral-800 hover:bg-neutral-700 text-white border border-neutral-600 px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 transition duration-150"
                >
                    <svg class="w-4 h-4 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Batal / Selesai
                </button>
            </template>

            <!-- Tautan login web admin -->
            <template x-if="screen === 'standby'">
                <a 
                    href="{{ route('login') }}" 
                    class="text-xs text-neutral-400 hover:text-white px-3 py-1.5 rounded-lg border border-neutral-700 hover:border-neutral-500 transition duration-150"
                >
                    Login Petugas &rarr;
                </a>
            </template>
        </div>
    </header>

    <!-- Global Toast Alert (Suara & Peringatan) -->
    <div 
        x-show="toast.visible" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed top-24 left-1/2 -translate-x-1/2 z-50 max-w-xl w-full px-4"
    >
        <div 
            :class="{
                'bg-success-dark text-white border-success': toast.type === 'success',
                'bg-danger-dark text-white border-danger': toast.type === 'error',
                'bg-primary-dark text-white border-primary': toast.type === 'info'
            }"
            class="p-4 rounded-2xl shadow-2xl border flex items-center gap-4"
        >
            <div class="text-2xl" x-text="toast.type === 'success' ? '✅' : (toast.type === 'error' ? '⚠️' : 'ℹ️')"></div>
            <div class="flex-1">
                <div class="font-bold text-sm tracking-wide uppercase" x-text="toast.title"></div>
                <div class="text-sm opacity-90" x-text="toast.message"></div>
            </div>
            <button @click="toast.visible = false" class="text-white/60 hover:text-white text-lg font-bold">&times;</button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL FALLBACK SCAN KAMERA (Untuk Scan Barcode Kartu/Barang via Kamera) -->
    <!-- ========================================================================= -->
    <div 
        x-show="cameraScanner.active"
        class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm"
        x-transition
    >
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-neutral-200 flex flex-col items-center">
            <div class="w-full flex items-center justify-between mb-4 border-b border-neutral-200 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">📷</span>
                    <div>
                        <h3 class="font-bold text-lg text-neutral-900" x-text="cameraScanner.title">Scan via Kamera</h3>
                        <p class="text-xs text-neutral-500">Posisikan barcode/QR code di dalam bingkai kotak</p>
                    </div>
                </div>
                <button @click="stopCameraScanner()" class="text-neutral-400 hover:text-neutral-800 text-2xl font-bold">&times;</button>
            </div>

            <!-- Kamera Feed Container -->
            <div class="relative w-full aspect-square bg-black rounded-2xl overflow-hidden mb-4 border-2 border-primary shadow-inner">
                <div id="camera-reader" class="w-full h-full"></div>
                <div class="absolute inset-8 border-2 border-dashed border-white/70 rounded-xl pointer-events-none flex items-center justify-center">
                    <span class="bg-black/60 text-white text-xs font-semibold px-3 py-1 rounded-full tracking-wider">
                        BIDIK KODE DISINI
                    </span>
                </div>
            </div>

            <p class="text-xs text-center text-neutral-600 mb-4">
                Mendukung pembacaan Barcode NIS Kartu Pelajar (Code 128 / Code 39) dan QR Code barang.
            </p>

            <button 
                @click="stopCameraScanner()" 
                type="button" 
                class="w-full py-3 bg-neutral-200 hover:bg-neutral-300 text-neutral-800 font-bold rounded-xl transition text-sm"
            >
                Tutup Kamera & Kembali ke Scanner USB
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MAIN CONTAINER -->
    <!-- ========================================================================= -->
    <main class="max-w-6xl mx-auto p-6 md:p-8 flex flex-col justify-center min-h-[calc(100vh-80px)]">

        <!-- ===================================================================== -->
        <!-- LAYAR 1: STANDBY / IDLE SCREEN -->
        <!-- ===================================================================== -->
        <section x-show="screen === 'standby'" class="flex flex-col items-center justify-center text-center py-6">
            
            <div class="relative w-64 h-64 mb-8 flex items-center justify-center">
                <!-- Outer Pulse Glow -->
                <div class="absolute inset-0 rounded-3xl bg-primary/10 scanner-beacon border-2 border-primary/40"></div>
                
                <!-- Target Box Scanner -->
                <div class="relative w-56 h-56 rounded-2xl bg-white border-2 border-dashed border-primary shadow-xl flex flex-col items-center justify-center p-6 overflow-hidden">
                    <div class="scan-laser"></div>
                    <svg class="w-24 h-24 text-primary mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-wider text-primary">SCANNER USB SIAGA</span>
                </div>
            </div>

            <h2 class="text-3xl md:text-4xl font-extrabold text-neutral-900 tracking-tight mb-3">
                Silakan Tempel / Scan Barcode Kartu Pelajar
            </h2>
            <p class="text-lg text-neutral-600 max-w-xl mb-6">
                Arahkan barcode <span class="font-semibold text-primary">NIS Kartu Siswa</span> ke pemindai barcode untuk memulai peminjaman mandiri peralatan praktikum.
            </p>

            <!-- Input Barcode Listener (Autofocus Terus-menerus) -->
            <form @submit.prevent="handleNisSubmit()" class="w-full max-w-md mb-4">
                <div class="relative">
                    <input 
                        x-ref="nisInput"
                        x-model="scanInputs.nis"
                        type="text" 
                        autocomplete="off"
                        autofocus
                        placeholder="Menunggu pembacaan barcode NIS..."
                        class="w-full bg-white text-center text-xl font-bold tracking-widest text-primary-dark placeholder-neutral-400 border-2 border-primary/50 focus:border-primary focus:ring-4 focus:ring-primary/20 rounded-2xl py-4 px-6 shadow-lg transition"
                        @blur="keepFocus($event)"
                    />
                    <div class="absolute right-4 top-1/2 -translate-y-1/2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success/20 text-success-dark">
                            USB Scanner
                        </span>
                    </div>
                </div>
            </form>

            <!-- FALLBACK BUTTON: SCAN VIA KAMERA -->
            <div class="mb-8 flex flex-col items-center">
                <button 
                    @click="startCameraScanner('nis')"
                    type="button" 
                    class="inline-flex items-center gap-2 text-sm font-bold text-primary hover:text-primary-dark bg-primary/10 hover:bg-primary/20 border border-primary/30 px-5 py-2.5 rounded-xl transition duration-150 shadow-sm"
                >
                    <span>📷</span>
                    <span>Scanner USB bermasalah? Pindai Barcode via Kamera Webcam</span>
                </button>
                <p class="text-xs text-neutral-500 mt-2">
                    *Gunakan opsi kamera bila meja Kiosk tidak tersambung barcode scanner fisik.
                </p>
            </div>

            <!-- Alternatif Cepat: Tombol Pengembalian Mandiri -->
            <div class="pt-6 border-t border-neutral-300 w-full max-w-lg flex flex-col sm:flex-row items-center justify-center gap-4">
                <button 
                    @click="goToReturnScreen()"
                    type="button" 
                    class="w-full sm:w-auto bg-white hover:bg-neutral-50 text-neutral-800 border-2 border-neutral-300 hover:border-primary font-bold px-8 py-3.5 rounded-2xl shadow-md hover:shadow-lg flex items-center justify-center gap-3 transition duration-150 text-base"
                >
                    <span class="text-2xl">🔄</span>
                    <span>Pengembalian Barang Mandiri</span>
                </button>
            </div>
        </section>

        <!-- ===================================================================== -->
        <!-- LAYAR 2: SCAN BARANG & PILIHAN DURASI PEMAKAIAN -->
        <!-- ===================================================================== -->
        <section x-show="screen === 'scan_item'" class="py-4">
            
            <!-- User Header Card -->
            <div class="bg-white rounded-3xl p-6 shadow-md border border-neutral-200 mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-bold text-2xl border border-primary/30">
                        <span x-text="currentUser.name ? currentUser.name.charAt(0).toUpperCase() : '?'"></span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-neutral-900" x-text="currentUser.name">Nama Siswa</h2>
                            <span class="bg-neutral-100 text-neutral-700 text-xs px-2.5 py-1 rounded-full font-semibold border border-neutral-200" x-text="'NIS: ' + currentUser.nis_nip"></span>
                        </div>
                        <div class="text-sm mt-0.5 flex items-center gap-2">
                            <span class="text-neutral-500">WhatsApp:</span>
                            <template x-if="currentUser.phone">
                                <span class="font-semibold text-success-dark flex items-center gap-1">
                                    <span class="text-xs">📱</span> <span x-text="currentUser.phone"></span> (Terverifikasi)
                                </span>
                            </template>
                            <template x-if="!currentUser.phone">
                                <span class="font-bold text-danger flex items-center gap-1">
                                    <span>⚠️</span> Belum Terdaftar (Wajib Lapor Admin)
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button 
                        @click="resetToStandby()" 
                        class="text-neutral-500 hover:text-neutral-800 text-sm font-semibold px-4 py-2 rounded-xl hover:bg-neutral-100 transition"
                    >
                        Batal
                    </button>
                </div>
            </div>

            <!-- Peringatan Jika Nomor Telepon Kosong -->
            <template x-if="!currentUser.phone">
                <div class="bg-danger/15 border-2 border-danger text-danger-dark p-6 rounded-3xl mb-6 flex items-start gap-4">
                    <div class="text-3xl">🚫</div>
                    <div>
                        <h3 class="font-bold text-lg mb-1">Nomor WhatsApp Siswa Belum Terdaftar!</h3>
                        <p class="text-sm">
                            Sesuai aturan keamanan laboratorium (PRD Modul 2 & Logic §1.A), peminjaman hanya dapat diproses bila nomor WhatsApp aktif terdaftar sebagai media notifikasi batas pengembalian (*overdue reminder*).
                        </p>
                        <p class="text-sm font-bold mt-2">
                            Silakan temui Teknisi atau Kepala Bengkel Lab SIJA untuk memperbarui nomor telepon Anda.
                        </p>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6" x-show="currentUser.phone">
                
                <!-- Kolom Kiri: Input Scan QR Barang & Barang Terpilih -->
                <div class="lg:col-span-6 flex flex-col gap-6">
                    
                    <!-- Scan Item Box -->
                    <div class="bg-white rounded-3xl p-6 shadow-md border border-neutral-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-neutral-900 flex items-center gap-2">
                                <span>📦</span> Pindai QR / Barcode Alat
                            </h3>
                            <button 
                                @click="startCameraScanner('item')" 
                                type="button"
                                class="text-xs bg-primary/10 hover:bg-primary/20 text-primary font-bold px-3 py-1.5 rounded-xl border border-primary/30 flex items-center gap-1 transition"
                            >
                                <span>📷</span>
                                <span>Scan via Kamera</span>
                            </button>
                        </div>

                        <form @submit.prevent="handleItemScan()">
                            <input 
                                x-ref="itemInput"
                                x-model="scanInputs.itemCode"
                                type="text"
                                autofocus
                                placeholder="Arahkan scanner ke QR Code barang..."
                                class="w-full bg-neutral-50 text-neutral-900 font-semibold border-2 border-dashed border-primary/60 focus:border-primary focus:bg-white focus:ring-4 focus:ring-primary/20 rounded-2xl py-4 px-4 text-center tracking-wider text-base shadow-inner transition"
                            />
                        </form>
                        <p class="text-xs text-neutral-500 mt-2 text-center">
                            Scanner USB siap membaca kode barang. Atau gunakan tombol <strong>Scan via Kamera</strong> di atas.
                        </p>

                        <!-- Preview Barang Terpilih -->
                        <template x-if="selectedItem">
                            <div class="mt-4 p-4 rounded-2xl bg-success/10 border-2 border-success flex items-start justify-between">
                                <div>
                                    <div class="text-xs font-bold uppercase tracking-wider text-success-dark">Barang Terpilih:</div>
                                    <div class="text-lg font-extrabold text-neutral-900" x-text="selectedItem.name"></div>
                                    <div class="text-xs text-neutral-600 mt-0.5">
                                        Kode: <span class="font-mono font-bold" x-text="selectedItem.item_code"></span> | Lokasi: <span class="font-semibold" x-text="selectedItem.location"></span>
                                    </div>
                                    <div class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-0.5 rounded-full bg-success text-white">
                                        <span>Status:</span> <span x-text="selectedItem.status"></span>
                                    </div>
                                </div>
                                <button @click="selectedItem = null" class="text-neutral-400 hover:text-danger text-sm font-bold p-1">
                                    Ganti &times;
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Quick Pick List (Barang Tersedia) -->
                    <div class="bg-white rounded-3xl p-6 shadow-md border border-neutral-200 flex-1">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-neutral-700 uppercase tracking-wider">Katalog Barang Tersedia</h4>
                            <span class="text-xs text-neutral-500">Klik untuk pilih langsung</span>
                        </div>
                        <div class="max-h-56 overflow-y-auto space-y-2 pr-1">
                            <template x-for="item in availableItems" :key="item.id">
                                <div 
                                    @click="selectItemDirectly(item)"
                                    class="p-3 rounded-xl border border-neutral-200 hover:border-primary hover:bg-primary/5 cursor-pointer flex items-center justify-between transition"
                                >
                                    <div>
                                        <div class="font-bold text-sm text-neutral-900" x-text="item.name"></div>
                                        <div class="text-xs text-neutral-500 font-mono" x-text="item.item_code + ' • ' + item.location"></div>
                                    </div>
                                    <span class="text-xs font-bold text-primary px-2 py-1 rounded-lg bg-primary/10">Pilih</span>
                                </div>
                            </template>
                            <template x-if="availableItems.length === 0">
                                <div class="text-center py-6 text-sm text-neutral-400">
                                    Tidak ada peralatan berstatus 'tersedia'.
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Pilihan Durasi Pemakaian (Touch Friendly Cards) -->
                <div class="lg:col-span-6 flex flex-col gap-6">
                    <div class="bg-white rounded-3xl p-6 shadow-md border border-neutral-200 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-lg font-bold text-neutral-900 flex items-center gap-2">
                                    <span>⏱️</span> Pilih Durasi Pemakaian
                                </h3>
                                <span class="text-xs text-neutral-500">*Sentuh salah satu</span>
                            </div>
                            <p class="text-xs text-neutral-600 mb-4">
                                Batas waktu pengembalian (*due date*) akan dihitung secara otomatis dan dipantau sistem.
                            </p>

                            <!-- Duration Grid (Cards) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                                
                                <!-- Card 1: Pulang Sekolah -->
                                <button 
                                    type="button"
                                    @click="setDuration('pulang_sekolah')"
                                    :class="selectedDuration === 'pulang_sekolah' ? 'border-primary ring-4 ring-primary/20 bg-primary/5' : 'border-neutral-200 hover:border-primary/50 bg-white'"
                                    class="p-4 rounded-2xl border-2 text-left transition duration-150 flex flex-col justify-between h-24"
                                >
                                    <div class="flex items-center justify-between w-full">
                                        <span class="font-bold text-base text-neutral-900">Pulang Sekolah</span>
                                        <span class="text-xl" x-show="selectedDuration === 'pulang_sekolah'">🔘</span>
                                    </div>
                                    <span class="text-xs text-neutral-500">Hari ini pukul 16:00 WIB</span>
                                </button>

                                <!-- Card 2: 1 Hari -->
                                <button 
                                    type="button"
                                    @click="setDuration('1_hari')"
                                    :class="selectedDuration === '1_hari' ? 'border-primary ring-4 ring-primary/20 bg-primary/5' : 'border-neutral-200 hover:border-primary/50 bg-white'"
                                    class="p-4 rounded-2xl border-2 text-left transition duration-150 flex flex-col justify-between h-24"
                                >
                                    <div class="flex items-center justify-between w-full">
                                        <span class="font-bold text-base text-neutral-900">1 Hari</span>
                                        <span class="text-xl" x-show="selectedDuration === '1_hari'">🔘</span>
                                    </div>
                                    <span class="text-xs text-neutral-500">Besok malam (23:59 WIB)</span>
                                </button>

                                <!-- Card 2: 2 Hari -->
                                <button 
                                    type="button"
                                    @click="setDuration('2_hari')"
                                    :class="selectedDuration === '2_hari' ? 'border-primary ring-4 ring-primary/20 bg-primary/5' : 'border-neutral-200 hover:border-primary/50 bg-white'"
                                    class="p-4 rounded-2xl border-2 text-left transition duration-150 flex flex-col justify-between h-24"
                                >
                                    <div class="flex items-center justify-between w-full">
                                        <span class="font-bold text-base text-neutral-900">2 Hari</span>
                                        <span class="text-xl" x-show="selectedDuration === '2_hari'">🔘</span>
                                    </div>
                                    <span class="text-xs text-neutral-500">2 Hari kemudian (23:59 WIB)</span>
                                </button>

                                <!-- Card 4: 3 Hari -->
                                <button 
                                    type="button"
                                    @click="setDuration('3_hari')"
                                    :class="selectedDuration === '3_hari' ? 'border-primary ring-4 ring-primary/20 bg-primary/5' : 'border-neutral-200 hover:border-primary/50 bg-white'"
                                    class="p-4 rounded-2xl border-2 text-left transition duration-150 flex flex-col justify-between h-24"
                                >
                                    <div class="flex items-center justify-between w-full">
                                        <span class="font-bold text-base text-neutral-900">3 Hari</span>
                                        <span class="text-xl" x-show="selectedDuration === '3_hari'">🔘</span>
                                    </div>
                                    <span class="text-xs text-neutral-500">3 Hari praktikum (23:59 WIB)</span>
                                </button>

                                <!-- Card 5: 1 Minggu (Span 2) -->
                                <button 
                                    type="button"
                                    @click="setDuration('1_minggu')"
                                    :class="selectedDuration === '1_minggu' ? 'border-primary ring-4 ring-primary/20 bg-primary/5' : 'border-neutral-200 hover:border-primary/50 bg-white'"
                                    class="sm:col-span-2 p-4 rounded-2xl border-2 text-left transition duration-150 flex flex-col justify-between h-24"
                                >
                                    <div class="flex items-center justify-between w-full">
                                        <div>
                                            <span class="font-bold text-base text-neutral-900">1 Minggu</span>
                                            <span class="ml-2 text-xs bg-neutral-200 text-neutral-700 px-2 py-0.5 rounded-full font-semibold">Proyek Khusus</span>
                                        </div>
                                        <span class="text-xl" x-show="selectedDuration === '1_minggu'">🔘</span>
                                    </div>
                                    <span class="text-xs text-neutral-500">7 Hari kalender (23:59 WIB)</span>
                                </button>
                            </div>

                            <!-- Perkiraan Estimasi Due At Box -->
                            <div class="bg-neutral-50 rounded-2xl p-4 border border-neutral-200 mb-6">
                                <div class="text-xs font-bold uppercase text-neutral-500 tracking-wider">Estimasi Batas Pengembalian:</div>
                                <div class="text-base font-extrabold text-primary-dark mt-1 flex items-center gap-2">
                                    <span>📅</span>
                                    <span x-text="estimatedDueText">Pilih durasi pemakaian...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Next Button -->
                        <div>
                            <button 
                                @click="proceedToSnapshotScreen()"
                                :disabled="!selectedItem"
                                type="button"
                                class="w-full py-4 rounded-2xl font-bold text-lg text-white shadow-xl transition flex items-center justify-center gap-3"
                                :class="selectedItem ? 'bg-primary hover:bg-primary/90 cursor-pointer shadow-primary/30' : 'bg-neutral-300 cursor-not-allowed text-neutral-500'"
                            >
                                <span>Lanjutkan ke Foto Wajah</span>
                                <span>&rarr;</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- ===================================================================== -->
        <!-- LAYAR 3: SNAPSHOT KAMERA & KONFIRMASI PEMINJAMAN -->
        <!-- ===================================================================== -->
        <section x-show="screen === 'snapshot_confirm'" class="py-4">
            
            <div class="max-w-4xl mx-auto bg-white rounded-3xl p-6 md:p-8 shadow-2xl border border-neutral-200">
                <div class="text-center mb-6">
                    <span class="bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-primary/20">
                        Langkah Terakhir Konfirmasi
                    </span>
                    <h2 class="text-2xl md:text-3xl font-extrabold text-neutral-900 mt-2">
                        Pratinjau Foto Wajah Siswa
                    </h2>
                    <p class="text-sm text-neutral-600">
                        Posisikan wajah Anda tegak lurus di depan kamera Kiosk untuk dokumentasi keamanan laboratorium.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                    
                    <!-- WebRTC Camera Preview -->
                    <div class="md:col-span-7 flex flex-col items-center">
                        <div class="relative w-full aspect-video rounded-3xl overflow-hidden bg-black border-4 border-primary shadow-inner flex items-center justify-center">
                            
                            <!-- Video Element -->
                            <video 
                                x-ref="videoElement" 
                                autoplay 
                                playsinline 
                                muted 
                                class="w-full h-full object-cover"
                            ></video>

                            <!-- Hidden Canvas for Capture -->
                            <canvas x-ref="captureCanvas" class="hidden"></canvas>

                            <!-- Face Target Overlay Frame -->
                            <div class="absolute inset-8 border-2 border-dashed border-white/60 rounded-2xl pointer-events-none flex items-center justify-center">
                                <span class="text-xs font-bold tracking-widest text-white/80 bg-black/40 px-3 py-1 rounded-full">
                                    POSISI WAJAH
                                </span>
                            </div>

                            <!-- Loading/Error Indicator jika webcam belum izin -->
                            <div x-show="cameraStatus !== 'ready'" class="absolute inset-0 bg-neutral-900 text-white flex flex-col items-center justify-center p-6 text-center">
                                <span class="text-4xl mb-2">📷</span>
                                <span class="text-sm font-semibold" x-text="cameraStatusMessage">Menghubungkan webcam...</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 mt-3 text-xs text-neutral-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-success"></span>
                            <span>Kamera siaga aktif (WebRTC Camera Kiosk)</span>
                        </div>
                    </div>

                    <!-- Ringkasan Transaksi & Tombol Konfirmasi -->
                    <div class="md:col-span-5 flex flex-col justify-between h-full space-y-6">
                        
                        <div class="bg-neutral-50 rounded-2xl p-5 border border-neutral-200 space-y-3">
                            <div class="text-xs font-bold uppercase tracking-wider text-neutral-500">Ringkasan Peminjaman:</div>
                            
                            <div class="border-b border-neutral-200 pb-2">
                                <div class="text-xs text-neutral-500">Peminjam</div>
                                <div class="font-bold text-neutral-900 text-sm" x-text="currentUser.name"></div>
                                <div class="text-xs text-neutral-600 font-mono" x-text="'NIS: ' + currentUser.nis_nip"></div>
                            </div>

                            <div class="border-b border-neutral-200 pb-2">
                                <div class="text-xs text-neutral-500">Barang</div>
                                <div class="font-bold text-neutral-900 text-sm" x-text="selectedItem ? selectedItem.name : '-'"></div>
                                <div class="text-xs text-neutral-600 font-mono" x-text="selectedItem ? selectedItem.item_code : '-'"></div>
                            </div>

                            <div>
                                <div class="text-xs text-neutral-500">Batas Waktu Pengembalian (*Due Date*)</div>
                                <div class="font-bold text-danger text-sm" x-text="estimatedDueText"></div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <!-- Big Green Button Confirm -->
                            <button 
                                @click="confirmLoan()"
                                :disabled="isSubmitting"
                                type="button" 
                                class="w-full bg-success hover:bg-success-dark text-white font-extrabold text-xl py-5 px-6 rounded-2xl shadow-xl shadow-success/30 hover:shadow-2xl transition duration-150 flex items-center justify-center gap-3 cursor-pointer disabled:opacity-50"
                            >
                                <template x-if="!isSubmitting">
                                    <div class="flex items-center gap-2">
                                        <span>📸</span>
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
                                @click="screen = 'scan_item'" 
                                type="button" 
                                class="w-full text-neutral-600 hover:text-neutral-900 text-sm font-semibold py-2"
                            >
                                &larr; Kembali Ubah Data
                            </button>
                        </div>

                    </div>

                </div>
            </div>

        </section>

        <!-- ===================================================================== -->
        <!-- LAYAR SUKSES TRANSAKSI (3 Detik Auto-Reset ke Standby) -->
        <!-- ===================================================================== -->
        <section x-show="screen === 'success'" class="flex flex-col items-center justify-center text-center py-12">
            <div class="w-28 h-28 rounded-full bg-success/20 border-4 border-success text-success flex items-center justify-center text-6xl mb-6 shadow-2xl animate-bounce">
                ✓
            </div>
            <h2 class="text-3xl md:text-4xl font-extrabold text-neutral-900 mb-2" x-text="successMessage.title">
                Peminjaman Berhasil!
            </h2>
            <p class="text-lg text-neutral-600 max-w-lg mb-4" x-text="successMessage.body">
                Barang siap digunakan untuk kegiatan praktikum laboratorium.
            </p>
            <div class="bg-white border-2 border-dashed border-primary/40 rounded-2xl px-6 py-4 mb-8 shadow-sm">
                <div class="text-xs uppercase font-bold text-neutral-500">Batas Pengembalian:</div>
                <div class="text-xl font-mono font-bold text-primary-dark mt-1" x-text="successMessage.dueAt"></div>
            </div>
            <div class="flex items-center gap-2 text-sm text-neutral-500">
                <span>Layar akan otomatis kembali ke menu awal dalam</span>
                <span class="font-bold text-primary" x-text="countdown">3</span>
                <span>detik...</span>
            </div>
        </section>

        <!-- ===================================================================== -->
        <!-- LAYAR 4: PENGEMBALIAN MANDIRI & PELAPORAN KERUSAKAN -->
        <!-- ===================================================================== -->
        <section x-show="screen === 'return_screen'" class="py-4">
            <div class="max-w-2xl mx-auto bg-white rounded-3xl p-6 md:p-8 shadow-xl border border-neutral-200">
                
                <div class="flex items-center justify-between mb-6 border-b border-neutral-200 pb-4">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-primary">Layanan Mandiri</span>
                        <h2 class="text-2xl font-extrabold text-neutral-900">Pengembalian Barang</h2>
                    </div>
                    <button @click="resetToStandby()" class="text-sm font-semibold text-neutral-500 hover:text-neutral-800">
                        &larr; Beranda
                    </button>
                </div>

                <!-- Step A: Scan Barang -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-bold text-neutral-700">Pindai QR / Barcode Barang:</label>
                        <button 
                            @click="startCameraScanner('return_item')" 
                            type="button" 
                            class="text-xs bg-primary/10 hover:bg-primary/20 text-primary font-bold px-3 py-1.5 rounded-xl border border-primary/30 flex items-center gap-1 transition"
                        >
                            <span>📷</span>
                            <span>Scan via Kamera</span>
                        </button>
                    </div>

                    <form @submit.prevent="checkReturnItem()">
                        <input 
                            x-ref="returnItemInput"
                            x-model="returnFlow.itemCode"
                            type="text" 
                            autofocus
                            placeholder="Arahkan scanner ke QR Code barang yang dikembalikan..."
                            class="w-full bg-neutral-50 font-semibold border-2 border-dashed border-primary/60 focus:border-primary focus:bg-white rounded-2xl py-3.5 px-4 text-center tracking-wider text-base"
                        />
                    </form>
                    <p class="text-xs text-neutral-500 mt-2 text-center">
                        Scan kode fisik barang (contoh: <code>AST-RTR-001</code>) atau gunakan tombol kamera.
                    </p>
                </div>

                <!-- Info Barang Terdeteksi -->
                <template x-if="returnFlow.itemData">
                    <div class="mb-6 p-4 rounded-2xl bg-neutral-50 border border-neutral-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-xs text-neutral-500 font-bold uppercase">Barang Ditemukan:</div>
                                <div class="text-lg font-bold text-neutral-900" x-text="returnFlow.itemData.name"></div>
                                <div class="text-xs text-neutral-600 font-mono" x-text="returnFlow.itemData.item_code"></div>
                            </div>
                            <div class="text-right">
                                <span 
                                    class="text-xs px-2.5 py-1 rounded-full font-bold uppercase"
                                    :class="returnFlow.itemData.status === 'dipinjam' ? 'bg-primary/10 text-primary' : 'bg-neutral-200 text-neutral-700'"
                                    x-text="returnFlow.itemData.status"
                                ></span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Step B: Pilihan Kondisi Fisik -->
                <template x-if="returnFlow.itemData">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-neutral-800 mb-3">Pilih Kondisi Fisik Barang:</label>
                            <div class="grid grid-cols-2 gap-4">
                                <button 
                                    type="button" 
                                    @click="returnFlow.condition = 'bagus'"
                                    :class="returnFlow.condition === 'bagus' ? 'border-success ring-4 ring-success/20 bg-success/10' : 'border-neutral-200 bg-white'"
                                    class="p-4 rounded-2xl border-2 text-center transition"
                                >
                                    <div class="text-3xl mb-1">👍</div>
                                    <div class="font-bold text-base text-neutral-900">Bagus / Normal</div>
                                    <div class="text-xs text-neutral-500 mt-1">Siap disimpan di rak</div>
                                </button>

                                <button 
                                    type="button" 
                                    @click="returnFlow.condition = 'rusak'"
                                    :class="returnFlow.condition === 'rusak' ? 'border-danger ring-4 ring-danger/20 bg-danger/10' : 'border-neutral-200 bg-white'"
                                    class="p-4 rounded-2xl border-2 text-center transition"
                                >
                                    <div class="text-3xl mb-1">⚠️</div>
                                    <div class="font-bold text-base text-neutral-900">Rusak / Ada Kendala</div>
                                    <div class="text-xs text-neutral-500 mt-1">Buat tiket perbaikan</div>
                                </button>
                            </div>
                        </div>

                        <!-- Form Laporan Kerusakan jika rusak -->
                        <template x-if="returnFlow.condition === 'rusak'">
                            <div class="bg-danger/10 border border-danger/30 rounded-2xl p-4 space-y-2">
                                <label class="block text-xs font-bold text-danger-dark uppercase tracking-wider">Deskripsi Kerusakan Fisik/Teknis:</label>
                                <textarea 
                                    x-model="returnFlow.issueDescription"
                                    rows="3" 
                                    placeholder="Jelaskan kendala alat (contoh: Port LAN 1 mati, adaptor tidak menyala, kabel putus)..."
                                    class="w-full bg-white text-sm border border-neutral-300 rounded-xl p-3 focus:ring-2 focus:ring-danger"
                                ></textarea>
                                <p class="text-xs text-danger-dark">
                                    *Status barang akan seketika diubah menjadi 'under_maintenance' dan terkunci dari katalog Kiosk.
                                </p>
                            </div>
                        </template>

                        <!-- Tombol Submit Pengembalian -->
                        <button 
                            @click="submitReturn()"
                            :disabled="returnFlow.isSubmitting"
                            type="button" 
                            class="w-full bg-primary hover:bg-primary-dark text-white font-bold text-lg py-4 rounded-2xl shadow-lg transition flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span x-show="!returnFlow.isSubmitting">Konfirmasi Pengembalian</span>
                            <span x-show="returnFlow.isSubmitting">Memproses Pengembalian...</span>
                        </button>
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

                // Available items from server Blade injection
                availableItems: @json($availableItems ?? []),

                // Inputs
                scanInputs: {
                    nis: '',
                    itemCode: ''
                },

                // Active Session State
                currentUser: {
                    id: null,
                    name: '',
                    nis_nip: '',
                    phone: '',
                    token: ''
                },
                selectedItem: null,
                selectedDuration: 'pulang_sekolah',
                estimatedDueText: '',

                // Return flow state
                returnFlow: {
                    itemCode: '',
                    itemData: null,
                    condition: 'bagus',
                    issueDescription: '',
                    isSubmitting: false
                },

                // Camera Snapshot confirmation state
                cameraStream: null,
                cameraStatus: 'pending',
                cameraStatusMessage: 'Menyiapkan kamera Kiosk...',

                // Fallback Scanner Camera Barcode Reader State
                cameraScanner: {
                    active: false,
                    target: 'nis', // 'nis' | 'item' | 'return_item'
                    title: 'Scan Barcode via Kamera',
                    instance: null
                },

                // Notification Toast
                toast: {
                    visible: false,
                    type: 'info',
                    title: '',
                    message: ''
                },
                toastTimeout: null,

                // Success Message display
                successMessage: {
                    title: 'Peminjaman Berhasil!',
                    body: 'Barang siap digunakan untuk kegiatan praktikum laboratorium.',
                    dueAt: ''
                },

                // Init method
                initKiosk() {
                    this.updateClock();
                    this.clockInterval = setInterval(() => this.updateClock(), 1000);
                    this.calculateEstimatedDue();
                    
                    // Autofocus listener
                    this.$nextTick(() => {
                        this.focusNisInput();
                    });
                },

                // Sound feedback sintetis via Web Audio API (tidak butuh file eksternal)
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

                focusNisInput() {
                    if (this.$refs.nisInput && !this.cameraScanner.active) {
                        this.$refs.nisInput.focus();
                    }
                },

                keepFocus(event) {
                    if (this.screen === 'standby' && !this.cameraScanner.active) {
                        setTimeout(() => this.focusNisInput(), 150);
                    }
                },

                // =========================================================================
                // CAMERA SCANNER FALLBACK (HTML5-QRCODE INTEGRATION)
                // =========================================================================
                startCameraScanner(target) {
                    this.cameraScanner.target = target;
                    this.cameraScanner.active = true;

                    if (target === 'nis') {
                        this.cameraScanner.title = 'Scan Barcode NIS Kartu Pelajar';
                    } else if (target === 'item') {
                        this.cameraScanner.title = 'Scan QR/Barcode Alat Praktikum';
                    } else if (target === 'return_item') {
                        this.cameraScanner.title = 'Scan QR Alat yang Dikembalikan';
                    }

                    this.$nextTick(async () => {
                        try {
                            if (!window.Html5Qrcode) {
                                this.showToast('error', 'Library Kamera Belum Siap', 'Gagal memuat komponen pemindai kamera.');
                                this.cameraScanner.active = false;
                                return;
                            }

                            if (this.cameraScanner.instance) {
                                try { await this.cameraScanner.instance.stop(); } catch(e) {}
                            }

                            const html5QrCode = new window.Html5Qrcode("camera-reader");
                            this.cameraScanner.instance = html5QrCode;

                            const config = {
                                fps: 15,
                                qrbox: { width: 250, height: 250 },
                                aspectRatio: 1.0
                            };

                            await html5QrCode.start(
                                { facingMode: "environment" },
                                config,
                                (decodedText) => {
                                    this.onCameraScanSuccess(decodedText);
                                },
                                (errorMessage) => {
                                    // continuous scanning
                                }
                            );
                        } catch (err) {
                            console.error('Camera Scanner start error:', err);
                            this.showToast('error', 'Izin Kamera Ditolak', 'Tidak dapat mengakses perangkat kamera untuk pemindaian.');
                            this.cameraScanner.active = false;
                        }
                    });
                },

                async stopCameraScanner() {
                    if (this.cameraScanner.instance) {
                        try {
                            await this.cameraScanner.instance.stop();
                            this.cameraScanner.instance.clear();
                        } catch (e) {
                            console.warn('Stop scanner warn:', e);
                        }
                        this.cameraScanner.instance = null;
                    }
                    this.cameraScanner.active = false;

                    this.$nextTick(() => {
                        if (this.screen === 'standby') this.focusNisInput();
                        if (this.screen === 'scan_item' && this.$refs.itemInput) this.$refs.itemInput.focus();
                        if (this.screen === 'return_screen' && this.$refs.returnItemInput) this.$refs.returnItemInput.focus();
                    });
                },

                async onCameraScanSuccess(decodedText) {
                    const target = this.cameraScanner.target;
                    await this.stopCameraScanner();

                    if (target === 'nis') {
                        this.scanInputs.nis = decodedText;
                        await this.handleNisSubmit();
                    } else if (target === 'item') {
                        this.scanInputs.itemCode = decodedText;
                        await this.handleItemScan();
                    } else if (target === 'return_item') {
                        this.returnFlow.itemCode = decodedText;
                        await this.checkReturnItem();
                    }
                },

                // LAYAR 1: Handle Scan NIS Kartu Pelajar
                async handleNisSubmit() {
                    const nis = this.scanInputs.nis.trim();
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

                        // Berhasil login Kiosk
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
                                this.$refs.itemInput.focus();
                            }
                        });

                    } catch (error) {
                        this.playAudioTone('error');
                        this.showToast('error', 'Koneksi Bermasalah', 'Gagal berkomunikasi dengan server Kiosk.');
                    }
                },

                // LAYAR 2: Handle Durasi & Due Date calculation
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

                // LAYAR 2: Handle Scan QR Item
                async handleItemScan() {
                    const code = this.scanInputs.itemCode.trim();
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

                        this.playAudioTone('beep');
                        this.selectedItem = item;
                        this.scanInputs.itemCode = '';
                        this.showToast('info', 'Barang Terpilih', `Alat "${item.name}" berhasil dipindai.`);

                    } catch (error) {
                        this.playAudioTone('error');
                        this.showToast('error', 'Error', 'Gagal memverifikasi status barang.');
                    }
                },

                selectItemDirectly(item) {
                    this.selectedItem = item;
                    this.playAudioTone('beep');
                },

                // LAYAR 2 -> LAYAR 3: Pindah ke Layar Snapshot Webcam
                proceedToSnapshotScreen() {
                    if (!this.selectedItem) return;
                    this.screen = 'snapshot_confirm';
                    this.startWebcam();
                },

                // WebRTC Camera Handler
                async startWebcam() {
                    this.cameraStatus = 'pending';
                    this.cameraStatusMessage = 'Mengakses kamera Kiosk...';

                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: { 
                                width: { ideal: 640 },
                                height: { ideal: 480 },
                                facingMode: 'user'
                            },
                            audio: false
                        });

                        this.cameraStream = stream;
                        if (this.$refs.videoElement) {
                            this.$refs.videoElement.srcObject = stream;
                        }
                        this.cameraStatus = 'ready';
                    } catch (err) {
                        console.warn('Webcam error:', err);
                        this.cameraStatus = 'error';
                        this.cameraStatusMessage = 'Gagal mengakses webcam. Pastikan izin kamera aktif.';
                    }
                },

                stopWebcam() {
                    if (this.cameraStream) {
                        this.cameraStream.getTracks().forEach(track => track.stop());
                        this.cameraStream = null;
                    }
                },

                captureSnapshot() {
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

                // LAYAR 3: Konfirmasi Peminjaman (Kirim ke API)
                async confirmLoan() {
                    if (this.isSubmitting) return;
                    this.isSubmitting = true;

                    const photoBase64 = this.captureSnapshot();

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
                                item_id: this.selectedItem.id,
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

                        // Berhasil
                        this.playAudioTone('success');
                        this.stopWebcam();

                        this.successMessage = {
                            title: 'Peminjaman Berhasil!',
                            body: `Peralatan "${result.data.item_name}" berhasil dipinjam atas nama ${this.currentUser.name}.`,
                            dueAt: this.estimatedDueText
                        };

                        this.screen = 'success';
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

                // LAYAR 4: Flow Pengembalian Mandiri
                goToReturnScreen() {
                    this.screen = 'return_screen';
                    this.returnFlow = {
                        itemCode: '',
                        itemData: null,
                        condition: 'bagus',
                        issueDescription: '',
                        isSubmitting: false
                    };
                    this.$nextTick(() => {
                        if (this.$refs.returnItemInput) {
                            this.$refs.returnItemInput.focus();
                        }
                    });
                },

                async checkReturnItem() {
                    const code = this.returnFlow.itemCode.trim();
                    if (!code) return;

                    try {
                        const response = await fetch(`/api/v1/items/${encodeURIComponent(code)}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            this.playAudioTone('error');
                            this.showToast('error', 'Barang Tidak Ditemukan', 'Kode QR barang tidak terdaftar.');
                            return;
                        }

                        this.playAudioTone('beep');
                        this.returnFlow.itemData = result.data;

                    } catch (error) {
                        this.playAudioTone('error');
                        this.showToast('error', 'Error', 'Gagal memeriksa status barang.');
                    }
                },

                async submitReturn() {
                    if (!this.returnFlow.itemData || this.returnFlow.isSubmitting) return;
                    this.returnFlow.isSubmitting = true;

                    try {
                        const response = await fetch('/api/v1/loans/return-kiosk', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                item_code: this.returnFlow.itemData.item_code,
                                condition_in: this.returnFlow.condition,
                                issue_description: this.returnFlow.issueDescription
                            })
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
                            title: 'Pengembalian Selesai!',
                            body: result.message,
                            dueAt: 'Barang kembali ke laboratorium'
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

                // Reset Kiosk State to Idle
                resetToStandby() {
                    this.stopWebcam();
                    this.stopCameraScanner();
                    clearInterval(this.countdownInterval);
                    this.screen = 'standby';
                    this.scanInputs.nis = '';
                    this.scanInputs.itemCode = '';
                    this.currentUser = { id: null, name: '', nis_nip: '', phone: '', token: '' };
                    this.selectedItem = null;
                    this.selectedDuration = 'pulang_sekolah';
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
