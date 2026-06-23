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

    {{-- <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6 text-sm text-gray-700">
        Total terpakai: <span class="font-semibold">{{ number_format($totalUsed, 2, ',', '.') }} m</span>
        ({{ number_format($totalUsed * $rollWidth, 2, ',', '.') }} m²)
        • Total sisa: <span class="font-semibold">{{ number_format($totalWaste, 2, ',', '.') }} m</span>
        ({{ number_format($totalWaste * $rollWidth, 2, ',', '.') }} m²)
        • Ukuran roll referensi: <span class="font-semibold">{{ $rollWidth }} × {{ $rollLength }} m</span>
    </div> --}}

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Rekomendasi Pemotongan Terpal</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nomer</th>
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
                            <td class="px-4 py-3 font-medium text-gray-900">#{{ $loop->iteration }}</td>
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
    <div id="optimizationModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-gray-900 bg-opacity-40">
        <div class="bg-white rounded-lg w-11/12 md:w-2/3 lg:w-1/2 p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Detail Optimasi</h3>
                <button id="modalClose" class="text-gray-600">Tutup</button>
            </div>
            <div class="space-y-3 text-sm text-gray-700">
                <div>
                    <h4 class="font-semibold">Rincian Potongan</h4>
                    <div id="modalDetails" class="text-xs"></div>
                    <div id="cuttingVizInModal" class="mt-3">
                        <div style="width:100%; overflow:auto;">
                            <canvas id="cuttingCanvas" width="1000" height="300"
                                style="border:1px solid #e5e7eb; max-width:100%; height:auto;"></canvas>
                        </div>
                        <div id="cuttingLegend" class="mt-3 grid grid-cols-1 gap-2 text-xs text-gray-700"></div>
                    </div>
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
        const cuttingLegend = document.getElementById('cuttingLegend');

        function buildCuttingViewModel(segments) {
            const standardRollWidth = 2;
            const ordersById = {};

            segments.forEach((segment) => {
                const orderId = segment.order_id || 'unknown';
                const orderCode = segment.order_code || 'N/A';
                const itemLen = Number(segment.item_length) || 0;
                const itemW = Number(segment.item_width) || 0;
                const itemQty = Number(segment.item_quantity) || 1;

                if (!ordersById[orderId]) {
                    ordersById[orderId] = {
                        order_code: orderCode,
                        itemsByLabel: {}
                    };
                }

                let remainingWidth = itemW;
                while (remainingWidth > 0) {
                    const pieceWidth = Math.min(standardRollWidth, remainingWidth);
                    // label ukuran ditulis sebagai lebar × panjang
                    const sizeLabel = `${pieceWidth}×${itemLen}`;

                    if (!ordersById[orderId].itemsByLabel[sizeLabel]) {
                        ordersById[orderId].itemsByLabel[sizeLabel] = {
                            sizeLabel,
                            quantity: 0,
                            width: pieceWidth,
                            length: itemLen
                        };
                    }

                    ordersById[orderId].itemsByLabel[sizeLabel].quantity += itemQty;
                    remainingWidth -= pieceWidth;
                }
            });

            return {
                orders: Object.values(ordersById).map((order) => ({
                    order_code: order.order_code,
                    items: Object.values(order.itemsByLabel)
                }))
            };
        }

        function showModal() {
            modal.classList.add('flex');
            modal.classList.remove('hidden');
        }

        function hideModal() {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        modalClose.addEventListener('click', hideModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) hideModal();
        });

        document.querySelectorAll('.detailBtn').forEach((btn) => {
            btn.addEventListener('click', () => {
                let roll = {};
                let row = {};
                try {
                    roll = JSON.parse(btn.getAttribute('data-roll') || '{}');
                    row = JSON.parse(btn.getAttribute('data-row') || '{}');
                } catch (err) {
                    roll = {};
                    row = {};
                }

                const currentOrderCode = row.order_code || '';

                // Hanya tampilkan assignments dari order_code yang sama dengan baris ini
                const allAssignments = roll.assignments || [];
                const segments = allAssignments.filter(a => (a.order_code || '') === currentOrderCode);

                const viewModel = buildCuttingViewModel(segments);

                let detailsHtml = '<div class="mb-2"><strong>Rincian Potongan</strong>';
                if (viewModel.orders.length === 0) {
                    detailsHtml += '<p class="text-gray-500 mt-2">Tidak ada data potongan untuk pesanan ini.</p>';
                } else {
                    for (const orderData of viewModel.orders) {
                        detailsHtml +=
                            `<div class="mt-3 border-t pt-2"><strong class="text-sm">Pesanan: ${orderData.order_code}</strong><ul class="list-disc pl-5 text-sm">`;
                        for (const item of orderData.items) {
                            detailsHtml += `<li>${item.sizeLabel} m sebanyak ${item.quantity} lembar</li>`;
                        }
                        detailsHtml += '</ul></div>';
                    }
                }
                detailsHtml += '</div>';

                modalDetails.innerHTML = detailsHtml;

                try {
                    renderCutting('cuttingCanvas', viewModel, {{ $rollWidth }}, {{ $rollLength }});
                    renderLegend(cuttingLegend, viewModel);
                } catch (err) {
                    console.error('renderCutting error', err);
                }

                showModal();
            });
        });

        // Rendering visualisasi pemotongan ke canvas
        function renderCutting(canvasId, viewModel, rollWidthMeter, rollLengthMeter) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            // tentukan ukuran canvas dinamis (pixel)
            const maxW = Math.min(1200, Math.max(600, window.innerWidth - 200));
            const maxH = 300;
            canvas.width = maxW;
            canvas.height = maxH;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const padding = 16;
            const usableW = canvas.width - padding * 2;
            const usableH = canvas.height - padding * 2;

            // skala: meter -> pixel
            const scaleX = rollLengthMeter > 0 ? usableW / rollLengthMeter : 1;
            const scaleY = rollWidthMeter > 0 ? usableH / rollWidthMeter : 1;

            // background roll area
            ctx.fillStyle = '#f8fafc';
            ctx.fillRect(padding, padding, usableW, usableH);
            ctx.strokeStyle = '#e5e7eb';
            ctx.strokeRect(padding, padding, usableW, usableH);

            // prepare pieces from the same normalized data used for detailsHtml
            const pieces = [];
            (viewModel.orders || []).forEach((order) => {
                (order.items || []).forEach((item) => {
                    for (let i = 0; i < item.quantity; i++) {
                        pieces.push({
                            order_code: order.order_code,
                            length: Number(item.length) || 0,
                            width: Number(item.width) || 0,
                            sizeLabel: item.sizeLabel
                        });
                    }
                });
            });

            const colors = ['#ef4444', '#f97316', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#06b6d4'];
            const orderColorMap = {};
            let colorIdx = 0;

            // simple packing: row-wise along length, wrap to next row when exceeding length
            let curX = padding;
            let curY = padding;
            let rowMaxH = 0;

            for (let p of pieces) {
                // panjang digambar horizontal, lebar digambar vertikal
                const wPx = Math.max(2, p.length * scaleX);
                const hPx = Math.max(2, p.width * scaleY);

                if (curX + wPx > padding + usableW + 0.0001) {
                    // wrap
                    curX = padding;
                    curY += rowMaxH + 4; // gap
                    rowMaxH = 0;
                }

                // if overflow height, scale down everything to fit
                if (curY + hPx > padding + usableH) {
                    // scale factor to fit vertically
                    const scaleDown = (padding + usableH - padding) / (curY + hPx - padding);
                    ctx.setTransform(scaleDown, 0, 0, scaleDown, 0, 0);
                }

                if (!orderColorMap[p.order_code]) {
                    orderColorMap[p.order_code] = colors[colorIdx % colors.length];
                    colorIdx++;
                }

                ctx.fillStyle = orderColorMap[p.order_code];
                ctx.fillRect(curX, curY, wPx, hPx);
                ctx.strokeStyle = '#ffffff';
                ctx.strokeRect(curX, curY, wPx, hPx);

                // text label
                ctx.fillStyle = '#111827';
                ctx.font = '21px sans-serif';
                ctx.fillText(p.sizeLabel || p.order_code, curX + 4, curY + 12);

                curX += wPx + 2;
                rowMaxH = Math.max(rowMaxH, hPx);
            }

            // size markers
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.fillStyle = '#374151';
            // ctx.fillText('Roll: ' + rollLengthMeter + ' m × ' + rollWidthMeter + ' m', padding, padding - 4);

            canvas.dataset.legendMap = JSON.stringify(orderColorMap);
        }

        function renderLegend(container, viewModel) {
            if (!container) return;

            const canvas = document.getElementById('cuttingCanvas');
            let legendMap = {};
            try {
                legendMap = JSON.parse(canvas?.dataset?.legendMap || '{}');
            } catch (err) {
                legendMap = {};
            }

            const legendItems = (viewModel.orders || []).map((order) => ({
                code: order.order_code,
                color: legendMap[order.order_code] || '#9ca3af'
            }));

            container.innerHTML = legendItems.map((item) => `
                <div class="flex items-center gap-3 rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
                    <span class="h-3 w-3 rounded-sm shrink-0" style="background:${item.color}"></span>
                    <span class="font-medium">${item.code}</span>
                </div>
            `).join('');
        }
    </script>
@endsection
