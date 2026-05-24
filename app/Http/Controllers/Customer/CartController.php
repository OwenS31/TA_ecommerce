<?php

namespace App\Http\Controllers\Customer;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends BaseCustomerController
{
    public function addToCart(Request $request, Product $product)
    {
        abort_unless(Auth::check(), 403);

        $validated = $request->validate([
            'length' => ['required', 'numeric', 'gt:0'],
            'width' => ['required', 'numeric', 'gt:0'],
            'quantity' => ['required', 'integer', 'min:1'],
            'action' => ['nullable', 'in:cart,checkout'],
        ]);

        $cart = $this->getCart();
        $stockError = $this->validateProductStock(
            $product,
            (float) $validated['length'],
            (float) $validated['width'],
            (int) $validated['quantity'],
            $cart
        );

        if ($stockError) {
            return back()->withErrors([
                'stock' => $stockError,
            ])->withInput();
        }

        $itemKey = $this->cartItemKey($product, (float) $validated['length'], (float) $validated['width']);
        $cart[$itemKey] = $this->buildCartItem(
            $product,
            (float) $validated['length'],
            (float) $validated['width'],
            (int) $validated['quantity']
        );

        $this->saveCart($cart);

        if (($validated['action'] ?? 'cart') === 'checkout') {
            return redirect()->route('checkout')->with('status', 'Produk siap dilanjutkan ke checkout.');
        }

        return redirect()->route('cart')->with('status', 'Produk berhasil ditambahkan ke keranjang.');
    }

    public function cart()
    {
        return view('customer.cart', $this->cartPayload());
    }

    public function updateCart(Request $request, string $cartKey)
    {
        abort_unless(Auth::check(), 403);

        $cart = $this->getCart();

        if (!isset($cart[$cartKey])) {
            return back()->withErrors([
                'cart' => 'Item keranjang tidak ditemukan.',
            ]);
        }

        $validated = $request->validate([
            'length' => ['required', 'numeric', 'gt:0'],
            'width' => ['required', 'numeric', 'gt:0'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($cart[$cartKey]['product_id']);
        $stockError = $this->validateProductStock(
            $product,
            (float) $validated['length'],
            (float) $validated['width'],
            (int) $validated['quantity'],
            $cart,
            $cartKey
        );

        if ($stockError) {
            return back()->withErrors([
                'stock' => $stockError,
            ])->withInput();
        }

        $newKey = $this->cartItemKey($product, (float) $validated['length'], (float) $validated['width']);

        unset($cart[$cartKey]);
        $cart[$newKey] = $this->buildCartItem(
            $product,
            (float) $validated['length'],
            (float) $validated['width'],
            (int) $validated['quantity']
        );

        $this->saveCart($cart);

        return back()->with('status', 'Keranjang berhasil diperbarui.');
    }

    public function removeCartItem(string $cartKey)
    {
        abort_unless(Auth::check(), 403);

        $cart = $this->getCart();
        unset($cart[$cartKey]);
        $this->saveCart($cart);

        return back()->with('status', 'Item berhasil dihapus dari keranjang.');
    }
}
