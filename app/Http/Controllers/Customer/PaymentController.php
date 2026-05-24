<?php

namespace App\Http\Controllers\Customer;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends BaseCustomerController
{
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
}
