@extends('layouts.admin')

@section('title', 'Optimasi Pemotongan Terpal - CV. Tri Jaya')
@section('page-title', 'Optimasi Pemotongan Terpal')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <p class="text-sm text-gray-600">Hanya pesanan dengan status <span class="font-semibold">Dibayar</span> yang diproses
            untuk pemotongan.</p>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.cutting-optimization.export') }}"
                class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-700">Ekspor CSV</a>
            <button type="button" onclick="window.print()"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Cetak Rekomendasi</button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Pesanan Dibayar</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $orders->count() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Segmen Potong</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $demandsCount }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6 text-sm text-gray-700">
        Total terpakai: <span class="font-semibold">{{ number_format($totalUsed, 2, ',', '.') }} m</span>
        ({{ number_format($totalUsed * $rollWidth, 2, ',', '.') }} m²)
        • Total sisa: <span class="font-semibold">{{ number_format($totalWaste, 2, ',', '.') }} m</span>
        ({{ number_format($totalWaste * $rollWidth, 2, ',', '.') }} m²)
        • Ukuran roll referensi: <span class="font-semibold">{{ $rollWidth }} × {{ $rollLength }} m</span>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Rekomendasi Urutan Pemotongan</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prioritas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pesanan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelanggan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produk</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terpakai (m)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terpakai (m²)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($recommendations as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">#{{ $row['priority_rank'] }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $row['order_code'] }}</td>
                            <td class="px-4 py-3">{{ $row['customer_name'] }}</td>
                            <td class="px-4 py-3">{{ $row['product_name'] }}</td>
                            <td class="px-4 py-3">{{ number_format($row['used_length'], 2, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ number_format($row['used_area'], 2, ',', '.') }}</td>
                            @php $rollForRow = $rolls[$row['roll_number'] - 1] ?? null; @endphp
                            <td class="px-4 py-3">
                                <button type="button" class="detailBtn px-3 py-1 bg-gray-800 text-white rounded text-sm"
                                    data-roll='{{ json_encode($rollForRow ?? [], JSON_HEX_APOS | JSON_HEX_QUOT) }}'
                                    data-row='{{ json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) }}'>Detail</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-400">Belum ada pesanan dibayar yang
                                siap dipotong.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <!-- Modal: Detail Optimasi -->
    <div id="optimizationModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
        <div class="bg-white rounded-lg w-11/12 md:w-2/3 lg:w-1/2 p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Detail Optimasi</h3>
                <button id="modalClose" class="text-gray-600">Tutup</button>
            </div>
            <div class="space-y-3 text-sm text-gray-700">
                <div>
                    <h4 class="font-semibold">Rincian Potongan</h4>
                    <div id="modalDetails" class="text-xs"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Modal handlers
        const modal = document.getElementById('optimizationModal');
        const modalClose = document.getElementById('modalClose');
        const modalDetails = document.getElementById('modalDetails');

        function showModal() {
            modal.classList.remove('hidden');
        }

        function hideModal() {
            modal.classList.add('hidden');
        }

        modalClose.addEventListener('click', hideModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) hideModal();
        });

        document.querySelectorAll('.detailBtn').forEach((btn) => {
            btn.addEventListener('click', () => {
                let roll = {};
                try {
                    roll = JSON.parse(btn.getAttribute('data-roll') || '{}');
                } catch (err) {
                    roll = {};
                }

                const segments = roll.assignments || [];

                // Details: pecah berdasarkan lebar standar terpal (2m), grouped per order
                const STANDARD_ROLL_WIDTH = 2;
                const byOrder = {};
                segments.forEach((s) => {
                    const orderId = s.order_id || 'unknown';
                    const orderCode = s.order_code || 'N/A';
                    const itemLen = Number(s.item_length) || 0;
                    const itemW = Number(s.item_width) || 0;
                    const itemQty = Number(s.item_quantity) || 1;

                    if (!byOrder[orderId]) {
                        byOrder[orderId] = {
                            order_code: orderCode,
                            bySize: {}
                        };
                    }

                    let remainingWidth = itemW;
                    while (remainingWidth > 0) {
                        const pieceWidth = Math.min(STANDARD_ROLL_WIDTH, remainingWidth);
                        const key = `${pieceWidth}×${itemLen}`;
                        byOrder[orderId].bySize[key] = (byOrder[orderId].bySize[key] || 0) +
                            itemQty;
                        remainingWidth -= pieceWidth;
                    }
                });

                let detailsHtml = '<div class="mb-2"><strong>Rincian Potongan</strong>';
                for (const orderId of Object.keys(byOrder)) {
                    const orderData = byOrder[orderId];
                    detailsHtml +=
                        `<div class="mt-3 border-t pt-2"><strong class="text-sm">Pesanan: ${orderData.order_code}</strong><ul class="list-disc pl-5 text-sm">`;
                    for (const k of Object.keys(orderData.bySize)) {
                        detailsHtml += `<li>${k} m sebanyak ${orderData.bySize[k]} lembar</li>`;
                    }
                    detailsHtml += '</ul></div>';
                }
                detailsHtml += '</div>';

                modalDetails.innerHTML = detailsHtml;

                showModal();
            });
        });
    </script>
@endsection
