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
                            <span class="inline-block w-5 text-center mr-2">🏠</span> Dashboard
                        </x-sidebar-link>
                    @endif

                    @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng', 'kaprog'], true))
                        <x-sidebar-link :href="route('items.index')" :active="request()->routeIs('items.*')">
                            <span class="inline-block w-5 text-center mr-2">📦</span> Master Aset
                        </x-sidebar-link>
                    @endif

                    @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng', 'kaprog'], true))
                        <x-sidebar-link :href="route('loans.index')" :active="request()->routeIs('loans.*')">
                            <span class="inline-block w-5 text-center mr-2">🔁</span> Peminjaman
                        </x-sidebar-link>
                    @endif

                    @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng'], true))
                        <x-sidebar-link :href="route('maintenance.index')" :active="request()->routeIs('maintenance.*')">
                            <span class="inline-block w-5 text-center mr-2">🔧</span> Maintenance
                        </x-sidebar-link>
                    @endif

                    @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng', 'kaprog'], true))
                        <x-sidebar-link :href="route('gate-logs.index')" :active="request()->routeIs('gate-logs.*')">
                            <span class="inline-block w-5 text-center mr-2">🚪</span> Gate Logs
                        </x-sidebar-link>
                    @endif

                    @if(in_array(auth()->user()?->role?->value, ['kabeng', 'kaprog'], true))
                        <x-sidebar-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                            <span class="inline-block w-5 text-center mr-2">📊</span> Laporan
                        </x-sidebar-link>
                    @endif

                    @if(auth()->user()?->role?->value === 'kabeng')
                        <x-sidebar-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            <span class="inline-block w-5 text-center mr-2">👥</span> Manajemen User
                        </x-sidebar-link>
                    @endif
                </nav>

                <div class="absolute bottom-0 inset-x-0 p-3 border-t border-white/10">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left text-white/80 hover:text-white hover:bg-white/10 rounded-md px-3 py-2 text-sm">
                            <span class="inline-block w-5 text-center mr-2">↩</span> Logout
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
                    <button id="sidebar-toggle" class="lg:hidden text-neutral-600 hover:text-neutral-900 mr-3 text-xl" aria-label="Toggle sidebar">☰</button>

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
