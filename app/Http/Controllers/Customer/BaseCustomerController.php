<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderAllocationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Midtrans\Config as MidtransConfig;
use Throwable;

abstract class BaseCustomerController extends Controller
{
    protected function availableStockArea(Product $product): float
    {
        return (float) $product->total_stock;
    }

    protected function requestedArea(float $length, float $width, int $quantity): float
    {
        return $length * $width * $quantity;
    }

    protected function formatArea(float $area): string
    {
        return number_format($area, 2, ',', '.');
    }

    protected function cartAreaForProduct(array $cart, int $productId, ?string $ignoreCartKey = null): float
    {
        $area = 0.0;

        foreach ($cart as $cartKey => $item) {
            if ($ignoreCartKey !== null && $cartKey === $ignoreCartKey) {
                continue;
            }

            if ((int) ($item['product_id'] ?? 0) !== $productId) {
                continue;
            }

            $areaPerSheet = (float) ($item['area_per_sheet'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $area += $areaPerSheet * $quantity;
        }

        return $area;
    }

    protected function validateProductStock(Product $product, float $length, float $width, int $quantity, ?array $cart = null, ?string $ignoreCartKey = null): ?string
    {
        $cart = $cart ?? $this->getCart();
        $availableStock = $this->availableStockArea($product);
        $requestedStock = $this->requestedArea($length, $width, $quantity) + $this->cartAreaForProduct($cart, $product->id, $ignoreCartKey);

        if ($requestedStock > $availableStock + 0.00001) {
            $remainingStock = max($availableStock - $this->cartAreaForProduct($cart, $product->id, $ignoreCartKey), 0);

            return 'Stok tidak cukup. Sisa stok tersedia: ' . $this->formatArea($remainingStock) . ' m².';
        }

        return null;
    }

    protected function validateCartStock(?array $cart = null): ?string
    {
        $cart = $cart ?? $this->getCart();

        $groupedByProduct = [];
        foreach ($cart as $cartKey => $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $groupedByProduct[$productId]['area'] = ($groupedByProduct[$productId]['area'] ?? 0.0)
                + ((float) ($item['area_per_sheet'] ?? 0) * (int) ($item['quantity'] ?? 0));
            $groupedByProduct[$productId]['keys'][] = $cartKey;
        }

        foreach ($groupedByProduct as $productId => $data) {
            $product = Product::query()->find($productId);
            if (!$product) {
                continue;
            }

            $availableStock = $this->availableStockArea($product);
            $requestedStock = (float) $data['area'];

            if ($requestedStock > $availableStock + 0.00001) {
                return 'Stok produk ' . $product->name . ' tidak cukup. Sisa stok tersedia: ' . $this->formatArea($availableStock) . ' m².';
            }
        }

        return null;
    }

    protected function cartPayload(): array
    {
        $cart = $this->getCart();
        $cartItems = array_values($cart);
        $subtotal = collect($cartItems)->sum('subtotal');

        $productIds = collect($cartItems)->pluck('product_id')->unique()->filter()->all();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $totalWeightGram = 0;
        foreach ($cartItems as $item) {
            $weightPerSheetGram = 0.0;
            if (isset($item['weight_per_sheet_gram'])) {
                $weightPerSheetGram = (float) $item['weight_per_sheet_gram'];
            } elseif (isset($products[$item['product_id']])) {
                $product = $products[$item['product_id']];
                $weightPerM2 = (float) $product->weight_per_m2;
                $length = (float) ($item['length'] ?? 0);
                $width = (float) ($item['width'] ?? 0);
                $weightPerSheetGram = $length * $width * $weightPerM2;
            }
            $quantity = (int) ($item['quantity'] ?? 0);
            $totalWeightGram += $weightPerSheetGram * $quantity;
        }

        $totalWeightGram = max((int) round($totalWeightGram), 1);

        return [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'grandTotal' => $subtotal,
            'itemCount' => collect($cartItems)->sum('quantity'),
            'totalWeightGram' => $totalWeightGram,
        ];
    }

    protected function getCart(): array
    {
        return request()->session()->get('cart', []);
    }

    protected function saveCart(array $cart): void
    {
        request()->session()->put('cart', $cart);
    }

    protected function cartItemKey(Product $product, float $length, float $width): string
    {
        return 'product-' . $product->id . '-' . str_replace('.', '-', (string) $length) . '-' . str_replace('.', '-', (string) $width);
    }

    protected function buildCartItem(Product $product, float $length, float $width, int $quantity): array
    {
        $areaPerSheet = $length * $width;
        $subtotal = (float) $product->price_per_m2 * $areaPerSheet * $quantity;
        $weightPerSheetGram = $areaPerSheet * (float) ($product->weight_per_m2 ?? 0);

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
            'weight_per_sheet_gram' => $weightPerSheetGram,
        ];
    }

    protected function shippingRegions(): array
    {
        return [];
    }

    protected function shippingServicesByDistance(string $distance): array
    {
        return match ($distance) {};
    }

    protected function paymentMethods(): array
    {
        return [];
    }

    protected function findShippingOption(string $serviceCode): ?array
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

    protected function generateOrderCode(): string
    {
        return 'ORD-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
    }

    protected function decodeOrderNotes(Order $order): array
    {
        if (!$order->notes) {
            return [];
        }

        $decoded = json_decode($order->notes, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function resolveMidtransPaymentMethodLabel(array $payload): ?string
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

    protected function applyMidtransPayloadToOrder(Order $order, array $payload, ?string $notifiedAt = null): void
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

    protected function configureMidtrans(): void
    {
        MidtransConfig::$serverKey = (string) config('services.midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('services.midtrans.is_production', false);
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;
    }

    protected function buildSnapPayload(Order $order): array
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
