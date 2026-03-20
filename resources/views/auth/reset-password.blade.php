@extends('layouts.guest')

@section('title', 'Reset Password - CV. Tri Jaya')

@section('content')
    <h2 class="text-xl font-bold text-gray-900 mb-6">Reset Password</h2>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autofocus
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm @error('email') border-red-500 @enderror">
            @error('email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password Baru --}}
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
            <input type="password" id="password" name="password" required placeholder="Minimal 8 karakter"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm @error('password') border-red-500 @enderror">
            @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Konfirmasi Password --}}
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password
                Baru</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                placeholder="Ulangi password baru"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-sm">
        </div>

        {{-- Submit --}}
        <button type="submit"
            class="w-full bg-blue-600 text-white py-2.5 px-4 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition font-medium text-sm cursor-pointer">
            Reset Password
        </button>
    </form>
@endsection
