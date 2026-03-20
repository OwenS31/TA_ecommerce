@extends('layouts.guest')

@section('title', 'Masuk - CV. Tri Jaya')

@section('content')
    <h2 class="text-xl font-bold text-gray-900 mb-6">Masuk ke Akun Anda</h2>

    {{-- Success message (e.g. after password reset) --}}
    @if (session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                placeholder="contoh@email.com"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm @error('email') border-red-500 @enderror">
            @error('email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" id="password" name="password" required placeholder="Masukkan password"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm @error('password') border-red-500 @enderror">
            @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember Me & Forgot Password --}}
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-gray-600">Ingat saya</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-800">Lupa Password?</a>
        </div>

        {{-- Submit --}}
        <button type="submit"
            class="w-full bg-blue-600 text-white py-2.5 px-4 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition font-medium text-sm cursor-pointer">
            Masuk
        </button>
    </form>

    {{-- Register Link --}}
    <p class="text-center text-sm text-gray-600 mt-6">
        Belum punya akun?
        <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-800 font-medium">Daftar sekarang</a>
    </p>
@endsection
