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
                    @if ($errors->has('stock'))
                        {{ $errors->first('stock') }}
                    @else
                        Silakan lengkapi data checkout dengan benar.
                    @endif
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

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="relative">
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Cari wilayah
                                    kecamatan</label>
                                <div class="flex gap-2">
                                    <input type="text" id="shippingCitySearch"
                                        placeholder="Ketik nama kota (mis. Surabaya)"
                                        class="flex-1 rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                        autocomplete="off">
                                    <button type="button" id="shippingCityCheckBtn"
                                        class="inline-flex items-center justify-center rounded-2xl bg-cyan-500 px-4 py-3 text-sm font-semibold text-white hover:bg-cyan-400 transition">
                                        Cek Ongkir
                                    </button>
                                </div>
                                <div id="shippingCityDropdown"
                                    class="absolute z-50 left-0 right-0 mt-1 max-h-64 overflow-y-auto rounded-2xl border border-slate-300 bg-white shadow-lg hidden">
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="shippingCityId" name="shipping_city" value="">
                        <input type="hidden" id="shippingCityName" name="shipping_city_name" value="">

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Pilih jasa pengiriman</label>
                            <div id="shippingServiceContainer">
                                <p class="text-sm text-slate-500">Loading opsi pengiriman...</p>
                            </div>
                            <input type="hidden" name="shipping_service_code" id="shippingServiceCode" value="">
                            <input type="hidden" name="shipping_courier" id="shippingCourier" value="">
                            <input type="hidden" name="shipping_etd" id="shippingEtd" value="">
                            <input type="hidden" name="shipping_cost" id="shippingCostValue" value="0">
                            <p id="shippingError" class="text-xs text-red-600 mt-2" style="display: none;"></p>
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

                    <button type="submit" form="checkoutForm"
                        class="inline-flex w-full justify-center px-6 py-3 rounded-full bg-cyan-500 text-white font-semibold hover:bg-cyan-400 transition">Buat
                        Pesanan & Lanjut Bayar</button>

                    <p class="text-xs text-slate-500 leading-6">Setelah pesanan dibuat, pembayaran dibuka lewat Midtrans
                        Snap
                        (sandbox) di halaman detail pesanan.</p>
                </aside>
            </form>
        </div>
    </section>
@endsection

@section('scripts')
    @if ($errors->has('stock'))
        <script>
            alert(@json($errors->first('stock')));
        </script>
    @endif
    <script>
        (function() {
            const citySearchInput = document.getElementById('shippingCitySearch');
            const cityCheckButton = document.getElementById('shippingCityCheckBtn');
            const cityDropdown = document.getElementById('shippingCityDropdown');
            const cityIdField = document.getElementById('shippingCityId');
            const cityNameField = document.getElementById('shippingCityName');
            const postalCodeInput = document.getElementById('shippingPostalCode');
            const shippingServiceContainer = document.getElementById('shippingServiceContainer');
            const shippingServiceCode = document.getElementById('shippingServiceCode');
            const shippingCourier = document.getElementById('shippingCourier');
            const shippingEtd = document.getElementById('shippingEtd');
            const shippingError = document.getElementById('shippingError');
            const shippingCostLabel = document.getElementById('shippingCostLabel');
            const shippingCostValue = document.getElementById('shippingCostValue');
            const totalLabel = document.getElementById('totalLabel');
            const subtotal = @json((float) $subtotal);
            let currentShippingCost = 0;
            let currentShippingOptions = [];
            let searchTimeout;

            const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(value);

            const hideShippingError = () => {
                shippingError.style.display = 'none';
                shippingError.textContent = '';
            };

            const showShippingError = (message) => {
                shippingError.textContent = message;
                shippingError.style.display = 'block';
            };

            const hideDropdown = () => {
                cityDropdown.classList.add('hidden');
                cityDropdown.innerHTML = '';
            };

            const showDropdown = (destinations) => {
                if (destinations.length === 0) {
                    cityDropdown.innerHTML = '<div class="px-4 py-2 text-sm text-slate-500">Tidak ada hasil</div>';
                    cityDropdown.classList.remove('hidden');
                    return;
                }

                cityDropdown.innerHTML = destinations.map((dest) => `
                    <div class="px-4 py-2 cursor-pointer hover:bg-slate-100 border-b border-slate-100 last:border-b-0"
                        data-city-id="${dest.city_id}" data-city-name="${dest.city_name}" data-postal-code="${dest.postal_code || ''}">
                        <p class="text-sm font-medium text-slate-800">${dest.city_name}</p>
                        <p class="text-xs text-slate-500">${dest.province_id || ''}</p>
                    </div>
                `).join('');

                cityDropdown.classList.remove('hidden');

                // Add click handlers to dropdown items
                cityDropdown.querySelectorAll('[data-city-id]').forEach((item) => {
                    item.addEventListener('mousedown', (event) => {
                        event.preventDefault();
                        selectCity(item.dataset.cityId, item.dataset.cityName, item.dataset
                            .postalCode);
                    });
                });
            };

            const searchDestinations = async (query) => {
                if (query.length < 2) {
                    hideDropdown();
                    return;
                }

                try {
                    const response = await fetch(
                        `{{ route('shipping.search-destination') }}?search=${encodeURIComponent(query)}&limit=10`
                    );
                    const data = await response.json();

                    if (data.status !== 'ok' || !Array.isArray(data.data)) {
                        showDropdown([]);
                        return;
                    }

                    showDropdown(data.data);
                } catch (error) {
                    console.error('Error searching destinations:', error);
                    showDropdown([]);
                }
            };

            const selectCity = (cityId, cityName, postalCode) => {
                cityIdField.value = cityId;
                cityNameField.value = cityName;
                citySearchInput.value = cityName;
                postalCodeInput.value = postalCode || '';
                hideDropdown();
                shippingServiceContainer.innerHTML =
                    '<p class="text-sm text-slate-500">Lokasi dipilih. Klik tombol Cek Ongkir untuk melihat pilihan harga.</p>';
                shippingServiceCode.value = '';
                shippingCourier.value = '';
                shippingEtd.value = '';
                shippingCostValue.value = '0';
                currentShippingCost = 0;
                updateTotals();
            };

            const renderShippingOptions = (options) => {
                if (!options.length) {
                    shippingServiceContainer.innerHTML =
                        '<p class="text-sm text-slate-500">Tidak ada pilihan kurir untuk lokasi ini.</p>';
                    shippingServiceCode.value = '';
                    shippingCourier.value = '';
                    shippingEtd.value = '';
                    shippingCostValue.value = '0';
                    currentShippingCost = 0;
                    updateTotals();
                    return;
                }

                const applyShippingOption = (radio) => {
                    if (!radio) {
                        return;
                    }

                    const courier = radio.dataset.courier || 'jne';
                    const service = radio.dataset.service || 'REG';
                    const cost = parseInt(radio.dataset.cost || '0', 10);
                    const etd = radio.dataset.etd || '';

                    shippingServiceCode.value = radio.value || `${courier}-${service}`;
                    shippingCourier.value = courier;
                    shippingEtd.value = etd;
                    shippingCostValue.value = String(cost);
                    currentShippingCost = Number.isFinite(cost) ? cost : 0;
                    updateTotals();
                };

                shippingServiceContainer.innerHTML = options.map((option, index) => {
                    const checked = index === 0 ? 'checked' : '';
                    const etdText = option.etd ? `${option.etd}` : 'Tidak ada estimasi';
                    const courierName = (option.courier || 'jne').toUpperCase();
                    const serviceName = option.service || 'REG';
                    const costLabel = formatCurrency(Number(option.cost || 0));
                    return `
                        <label class="block rounded-2xl border border-slate-200 bg-white p-4 mb-3 cursor-pointer hover:border-cyan-400 hover:bg-cyan-50/40 transition">
                            <div class="flex items-start gap-3">
                                <input type="radio" name="shipping_option" value="${courierName.toLowerCase()}-${serviceName}"
                                    data-courier="${courierName.toLowerCase()}"
                                    data-service="${serviceName}"
                                    data-cost="${option.cost || 0}"
                                    data-etd="${option.etd || ''}"
                                    class="mt-1 h-4 w-4 text-cyan-600 border-slate-300 focus:ring-cyan-500" ${checked}>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="font-semibold text-slate-900">${option.name || courierName} - ${serviceName}</p>
                                            <p class="text-sm text-slate-600">${option.description || ''}</p>
                                            <p class="text-xs text-cyan-700 mt-1 font-semibold">Biaya ongkir ${costLabel}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-slate-950">${costLabel}</p>
                                            <p class="text-xs text-slate-500">${etdText}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </label>
                    `;
                }).join('');

                shippingServiceContainer.querySelectorAll('input[type="radio"][name="shipping_option"]').forEach((
                    radio) => {
                    radio.addEventListener('change', () => {
                        applyShippingOption(radio);
                    });
                });

                const defaultOption = shippingServiceContainer.querySelector(
                    'input[type="radio"][name="shipping_option"]:checked');
                if (defaultOption) {
                    applyShippingOption(defaultOption);
                }
            };

            const calculateShipping = async (cityId) => {
                if (!cityId) {
                    shippingServiceContainer.innerHTML =
                        '<p class="text-sm text-slate-500">Pilih kota terlebih dahulu.</p>';
                    hideShippingError();
                    shippingServiceCode.value = '';
                    shippingCourier.value = '';
                    shippingEtd.value = '';
                    shippingCostValue.value = '0';
                    currentShippingCost = 0;
                    updateTotals();
                    return;
                }

                shippingServiceContainer.innerHTML =
                    '<p class="text-sm text-slate-500">Menghitung biaya pengiriman...</p>';

                try {
                    const response = await fetch('{{ route('shipping.calculate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            destination_city_id: cityId,
                            weight: 1000,
                        }),
                    });

                    const data = await response.json();

                    if (data.status !== 'ok') {
                        showShippingError(data.message || 'Gagal menghitung ongkir. Coba lagi.');
                        shippingServiceContainer.innerHTML = '';
                        shippingServiceCode.value = '';
                        shippingCourier.value = '';
                        shippingEtd.value = '';
                        shippingCostValue.value = '0';
                        currentShippingCost = 0;
                        updateTotals();
                        return;
                    }

                    hideShippingError();

                    currentShippingOptions = Array.isArray(data.data) ? data.data : [];
                    renderShippingOptions(currentShippingOptions);
                } catch (error) {
                    console.error('Error calculating shipping:', error);
                    showShippingError('Gagal menghitung ongkir. Coba beberapa saat lagi.');
                    shippingServiceContainer.innerHTML = '';
                    shippingCostValue.value = '0';
                    currentShippingCost = 0;
                    updateTotals();
                }
            };

            const updateTotals = () => {
                shippingCostLabel.textContent = formatCurrency(currentShippingCost);
                totalLabel.textContent = formatCurrency(subtotal + currentShippingCost);
            };

            // City search input with debounce
            cityCheckButton.addEventListener('click', () => {
                if (!cityIdField.value) {
                    showShippingError('Pilih lokasi dari dropdown terlebih dahulu.');
                    return;
                }

                calculateShipping(cityIdField.value);
            });

            citySearchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchDestinations(e.target.value);
                }, 250);
            });

            citySearchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const query = (citySearchInput.value || '').trim();
                    searchDestinations(query);
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!citySearchInput.contains(e.target) && !cityDropdown.contains(e.target)) {
                    hideDropdown();
                }
            });

            // Add CSRF token to meta tag if not present
            if (!document.querySelector('meta[name="csrf-token"]')) {
                const meta = document.createElement('meta');
                meta.name = 'csrf-token';
                meta.content = document.querySelector('input[name="_token"]').value;
                document.head.appendChild(meta);
            }

            updateTotals();
        })();
    </script>
@endsection
