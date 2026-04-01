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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roll</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pesanan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelanggan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produk</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terpakai (m)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terpakai (m²)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sisa Roll (m)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($recommendations as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">#{{ $row['priority_rank'] }}</td>
                            <td class="px-4 py-3">Roll #{{ $row['roll_number'] }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $row['order_code'] }}</td>
                            <td class="px-4 py-3">{{ $row['customer_name'] }}</td>
                            <td class="px-4 py-3">{{ $row['product_name'] }}</td>
                            <td class="px-4 py-3">{{ number_format($row['used_length'], 2, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ number_format($row['used_area'], 2, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ number_format($row['roll_remaining_length'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-gray-400">Belum ada pesanan dibayar yang
                                siap dipotong.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        <h2 class="text-lg font-semibold text-gray-900">Visualisasi Pola Pemotongan (Canvas)</h2>

        @forelse ($rolls as $rollIndex => $roll)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3 text-sm">
                    <p class="font-semibold text-gray-900">Roll #{{ $rollIndex + 1 }} (2m × 100m)</p>
                    <p class="text-gray-600">
                        Terpakai: {{ number_format($roll['used_length'], 2, ',', '.') }}m |
                        Sisa: {{ number_format($roll['remaining_length'], 2, ',', '.') }}m |
                        DP Best Fill: {{ number_format($roll['dp_best_fill_length'], 2, ',', '.') }}m
                    </p>
                </div>
                <canvas class="rollCanvas w-full border border-gray-200 rounded" height="90"
                    data-roll-length="{{ $rollLength }}" data-segments='@json($roll['assignments'])'></canvas>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                Tidak ada visualisasi karena belum ada data pesanan dibayar.
            </div>
        @endforelse
    </div>
@endsection

@section('scripts')
    <script>
        const palette = [
            '#2563eb', '#16a34a', '#dc2626', '#d97706', '#7c3aed', '#0891b2', '#ea580c', '#be123c', '#4f46e5', '#0f766e'
        ];

        document.querySelectorAll('.rollCanvas').forEach((canvas) => {
            const ctx = canvas.getContext('2d');
            const rollLength = Number(canvas.dataset.rollLength || 100);
            const segments = JSON.parse(canvas.dataset.segments || '[]');

            const width = canvas.clientWidth || 800;
            const height = canvas.height;
            canvas.width = width;

            const margin = 20;
            const barY = 40;
            const barHeight = 24;
            const barWidth = width - margin * 2;

            ctx.clearRect(0, 0, width, height);

            ctx.fillStyle = '#f3f4f6';
            ctx.fillRect(margin, barY, barWidth, barHeight);

            let x = margin;
            segments.forEach((seg, idx) => {
                const segmentW = Math.max(1, (Number(seg.used_length) / rollLength) * barWidth);
                const color = palette[idx % palette.length];

                ctx.fillStyle = color;
                ctx.fillRect(x, barY, segmentW, barHeight);

                ctx.fillStyle = '#111827';
                ctx.font = '11px sans-serif';
                const label = seg.order_code;
                if (segmentW > 42) {
                    ctx.fillText(label, x + 4, barY + 16);
                } else {
                    // Tetap beri label untuk semua segmen kecil di area atas bar.
                    ctx.fillText(label, x + 1, barY - 8);
                }

                x += segmentW;
            });

            ctx.fillStyle = '#374151';
            ctx.font = '11px sans-serif';
            ctx.fillText('0m', margin, barY + barHeight + 16);
            ctx.fillText(rollLength + 'm', margin + barWidth - 24, barY + barHeight + 16);
        });
    </script>
@endsection
