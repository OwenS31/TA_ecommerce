<?php

namespace App\Http\Controllers\Customer;

use App\Models\Product;

class HomeController extends BaseCustomerController
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
}
