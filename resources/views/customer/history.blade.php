@extends('layouts.app')

@section('title', 'Riwayat Pesanan - CV. Tri Jaya')

@section('content')
    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">Riwayat Pesanan</p>
                <h1 class="text-3xl font-black text-slate-950">Riwayat pesanan Anda</h1>
            </div>

            <form method="GET" action="{{ route('orders.index') }}"
                class="mb-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-end gap-4">
                    <div class="flex-1">
                        <label for="status" class="block text-sm font-semibold text-slate-700 mb-2">Filter status
                            pesanan</label>
                        <select name="status" id="status"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                            <option value="">Semua status</option>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                    {{ str_replace('_', ' ', $status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-full bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition">Terapkan
                        Filter</button>
                </div>
            </form>

            <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">ID Pesanan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Produk</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Total Harga
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status
                                    Pembayaran</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($orders as $order)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $order->order_code }}</td>
                                    <td class="px-4 py-3">{{ $order->order_date?->format('d M Y H:i') ?: '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $order->items->pluck('product_name')->join(', ') }}</td>
                                    <td class="px-4 py-3">Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3">{{ str_replace('_', ' ', $order->payment_status) }}</td>
                                    <td class="px-4 py-3">{{ str_replace('_', ' ', $order->order_status) }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('orders.show', $order) }}"
                                            class="font-semibold text-blue-600 hover:text-blue-800">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-slate-500">Belum ada pesanan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-slate-200">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
