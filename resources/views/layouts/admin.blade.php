<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - CV. Tri Jaya')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-100 font-sans">

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 bg-gray-900 text-white flex-shrink-0">
            <div class="p-6">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">TJ</span>
                    </div>
                    <span class="text-lg font-bold">Admin Panel</span>
                </a>
            </div>
            <nav class="mt-4 px-4 space-y-1">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z">
                        </path>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('admin.products.index') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.products.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    Produk
                </a>
            </nav>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col">
            {{-- Top Bar --}}
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
                <div class="flex justify-between items-center">
                    <h1 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600">{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit"
                                class="text-sm text-red-600 hover:text-red-800 cursor-pointer">Keluar</button>
                        </form>
                    </div>
                </div>
            </header>

            {{-- Flash Messages --}}
            @if (session('status'))
                <div class="px-6 pt-4">
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                        {{ session('status') }}
                    </div>
                </div>
            @endif

            {{-- Page Content --}}
            <main class="flex-1 p-6">
                @yield('content')
            </main>
        </div>
    </div>

    @yield('scripts')

</body>

</html>
