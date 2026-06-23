<?php

namespace App\Http\Controllers\Customer;

use App\Models\Product;
use App\Models\Order;

class HomeController extends BaseCustomerController
{
    /**
     * Show the user home / storefront page.
     */
    public function index()
    {
        $featuredProducts = Product::query()
            ->where('is_active', true)
            ->select('products.*')
            ->selectSub(function ($query) {
                $query->from('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->whereColumn('order_items.product_id', 'products.id')
                    ->whereIn('orders.order_status', [
                        Order::STATUS_DIBAYAR,
                        Order::STATUS_DIPROSES,
                        Order::STATUS_DIKIRIM,
                        Order::STATUS_SELESAI,
                    ])
                    ->selectRaw('COALESCE(SUM(order_items.quantity), 0)');
            }, 'total_ordered')
            ->orderBy('total_ordered', 'desc')
            ->limit(5)
            ->get();

        return view('home', [
            'featuredProducts' => $featuredProducts,
        ]);
    }
}
