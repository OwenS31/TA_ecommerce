@extends('layouts.app')

@section('title', 'Riwayat Pesanan - CV. Tri Jaya')

@section('content')
    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">Riwayat Pesanan</p>
                <h1 class="text-3xl font-black text-slate-950">Riwayat pesanan Anda</h1>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Kode</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($orders as $order)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $order->order_code }}</td>
                                    <td class="px-4 py-3">{{ $order->order_date?->format('d M Y H:i') ?: '-' }}</td>
                                    <td class="px-4 py-3">{{ str_replace('_', ' ', $order->order_status) }}</td>
                                    <td class="px-4 py-3">Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-slate-400 text-sm">Rincian tersedia di status pesanan admin</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">Belum ada pesanan.</td>
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
