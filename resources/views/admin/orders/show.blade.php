@extends('layouts.admin')

@section('title', 'Detail Pesanan - CV. Tri Jaya')
@section('page-title', 'Detail Pesanan')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Kembali ke daftar
            pesanan</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Informasi Pesanan</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">ID Pesanan</p>
                        <p class="font-semibold text-gray-900">{{ $order->order_code }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Tanggal Pesanan</p>
                        <p class="font-semibold text-gray-900">{{ $order->order_date?->format('d M Y H:i') ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Status Pembayaran</p>
                        <p class="font-semibold text-gray-900">{{ ucwords(str_replace('_', ' ', $order->payment_status)) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500">Status Pesanan</p>
                        <p class="font-semibold text-gray-900">{{ ucwords(str_replace('_', ' ', $order->order_status)) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Data Pelanggan & Pengiriman</h2>
                <div class="space-y-2 text-sm text-gray-700">
                    <p><span class="text-gray-500">Nama:</span> {{ $order->customer_name }}</p>
                    <p><span class="text-gray-500">Email:</span> {{ $order->customer_email }}</p>
                    <p><span class="text-gray-500">No. Telepon:</span> {{ $order->customer_phone }}</p>
                    <p><span class="text-gray-500">Alamat:</span> {{ $order->shipping_address }}</p>
                    <p><span class="text-gray-500">Kota/Provinsi:</span> {{ $order->shipping_city ?? '-' }},
                        {{ $order->shipping_province ?? '-' }}</p>
                    <p><span class="text-gray-500">Kode Pos:</span> {{ $order->postal_code ?? '-' }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Rincian Item</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis Terpal
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ukuran</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Harga/m²</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-gray-800">{{ $item->product_name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ number_format($item->length, 2, ',', '.') }} ×
                                        {{ number_format($item->width, 2, ',', '.') }} m</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $item->quantity }} lembar</td>
                                    <td class="px-4 py-3 text-gray-700">Rp
                                        {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">Rp
                                        {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Harga</h2>
                <div class="space-y-2 text-sm text-gray-700">
                    <div class="flex items-center justify-between">
                        <span>Subtotal</span>
                        <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Estimasi Ongkir</span>
                        <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                    </div>
                    <div
                        class="border-t border-gray-200 pt-2 mt-2 flex items-center justify-between font-semibold text-gray-900">
                        <span>Total Akhir</span>
                        <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Aksi Status</h2>

                @if ($order->order_status === \App\Models\Order::STATUS_MENUNGGU_PEMBAYARAN)
                    <form method="POST" action="{{ route('admin.orders.confirm-payment', $order) }}" class="mb-3"
                        onsubmit="return confirm('Konfirmasi pembayaran manual untuk pesanan ini?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="w-full px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">Konfirmasi
                            Pembayaran Manual</button>
                    </form>
                @endif

                @if ($nextStatus)
                    <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="mb-3"
                        onsubmit="return confirm('Ubah status ke {{ ucwords(str_replace('_', ' ', $nextStatus)) }}?')">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $nextStatus }}">
                        <button type="submit"
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                            Ubah ke {{ ucwords(str_replace('_', ' ', $nextStatus)) }}
                        </button>
                    </form>
                @endif

                @if (!in_array($order->order_status, [\App\Models\Order::STATUS_SELESAI, \App\Models\Order::STATUS_DIBATALKAN], true))
                    <form method="POST" action="{{ route('admin.orders.update-status', $order) }}"
                        onsubmit="return confirm('Batalkan pesanan ini?')">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ \App\Models\Order::STATUS_DIBATALKAN }}">
                        <button type="submit"
                            class="w-full px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Batalkan
                            Pesanan</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
