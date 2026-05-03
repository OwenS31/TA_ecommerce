@extends('layouts.admin')

@section('title', 'Manajemen Pesanan - CV. Tri Jaya')
@section('page-title', 'Manajemen Pesanan')

@section('content')
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Status Pesanan</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Semua Status</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs text-gray-500">Tanggal Dari</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500">Tanggal Sampai</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>

            <div class="md:col-span-4 flex items-center gap-2">
                <button type="submit"
                    class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-700">Filter</button>
                <a href="{{ route('admin.orders.index') }}"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID Pesanan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelanggan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis Terpal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ukuran (P×L)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pembayaran</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($orders as $order)
                        @php
                            $firstItem = $order->items->first();
                            $totalSheets = $order->items->sum('quantity');
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $order->order_code }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $order->customer_name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $firstItem?->product_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                @if ($firstItem)
                                    {{ number_format($firstItem->length, 2, ',', '.') }} ×
                                    {{ number_format($firstItem->width, 2, ',', '.') }} m
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $totalSheets }} lembar</td>
                            <td class="px-4 py-3 text-gray-900 font-medium">Rp
                                {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">
                                    {{ $order->paymentMethodLabel() ?? $order->paymentStatusLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">
                                    {{ ucwords(str_replace('_', ' ', $order->order_status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $order->order_date?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.orders.show', $order) }}"
                                    class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-xs hover:bg-blue-100">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-10 text-center text-gray-400">Belum ada pesanan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
@endsection
