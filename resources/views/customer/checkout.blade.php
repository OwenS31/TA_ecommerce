@extends('layouts.app')

@section('title', 'Checkout - CV. Tri Jaya')

@section('content')
    @php
        $shippingData = $shippingRegions;
    @endphp

    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">Checkout</p>
                <h1 class="text-3xl font-black text-slate-950">Lengkapi Pengiriman & Pembayaran</h1>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
                    Silakan lengkapi data checkout dengan benar.
                </div>
            @endif

            <form method="POST" action="{{ route('checkout.store') }}" class="grid grid-cols-1 lg:grid-cols-3 gap-8"
                id="checkoutForm">
                @csrf
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
                        <h2 class="text-xl font-bold text-slate-950">Form Pengiriman</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Nama penerima</label>
                                <input type="text" name="customer_name"
                                    value="{{ old('customer_name', auth()->user()->name) }}"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor telepon</label>
                                <input type="text" name="customer_phone"
                                    value="{{ old('customer_phone', auth()->user()->phone) }}"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat lengkap</label>
                            <textarea name="shipping_address" rows="4"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">{{ old('shipping_address') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Provinsi</label>
                                <select name="shipping_province" id="shippingProvince"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                                    <option value="">Pilih provinsi</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Kota/Kabupaten</label>
                                <select name="shipping_city" id="shippingCity"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                                    <option value="">Pilih kota</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Kode pos</label>
                                <input type="text" name="postal_code" value="{{ old('postal_code') }}"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Pilih jasa pengiriman</label>
                            <select name="shipping_service_code" id="shippingService"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                                <option value="">Pilih jasa pengiriman</option>
                            </select>
                            <p class="text-xs text-slate-500 mt-2">Opsi pengiriman akan muncul berdasarkan provinsi dan kota
                                tujuan.</p>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-slate-950 mb-4">Ringkasan Pesanan</h2>
                        <div class="space-y-4">
                            @foreach ($cartItems as $item)
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-semibold text-slate-950">{{ $item['product_name'] }}</p>
                                            <p class="text-sm text-slate-500 mt-1">Ukuran:
                                                {{ number_format((float) $item['length'], 2) }} m x
                                                {{ number_format((float) $item['width'], 2) }} m</p>
                                            <p class="text-sm text-slate-500">Jumlah lembar: {{ $item['quantity'] }}</p>
                                        </div>
                                        <p class="font-bold text-slate-950">Rp
                                            {{ number_format((float) $item['subtotal'], 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <aside class="space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-slate-950">Total Pembayaran</h2>
                        <div class="mt-5 space-y-3 text-sm">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Subtotal produk</span>
                                <span id="subtotalLabel">Rp {{ number_format((float) $subtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Biaya pengiriman</span>
                                <span id="shippingCostLabel">Rp 0</span>
                            </div>
                            <div
                                class="flex items-center justify-between text-base font-bold text-slate-950 border-t border-slate-200 pt-3">
                                <span>Total akhir</span>
                                <span id="totalLabel">Rp {{ number_format((float) $subtotal, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-950 mb-4">Metode Pembayaran</h3>
                        <div class="grid grid-cols-1 gap-3 text-sm">
                            @foreach ($paymentMethods as $method)
                                <div class="rounded-2xl border border-slate-200 px-4 py-3 bg-slate-50">
                                    {{ $method['name'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <button type="button" id="openPaymentModal"
                        class="inline-flex w-full justify-center px-6 py-3 rounded-full bg-cyan-500 text-white font-semibold hover:bg-cyan-400 transition">Bayar
                        Sekarang</button>

                    <p class="text-xs text-slate-500 leading-6">Tombol ini menampilkan popup pembayaran. Untuk Midtrans Snap
                        asli, token dan konfigurasi gateway perlu dihubungkan di server.</p>
                </aside>
            </form>
        </div>
    </section>

    <div id="paymentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 px-4">
        <div class="w-full max-w-lg rounded-4xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-cyan-700 uppercase tracking-[0.2em] mb-2">Popup Pembayaran</p>
                    <h2 class="text-2xl font-black text-slate-950">Midtrans Snap</h2>
                </div>
                <button type="button" id="closePaymentModal"
                    class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
            </div>
            <p class="mt-4 text-sm leading-7 text-slate-600">Di implementasi penuh, modal ini diganti Snap popup resmi dari
                Midtrans. Saat ini Anda bisa memilih jasa pengiriman lalu melanjutkan checkout untuk membuat pesanan
                konfirmasi.</p>
            <div class="mt-6 rounded-3xl bg-slate-50 p-4 text-sm text-slate-600">
                Metode yang didukung: Virtual Account, Transfer Bank, QRIS, GoPay, OVO, dan Kartu Kredit.
            </div>
            <button type="submit" form="checkoutForm"
                class="mt-6 inline-flex w-full justify-center px-6 py-3 rounded-full bg-slate-900 text-white font-semibold hover:bg-slate-800 transition">Lanjutkan
                & Buat Pesanan</button>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            const shippingData = @json($shippingData);
            const provinceSelect = document.getElementById('shippingProvince');
            const citySelect = document.getElementById('shippingCity');
            const serviceSelect = document.getElementById('shippingService');
            const shippingCostLabel = document.getElementById('shippingCostLabel');
            const totalLabel = document.getElementById('totalLabel');
            const subtotal = @json((float) $subtotal);
            const paymentModal = document.getElementById('paymentModal');

            const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(value);

            const populateProvinces = () => {
                Object.keys(shippingData).forEach((province) => {
                    const option = document.createElement('option');
                    option.value = province;
                    option.textContent = province;
                    provinceSelect.appendChild(option);
                });
            };

            const populateCities = () => {
                citySelect.innerHTML = '<option value="">Pilih kota</option>';
                serviceSelect.innerHTML = '<option value="">Pilih jasa pengiriman</option>';

                const province = provinceSelect.value;
                const cities = shippingData[province] || [];

                cities.forEach((cityEntry) => {
                    const option = document.createElement('option');
                    option.value = cityEntry.city;
                    option.textContent = cityEntry.city;
                    option.dataset.services = JSON.stringify(cityEntry.services);
                    citySelect.appendChild(option);
                });
            };

            const populateServices = () => {
                serviceSelect.innerHTML = '<option value="">Pilih jasa pengiriman</option>';
                const selectedCity = citySelect.selectedOptions[0];

                if (!selectedCity || !selectedCity.dataset.services) {
                    return;
                }

                const services = JSON.parse(selectedCity.dataset.services);

                services.forEach((service) => {
                    const option = document.createElement('option');
                    option.value = service.code;
                    option.textContent =
                        `${service.name} - ${formatCurrency(service.cost)} - ${service.estimate}`;
                    option.dataset.cost = service.cost;
                    option.dataset.estimate = service.estimate;
                    serviceSelect.appendChild(option);
                });
            };

            const updateTotals = () => {
                const selectedService = serviceSelect.selectedOptions[0];
                const shippingCost = selectedService && selectedService.dataset.cost ? parseFloat(selectedService
                    .dataset.cost) : 0;
                shippingCostLabel.textContent = formatCurrency(shippingCost);
                totalLabel.textContent = formatCurrency(subtotal + shippingCost);
            };

            document.getElementById('openPaymentModal').addEventListener('click', () => {
                paymentModal.classList.remove('hidden');
                paymentModal.classList.add('flex');
            });

            document.getElementById('closePaymentModal').addEventListener('click', () => {
                paymentModal.classList.add('hidden');
                paymentModal.classList.remove('flex');
            });

            paymentModal.addEventListener('click', (event) => {
                if (event.target === paymentModal) {
                    paymentModal.classList.add('hidden');
                    paymentModal.classList.remove('flex');
                }
            });

            provinceSelect.addEventListener('change', populateCities);
            citySelect.addEventListener('change', populateServices);
            serviceSelect.addEventListener('change', updateTotals);

            populateProvinces();
            updateTotals();
        })();
    </script>
@endsection
