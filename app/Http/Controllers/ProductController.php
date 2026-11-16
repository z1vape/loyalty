<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use Illuminate\Support\Facades\Gate;

use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use App\Exports\TemplateExport;
use App\Exports\StockCardExport;
use Carbon\Carbon;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('Inventory List');
        $query = Product::query();

        // Search by SKU or Name
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by Low Stock
        if ($request->has('filter') && $request->filter === 'low_stock') {
            $query->whereColumn('stock', '<', 'min_stock');
        }

        // Filter by Category
        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }

        // Filter by Variant
        if ($request->has('variant') && $request->variant != '') {
            $query->where('variant', $request->variant);
        }

        // Sorting
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');
        if (in_array($sort, ['name', 'sku', 'purchase_price', 'selling_price', 'stock'])) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        $products = $query->paginate(10)->withQueryString();

        $categories = Setting::where('type', 'category')->pluck('value');
        $variants = Setting::where('type', 'variant')->pluck('value');

        $stats = [
            'total_products' => Product::count(),
            'total_stock' => (int) Product::sum('stock'),
            'total_asset_value' => (float) (Product::selectRaw('SUM(stock * purchase_price) as total_asset')->value('total_asset') ?? 0),
            'total_retail_value' => (float) (Product::selectRaw('SUM(stock * selling_price) as total_retail')->value('total_retail') ?? 0),
            'low_stock' => Product::whereColumn('stock', '<=', 'min_stock')->count(),
            'total_categories' => Product::whereNotNull('category')->where('category', '!=', '')->distinct()->count('category'),
        ];

        return view('inventory.index', compact('products', 'categories', 'variants', 'stats'));
    }

    public function create()
    {
        Gate::authorize('Inventory Create');
        $categories = Setting::where('type', 'category')->pluck('value');
        $variants = Setting::where('type', 'variant')->pluck('value');
        return view('inventory.create', compact('categories', 'variants'));
    }

    public function store(StoreProductRequest $request)
    {
        Gate::authorize('Inventory Store');
        $validated = $request->validate([
            'sku' => 'required|string|unique:products,sku|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'category' => 'nullable|string|max:100',
            'variant' => 'nullable|string|max:100',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('products', 'public');
        }

        Product::create($validated);

        return redirect()->route('inventory.index')->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        Gate::authorize('Inventory List');
        $product->load(['movements' => function ($q) {
            $q->latest()->take(10);
        }]);
        return view('inventory.show', compact('product'));
    }

    public function edit(Product $product)
    {
        Gate::authorize('Inventory Edit');
        $categories = Setting::where('type', 'category')->pluck('value');
        $variants = Setting::where('type', 'variant')->pluck('value');
        return view('inventory.edit', compact('product', 'categories', 'variants'));
    }

    public function update(StoreProductRequest $request, Product $product)
    {
        Gate::authorize('Inventory Update');
        $validated = $request->validate([
            'sku' => 'required|string|max:50|unique:products,sku,' . $product->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'category' => 'nullable|string|max:100',
            'variant' => 'nullable|string|max:100',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($product->photo) {
                Storage::disk('public')->delete($product->photo);
            }
            $validated['photo'] = $request->file('photo')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()->route('inventory.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        Gate::authorize('Inventory Destroy');
        
        if ($product->photo) {
            Storage::disk('public')->delete($product->photo);
        }
        
        $product->delete();

        return redirect()->route('inventory.index')->with('success', 'Product deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        Gate::authorize('Inventory Destroy');

        if ($request->boolean('delete_all')) {
            $products = Product::all();
            $count = $products->count();

            if ($count === 0) {
                return redirect()->route('inventory.index')->with('error', 'Tidak ada produk untuk dihapus.');
            }

            foreach ($products as $product) {
                if ($product->photo) {
                    Storage::disk('public')->delete($product->photo);
                }
                $product->delete();
            }

            return redirect()->route('inventory.index')->with('success', "Berhasil menghapus seluruh {$count} produk.");
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
        ], [
            'ids.required' => 'Pilih minimal satu produk untuk dihapus.',
            'ids.min' => 'Pilih minimal satu produk untuk dihapus.',
        ]);

        $products = Product::whereIn('id', $validated['ids'])->get();
        $count = $products->count();

        foreach ($products as $product) {
            if ($product->photo) {
                Storage::disk('public')->delete($product->photo);
            }
            $product->delete();
        }

        return redirect()->route('inventory.index')->with('success', "Berhasil menghapus {$count} produk terpilih.");
    }

    public function export(Request $request)
    {
        Gate::authorize('Inventory List');
        $query = Product::query();

        // Re-apply filters for export
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        if ($request->has('filter') && $request->filter === 'low_stock') {
            $query->whereColumn('stock', '<', 'min_stock');
        }
        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }
        if ($request->has('variant') && $request->variant != '') {
            $query->where('variant', $request->variant);
        }
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');
        if (in_array($sort, ['name', 'sku', 'purchase_price', 'selling_price', 'stock'])) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        $products = $query->get();
        return Excel::download(new ProductsExport($products), 'inventory_export_' . date('Y_m_d_H_i_s') . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function importTemplate()
    {
        Gate::authorize('Inventory Create');
        
        return Excel::download(new TemplateExport, 'inventory_import_template.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function importView()
    {
        Gate::authorize('Inventory Create');
        return view('inventory.import');
    }

    public function import(Request $request)
    {
        Gate::authorize('Inventory Create');
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new ProductsImport;
        Excel::import($import, $request->file('file'));
        
        $message = 'Products imported successfully.';
        if ($import->failures()->count() > 0) {
            $message .= ' Note: Some rows were skipped due to duplicate SKU or invalid data.';
        }

        return redirect()->route('inventory.index')->with('success', $message);
    }

    /**
     * Stock Card Hub: If product_id specified, redirect to product stock card.
     * Otherwise, show product selection list and stats.
     */
    public function stockCardIndex(Request $request)
    {
        Gate::authorize('Inventory List');

        if ($request->boolean('direct') && $request->filled('product_id')) {
            $product = Product::findOrFail($request->product_id);
            return redirect()->route('inventory.stock-card', [
                'product' => $product->id,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'type' => $request->type,
            ]);
        }

        $productId = $request->product_id;
        $category = $request->category;
        $variant = $request->variant;
        $search = $request->search;

        $products = Product::when($productId, function($q) use ($productId) {
                $q->where('id', $productId);
            })
            ->when($category, function($q) use ($category) {
                $q->where('category', $category);
            })
            ->when($variant, function($q) use ($variant) {
                $q->where('variant', $variant);
            })
            ->when($search, function($q) use ($search) {
                $q->where(function($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('name', 'asc')
            ->paginate(15)
            ->withQueryString();

        $categories = Setting::where('type', 'category')->pluck('value')
            ->merge(Product::whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $variants = Setting::where('type', 'variant')->pluck('value')
            ->merge(Product::whereNotNull('variant')->where('variant', '!=', '')->distinct()->pluck('variant'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $allProducts = Product::when($category, fn($q) => $q->where('category', $category))
            ->when($variant, fn($q) => $q->where('variant', $variant))
            ->orderBy('name', 'asc')
            ->get(['id', 'sku', 'name', 'stock', 'min_stock', 'category', 'variant']);

        return view('inventory.stock_card_index', compact('products', 'allProducts', 'categories', 'variants'));
    }

    /**
     * Display detailed Stock Card (Stock Ledger) for a specific product.
     */
    public function stockCard(Request $request, Product $product)
    {
        Gate::authorize('Inventory List');

        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to', Carbon::today()->toDateString());
        $type = $request->get('type', 'all');
        $category = $request->get('category');
        $variant = $request->get('variant');

        $ledgerData = $this->buildStockCardData($product, $dateFrom, $dateTo, $type);

        $categories = Setting::where('type', 'category')->pluck('value')
            ->merge(Product::whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $variants = Setting::where('type', 'variant')->pluck('value')
            ->merge(Product::whereNotNull('variant')->where('variant', '!=', '')->distinct()->pluck('variant'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $allProductsQuery = Product::orderBy('name', 'asc');
        if ($category) {
            $allProductsQuery->where('category', $category);
        }
        if ($variant) {
            $allProductsQuery->where('variant', $variant);
        }
        $allProducts = $allProductsQuery->get(['id', 'sku', 'name', 'stock', 'category', 'variant']);

        // Make sure current product is present in allProducts list
        if (!$allProducts->contains('id', $product->id)) {
            $allProducts->prepend($product);
        }

        return view('inventory.stock_card', array_merge($ledgerData, [
            'product' => $product,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'type' => $type,
            'category' => $category,
            'variant' => $variant,
            'categories' => $categories,
            'variants' => $variants,
            'allProducts' => $allProducts,
        ]));
    }

    /**
     * Export Stock Card to Excel.
     */
    public function exportStockCard(Request $request, Product $product)
    {
        Gate::authorize('Inventory List');

        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to', Carbon::today()->toDateString());
        $type = $request->get('type', 'all');

        $ledgerData = $this->buildStockCardData($product, $dateFrom, $dateTo, $type);

        $storeName = $product->store ? $product->store->name : session('active_store_name', 'Toko');
        $cleanSku = preg_replace('/[^A-Za-z0-9_\-]/', '_', $product->sku);
        $fileName = 'kartu-stok-' . $cleanSku . '-' . date('Ymd') . '.xlsx';

        return Excel::download(
            new StockCardExport(
                $product,
                $ledgerData['rows'],
                $ledgerData['openingBalance'],
                $dateFrom,
                $dateTo,
                $storeName
            ),
            $fileName
        );
    }

    /**
     * Helper to compute opening balance, rows, and running balances for a stock card.
     */
    private function buildStockCardData(Product $product, ?string $dateFrom, ?string $dateTo, ?string $type): array
    {
        return app(\App\Services\StockCalculationService::class)->stockCard($product, $dateFrom, $dateTo, $type);
    }
}
