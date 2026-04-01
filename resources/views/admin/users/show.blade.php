@extends('layouts.admin')

@section('title', 'Detail Pengguna - CV. Tri Jaya')
@section('page-title', 'Detail Pengguna')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-blue-600 hover:text-blue-800"><- Kembali ke daftar
                pengguna</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Profil Pengguna</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-500">Nama</p>
                    <p class="font-medium text-gray-900">{{ $user->name }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Email</p>
                    <p class="font-medium text-gray-900">{{ $user->email }}</p>
                </div>
                <div>
                    <p class="text-gray-500">No. Telepon</p>
                    <p class="font-medium text-gray-900">{{ $user->phone ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Tanggal Daftar</p>
                    <p class="font-medium text-gray-900">{{ $user->created_at?->format('d M Y H:i') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Status Akun</h2>
            @if ($user->is_active)
                <span class="inline-flex px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Aktif</span>
            @else
                <span class="inline-flex px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">Nonaktif</span>
            @endif
            <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" class="mt-4"
                onsubmit="return confirm('Ubah status akun pengguna ini?')">
                @csrf
                @method('PATCH')
                <button type="submit"
                    class="w-full px-4 py-2 text-sm rounded-lg {{ $user->is_active ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-green-600 text-white hover:bg-green-700' }}">
                    {{ $user->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Riwayat Pesanan Pengguna</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produk</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $order->order_code }}</td>
                            <td class="px-4 py-3">{{ $order->order_date?->format('d M Y') ?: '-' }}</td>
                            <td class="px-4 py-3">
                                {{ $order->items->pluck('product_name')->filter()->unique()->implode(', ') ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">
                                    {{ str_replace('_', ' ', $order->order_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}"
                                    class="px-3 py-1.5 text-xs rounded bg-gray-100 text-gray-700 hover:bg-gray-200">Detail
                                    Pesanan</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">Pengguna ini belum memiliki
                                riwayat pesanan.</td>
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
