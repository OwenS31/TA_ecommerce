<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CV. Tri Jaya')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50 font-sans flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center">
                    <span class="text-white font-bold text-lg">TJ</span>
                </div>
                <span class="text-2xl font-bold text-gray-900">CV. Tri Jaya</span>
            </a>
            <p class="text-sm text-gray-500 mt-1">Penjualan Terpal Berkualitas</p>
        </div>

        {{-- Card Content --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
            @yield('content')
        </div>
    </div>

</body>

</html>
