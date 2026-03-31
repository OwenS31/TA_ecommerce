<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $todayOrderCount = Order::whereDate('order_date', $today)->count();
        $todayRevenue = (float) Order::whereDate('paid_at', $today)
            ->where('payment_status', Order::PAYMENT_DIBAYAR)
            ->sum('total_amount');

        $totalProducts = Product::where('is_active', true)->count();
        $totalCustomers = User::where('role', User::ROLE_USER)->count();

        $monthlySalesMap = Order::query()
            ->selectRaw('MONTH(paid_at) as month_num, COALESCE(SUM(total_amount), 0) as total')
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', now()->year)
            ->where('payment_status', Order::PAYMENT_DIBAYAR)
            ->groupBy(DB::raw('MONTH(paid_at)'))
            ->pluck('total', 'month_num');

        $monthlySales = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlySales[] = (float) ($monthlySalesMap[$month] ?? 0);
        }

        $recentOrders = Order::query()
            ->latest('order_date')
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'todayOrderCount',
            'todayRevenue',
            'totalProducts',
            'totalCustomers',
            'monthlySales',
            'recentOrders',
        ));
    }
}
