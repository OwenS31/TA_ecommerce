<?php

namespace App\Http\Controllers\Customer;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Midtrans\Snap;
use Midtrans\Transaction;
use Throwable;

class OrderController extends BaseCustomerController
{
    public function cancelOrder(Order $order): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        abort_unless($currentUser->isAdmin() || $order->user_id === Auth::id(), 403);

        if (!in_array($order->order_status, [Order::STATUS_MENUNGGU_PEMBAYARAN], true)) {
            return back()->withErrors([
                'status' => 'Pesanan hanya bisa dibatalkan saat masih menunggu pembayaran.',
            ]);
        }

        if (in_array($order->payment_status, [Order::PAYMENT_DIBAYAR, Order::PAYMENT_DIBATALKAN, Order::PAYMENT_GAGAL, Order::PAYMENT_KADALUARSA], true)) {
            return back()->withErrors([
                'status' => 'Pesanan ini sudah tidak bisa dibatalkan.',
            ]);
        }

        $notes = $this->decodeOrderNotes($order);
        $notes['customer_cancelled_at'] = now()->toIso8601String();
        $notes['customer_cancelled_by'] = Auth::id();

        $order->update([
            'payment_status' => Order::PAYMENT_DIBATALKAN,
            'order_status' => Order::STATUS_DIBATALKAN,
            'notes' => json_encode($notes, JSON_UNESCAPED_UNICODE),
        ]);

        return back()->with('status', 'Pesanan berhasil dibatalkan.');
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
            'canCancel' => $this->canCustomerCancel($order),
        ]);
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

    private function canCustomerCancel(Order $order): bool
    {
        return $order->order_status === Order::STATUS_MENUNGGU_PEMBAYARAN
            && in_array($order->payment_status, [Order::PAYMENT_MENUNGGU, Order::PAYMENT_PENDING], true);
    }
}
