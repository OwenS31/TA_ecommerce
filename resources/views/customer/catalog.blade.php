@extends('layouts.app')

@section('title', 'Katalog Produk - CV. Tri Jaya')

@section('content')
    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">Katalog</p>
                <h1 class="text-3xl font-black text-slate-950">Semua Produk Terpal</h1>
                <p class="text-slate-600 mt-2">Pilih produk terpal original sesuai kebutuhan Anda.</p>
            </div>

            <form method="GET" action="{{ route('catalog') }}"
                class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm mb-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-semibold text-slate-700 mb-2">Cari produk</label>
                        <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Cari berdasarkan nama produk"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                    </div>
                    <div>
                        <label for="type" class="block text-sm font-semibold text-slate-700 mb-2">Jenis terpal</label>
                        <select id="type" name="type"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                            <option value="">Semua jenis</option>
                            @foreach ($productTypes as $type)
                                <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="price_range" class="block text-sm font-semibold text-slate-700 mb-2">Harga</label>
                        <select id="price_range" name="price_range"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                            <option value="">Semua harga</option>
                            @foreach ($priceRanges as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['price_range'] ?? '') === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="submit"
                        class="inline-flex items-center px-5 py-3 rounded-full bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition">Terapkan
                        Filter</button>
                    <a href="{{ route('catalog') }}"
                        class="inline-flex items-center px-5 py-3 rounded-full border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">Reset</a>
                    <p class="text-sm text-slate-500">{{ $products->count() }} produk ditemukan</p>
                </div>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($products as $product)
                    <a href="{{ route('product.show', $product) }}" class="group block h-full">
                        <article
                            class="h-full rounded-3xl overflow-hidden border border-slate-200 bg-white shadow-sm transition group-hover:-translate-y-1 group-hover:shadow-lg flex flex-col">
                            @if ($product->image)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image) }}"
                                    alt="{{ $product->name }}" class="h-48 w-full object-cover object-center">
                            @else
                                <div
                                    class="h-48 bg-slate-200 bg-[linear-gradient(135deg,rgba(15,23,42,0.14),rgba(14,165,233,0.22)),url('https://images.unsplash.com/photo-1523413454516-899b543d3fdd?auto=format&fit=crop&w=1000&q=80')] bg-cover bg-center">
                                </div>
                            @endif
                            <div class="p-5 flex-1 flex flex-col">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 class="text-lg font-bold text-slate-950 group-hover:text-cyan-700 transition">
                                            {{ $product->name }}</h2>
                                        <p class="text-sm text-slate-500 mt-1">
                                            {{ $product->description ?: 'Terpal original berkualitas tinggi.' }}</p>
                                    </div>
                                    <span
                                        class="text-xs font-semibold px-2 py-1 rounded-full bg-cyan-100 text-cyan-800">Aktif</span>
                                </div>
                                <div class="mt-4 flex items-center justify-between gap-4">
                                    <p class="text-base font-extrabold text-slate-950">Rp
                                        {{ number_format((float) $product->price_per_m2, 0, ',', '.') }} / m²</p>
                                    <span class="text-sm font-semibold text-blue-600 group-hover:text-blue-800">Pesan
                                        Sekarang</span>
                                </div>
                            </div>
                        </article>
                    </a>
                @empty
                    <div
                        class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                        Data produk belum tersedia.
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
