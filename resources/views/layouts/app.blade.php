<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CV. Tri Jaya - Penjualan Terpal')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50 text-gray-800 font-sans">

    {{-- Navbar --}}
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                {{-- Logo --}}
                <a href="{{ url('/') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">TJ</span>
                    </div>
                    <span class="text-lg font-bold text-gray-900">CV. Tri Jaya</span>
                </a>

                {{-- Navigation --}}
                <div class="flex items-center gap-4">
                    @auth
                        <span class="text-sm text-gray-600">Halo, {{ Auth::user()->name }}</span>
                        @if (Auth::user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}"
                                class="text-sm text-blue-600 hover:text-blue-800">Dashboard</a>
                        @else
                            <a href="{{ route('home') }}" class="text-sm text-blue-600 hover:text-blue-800">Beranda</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit"
                                class="text-sm text-red-600 hover:text-red-800 cursor-pointer">Keluar</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900">Masuk</a>
                        <a href="{{ route('register') }}"
                            class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Daftar</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Flash Messages --}}
    @if (session('status'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('status') }}
            </div>
        </div>
    @endif

    {{-- Main Content --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-sm text-gray-500">&copy; {{ date('Y') }} CV. Tri Jaya. Semua hak dilindungi.
            </p>
        </div>
    </footer>

</body>

</html>
