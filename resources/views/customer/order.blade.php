@extends('layouts.app')

@section('title', 'Detail Pesanan - CV. Tri Jaya')

@section('content')
    <section class="py-16 bg-slate-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">Konfirmasi Pesanan</p>
                <h1 class="text-3xl font-black text-slate-950">Order ID: {{ $order->order_code }}</h1>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-slate-500">Status pembayaran</p>
                                <p class="mt-1 font-bold text-slate-900">{{ str_replace('_', ' ', $order->payment_status) }}
                                </p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-slate-500">Status pesanan</p>
                                <p class="mt-1 font-bold text-slate-900">{{ str_replace('_', ' ', $order->order_status) }}
                                </p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-slate-500">Estimasi pengiriman</p>
                                <p class="mt-1 font-bold text-slate-900">{{ $notes['shipping_estimate'] ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-slate-950 mb-4">Rincian Pesanan</h2>
                        <div class="space-y-4">
                            @foreach ($order->items as $item)
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-semibold text-slate-950">{{ $item->product_name }}</p>
                                            <p class="text-sm text-slate-500 mt-1">Ukuran:
                                                {{ number_format((float) $item->length, 2) }} m x
                                                {{ number_format((float) $item->width, 2) }} m</p>
                                            <p class="text-sm text-slate-500">Jumlah lembar: {{ $item->quantity }}</p>
                                            <p class="text-sm text-slate-500">Harga satuan: Rp
                                                {{ number_format((float) $item->unit_price, 0, ',', '.') }} / m²</p>
                                        </div>
                                        <p class="font-bold text-slate-950">Rp
                                            {{ number_format((float) $item->line_total, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <aside class="space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-slate-950">Ringkasan Pembayaran</h2>
                        <div class="mt-5 space-y-3 text-sm">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Subtotal produk</span>
                                <span>Rp {{ number_format((float) $order->subtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Biaya pengiriman</span>
                                <span>Rp {{ number_format((float) $order->shipping_cost, 0, ',', '.') }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between text-base font-bold text-slate-950 border-t border-slate-200 pt-3">
                                <span>Total akhir</span>
                                <span>Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-950 mb-4">Alamat Pengiriman</h3>
                        <p class="text-sm text-slate-600 leading-7">{{ $order->customer_name }}</p>
                        <p class="text-sm text-slate-600 leading-7">{{ $order->customer_phone }}</p>
                        <p class="text-sm text-slate-600 leading-7 mt-3">{{ $order->shipping_address }}</p>
                        <p class="text-sm text-slate-600 leading-7 mt-3">
                            {{ collect([$order->shipping_city, $order->shipping_province, $order->postal_code])->filter()->join(', ') }}
                        </p>
                        <p class="text-sm text-slate-600 leading-7 mt-3">Jasa kirim:
                            {{ $notes['shipping_service'] ?? '-' }}</p>
                    </div>

                    <a href="{{ route('orders.index') }}"
                        class="inline-flex w-full justify-center px-6 py-3 rounded-full bg-slate-900 text-white font-semibold hover:bg-slate-800 transition">Lihat
                        Riwayat Pesanan</a>
                </aside>
            </div>
        </div>
    </section>
@endsection
