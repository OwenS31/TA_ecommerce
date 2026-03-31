<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::query()
            ->with(['items', 'user'])
            ->latest('order_date')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('order_status', $request->string('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->string('date_to'));
        }

        $orders = $query->paginate(10)->withQueryString();
        $statusOptions = Order::statusOptions();

        return view('admin.orders.index', compact('orders', 'statusOptions'));
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'user']);

        return view('admin.orders.show', [
            'order' => $order,
            'nextStatus' => $this->getNextStatus($order->order_status),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', Order::statusOptions())],
        ]);

        $nextStatus = $validated['status'];
        if (!$this->isValidTransition($order->order_status, $nextStatus)) {
            return back()->withErrors([
                'status' => 'Perubahan status tidak valid.',
            ]);
        }

        $payload = ['order_status' => $nextStatus];
        if ($nextStatus === Order::STATUS_DIBAYAR) {
            $payload['payment_status'] = Order::PAYMENT_DIBAYAR;
            $payload['paid_at'] = now();
            $payload['payment_confirmed_at'] = now();
        }

        $order->update($payload);

        return back()->with('status', 'Status pesanan berhasil diperbarui.');
    }

    public function confirmPayment(Order $order): RedirectResponse
    {
        if ($order->order_status !== Order::STATUS_MENUNGGU_PEMBAYARAN) {
            return back()->withErrors([
                'status' => 'Konfirmasi manual hanya untuk pesanan menunggu pembayaran.',
            ]);
        }

        $order->update([
            'payment_status' => Order::PAYMENT_DIBAYAR,
            'order_status' => Order::STATUS_DIBAYAR,
            'payment_confirmed_at' => now(),
            'paid_at' => now(),
        ]);

        return back()->with('status', 'Pembayaran berhasil dikonfirmasi secara manual.');
    }

    private function getNextStatus(string $currentStatus): ?string
    {
        return match ($currentStatus) {
            Order::STATUS_MENUNGGU_PEMBAYARAN => Order::STATUS_DIBAYAR,
            Order::STATUS_DIBAYAR => Order::STATUS_DIPROSES,
            Order::STATUS_DIPROSES => Order::STATUS_DIKIRIM,
            Order::STATUS_DIKIRIM => Order::STATUS_SELESAI,
            default => null,
        };
    }

    private function isValidTransition(string $from, string $to): bool
    {
        if ($to === Order::STATUS_DIBATALKAN && $from !== Order::STATUS_SELESAI) {
            return true;
        }

        return $this->getNextStatus($from) === $to;
    }
}
