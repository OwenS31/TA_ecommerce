@extends('layouts.app')

@section('title', 'Beranda - CV. Tri Jaya')

@section('content')
    @php
        $heroProduct = $featuredProducts->first();
    @endphp

    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(56,189,248,0.24),transparent_35%),radial-gradient(circle_at_bottom_left,rgba(234,88,12,0.2),transparent_30%)]">
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28 relative">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <div
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 border border-white/10 text-sm text-slate-200 mb-6">
                        <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                        Terpal original, ukuran custom, pengiriman seluruh Indonesia
                    </div>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight mb-5">
                        Terpal berkualitas untuk kebutuhan industri dan rumah tangga.
                    </h1>
                    <p class="text-lg text-slate-300 leading-8 max-w-2xl mb-8">
                        CV. Tri Jaya menyediakan terpal original dengan pilihan 5 jenis produk, harga transparan otomatis,
                        dan layanan pemesanan yang mudah.
                    </p>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('catalog') }}"
                            class="inline-flex items-center px-6 py-3 rounded-full bg-cyan-400 text-slate-950 font-semibold hover:bg-cyan-300 transition shadow-lg shadow-cyan-500/20">
                            Belanja Sekarang
                        </a>
                        <a href="#cara-pemesanan"
                            class="inline-flex items-center px-6 py-3 rounded-full border border-white/15 bg-white/5 text-white font-semibold hover:bg-white/10 transition">
                            Lihat Cara Pemesanan
                        </a>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute -inset-4 rounded-4xl bg-cyan-400/10 blur-3xl"></div>
                    <div class="relative rounded-4xl overflow-hidden border border-white/10 bg-white/5 shadow-2xl">
                        <img src="/storage/products/Foto_3terpal.jpg" alt="Terpal"
                            class="aspect-4/3 w-full object-cover object-center">
                        <div class="p-5 border-t border-white/10 bg-slate-900/80">
                            <p class="text-sm text-cyan-300 font-semibold mb-1">Gambar Terpal Berkualitas</p>
                            <p class="text-sm text-slate-300">Visual produk dapat diganti dengan foto gudang, produk, atau
                                proyek resmi ketika tersedia.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                    <div class="w-12 h-12 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Bahan Plastik Ori / Original</h3>
                    <p class="text-sm text-slate-600 leading-6">Terpal original dengan kualitas terjaga untuk berbagai
                        kebutuhan.</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                    <div
                        class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-2.761 0-5 2.239-5 5v2h10v-2c0-2.761-2.239-5-5-5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 18h8" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Pengiriman Seluruh Indonesia</h3>
                    <p class="text-sm text-slate-600 leading-6">Kami melayani pengiriman ke berbagai wilayah dengan kurir
                        terpercaya.</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Harga Transparan &amp; Otomatis</h3>
                    <p class="text-sm text-slate-600 leading-6">Harga dihitung otomatis sesuai ukuran, jumlah, dan jenis
                        terpal.</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                    <div class="w-12 h-12 rounded-2xl bg-violet-100 text-violet-700 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Tersedia 5 Jenis Terpal</h3>
                    <p class="text-sm text-slate-600 leading-6">Pilihan A3, A5, A8, A12, dan A15 untuk berbagai ketebalan
                        kebutuhan.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-slate-50" id="produk-unggulan">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between gap-4 mb-8">
                <div>
                    <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">Katalog Produk Unggulan
                    </p>
                    <h2 class="text-3xl font-black text-slate-950">Produk terpal pilihan</h2>
                </div>
                <a href="{{ route('catalog') }}" class="text-sm font-semibold text-slate-700 hover:text-slate-950">Lihat
                    Semua Produk →</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
                @forelse ($featuredProducts as $product)
                    <article
                        class="rounded-3xl border border-slate-200 bg-white overflow-hidden shadow-sm hover:shadow-lg transition">
                        @if ($product->image)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image) }}"
                                alt="{{ $product->name }}" class="h-40 w-full object-cover object-center">
                        @else
                            <div
                                class="h-40 bg-slate-200 bg-[linear-gradient(135deg,rgba(15,23,42,0.12),rgba(14,165,233,0.22)),url('https://images.unsplash.com/photo-1494438639946-1ebd1d20bf85?auto=format&fit=crop&w=900&q=80')] bg-cover bg-center">
                            </div>
                        @endif
                        <div class="p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-cyan-700 font-semibold">Unggulan</p>
                            <h3 class="mt-2 font-bold text-slate-900">{{ $product->name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $product->description ?: 'Terpal berkualitas untuk kebutuhan harian dan industri.' }}</p>
                            <p class="mt-4 text-sm font-semibold text-slate-900">Rp
                                {{ number_format((float) $product->price_per_m2, 0, ',', '.') }} / m²</p>
                        </div>
                    </article>
                @empty
                    <div
                        class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                        Produk unggulan akan tampil setelah data produk diisi.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="py-16 bg-white" id="tentang-kami">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                <div>
                    <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">Tentang Kami</p>
                    <h2 class="text-3xl font-black text-slate-950 mb-4">CV. Tri Jaya, berpengalaman sejak 1997</h2>
                    <p class="text-slate-600 leading-8 mb-4">
                        CV. Tri Jaya dipimpin oleh Hienarta dan bergerak di bidang penjualan terpal berkualitas untuk
                        kebutuhan industri dan rumah tangga. Dengan pengalaman lebih dari 28 tahun, kami berkomitmen
                        memberikan produk yang awet, layanan cepat, dan harga yang transparan.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs text-slate-500 uppercase tracking-[0.2em]">Didirikan</p>
                            <p class="text-xl font-extrabold mt-2">1997</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs text-slate-500 uppercase tracking-[0.2em]">Pemilik</p>
                            <p class="text-xl font-extrabold mt-2">Hienarta</p>
                        </div>
                    </div>
                </div>
                <div
                    class="rounded-4xl border border-slate-200 bg-slate-100 overflow-hidden min-h-85 flex items-center justify-center">
                    <div class="text-center p-10">
                        <div
                            class="w-20 h-20 mx-auto rounded-3xl bg-slate-900 text-white flex items-center justify-center mb-4">
                            <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4-4a2 2 0 012.828 0L16 17m-2-2l2-2a2 2 0 012.828 0L20 17m-8-11h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <p class="text-lg font-bold text-slate-900">Foto Gudang / Toko</p>
                        <p class="text-sm text-slate-500 mt-2">Placeholder jika foto asli belum tersedia.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-slate-950 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                <div class="rounded-3xl bg-white/5 border border-white/10 p-6">
                    <p class="text-4xl font-black text-cyan-300">28+</p>
                    <p class="mt-2 text-sm text-slate-300">Tahun berpengalaman sejak 1997</p>
                </div>
                <div class="rounded-3xl bg-white/5 border border-white/10 p-6">
                    <p class="text-4xl font-black text-cyan-300">40+</p>
                    <p class="mt-2 text-sm text-slate-300">Pelanggan terpercaya</p>
                </div>
                <div class="rounded-3xl bg-white/5 border border-white/10 p-6">
                    <p class="text-4xl font-black text-cyan-300">5</p>
                    <p class="mt-2 text-sm text-slate-300">Jenis produk terpal</p>
                </div>
                <div class="rounded-3xl bg-white/5 border border-white/10 p-6">
                    <p class="text-4xl font-black text-cyan-300">ID</p>
                    <p class="mt-2 text-sm text-slate-300">Pengiriman ke seluruh Indonesia</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-white" id="galeri">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">Portofolio / Galeri</p>
                <h2 class="text-3xl font-black text-slate-950">Galeri Produk Kami</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @for ($i = 1; $i <= 8; $i++)
                    <div
                        class="aspect-4/3 rounded-3xl border border-slate-200 bg-[linear-gradient(135deg,rgba(15,23,42,0.2),rgba(14,165,233,0.12)),linear-gradient(120deg,#cbd5e1,#f8fafc)] flex items-end p-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-700 font-semibold">Placeholder</p>
                            <p class="text-sm font-bold text-slate-900">Foto {{ $i }}</p>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </section>

    <section class="py-16 bg-slate-50" id="faq">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">FAQ</p>
                <h2 class="text-3xl font-black text-slate-950">Pertanyaan yang sering diajukan</h2>
            </div>
            <div class="space-y-4">
                <details class="group rounded-3xl border border-slate-200 bg-white p-6">
                    <summary
                        class="cursor-pointer list-none flex items-center justify-between gap-4 font-semibold text-slate-900">
                        <span>Apa perbedaan terpal A3, A5, A8, A12, dan A15?</span>
                        <span class="text-slate-400 group-open:rotate-45 transition text-2xl leading-none">+</span>
                    </summary>
                    <p class="mt-4 text-sm leading-7 text-slate-600">Perbedaannya ada pada ketebalan masing-masing jenis
                        terpal, dan A15 adalah yang paling tebal.</p>
                </details>
                <details class="group rounded-3xl border border-slate-200 bg-white p-6">
                    <summary
                        class="cursor-pointer list-none flex items-center justify-between gap-4 font-semibold text-slate-900">
                        <span>Apakah bisa memesan terpal dengan ukuran custom sesuai kebutuhan?</span>
                        <span class="text-slate-400 group-open:rotate-45 transition text-2xl leading-none">+</span>
                    </summary>
                    <p class="mt-4 text-sm leading-7 text-slate-600">Bisa, Anda dapat memesan ukuran custom sesuai
                        kebutuhan proyek atau penggunaan harian.</p>
                </details>
                <details class="group rounded-3xl border border-slate-200 bg-white p-6">
                    <summary
                        class="cursor-pointer list-none flex items-center justify-between gap-4 font-semibold text-slate-900">
                        <span>Bagaimana cara mengetahui total harga pesanan saya?</span>
                        <span class="text-slate-400 group-open:rotate-45 transition text-2xl leading-none">+</span>
                    </summary>
                    <p class="mt-4 text-sm leading-7 text-slate-600">Setelah memesan, total harga akan tampil di ikon
                        keranjang pada akun Anda.</p>
                </details>
                <details class="group rounded-3xl border border-slate-200 bg-white p-6">
                    <summary
                        class="cursor-pointer list-none flex items-center justify-between gap-4 font-semibold text-slate-900">
                        <span>Apakah CV. Tri Jaya melayani pengiriman ke seluruh Indonesia?</span>
                        <span class="text-slate-400 group-open:rotate-45 transition text-2xl leading-none">+</span>
                    </summary>
                    <p class="mt-4 text-sm leading-7 text-slate-600">Ya, kami melayani pengiriman ke seluruh Indonesia.</p>
                </details>
            </div>
        </div>
    </section>

    <section class="py-16 bg-white" id="cara-pemesanan">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">Cara Pemesanan</p>
                <h2 class="text-3xl font-black text-slate-950">Step-by-step visual</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                @php
                    $steps = [
                        ['title' => 'Pilih Jenis Terpal', 'desc' => 'Pilih produk sesuai kebutuhan Anda.'],
                        ['title' => 'Masukkan Ukuran & Jumlah', 'desc' => 'Isi panjang, lebar, dan quantity.'],
                        ['title' => 'Lakukan Checkout', 'desc' => 'Periksa ringkasan pesanan sebelum lanjut.'],
                        ['title' => 'Bayar via Midtrans', 'desc' => 'Pembayaran aman dan terintegrasi.'],
                        ['title' => 'Terpal Dikirim ke Alamat Anda', 'desc' => 'Pesanan diproses dan dikirim.'],
                    ];
                @endphp
                @foreach ($steps as $index => $step)
                    <div class="relative rounded-3xl border border-slate-200 bg-slate-50 p-6">
                        <div
                            class="w-11 h-11 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-black mb-4">
                            {{ $index + 1 }}</div>
                        <h3 class="font-bold text-slate-950 mb-2">{{ $step['title'] }}</h3>
                        <p class="text-sm text-slate-600 leading-6">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
