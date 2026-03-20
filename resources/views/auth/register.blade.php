@extends('layouts.guest')

@section('title', 'Daftar - CV. Tri Jaya')

@section('content')
    <h2 class="text-xl font-bold text-gray-900 mb-6">Buat Akun Baru</h2>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        {{-- Nama Lengkap --}}
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                placeholder="Masukkan nama lengkap"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm @error('name') border-red-500 @enderror">
            @error('name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required
                placeholder="contoh@email.com"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm @error('email') border-red-500 @enderror">
            @error('email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Nomor Telepon --}}
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Nomor Telepon</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required
                placeholder="08xxxxxxxxxx"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm @error('phone') border-red-500 @enderror">
            @error('phone')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" id="password" name="password" required placeholder="Minimal 8 karakter"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm @error('password') border-red-500 @enderror">
            @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Konfirmasi Password --}}
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi
                Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                placeholder="Ulangi password"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm">
        </div>

        {{-- Submit --}}
        <button type="submit"
            class="w-full bg-blue-600 text-white py-2.5 px-4 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition font-medium text-sm cursor-pointer">
            Daftar
        </button>
    </form>

    {{-- Login Link --}}
    <p class="text-center text-sm text-gray-600 mt-6">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-medium">Masuk di sini</a>
    </p>
@endsection
