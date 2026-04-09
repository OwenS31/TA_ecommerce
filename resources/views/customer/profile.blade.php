@extends('layouts.app')

@section('title', 'Profil Saya - CV. Tri Jaya')

@section('content')
    <section class="py-16 bg-slate-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">Profil</p>
                <h1 class="text-3xl font-black text-slate-950">Profil akun Anda</h1>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <form method="POST" action="{{ route('profile.update') }}"
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <h2 class="text-xl font-bold text-slate-950">Data Profil</h2>
                        <p class="text-sm text-slate-500 mt-1">Lihat dan edit nama, email, dan nomor telepon.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">No. Telepon</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                    </div>

                    <button type="submit"
                        class="inline-flex items-center px-5 py-3 rounded-full bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition">Simpan
                        Profil</button>
                </form>

                <div class="space-y-6">
                    <form method="POST" action="{{ route('profile.password') }}"
                        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <h2 class="text-xl font-bold text-slate-950">Ubah Password</h2>
                            <p class="text-sm text-slate-500 mt-1">Gunakan password baru yang kuat dan aman.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Password saat ini</label>
                            <input type="password" name="current_password"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Password baru</label>
                            <input type="password" name="password"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Konfirmasi password baru</label>
                            <input type="password" name="password_confirmation"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                        </div>

                        <button type="submit"
                            class="inline-flex items-center px-5 py-3 rounded-full bg-cyan-500 text-white text-sm font-semibold hover:bg-cyan-400 transition">Ubah
                            Password</button>
                    </form>

                    <div class="rounded-3xl border border-slate-200 bg-slate-950 text-white p-6 shadow-sm">
                        <p class="text-sm text-slate-300">Status Akun</p>
                        <p class="text-2xl font-black mt-1">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                        <p class="text-sm text-slate-300 mt-4">Tanggal daftar</p>
                        <p class="font-medium mt-1">{{ $user->created_at?->format('d M Y H:i') }}</p>
                        <p class="text-sm text-slate-300 mt-4">Alamat tersimpan</p>
                        <p class="font-medium mt-1">Belum ada daftar alamat tambahan. Bisa ditambahkan nanti jika
                            dibutuhkan.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
