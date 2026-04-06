@extends('layouts.app')

@section('title', 'Keranjang - CV. Tri Jaya')

@section('content')
    <section class="py-16 bg-slate-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm text-center">
                <div class="w-16 h-16 mx-auto rounded-3xl bg-cyan-100 text-cyan-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-950">Keranjang Anda masih kosong</h1>
                <p class="text-slate-600 mt-3">Silakan pilih produk di katalog untuk mulai menambahkan pesanan.</p>
                <a href="{{ route('catalog') }}"
                    class="inline-flex mt-6 px-6 py-3 rounded-full bg-slate-900 text-white font-semibold hover:bg-slate-800 transition">Lihat
                    Katalog</a>
            </div>
        </div>
    </section>
@endsection
