<?php

namespace App\Http\Controllers\Customer;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutController extends BaseCustomerController
{
    public function checkout()
    {
        $payload = $this->cartPayload();

        if (empty($payload['cartItems'])) {
            return redirect()->route('cart')->withErrors([
                'cart' => 'Keranjang masih kosong. Tambahkan produk terlebih dahulu.',
            ]);
        }

        if ($stockError = $this->validateCartStock($payload['cartItems'])) {
            return back()->withErrors([
                'stock' => $stockError,
            ])->withInput();
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

        if ($stockError = $this->validateCartStock($payload['cartItems'])) {
            return redirect()->route('cart')->withErrors([
                'stock' => $stockError,
            ]);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_city' => ['required', 'string', 'max:100'],
            'shipping_city_name' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'shipping_service_code' => ['required', 'string'],
            'shipping_courier' => ['required', 'string', 'max:20'],
            'shipping_etd' => ['nullable', 'string'],
            'shipping_cost' => ['required', 'integer', 'min:0'],
        ]);

        $shippingCourier = (string) $validated['shipping_courier'];
        $shippingEtd = (string) ($validated['shipping_etd'] ?? '');
        $shippingCost = (int) $validated['shipping_cost'];
        $shippingServiceLabel = $validated['shipping_service_code'];

        if ($shippingCost <= 0) {
            return back()->withErrors([
                'shipping_service_code' => 'Pilih jasa pengiriman yang valid dan hitung biaya ongkir terlebih dahulu.',
            ])->withInput();
        }

        Log::info('Checkout with RajaOngkir shipping.', [
            'city_id' => $validated['shipping_city'],
            'city_name' => $validated['shipping_city_name'] ?? '',
            'courier' => $shippingCourier,
            'service_code' => $shippingServiceLabel,
            'etd' => $shippingEtd,
            'cost' => $shippingCost,
        ]);

        $subtotal = $payload['subtotal'];
        $totalAmount = $subtotal + $shippingCost;

        $order = DB::transaction(function () use ($validated, $payload, $subtotal, $shippingCost, $totalAmount, $shippingCourier, $shippingEtd, $shippingServiceLabel) {
            $order = Order::create([
                'order_code' => $this->generateOrderCode(),
                'user_id' => Auth::id(),
                'customer_name' => $validated['customer_name'],
                'customer_email' => Auth::user()->email,
                'customer_phone' => $validated['customer_phone'],
                'shipping_address' => $validated['shipping_address'],
                'shipping_city' => $validated['shipping_city_name'] ?? $validated['shipping_city'],
                'shipping_province' => '',
                'postal_code' => $validated['postal_code'] ?? null,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'payment_status' => Order::PAYMENT_MENUNGGU,
                'order_status' => Order::STATUS_MENUNGGU_PEMBAYARAN,
                'order_date' => now(),
                'notes' => json_encode([
                    'shipping_method' => 'rajaongkir',
                    'shipping_courier' => $shippingCourier,
                    'shipping_service' => $shippingServiceLabel,
                    'shipping_etd' => $shippingEtd,
                    'shipping_cost' => $shippingCost,
                    'destination_city_id' => $validated['shipping_city'],
                    // 'payment_methods' => $this->paymentMethods(),
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
}
