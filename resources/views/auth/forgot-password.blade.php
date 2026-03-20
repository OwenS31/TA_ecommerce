@extends('layouts.guest')

@section('title', 'Lupa Password - CV. Tri Jaya')

@section('content')
    <h2 class="text-xl font-bold text-gray-900 mb-2">Lupa Password?</h2>
    <p class="text-sm text-gray-500 mb-6">Masukkan email Anda dan kami akan mengirimkan link untuk mereset password.</p>

    {{-- Success message --}}
    @if (session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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

        {{-- Submit --}}
        <button type="submit"
            class="w-full bg-blue-600 text-white py-2.5 px-4 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition font-medium text-sm cursor-pointer">
            Kirim Link Reset Password
        </button>
    </form>

    {{-- Back to Login --}}
    <p class="text-center text-sm text-gray-600 mt-6">
        <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-medium">&larr; Kembali ke halaman
            login</a>
    </p>
@endsection
