<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name', 'SILAMET') }}</title>

        <!-- Google Fonts: Inter, Outfit & JetBrains Mono -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="h-full font-sans antialiased bg-neutral-50 text-neutral-800 flex overflow-hidden">

        <!-- Mobile Sidebar Backdrop -->
        <div id="sidebar-overlay" class="fixed inset-0 z-40 bg-neutral-950/60 hidden lg:hidden transition-opacity"></div>

        <!-- Sidebar Navigation (Orbix Studio Dark Surface) -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-neutral-900 border-r border-neutral-800/80 flex flex-col justify-between transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out lg:static lg:inset-auto shrink-0 shadow-lg lg:shadow-none">
            
            <!-- Brand Wordmark Header -->
            <div>
                <div class="px-5 h-14 border-b border-neutral-800 flex items-center justify-between">
                    <a href="{{ route('dashboard') }}" class="focus:outline-none flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-white/10 border border-white/15 flex items-center justify-center text-white font-mono font-bold text-xs tracking-wider">
                            S
                        </div>
                        <span class="font-heading font-extrabold text-lg tracking-[0.14em] text-white leading-none uppercase">
                            SILAMET
                        </span>
                    </a>
                    <span class="font-mono text-[9px] font-bold px-1.5 py-0.5 rounded bg-neutral-800 text-neutral-400 border border-neutral-700/60 uppercase">
                        LAB SIJA
                    </span>
                </div>

                <!-- Navigation Links Categorized -->
                <nav class="p-3 space-y-5 text-sm overflow-y-auto max-h-[calc(100vh-160px)]">
                    
                    <!-- KELOMPOK UTAMA -->
                    <div>
                        <div class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-neutral-500">Utama</div>
                        <div class="space-y-1">
                            @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng', 'kaprog'], true))
                                <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                                    <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                    <span>Dashboard</span>
                                </x-sidebar-link>
                            @endif
                        </div>
                    </div>

                    <!-- INVENTARIS & ALAT -->
                    <div>
                        <div class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-neutral-500">Inventaris & Sirkulasi</div>
                        <div class="space-y-1">
                            @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng', 'kaprog'], true))
                                <x-sidebar-link :href="route('items.index')" :active="request()->routeIs('items.*')">
                                    <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <span>Master Aset</span>
                                </x-sidebar-link>
                            @endif

                            @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng', 'kaprog'], true))
                                @php
                                    $activeLoanCountBadge = \App\Models\Loan::query()
                                        ->whereIn('status', [\App\Enums\LoanStatus::AKTIF->value, \App\Enums\LoanStatus::MENUNGGU_APPROVAL_KEMBALI->value])
                                        ->count();
                                @endphp
                                <x-sidebar-link :href="route('loans.index')" :active="request()->routeIs('loans.index') || request()->routeIs('loans.create') || request()->routeIs('loans.edit') || (request()->routeIs('loans.show') && (!isset($loan) || $loan->status?->value !== 'selesai'))">
                                    <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                    <span>Peminjaman Alat</span>
                                    @if($activeLoanCountBadge > 0)
                                        <span class="ml-auto px-1.5 py-0.5 rounded-full font-mono text-[10px] font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30">
                                            {{ $activeLoanCountBadge }}
                                        </span>
                                    @endif
                                </x-sidebar-link>

                                <x-sidebar-link :href="route('loans.history')" :active="request()->routeIs('loans.history') || (request()->routeIs('loans.show') && isset($loan) && $loan->status?->value === 'selesai')">
                                    <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Histori Peminjaman</span>
                                </x-sidebar-link>
                            @endif

                            @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng'], true))
                                @php
                                    $openTicketCountBadge = \App\Models\MaintenanceTicket::query()
                                        ->where('status', 'open')
                                        ->count();
                                @endphp
                                <x-sidebar-link :href="route('maintenance.index')" :active="request()->routeIs('maintenance.*')">
                                    <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>Maintenance &amp; Repair</span>
                                    @if($openTicketCountBadge > 0)
                                        <span class="ml-auto px-1.5 py-0.5 rounded-full font-mono text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                            {{ $openTicketCountBadge }}
                                        </span>
                                    @endif
                                </x-sidebar-link>
                            @endif
                        </div>
                    </div>

                    <!-- KEAMANAN GERBANG -->
                    <div>
                        <div class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-neutral-500">Keamanan Lab</div>
                        <div class="space-y-1">
                            @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng', 'kaprog'], true))
                                <x-sidebar-link :href="route('gate-logs.index')" :active="request()->routeIs('gate-logs.*')">
                                    <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span>Gate Logs RFID</span>
                                </x-sidebar-link>
                            @endif
                        </div>
                    </div>

                    <!-- ADMINISTRASI & LAPORAN -->
                    <div>
                        <div class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-neutral-500">Administrasi</div>
                        <div class="space-y-1">
                            @if(in_array(auth()->user()?->role?->value, ['kabeng', 'kaprog'], true))
                                <x-sidebar-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                                    <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>Laporan & Ekspor</span>
                                </x-sidebar-link>
                            @endif

                            @if(auth()->user()?->role?->value === 'kabeng')
                                <x-sidebar-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                                    <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span>Manajemen User</span>
                                </x-sidebar-link>
                            @endif
                        </div>
                    </div>

                </nav>
            </div>

            <!-- Bottom Section: Kiosk Switcher & User Profile (Pill Style) -->
            <div class="p-3 border-t border-neutral-800 bg-neutral-950/70 space-y-2">
                <a href="{{ route('kiosk.home') }}" target="_blank" class="flex items-center justify-between w-full px-3 py-2 rounded-xl text-xs font-semibold text-neutral-200 bg-neutral-900 hover:bg-neutral-800 border border-neutral-800 transition-colors shadow-sm">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>Buka Kiosk Siswa</span>
                    </span>
                    <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>

                <!-- User Card & Logout -->
                <div class="flex items-center justify-between p-2 rounded-xl bg-neutral-900 border border-neutral-800">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-neutral-800 border border-neutral-700 text-white font-bold flex items-center justify-center text-xs shrink-0 font-heading">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="min-w-0 truncate">
                            <div class="text-xs font-semibold text-white truncate">{{ auth()->user()?->name }}</div>
                            <div class="text-[10px] text-neutral-400 font-mono truncate">{{ auth()->user()?->role?->label() ?? 'Petugas' }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-1.5 text-neutral-400 hover:text-rose-400 hover:bg-neutral-800 rounded-lg transition-colors" title="Logout" aria-label="Logout">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

        </aside>

        <!-- Main Workspace -->
        <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
            
            <!-- Solid Border-First Topbar (Clean Dribbble SaaS Style) -->
            <header class="bg-white border-b border-neutral-200 h-14 flex items-center justify-between px-4 lg:px-6 shrink-0 z-30">
                <div class="flex items-center gap-3">
                    <button id="sidebar-toggle" class="lg:hidden text-neutral-600 hover:text-neutral-900 flex items-center justify-center p-2 rounded-xl border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors" aria-label="Toggle sidebar" title="Menu Navigasi">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('kiosk.home') }}" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold text-neutral-700 bg-neutral-100 hover:bg-neutral-200/70 border border-neutral-200 transition-colors shadow-sm">
                        <svg class="w-3.5 h-3.5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>Layar Kiosk</span>
                    </a>

                    <div class="flex items-center gap-2.5 pl-3 border-l border-neutral-200">
                        <div class="text-right hidden md:block">
                            <div class="text-xs font-semibold text-neutral-900">{{ auth()->user()?->name }}</div>
                            <span class="block text-[10px] font-mono text-neutral-500">
                                {{ auth()->user()?->role?->label() ?? 'Petugas' }}
                            </span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-neutral-900 text-white font-bold flex items-center justify-center text-xs font-heading shadow-sm">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-6 space-y-5">
                <!-- Flash Messages (Rounded-2xl Pill Alert) -->
                @if(session('success'))
                    <div class="bg-emerald-50/90 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 p-1 rounded-full hover:bg-emerald-100 transition-colors" aria-label="Tutup notifikasi">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="bg-rose-50/90 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-medium">{{ session('error') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900 p-1 rounded-full hover:bg-rose-100 transition-colors" aria-label="Tutup notifikasi">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif

                {{ $slot }}

                <footer class="pt-6 pb-2 text-center text-xs text-neutral-400 border-t border-neutral-200/60 mt-6">
                    <p>SILAMET &copy; {{ date('Y') }} — Sistem Inventarisasi Lab SIJA SMK Negeri 1 Cibinong</p>
                </footer>
            </main>

        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toggle = document.getElementById('sidebar-toggle');
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebar-overlay');

                if (toggle && sidebar && overlay) {
                    toggle.addEventListener('click', function () {
                        sidebar.classList.toggle('-translate-x-full');
                        overlay.classList.toggle('hidden');
                    });
                    overlay.addEventListener('click', function () {
                        sidebar.classList.add('-translate-x-full');
                        overlay.classList.add('hidden');
                    });
                }
            });
        </script>
        @stack('scripts')
    </body>
</html>