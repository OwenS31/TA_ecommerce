<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Midtrans\Config as MidtransConfig;
use Midtrans\Transaction;
use Midtrans\Snap;
use Throwable;
use App\Services\OrderAllocationService;

class HomeController extends Controller
{
    /**
     * Show the user home / storefront page.
     */
    public function index()
    {
        $featuredProducts = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(5)
            ->get();

        return view('home', [
            'featuredProducts' => $featuredProducts,
        ]);
    }

    public function catalog(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($request->filled('type')) {
            $query->where('name', $request->input('type'));
        }

        if ($request->filled('price_range')) {
            match ($request->input('price_range')) {
                'under_100k' => $query->where('price_per_m2', '<', 100000),
                '100k_250k' => $query->whereBetween('price_per_m2', [100000, 250000]),
                'above_250k' => $query->where('price_per_m2', '>', 250000),
                default => null,
            };
        }

        $products = $query->get();
        $productTypes = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();

        $priceRanges = [
            'under_100k' => 'Di bawah Rp 100.000',
            '100k_250k' => 'Rp 100.000 - Rp 250.000',
            'above_250k' => 'Di atas Rp 250.000',
        ];

        return view('customer.catalog', [
            'products' => $products,
            'productTypes' => $productTypes,
            'priceRanges' => $priceRanges,
            'filters' => $request->only(['search', 'type', 'price_range']),
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return view('customer.product', compact('product'));
    }

    public function addToCart(Request $request, Product $product)
    {
        abort_unless(Auth::check(), 403);

        $validated = $request->validate([
            'length' => ['required', 'numeric', 'gt:0'],
            'width' => ['required', 'numeric', 'gt:0'],
            'quantity' => ['required', 'integer', 'min:1'],
            'action' => ['nullable', 'in:cart,checkout'],
        ]);

        $cart = $this->getCart();
        $itemKey = $this->cartItemKey($product, (float) $validated['length'], (float) $validated['width']);
        $cart[$itemKey] = $this->buildCartItem(
            $product,
            (float) $validated['length'],
            (float) $validated['width'],
            (int) $validated['quantity']
        );

        $this->saveCart($cart);

        if (($validated['action'] ?? 'cart') === 'checkout') {
            return redirect()->route('checkout')->with('status', 'Produk siap dilanjutkan ke checkout.');
        }

        return redirect()->route('cart')->with('status', 'Produk berhasil ditambahkan ke keranjang.');
    }

    public function cart()
    {
        return view('customer.cart', $this->cartPayload());
    }

    public function updateCart(Request $request, string $cartKey)
    {
        abort_unless(Auth::check(), 403);

        $cart = $this->getCart();

        if (!isset($cart[$cartKey])) {
            return back()->withErrors([
                'cart' => 'Item keranjang tidak ditemukan.',
            ]);
        }

        $validated = $request->validate([
            'length' => ['required', 'numeric', 'gt:0'],
            'width' => ['required', 'numeric', 'gt:0'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($cart[$cartKey]['product_id']);
        $newKey = $this->cartItemKey($product, (float) $validated['length'], (float) $validated['width']);

        unset($cart[$cartKey]);
        $cart[$newKey] = $this->buildCartItem(
            $product,
            (float) $validated['length'],
            (float) $validated['width'],
            (int) $validated['quantity']
        );

        $this->saveCart($cart);

        return back()->with('status', 'Keranjang berhasil diperbarui.');
    }

    public function removeCartItem(string $cartKey)
    {
        abort_unless(Auth::check(), 403);

        $cart = $this->getCart();
        unset($cart[$cartKey]);
        $this->saveCart($cart);

        return back()->with('status', 'Item berhasil dihapus dari keranjang.');
    }

    public function checkout()
    {
        $payload = $this->cartPayload();

        if (empty($payload['cartItems'])) {
            return redirect()->route('cart')->withErrors([
                'cart' => 'Keranjang masih kosong. Tambahkan produk terlebih dahulu.',
            ]);
        }

        return view('customer.checkout', array_merge($payload, [
            'shippingRegions' => $this->shippingRegions(),
            'paymentMethods' => $this->paymentMethods(),
        ]));
    }

    public function storeCheckout(Request $request)
    {
        $payload = $this->cartPayload();

        if (empty($payload['cartItems'])) {
            return redirect()->route('cart')->withErrors([
                'cart' => 'Keranjang masih kosong. Tambahkan produk terlebih dahulu.',
            ]);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_province' => ['required', 'string', 'max:100'],
            'shipping_city' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'shipping_service_code' => ['required', 'string', 'max:100'],
        ]);

        $shippingOption = $this->findShippingOption($validated['shipping_service_code']);

        if (!$shippingOption) {
            return back()->withErrors([
                'shipping_service_code' => 'Pilih jasa pengiriman yang valid.',
            ])->withInput();
        }

        $subtotal = $payload['subtotal'];
        $shippingCost = (float) $shippingOption['cost'];
        $totalAmount = $subtotal + $shippingCost;

        $order = DB::transaction(function () use ($validated, $payload, $shippingOption, $subtotal, $shippingCost, $totalAmount) {
            $order = Order::create([
                'order_code' => $this->generateOrderCode(),
                'user_id' => Auth::id(),
                'customer_name' => $validated['customer_name'],
                'customer_email' => Auth::user()->email,
                'customer_phone' => $validated['customer_phone'],
                'shipping_address' => $validated['shipping_address'],
                'shipping_city' => $validated['shipping_city'],
                'shipping_province' => $validated['shipping_province'],
                'postal_code' => $validated['postal_code'] ?? null,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'payment_status' => Order::PAYMENT_MENUNGGU,
                'order_status' => Order::STATUS_MENUNGGU_PEMBAYARAN,
                'order_date' => now(),
                'notes' => json_encode([
                    'shipping_service' => $shippingOption['name'],
                    'shipping_estimate' => $shippingOption['estimate'],
                    'payment_methods' => $this->paymentMethods(),
                    'payment_reference' => 'SNAP-' . strtoupper(Str::random(8)),
                ], JSON_UNESCAPED_UNICODE),
            ]);

            foreach ($payload['cartItems'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'length' => $item['length'],
                    'width' => $item['width'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price_per_m2'],
                    'line_total' => $item['subtotal'],
                ]);
            }

            return $order;
        });

        $request->session()->forget('cart');

        return redirect()->route('orders.show', ['order' => $order, 'pay' => 1])->with('status', 'Pesanan berhasil dibuat. Lanjutkan pembayaran.');
    }

    public function snapToken(Order $order): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        abort_unless($currentUser->isAdmin() || $order->user_id === Auth::id(), 403);

        if ($order->payment_status === Order::PAYMENT_DIBAYAR) {
            return response()->json([
                'message' => 'Pesanan ini sudah dibayar.',
            ], 422);
        }

        if (in_array($order->payment_status, [Order::PAYMENT_GAGAL, Order::PAYMENT_KADALUARSA, Order::PAYMENT_DIBATALKAN], true)) {
            return response()->json([
                'message' => 'Status pembayaran pesanan ini sudah final dan tidak bisa dibayar ulang.',
            ], 422);
        }

        if (!config('services.midtrans.server_key') || !config('services.midtrans.client_key')) {
            return response()->json([
                'message' => 'Konfigurasi Midtrans belum lengkap. Isi MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY.',
            ], 422);
        }

        try {
            $this->configureMidtrans();

            $token = Snap::getSnapToken($this->buildSnapPayload($order));

            $notes = $this->decodeOrderNotes($order);
            $notes['midtrans'] = array_merge($notes['midtrans'] ?? [], [
                'snap_token' => $token,
                'snap_created_at' => now()->toIso8601String(),
            ]);

            $order->update([
                'notes' => json_encode($notes, JSON_UNESCAPED_UNICODE),
            ]);

            return response()->json([
                'token' => $token,
            ]);
        } catch (Throwable $exception) {
            Log::error('Gagal membuat Midtrans Snap token.', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal terhubung ke Midtrans. Coba beberapa saat lagi.',
            ], 500);
        }
    }

    public function midtransNotification(Request $request): JsonResponse
    {
        if (!config('services.midtrans.server_key')) {
            return response()->json([
                'message' => 'MIDTRANS_SERVER_KEY belum dikonfigurasi.',
            ], 503);
        }

        $payload = $request->json()->all();
        if (empty($payload)) {
            $payload = $request->all();
        }

        $orderCode = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $incomingSignature = (string) ($payload['signature_key'] ?? '');

        if ($orderCode === '' || $statusCode === '' || $grossAmount === '' || $incomingSignature === '') {
            return response()->json([
                'message' => 'Payload Midtrans tidak lengkap.',
            ], 422);
        }

        $expectedSignature = hash('sha512', $orderCode . $statusCode . $grossAmount . config('services.midtrans.server_key'));

        if (!hash_equals($expectedSignature, $incomingSignature)) {
            return response()->json([
                'message' => 'Signature Midtrans tidak valid.',
            ], 403);
        }

        $order = Order::query()->where('order_code', $orderCode)->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        $this->applyMidtransPayloadToOrder($order, $payload, now()->toIso8601String());

        return response()->json(['message' => 'OK']);
    }

    public function syncMidtransStatus(Order $order): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        abort_unless($currentUser->isAdmin() || $order->user_id === Auth::id(), 403);

        if (!config('services.midtrans.server_key') || !config('services.midtrans.client_key')) {
            return response()->json([
                'message' => 'Konfigurasi Midtrans belum lengkap.',
            ], 422);
        }

        try {
            $this->configureMidtrans();

            $response = Transaction::status($order->order_code);
            // ensure associative array (nested objects -> arrays)
            $payload = json_decode(json_encode($response), true);
            $this->applyMidtransPayloadToOrder($order, $payload);

            return response()->json([
                'message' => 'OK',
                'transaction_status' => $payload['transaction_status'] ?? null,
                'payment_status' => $order->fresh()->payment_status,
                'order_status' => $order->fresh()->order_status,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Gagal sinkronisasi status Midtrans.', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal mengambil status terbaru dari Midtrans.',
            ], 500);
        }
    }

    public function ordersIndex(Request $request)
    {
        $query = Order::query()
            ->where('user_id', Auth::id())
            ->with('items')
            ->latest('order_date')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('order_status', $request->string('status'));
        }

        $orders = $query->paginate(10)->withQueryString();

        return view('customer.history', [
            'orders' => $orders,
            'statusOptions' => Order::statusOptions(),
            'filters' => $request->only(['status']),
        ]);
    }

    public function showOrder(Order $order)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        abort_unless($currentUser->isAdmin() || $order->user_id === Auth::id(), 403);

        $order->load(['items.product', 'user']);

        $notes = [];
        if ($order->notes) {
            $decodedNotes = json_decode($order->notes, true);
            $notes = is_array($decodedNotes) ? $decodedNotes : [];
        }

        return view('customer.order', [
            'order' => $order,
            'notes' => $notes,
        ]);
    }

    public function profile()
    {
        return view('customer.profile', [
            'user' => Auth::user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        User::query()->whereKey($user->id)->update($validated);

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::query()->whereKey($user->id)->update([
            'password' => $validated['password'],
        ]);

        return back()->with('status', 'Password berhasil diperbarui.');
    }

    private function cartPayload(): array
    {
        $cart = $this->getCart();
        $cartItems = array_values($cart);
        $subtotal = collect($cartItems)->sum('subtotal');

        return [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'grandTotal' => $subtotal,
            'itemCount' => collect($cartItems)->sum('quantity'),
        ];
    }

    private function getCart(): array
    {
        return session()->get('cart', []);
    }

    private function saveCart(array $cart): void
    {
        session()->put('cart', $cart);
    }

    private function cartItemKey(Product $product, float $length, float $width): string
    {
        return 'product-' . $product->id . '-' . str_replace('.', '-', (string) $length) . '-' . str_replace('.', '-', (string) $width);
    }

    private function buildCartItem(Product $product, float $length, float $width, int $quantity): array
    {
        $areaPerSheet = $length * $width;
        $subtotal = (float) $product->price_per_m2 * $areaPerSheet * $quantity;

        return [
            'key' => $this->cartItemKey($product, $length, $width),
            'product_id' => $product->id,
            'product_name' => $product->name,
            'image' => $product->image,
            'price_per_m2' => (float) $product->price_per_m2,
            'length' => $length,
            'width' => $width,
            'quantity' => $quantity,
            'area_per_sheet' => $areaPerSheet,
            'subtotal' => $subtotal,
        ];
    }

    private function shippingRegions(): array
    {
        return [
            'Banten' => [
                ['city' => 'Tangerang', 'services' => $this->shippingServicesByDistance('near')],
                ['city' => 'Serang', 'services' => $this->shippingServicesByDistance('medium')],
            ],
            'DKI Jakarta' => [
                ['city' => 'Jakarta Pusat', 'services' => $this->shippingServicesByDistance('near')],
                ['city' => 'Jakarta Selatan', 'services' => $this->shippingServicesByDistance('near')],
            ],
            'Jawa Barat' => [
                ['city' => 'Bandung', 'services' => $this->shippingServicesByDistance('medium')],
                ['city' => 'Bekasi', 'services' => $this->shippingServicesByDistance('near')],
            ],
            'Jawa Tengah' => [
                ['city' => 'Semarang', 'services' => $this->shippingServicesByDistance('far')],
                ['city' => 'Solo', 'services' => $this->shippingServicesByDistance('far')],
            ],
            'Jawa Timur' => [
                ['city' => 'Surabaya', 'services' => $this->shippingServicesByDistance('far')],
                ['city' => 'Malang', 'services' => $this->shippingServicesByDistance('far')],
            ],
        ];
    }

    private function shippingServicesByDistance(string $distance): array
    {
        return match ($distance) {
            'near' => [
                ['code' => 'jne-reg', 'name' => 'JNE Reguler', 'cost' => 25000, 'estimate' => '2-3 hari'],
                ['code' => 'jnt-eco', 'name' => 'J&T Eco', 'cost' => 22000, 'estimate' => '2-4 hari'],
                ['code' => 'sicepat', 'name' => 'SiCepat Reguler', 'cost' => 24000, 'estimate' => '2-3 hari'],
            ],
            'medium' => [
                ['code' => 'jne-reg', 'name' => 'JNE Reguler', 'cost' => 35000, 'estimate' => '3-4 hari'],
                ['code' => 'jnt-eco', 'name' => 'J&T Eco', 'cost' => 33000, 'estimate' => '3-5 hari'],
                ['code' => 'sicepat', 'name' => 'SiCepat Reguler', 'cost' => 34000, 'estimate' => '3-4 hari'],
            ],
            default => [
                ['code' => 'jne-reg', 'name' => 'JNE Reguler', 'cost' => 45000, 'estimate' => '4-6 hari'],
                ['code' => 'jnt-eco', 'name' => 'J&T Eco', 'cost' => 42000, 'estimate' => '4-6 hari'],
                ['code' => 'sicepat', 'name' => 'SiCepat Reguler', 'cost' => 44000, 'estimate' => '4-6 hari'],
            ],
        };
    }

    private function paymentMethods(): array
    {
        return [
            ['code' => 'va', 'name' => 'Virtual Account'],
            ['code' => 'bank_transfer', 'name' => 'Transfer Bank'],
            ['code' => 'qris', 'name' => 'QRIS'],
            ['code' => 'gopay', 'name' => 'GoPay'],
            ['code' => 'ovo', 'name' => 'OVO'],
            ['code' => 'credit_card', 'name' => 'Kartu Kredit'],
        ];
    }

    private function findShippingOption(string $serviceCode): ?array
    {
        foreach ($this->shippingRegions() as $regionCities) {
            foreach ($regionCities as $cityData) {
                foreach ($cityData['services'] as $service) {
                    if ($service['code'] === $serviceCode) {
                        return $service;
                    }
                }
            }
        }

        return null;
    }

    private function generateOrderCode(): string
    {
        return 'ORD-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
    }

    private function decodeOrderNotes(Order $order): array
    {
        if (!$order->notes) {
            return [];
        }

        $decoded = json_decode($order->notes, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function resolveMidtransPaymentMethodLabel(array $payload): ?string
    {
        $paymentType = (string) ($payload['payment_type'] ?? '');
        $bank = strtolower((string) ($payload['va_numbers'][0]['bank'] ?? $payload['bank'] ?? ''));

        return match ($paymentType) {
            'bank_transfer' => match ($bank) {
                    'bca' => 'BCA VA',
                    'bni' => 'BNI VA',
                    'bri' => 'BRI VA',
                    'permata' => 'Permata VA',
                    default => 'Transfer Bank',
                },
            'qris' => 'QRIS',
            'gopay' => 'GoPay',
            'ovo' => 'OVO',
            'credit_card' => 'Kartu Kredit',
            'cstore' => 'Convenience Store',
            default => null,
        };
    }

    private function applyMidtransPayloadToOrder(Order $order, array $payload, ?string $notifiedAt = null): void
    {
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');

        $paymentStatus = Order::PAYMENT_PENDING;
        $orderStatus = Order::STATUS_MENUNGGU_PEMBAYARAN;
        $paidAt = null;

        if ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept')) {
            $paymentStatus = Order::PAYMENT_DIBAYAR;
            $orderStatus = Order::STATUS_DIBAYAR;
            $paidAt = now();
        } elseif ($transactionStatus === 'expire') {
            $paymentStatus = Order::PAYMENT_KADALUARSA;
            $orderStatus = Order::STATUS_DIBATALKAN;
        } elseif ($transactionStatus === 'cancel') {
            $paymentStatus = Order::PAYMENT_DIBATALKAN;
            $orderStatus = Order::STATUS_DIBATALKAN;
        } elseif (in_array($transactionStatus, ['deny', 'failure'], true)) {
            $paymentStatus = Order::PAYMENT_GAGAL;
            $orderStatus = Order::STATUS_DIBATALKAN;
        } elseif ($transactionStatus === 'pending' || ($transactionStatus === 'capture' && $fraudStatus === 'challenge')) {
            $paymentStatus = Order::PAYMENT_PENDING;
            $orderStatus = Order::STATUS_MENUNGGU_PEMBAYARAN;
        }

        $notes = $this->decodeOrderNotes($order);
        $paymentMethodLabel = $this->resolveMidtransPaymentMethodLabel($payload);
        $notes['midtrans'] = array_merge($notes['midtrans'] ?? [], [
            'transaction_id' => (string) ($payload['transaction_id'] ?? ''),
            'transaction_status' => $transactionStatus,
            'payment_type' => (string) ($payload['payment_type'] ?? ''),
            'bank' => (string) ($payload['va_numbers'][0]['bank'] ?? $payload['bank'] ?? ''),
            'payment_method_label' => $paymentMethodLabel,
            'fraud_status' => $fraudStatus,
            'status_code' => (string) ($payload['status_code'] ?? ''),
            'status_message' => (string) ($payload['status_message'] ?? ''),
            'notified_at' => $notifiedAt ?? now()->toIso8601String(),
        ]);

        $order->update([
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus,
            'paid_at' => $paidAt ?? $order->paid_at,
            'notes' => json_encode($notes, JSON_UNESCAPED_UNICODE),
        ]);

        // If order just moved to paid, run allocation & decrement rolls (idempotent)
        if ($paymentStatus === Order::PAYMENT_DIBAYAR) {
            try {
                $allocationService = new OrderAllocationService();
                $allocationService->allocateForOrder($order->fresh());
            } catch (Throwable $e) {
                Log::error('Gagal melakukan alokasi stok roll untuk pesanan.', [
                    'order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function configureMidtrans(): void
    {
        MidtransConfig::$serverKey = (string) config('services.midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('services.midtrans.is_production', false);
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;
    }

    private function buildSnapPayload(Order $order): array
    {
        $order->loadMissing('items');

        $itemDetails = [];
        foreach ($order->items as $item) {
            $itemDetails[] = [
                'id' => 'ITEM-' . $item->id,
                'price' => (int) round((float) $item->line_total),
                'quantity' => 1,
                'name' => Str::limit($item->product_name . ' ' . $item->length . 'x' . $item->width . 'm x' . $item->quantity, 50, ''),
            ];
        }

        if ((float) $order->shipping_cost > 0) {
            $itemDetails[] = [
                'id' => 'SHIP-' . $order->id,
                'price' => (int) round((float) $order->shipping_cost),
                'quantity' => 1,
                'name' => 'Biaya Pengiriman',
            ];
        }

        return [
            'transaction_details' => [
                'order_id' => $order->order_code,
                'gross_amount' => (int) round((float) $order->total_amount),
            ],
            'customer_details' => [
                'first_name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
                'shipping_address' => [
                    'first_name' => $order->customer_name,
                    'phone' => $order->customer_phone,
                    'address' => $order->shipping_address,
                    'city' => $order->shipping_city,
                    'postal_code' => $order->postal_code,
                    'country_code' => 'IDN',
                ],
            ],
            'item_details' => $itemDetails,
            'enabled_payments' => [
                'bca_va',
                'bni_va',
                'bri_va',
                'permata_va',
                'gopay',
                'qris',
                'credit_card',
                'cstore',
                'bank_transfer',
            ],
        ];
    }
}
