<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $productId = $request->query('product_id');
        $orderStatus = $request->query('order_status');

        $ordersQuery = Order::query()
            ->with('items')
            ->when($dateFrom, fn($query) => $query->whereDate('order_date', '>=', $dateFrom))
            ->when($dateTo, fn($query) => $query->whereDate('order_date', '<=', $dateTo))
            ->when($orderStatus, fn($query) => $query->where('order_status', $orderStatus))
            ->when($productId, function ($query) use ($productId) {
                $query->whereHas('items', fn($itemQuery) => $itemQuery->where('product_id', $productId));
            });

        $orders = (clone $ordersQuery)
            ->latest('order_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total_transactions' => (clone $ordersQuery)->count(),
            'total_revenue' => (float) (clone $ordersQuery)->sum('total_amount'),
            'average_order_value' => (float) (clone $ordersQuery)->avg('total_amount'),
        ];

        $chartData = $this->buildRevenueChart((clone $ordersQuery), $dateFrom, $dateTo);

        return view('admin.reports.index', [
            'orders' => $orders,
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'statuses' => Order::statusOptions(),
            'filters' => [
                'date_from' => $dateFrom?->toDateString(),
                'date_to' => $dateTo?->toDateString(),
                'product_id' => $productId,
                'order_status' => $orderStatus,
            ],
            'summary' => $summary,
            'chartLabels' => $chartData['labels'],
            'chartValues' => $chartData['values'],
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $productId = $request->query('product_id');
        $orderStatus = $request->query('order_status');

        $orders = Order::query()
            ->with('items')
            ->when($dateFrom, fn($query) => $query->whereDate('order_date', '>=', $dateFrom))
            ->when($dateTo, fn($query) => $query->whereDate('order_date', '<=', $dateTo))
            ->when($orderStatus, fn($query) => $query->where('order_status', $orderStatus))
            ->when($productId, function ($query) use ($productId) {
                $query->whereHas('items', fn($itemQuery) => $itemQuery->where('product_id', $productId));
            })
            ->latest('order_date')
            ->get();

        return response()->streamDownload(function () use ($orders) {
            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Kode Pesanan',
                'Tanggal',
                'Pelanggan',
                'Produk',
                'Status Pesanan',
                'Status Pembayaran',
                'Total (Rp)',
            ]);

            foreach ($orders as $order) {
                $productNames = $order->items->pluck('product_name')->filter()->unique()->implode(' | ');

                fputcsv($output, [
                    $order->order_code,
                    $order->order_date?->format('Y-m-d H:i:s') ?? '',
                    $order->customer_name,
                    $productNames,
                    $order->order_status,
                    $order->payment_status,
                    number_format((float) $order->total_amount, 2, '.', ''),
                ]);
            }

            fclose($output);
        }, 'laporan-penjualan-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveDateRange(Request $request): array
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse((string) $request->query('date_from'))->startOfDay() : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse((string) $request->query('date_to'))->endOfDay() : null;

        if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        return [$dateFrom, $dateTo];
    }

    private function buildRevenueChart($ordersQuery, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $effectiveFrom = $dateFrom ?? Carbon::now()->subDays(29)->startOfDay();
        $effectiveTo = $dateTo ?? Carbon::now()->endOfDay();

        $totalDays = $effectiveFrom->diffInDays($effectiveTo) + 1;
        $groupByDay = $totalDays <= 45;

        if ($groupByDay) {
            $rows = (clone $ordersQuery)
                ->selectRaw('DATE(order_date) as period, COALESCE(SUM(total_amount), 0) as total')
                ->groupBy(DB::raw('DATE(order_date)'))
                ->orderBy(DB::raw('DATE(order_date)'))
                ->pluck('total', 'period');

            $labels = [];
            $values = [];
            $cursor = $effectiveFrom->copy()->startOfDay();
            $end = $effectiveTo->copy()->startOfDay();
            while ($cursor->lte($end)) {
                $key = $cursor->toDateString();
                $labels[] = $cursor->format('d M');
                $values[] = (float) ($rows[$key] ?? 0);
                $cursor->addDay();
            }

            return ['labels' => $labels, 'values' => $values];
        }

        $rows = (clone $ordersQuery)
            ->selectRaw("DATE_FORMAT(order_date, '%Y-%m') as period, COALESCE(SUM(total_amount), 0) as total")
            ->groupBy(DB::raw("DATE_FORMAT(order_date, '%Y-%m')"))
            ->orderBy(DB::raw("DATE_FORMAT(order_date, '%Y-%m')"))
            ->pluck('total', 'period');

        $labels = [];
        $values = [];
        $cursor = $effectiveFrom->copy()->startOfMonth();
        $end = $effectiveTo->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->translatedFormat('M Y');
            $values[] = (float) ($rows[$key] ?? 0);
            $cursor->addMonth();
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
