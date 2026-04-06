@extends('layouts.app')

@section('title', 'Profil Saya - CV. Tri Jaya')

@section('content')
    <section class="py-16 bg-slate-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">Profil</p>
                <h1 class="text-3xl font-black text-slate-950">Profil akun Anda</h1>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm text-slate-500">Nama</p>
                    <p class="text-xl font-bold text-slate-950 mt-1">{{ $user->name }}</p>
                    <p class="text-sm text-slate-500 mt-4">Email</p>
                    <p class="font-medium text-slate-900 mt-1">{{ $user->email }}</p>
                    <p class="text-sm text-slate-500 mt-4">No. Telepon</p>
                    <p class="font-medium text-slate-900 mt-1">{{ $user->phone ?: '-' }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-950 text-white p-6 shadow-sm">
                    <p class="text-sm text-slate-300">Status Akun</p>
                    <p class="text-2xl font-black mt-1">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                    <p class="text-sm text-slate-300 mt-4">Tanggal daftar</p>
                    <p class="font-medium mt-1">{{ $user->created_at?->format('d M Y H:i') }}</p>
                    <p class="text-sm text-slate-300 mt-4">Akun ini digunakan untuk pemesanan terpal, riwayat pesanan, dan
                        checkout.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
