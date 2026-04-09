@extends('layouts.app')

@section('title', 'Keranjang - CV. Tri Jaya')

@section('content')
    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8 space-y-1">
                <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">Keranjang</p>
                <h1 class="text-3xl font-black text-slate-950">Keranjang Belanja</h1>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('cart'))
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
                    {{ $errors->first('cart') }}
                </div>
            @endif

            @if ($cartItems)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-4">
                        @foreach ($cartItems as $item)
                            <form method="POST" action="{{ route('cart.update', $item['key']) }}"
                                class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4" data-cart-row>
                                @csrf
                                @method('PATCH')
                                <div class="flex items-start gap-4">
                                    <div class="w-24 h-24 rounded-2xl overflow-hidden bg-slate-100 shrink-0">
                                        <img src="{{ $item['image'] ? \Illuminate\Support\Facades\Storage::url($item['image']) : 'https://images.unsplash.com/photo-1523413454516-899b543d3fdd?auto=format&fit=crop&w=600&q=80' }}"
                                            alt="{{ $item['product_name'] }}" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1">
                                        <h2 class="text-lg font-bold text-slate-950">{{ $item['product_name'] }}</h2>
                                        <p class="text-sm text-slate-500 mt-1">Harga satuan: Rp
                                            {{ number_format((float) $item['price_per_m2'], 0, ',', '.') }} / m²</p>
                                        <p class="text-sm text-slate-500 mt-1">Subtotal: <span data-row-subtotal>Rp
                                                {{ number_format((float) $item['subtotal'], 0, ',', '.') }}</span></p>
                                    </div>
                                    <button type="submit" form="remove-{{ $item['key'] }}"
                                        class="text-sm font-semibold text-red-600 hover:text-red-700">Hapus</button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Panjang (m)</label>
                                        <input type="number" name="length" min="0" step="0.1"
                                            value="{{ $item['length'] }}"
                                            class="cart-length w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Lebar (m)</label>
                                        <input type="number" name="width" min="0" step="0.1"
                                            value="{{ $item['width'] }}"
                                            class="cart-width w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Jumlah lembar</label>
                                        <input type="number" name="quantity" min="1" step="1"
                                            value="{{ $item['quantity'] }}"
                                            class="cart-quantity w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-3 pt-2">
                                    <p class="text-sm text-slate-500">Ukuran: <span
                                            data-area>{{ number_format((float) $item['length'], 2) }} x
                                            {{ number_format((float) $item['width'], 2) }}</span> m</p>
                                    <button type="submit"
                                        class="inline-flex items-center px-4 py-2 rounded-full bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition">Simpan
                                        Perubahan</button>
                                </div>
                            </form>

                            <form id="remove-{{ $item['key'] }}" method="POST"
                                action="{{ route('cart.remove', $item['key']) }}">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endforeach
                    </div>

                    <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-fit">
                        <h2 class="text-xl font-bold text-slate-950">Ringkasan Total</h2>
                        <div class="mt-6 space-y-3 text-sm">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Total item</span>
                                <span>{{ $itemCount }}</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Subtotal semua item</span>
                                <span id="cartSubtotal">Rp {{ number_format((float) $subtotal, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="mt-6 rounded-3xl bg-slate-950 p-5 text-white">
                            <p class="text-sm text-slate-300">Total sementara</p>
                            <p class="mt-2 text-3xl font-black" id="cartGrandTotal">Rp
                                {{ number_format((float) $grandTotal, 0, ',', '.') }}</p>
                        </div>

                        <a href="{{ route('checkout') }}"
                            class="inline-flex w-full justify-center mt-6 px-6 py-3 rounded-full bg-cyan-500 text-white font-semibold hover:bg-cyan-400 transition">Lanjut
                            ke Checkout</a>

                        <a href="{{ route('catalog') }}"
                            class="inline-flex w-full justify-center mt-3 px-6 py-3 rounded-full border border-slate-300 text-slate-700 font-semibold hover:bg-slate-50 transition">Tambah
                            Produk Lagi</a>
                    </aside>
                </div>
            @else
                <div class="rounded-4xl border border-slate-200 bg-white p-8 shadow-sm text-center">
                    <div
                        class="w-16 h-16 mx-auto rounded-3xl bg-cyan-100 text-cyan-700 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-black text-slate-950">Keranjang Anda masih kosong</h1>
                    <p class="text-slate-600 mt-3">Silakan pilih produk di katalog untuk mulai menambahkan pesanan.</p>
                    <a href="{{ route('catalog') }}"
                        class="inline-flex mt-6 px-6 py-3 rounded-full bg-slate-900 text-white font-semibold hover:bg-slate-800 transition">Lihat
                        Katalog</a>
                </div>
            @endif
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        (function() {
            const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(value);

            document.querySelectorAll('[data-cart-row]').forEach((form) => {
                const lengthInput = form.querySelector('.cart-length');
                const widthInput = form.querySelector('.cart-width');
                const quantityInput = form.querySelector('.cart-quantity');
                const subtotalLabel = form.querySelector('[data-row-subtotal]');
                const areaLabel = form.querySelector('[data-area]');
                const priceText = form.querySelector('p.text-sm.text-slate-500.mt-1');
                const priceMatch = priceText ? priceText.textContent.match(/Rp\s*([\d.]+)/i) : null;
                const unitPrice = priceMatch ? parseFloat(priceMatch[1].replace(/\./g, '')) : 0;

                const calculate = () => {
                    const length = parseFloat(lengthInput.value) || 0;
                    const width = parseFloat(widthInput.value) || 0;
                    const quantity = parseInt(quantityInput.value, 10) || 0;
                    const subtotal = unitPrice * length * width * quantity;

                    subtotalLabel.textContent = formatCurrency(subtotal);
                    areaLabel.textContent = `${length.toFixed(2)} x ${width.toFixed(2)}`;
                };

                [lengthInput, widthInput, quantityInput].forEach((input) => {
                    input.addEventListener('input', calculate);
                });

                calculate();
            });
        })();
    </script>
@endsection
