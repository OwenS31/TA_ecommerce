@extends('layouts.admin')

@section('title', 'Laporan Penjualan - CV. Tri Jaya')
@section('page-title', 'Laporan Penjualan')

@section('content')
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" action="{{ route('admin.reports.index') }}"
            class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Jenis Terpal</label>
                <select name="product_id" id="product_id"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Semua Produk</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((string) $filters['product_id'] === (string) $product->id)>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="order_status" class="block text-sm font-medium text-gray-700 mb-1">Status Pesanan</label>
                <select name="order_status" id="order_status"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['order_status'] === $status)>
                            {{ str_replace('_', ' ', $status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Terapkan</button>
                <a href="{{ route('admin.reports.index') }}"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Reset</a>
            </div>
        </form>
        <div class="mt-3">
            <a href="{{ route('admin.reports.export-excel', request()->query()) }}"
                class="inline-flex px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700">Ekspor Excel
                (CSV)</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Total Transaksi</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['total_transactions'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Total Pendapatan</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Rata-rata Nilai Pesanan</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">Rp
                {{ number_format($summary['average_order_value'], 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Grafik Pendapatan per Periode</h2>
        <div class="relative" style="height: 280px;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Rincian Transaksi</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelanggan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produk</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $order->order_code }}</td>
                            <td class="px-4 py-3">{{ $order->order_date?->format('d M Y H:i') ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $order->customer_name }}</td>
                            <td class="px-4 py-3">
                                {{ $order->items->pluck('product_name')->filter()->unique()->implode(', ') ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">
                                    {{ str_replace('_', ' ', $order->order_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">Tidak ada transaksi pada filter
                                ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $orders->links() }}
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const reportCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(reportCtx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: @json($chartValues),
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.15)',
                    fill: true,
                    tension: 0.28,
                    pointRadius: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + Number(value).toLocaleString('id-ID');
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
@endsection
