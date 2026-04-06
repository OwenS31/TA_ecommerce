<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CV. Tri Jaya - Penjualan Terpal')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 font-sans">
    <div class="min-h-screen flex flex-col">
        <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between gap-4 py-3">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 shrink-0">
                        <div
                            class="w-11 h-11 rounded-2xl bg-linear-to-br from-sky-600 via-blue-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-sky-500/20">
                            <span class="text-white font-black tracking-wide text-sm">TJ</span>
                        </div>
                        <div>
                            <p class="text-base font-extrabold leading-none tracking-tight">CV. Tri Jaya</p>
                            <p class="text-xs text-slate-500">Solusi terpal berkualitas</p>
                        </div>
                    </a>

                    <nav class="hidden lg:flex items-center gap-1">
                        <a href="{{ route('home') }}"
                            class="px-4 py-2 text-sm font-medium rounded-full {{ request()->routeIs('home') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Beranda</a>
                        <a href="{{ route('catalog') }}"
                            class="px-4 py-2 text-sm font-medium rounded-full {{ request()->routeIs('catalog') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Katalog</a>
                        @auth
                            <a href="{{ route('history') }}"
                                class="px-4 py-2 text-sm font-medium rounded-full {{ request()->routeIs('history') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Riwayat
                                Pesanan</a>
                            <a href="{{ route('profile') }}"
                                class="px-4 py-2 text-sm font-medium rounded-full {{ request()->routeIs('profile') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Profil</a>
                        @endauth
                    </nav>

                    <div class="flex items-center gap-2 sm:gap-3">
                        @auth
                            <a href="{{ route('cart') }}"
                                class="inline-flex items-center justify-center w-11 h-11 rounded-full border border-slate-200 text-slate-700 hover:bg-slate-100 transition"
                                aria-label="Keranjang">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                                </svg>
                            </a>
                            <div class="hidden sm:block text-right">
                                <p class="text-sm font-semibold leading-none">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-slate-500">Pelanggan</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                                    </svg>
                                    Logout
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}"
                                class="inline-flex items-center px-4 py-2 rounded-full border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-100 transition">Login</a>
                            <a href="{{ route('register') }}"
                                class="inline-flex items-center px-4 py-2 rounded-full bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition">Daftar</a>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        @if (session('status'))
            <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        <main class="flex-1">
            @yield('content')
        </main>

        <footer class="mt-16 bg-slate-950 text-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                    <div>
                        <h3 class="text-lg font-bold text-white mb-3">CV. Tri Jaya</h3>
                        <p class="text-sm text-slate-400 leading-6">Penjualan terpal berkualitas untuk kebutuhan
                            industri dan rumah tangga, dengan layanan custom ukuran dan pengiriman ke seluruh Indonesia.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400 mb-3">Navigasi</h3>
                        <div class="space-y-2 text-sm">
                            <a href="{{ route('home') }}" class="block hover:text-white">Beranda</a>
                            <a href="{{ route('catalog') }}" class="block hover:text-white">Katalog</a>
                            <a href="{{ route('home') }}#tentang-kami" class="block hover:text-white">Tentang Kami</a>
                            <a href="{{ route('home') }}#faq" class="block hover:text-white">FAQ</a>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400 mb-3">Kontak</h3>
                        <div class="space-y-2 text-sm text-slate-300">
                            <p>Alamat: Lebak Timur 11 Kav 1-2</p>
                            <p>WhatsApp: 085100600657</p>
                            <p>Email: anghienarta@gmail.com</p>
                        </div>
                    </div>
                </div>
                <div class="mt-10 pt-6 border-t border-slate-800 text-center text-sm text-slate-500">
                    Copyright © CV. Tri Jaya 1997–sekarang
                </div>
            </div>
        </footer>
    </div>

    @yield('scripts')
</body>

</html>
