@extends('layouts.admin')

@section('title', 'Edit Produk - CV. Tri Jaya')
@section('page-title', 'Edit Produk')

@section('content')
    <div class="max-w-3xl">
        <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data"
            class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Product Info --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Informasi Produk</h2>

                <div class="space-y-4">
                    {{-- Nama --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Jenis Terpal</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}"
                            required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Harga --}}
                    <div>
                        <label for="price_per_m2" class="block text-sm font-medium text-gray-700 mb-1">Harga per m²
                            (Rupiah)</label>
                        <input type="number" id="price_per_m2" name="price_per_m2"
                            value="{{ old('price_per_m2', $product->price_per_m2) }}" required min="0" step="100"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition @error('price_per_m2') border-red-500 @enderror">
                        @error('price_per_m2')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <textarea id="description" name="description" rows="3"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition @error('description') border-red-500 @enderror">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Foto --}}
                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Foto Produk</label>
                        @if ($product->image)
                            <div class="mb-2 flex items-center gap-3">
                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                                    class="w-20 h-20 rounded-lg object-cover border border-gray-200">
                                <span class="text-xs text-gray-500">Foto saat ini</span>
                            </div>
                        @endif
                        <input type="file" id="image" name="image"
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 @error('image') border-red-500 @enderror">
                        <p class="text-xs text-gray-400 mt-1">Kosongkan jika tidak ingin mengganti foto. Format: JPEG, PNG,
                            WebP. Maks: 2MB.</p>
                        @error('image')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                            {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="is_active" class="text-sm text-gray-700">Produk Aktif</label>
                    </div>
                </div>
            </div>

            {{-- Stock Rolls --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Stok Roll</h2>
                        <p class="text-sm text-gray-500">Kelola roll terpal beserta ukuran panjang x lebar (meter).</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Total Stok</p>
                        <p class="text-lg font-bold text-blue-600" id="totalStock">0.00 m²</p>
                    </div>
                </div>

                <div id="rollsContainer" class="space-y-3">
                    @foreach ($product->rolls as $i => $roll)
                        <div class="roll-row flex items-center gap-3">
                            <input type="hidden" name="rolls[{{ $i }}][id]" value="{{ $roll->id }}">
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">Panjang (m)</label>
                                <input type="number" name="rolls[{{ $i }}][length]"
                                    value="{{ old("rolls.$i.length", $roll->length) }}" required min="0.01"
                                    step="0.01"
                                    class="roll-length w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                            </div>
                            <div class="flex items-end pb-0.5">
                                <span class="text-gray-400 text-lg font-light mt-5">×</span>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">Lebar (m)</label>
                                <input type="number" name="rolls[{{ $i }}][width]"
                                    value="{{ old("rolls.$i.width", $roll->width) }}" required min="0.01"
                                    step="0.01"
                                    class="roll-width w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                            </div>
                            <div class="flex items-end pb-0.5">
                                <span class="text-gray-400 text-sm mt-5">=</span>
                            </div>
                            <div class="w-28">
                                <label class="block text-xs text-gray-500 mb-1">Area</label>
                                <p class="roll-area text-sm font-medium text-gray-900 py-2">
                                    {{ number_format($roll->area, 2) }} m²</p>
                            </div>
                            <div class="flex items-end pb-1">
                                <button type="button" onclick="removeRoll(this)"
                                    class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer"
                                    title="Hapus roll">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @error('rolls')
                    <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                @enderror

                <button type="button" onclick="addRoll()"
                    class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Roll
                </button>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3">
                <button type="submit"
                    class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium cursor-pointer">
                    Perbarui Produk
                </button>
                <a href="{{ route('admin.products.index') }}"
                    class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium">
                    Batal
                </a>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        let rollIndex = {{ $product->rolls->count() }};

        function addRoll() {
            const container = document.getElementById('rollsContainer');
            const row = document.createElement('div');
            row.className = 'roll-row flex items-center gap-3';
            row.innerHTML = `
            <div class="flex-1">
                <label class="block text-xs text-gray-500 mb-1">Panjang (m)</label>
                <input type="number" name="rolls[${rollIndex}][length]" required min="0.01" step="0.01"
                    placeholder="Panjang"
                    class="roll-length w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
            </div>
            <div class="flex items-end pb-0.5">
                <span class="text-gray-400 text-lg font-light mt-5">×</span>
            </div>
            <div class="flex-1">
                <label class="block text-xs text-gray-500 mb-1">Lebar (m)</label>
                <input type="number" name="rolls[${rollIndex}][width]" required min="0.01" step="0.01"
                    placeholder="Lebar"
                    class="roll-width w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
            </div>
            <div class="flex items-end pb-0.5">
                <span class="text-gray-400 text-sm mt-5">=</span>
            </div>
            <div class="w-28">
                <label class="block text-xs text-gray-500 mb-1">Area</label>
                <p class="roll-area text-sm font-medium text-gray-900 py-2">0.00 m²</p>
            </div>
            <div class="flex items-end pb-1">
                <button type="button" onclick="removeRoll(this)"
                    class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer"
                    title="Hapus roll">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        `;
            container.appendChild(row);
            rollIndex++;
            bindRollEvents();
        }

        function removeRoll(btn) {
            const container = document.getElementById('rollsContainer');
            if (container.querySelectorAll('.roll-row').length > 1) {
                btn.closest('.roll-row').remove();
                recalcTotal();
            } else {
                alert('Minimal harus ada satu roll.');
            }
        }

        function recalcTotal() {
            let total = 0;
            document.querySelectorAll('.roll-row').forEach(row => {
                const l = parseFloat(row.querySelector('.roll-length')?.value) || 0;
                const w = parseFloat(row.querySelector('.roll-width')?.value) || 0;
                const area = l * w;
                row.querySelector('.roll-area').textContent = area.toFixed(2) + ' m²';
                total += area;
            });
            document.getElementById('totalStock').textContent = total.toFixed(2) + ' m²';
        }

        function bindRollEvents() {
            document.querySelectorAll('.roll-length, .roll-width').forEach(input => {
                input.removeEventListener('input', recalcTotal);
                input.addEventListener('input', recalcTotal);
            });
        }

        // Init
        bindRollEvents();
        recalcTotal();
    </script>
@endsection
