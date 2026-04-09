@extends('layouts.app')

@section('title', $product->name . ' - CV. Tri Jaya')

@section('content')
    @php
        $basePrice = (float) $product->price_per_m2;
        $imageUrl = $product->image
            ? \Illuminate\Support\Facades\Storage::url($product->image)
            : 'https://images.unsplash.com/photo-1523413454516-899b543d3fdd?auto=format&fit=crop&w=1200&q=80';
        $productDescription =
            $product->description ?:
            'Deskripsi lengkap belum diisi. Produk ini tetap siap digunakan untuk kebutuhan terpal harian maupun industri.';
    @endphp

    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('catalog') }}" class="text-sm font-semibold text-cyan-700 hover:text-cyan-800">← Kembali ke
                    katalog</a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                <div class="rounded-4xl overflow-hidden border border-slate-200 bg-white shadow-sm">
                    <img src="{{ $imageUrl }}" alt="{{ $product->name }}"
                        class="w-full aspect-4/3 object-cover object-center">
                </div>

                <div class="space-y-6">
                    <div>
                        <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">Detail Produk</p>
                        <h1 class="text-4xl font-black text-slate-950">{{ $product->name }}</h1>
                        <p class="mt-3 text-2xl font-extrabold text-slate-900">Rp
                            {{ number_format($basePrice, 0, ',', '.') }} / m²</p>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-slate-950 mb-3">Informasi Produk</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-slate-500">Nama jenis terpal</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ $product->name }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-slate-500">Harga per m²</p>
                                <p class="mt-1 font-semibold text-slate-900">Rp {{ number_format($basePrice, 0, ',', '.') }}
                                </p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4 sm:col-span-2">
                                <p class="text-slate-500">Deskripsi lengkap</p>
                                <p class="mt-1 font-medium leading-7 text-slate-700">{{ $productDescription }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-slate-950 mb-4">Kalkulator Harga</h2>
                        <form method="POST" action="{{ route('cart.add', $product) }}" class="space-y-4"
                            id="productOrderForm">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label for="length" class="block text-sm font-semibold text-slate-700 mb-2">Panjang
                                        (meter)</label>
                                    <input id="length" name="length" type="number" min="0" step="0.1"
                                        value="1"
                                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                                </div>
                                <div>
                                    <label for="width" class="block text-sm font-semibold text-slate-700 mb-2">Lebar
                                        (meter)</label>
                                    <input id="width" name="width" type="number" min="0" step="0.1"
                                        value="1"
                                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                                </div>
                                <div>
                                    <label for="quantity" class="block text-sm font-semibold text-slate-700 mb-2">Jumlah
                                        Lembar</label>
                                    <input id="quantity" name="quantity" type="number" min="1" step="1"
                                        value="1"
                                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                                </div>
                            </div>

                            <div class="mt-6 rounded-3xl bg-slate-950 p-6 text-white">
                                <p class="text-sm text-slate-300">Total harga otomatis</p>
                                <p id="totalPrice" class="mt-2 text-3xl font-black">Rp 0</p>
                                <p id="areaText" class="mt-2 text-sm text-slate-300"></p>
                            </div>

                            <div class="mt-6 flex flex-wrap gap-3">
                                <button type="submit" name="action" value="cart"
                                    class="inline-flex items-center px-5 py-3 rounded-full border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">Tambah
                                    ke Keranjang</button>
                                <button type="submit" name="action" value="checkout"
                                    class="inline-flex items-center px-5 py-3 rounded-full bg-cyan-500 text-white text-sm font-semibold hover:bg-cyan-400 transition">Pesan
                                    Sekarang</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        (function() {
            const basePrice = @json($basePrice);
            const lengthInput = document.getElementById('length');
            const widthInput = document.getElementById('width');
            const quantityInput = document.getElementById('quantity');
            const totalPrice = document.getElementById('totalPrice');
            const areaText = document.getElementById('areaText');
            const formatter = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            });

            function calculateTotal() {
                const length = parseFloat(lengthInput.value) || 0;
                const width = parseFloat(widthInput.value) || 0;
                const quantity = parseInt(quantityInput.value, 10) || 0;
                const areaPerSheet = length * width;
                const total = basePrice * areaPerSheet * quantity;

                totalPrice.textContent = formatter.format(total);
                areaText.textContent = `Luas per lembar: ${areaPerSheet.toFixed(2)} m² x ${quantity} lembar`;
            }

            [lengthInput, widthInput, quantityInput].forEach((input) => {
                input.addEventListener('input', calculateTotal);
            });

            calculateTotal();
        })();
    </script>
@endsection
