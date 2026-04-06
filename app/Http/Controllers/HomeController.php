<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

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

    public function catalog()
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('customer.catalog', compact('products'));
    }

    public function history()
    {
        $orders = Order::query()
            ->where('user_id', Auth::id())
            ->with('items')
            ->latest('order_date')
            ->latest('id')
            ->paginate(10);

        return view('customer.history', compact('orders'));
    }

    public function profile()
    {
        return view('customer.profile', [
            'user' => Auth::user(),
        ]);
    }

    public function cart()
    {
        return view('customer.cart');
    }
}
