<?php

namespace App\Http\Controllers\Customer;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends BaseCustomerController
{
    public function catalog(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($request->filled('type')) {
            $query->where('name', $request->input('type'));
        }

        if ($request->filled('price_range')) {
            match ($request->input('price_range')) {
                'under_100k' => $query->where('price_per_m2', '<', 100000),
                '100k_250k' => $query->whereBetween('price_per_m2', [100000, 250000]),
                'above_250k' => $query->where('price_per_m2', '>', 250000),
                default => null,
            };
        }

        $products = $query->get();
        $productTypes = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();

        $priceRanges = [
            'under_100k' => 'Di bawah Rp 100.000',
            '100k_250k' => 'Rp 100.000 - Rp 250.000',
            'above_250k' => 'Di atas Rp 250.000',
        ];

        return view('customer.catalog', [
            'products' => $products,
            'productTypes' => $productTypes,
            'priceRanges' => $priceRanges,
            'filters' => $request->only(['search', 'type', 'price_range']),
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return view('customer.product', compact('product'));
    }
}
