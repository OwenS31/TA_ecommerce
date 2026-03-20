<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductRoll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->withSum('rolls', 'area');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $products = $query->latest()->paginate(10)->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price_per_m2' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_active' => ['boolean'],
            'rolls' => ['required', 'array', 'min:1'],
            'rolls.*.length' => ['required', 'numeric', 'min:0.01'],
            'rolls.*.width' => ['required', 'numeric', 'min:0.01'],
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'price_per_m2.required' => 'Harga per m² wajib diisi.',
            'price_per_m2.numeric' => 'Harga harus berupa angka.',
            'rolls.required' => 'Minimal satu roll harus ditambahkan.',
            'rolls.min' => 'Minimal satu roll harus ditambahkan.',
            'rolls.*.length.required' => 'Panjang roll wajib diisi.',
            'rolls.*.width.required' => 'Lebar roll wajib diisi.',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
            }

            $product = Product::create([
                'name' => $validated['name'],
                'price_per_m2' => $validated['price_per_m2'],
                'description' => $validated['description'] ?? null,
                'image' => $imagePath,
                'is_active' => $request->boolean('is_active', true),
            ]);

            foreach ($validated['rolls'] as $roll) {
                $product->rolls()->create([
                    'length' => $roll['length'],
                    'width' => $roll['width'],
                    'area' => $roll['length'] * $roll['width'],
                ]);
            }
        });

        return redirect()->route('admin.products.index')
            ->with('status', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $product->load('rolls');
        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price_per_m2' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_active' => ['boolean'],
            'rolls' => ['required', 'array', 'min:1'],
            'rolls.*.id' => ['nullable', 'integer', 'exists:product_rolls,id'],
            'rolls.*.length' => ['required', 'numeric', 'min:0.01'],
            'rolls.*.width' => ['required', 'numeric', 'min:0.01'],
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'price_per_m2.required' => 'Harga per m² wajib diisi.',
            'price_per_m2.numeric' => 'Harga harus berupa angka.',
            'rolls.required' => 'Minimal satu roll harus ditambahkan.',
            'rolls.min' => 'Minimal satu roll harus ditambahkan.',
            'rolls.*.length.required' => 'Panjang roll wajib diisi.',
            'rolls.*.width.required' => 'Lebar roll wajib diisi.',
        ]);

        DB::transaction(function () use ($validated, $request, $product) {
            if ($request->hasFile('image')) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $product->image = $request->file('image')->store('products', 'public');
            }

            $product->update([
                'name' => $validated['name'],
                'price_per_m2' => $validated['price_per_m2'],
                'description' => $validated['description'] ?? null,
                'image' => $product->image,
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Collect submitted roll IDs to keep
            $submittedRollIds = collect($validated['rolls'])
                ->pluck('id')
                ->filter()
                ->all();

            // Delete rolls not in the submitted list
            $product->rolls()->whereNotIn('id', $submittedRollIds)->delete();

            // Update or create rolls
            foreach ($validated['rolls'] as $rollData) {
                $product->rolls()->updateOrCreate(
                    ['id' => $rollData['id'] ?? null],
                    [
                        'length' => $rollData['length'],
                        'width' => $rollData['width'],
                        'area' => $rollData['length'] * $rollData['width'],
                    ]
                );
            }
        });

        return redirect()->route('admin.products.index')
            ->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('status', 'Produk berhasil dihapus.');
    }
}
