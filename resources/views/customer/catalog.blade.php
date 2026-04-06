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

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($products as $product)
                    <article class="rounded-3xl overflow-hidden border border-slate-200 bg-white shadow-sm">
                        @if ($product->image)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image) }}"
                                alt="{{ $product->name }}" class="h-48 w-full object-cover object-center">
                        @else
                            <div
                                class="h-48 bg-slate-200 bg-[linear-gradient(135deg,rgba(15,23,42,0.14),rgba(14,165,233,0.22)),url('https://images.unsplash.com/photo-1523413454516-899b543d3fdd?auto=format&fit=crop&w=1000&q=80')] bg-cover bg-center">
                            </div>
                        @endif
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-950">{{ $product->name }}</h2>
                                    <p class="text-sm text-slate-500 mt-1">
                                        {{ $product->description ?: 'Terpal original berkualitas tinggi.' }}</p>
                                </div>
                                <span
                                    class="text-xs font-semibold px-2 py-1 rounded-full bg-cyan-100 text-cyan-800">Aktif</span>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <p class="text-base font-extrabold text-slate-950">Rp
                                    {{ number_format((float) $product->price_per_m2, 0, ',', '.') }} / m²</p>
                                <a href="{{ route('login') }}"
                                    class="text-sm font-semibold text-blue-600 hover:text-blue-800">Pesan</a>
                            </div>
                        </div>
                    </article>
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
