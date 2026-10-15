<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name', 'SILAMET') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="font-sans antialiased bg-neutral-100">
        <div class="min-h-screen flex">

            <!-- Sidebar -->
            <aside id="sidebar" class="fixed inset-y-0 left-0 z-30 w-64 bg-primary-dark transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out lg:static lg:inset-auto">
                <div class="flex items-center justify-center h-16 border-b border-white/10">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <span class="text-white font-bold text-lg tracking-tight">SILAMET</span>
                        <span class="text-primary-light text-xs">Lab SIJA</span>
                    </a>
                </div>

                <nav class="mt-4 px-3 space-y-1 text-sm">
                    @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng', 'kaprog'], true))
                        <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span>Dashboard</span>
                        </x-sidebar-link>
                    @endif

                    @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng', 'kaprog'], true))
                        <x-sidebar-link :href="route('items.index')" :active="request()->routeIs('items.*')">
                            <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span>Master Aset</span>
                        </x-sidebar-link>
                    @endif

                    @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng', 'kaprog'], true))
                        <x-sidebar-link :href="route('loans.index')" :active="request()->routeIs('loans.*')">
                            <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Peminjaman</span>
                        </x-sidebar-link>
                    @endif

                    @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng'], true))
                        <x-sidebar-link :href="route('maintenance.index')" :active="request()->routeIs('maintenance.*')">
                            <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Maintenance</span>
                        </x-sidebar-link>
                    @endif

                    @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng', 'kaprog'], true))
                        <x-sidebar-link :href="route('gate-logs.index')" :active="request()->routeIs('gate-logs.*')">
                            <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Gate Logs</span>
                        </x-sidebar-link>
                    @endif

                    @if(in_array(auth()->user()?->role?->value, ['kabeng', 'kaprog'], true))
                        <x-sidebar-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                            <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span>Laporan</span>
                        </x-sidebar-link>
                    @endif

                    @if(auth()->user()?->role?->value === 'kabeng')
                        <x-sidebar-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span>Manajemen User</span>
                        </x-sidebar-link>
                    @endif
                </nav>

                <div class="absolute bottom-0 inset-x-0 p-3 border-t border-white/10">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left text-white/80 hover:text-white hover:bg-white/10 rounded-md px-3 py-2 text-sm flex items-center">
                            <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Overlay untuk mobile -->
            <div id="sidebar-overlay" class="fixed inset-0 z-20 bg-black/50 hidden lg:hidden"></div>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col min-w-0">
                <!-- Topbar -->
                <header class="bg-white shadow-sm h-16 flex items-center px-4 lg:px-8">
                    <button id="sidebar-toggle" class="lg:hidden text-neutral-600 hover:text-neutral-900 mr-3 flex items-center justify-center p-1.5 rounded-lg border border-neutral-200" aria-label="Toggle sidebar">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <h1 class="text-neutral-800 font-semibold text-lg truncate">
                        {{ $heading ?? 'Dashboard' }}
                    </h1>

                    <div class="ml-auto flex items-center gap-3">
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-medium text-neutral-700">{{ auth()->user()?->name }}</div>
                            <div class="text-xs text-neutral-500">{{ auth()->user()?->role?->label() ?? '' }}</div>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center font-semibold uppercase">
                            {{ strtoupper(substr(auth()->user()?->name ?? '?', 0, 1)) }}
                        </div>
                    </div>
                </header>

                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mx-4 lg:mx-8 mt-4 bg-success/15 border border-success text-success-dark px-4 py-3 rounded-md text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mx-4 lg:mx-8 mt-4 bg-danger/15 border border-danger text-danger-dark px-4 py-3 rounded-md text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Page Content -->
                <main class="flex-1 p-4 lg:p-8">
                    {{ $slot }}
                </main>

                <footer class="text-center text-xs text-neutral-400 py-4">
                    SILAMET © {{ date('Y') }} — Lab SIJA SMK Negeri 1 Cibinong
                </footer>
            </div>
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
