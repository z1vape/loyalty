@extends('layouts.app')

@section('title', 'Z1 VapeOS | Buku dan Kartu Stok Produk')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="font-headline-lg text-headline-lg text-on-surface">Buku dan Kartu Stok Produk</h1>
        <p class="text-on-surface-variant text-body-lg">
            Pilih produk untuk memeriksa buku mutasi keluar-masuk barang, saldo awal, dan saldo akhir berjalan.
        </p>
    </div>

    {{-- Filter Card (Exact Inventory Standard Layout) --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 shadow-xs">
        <form action="{{ route('stock-card.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            {{-- 1. Search Input --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Cari Produk</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-base">search</span>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Cari SKU atau Nama Produk..." 
                        class="w-full pl-9 pr-3.5 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary"
                    />
                </div>
            </div>

            {{-- 2. Category Filter --}}
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Kategori</label>
                <select 
                    name="category" 
                    id="stock_card_category" 
                    class="w-full"
                >
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                            {{ $cat }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 3. Variant Filter --}}
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Varian</label>
                <select 
                    name="variant" 
                    id="stock_card_variant" 
                    class="w-full"
                >
                    <option value="">Semua Varian</option>
                    @foreach($variants as $var)
                        <option value="{{ $var }}" {{ request('variant') === $var ? 'selected' : '' }}>
                            {{ $var }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 4. Action Buttons (Inline) --}}
            <div class="flex items-end gap-2">
                <button 
                    type="submit" 
                    class="flex-1 px-4 py-2 bg-secondary text-on-secondary text-xs font-bold rounded-xl hover:opacity-90 active:scale-95 transition-all cursor-pointer h-[38px] flex items-center justify-center gap-1.5 shadow-xs border-0"
                >
                    <span class="material-symbols-outlined text-sm">filter_alt</span>
                    <span>Filter</span>
                </button>
                @if(request('search') || request('category') || request('variant') || request('product_id'))
                    <a 
                        href="{{ route('stock-card.index') }}" 
                        class="px-3.5 py-2 bg-surface-container-low border border-outline-variant text-on-surface text-xs font-semibold rounded-xl hover:bg-surface-container-high transition-all text-center no-underline h-[38px] flex items-center justify-center shrink-0" 
                        title="Reset Filter"
                    >
                        <span>Reset</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Product List Table Card --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5 w-12 text-center">No</th>
                        <th class="px-4 py-3.5 w-12 text-center">Foto</th>
                        <th class="px-4 py-3.5">SKU</th>
                        <th class="px-4 py-3.5">Nama Produk</th>
                        <th class="px-4 py-3.5">Kategori & Varian</th>
                        <th class="px-4 py-3.5 text-right">Harga Beli</th>
                        <th class="px-4 py-3.5 text-right">Harga Jual</th>
                        <th class="px-4 py-3.5 text-center">Sisa Stok</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @forelse($products as $product)
                        @php
                            $isOutOfStock = $product->stock <= 0;
                            $isLowStock = !$isOutOfStock && $product->stock <= $product->min_stock;
                        @endphp
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-4 py-3 text-center font-mono font-bold text-on-surface-variant">
                                {{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="w-8 h-8 rounded-lg bg-surface-container-low border border-outline-variant overflow-hidden flex items-center justify-center mx-auto">
                                    @if($product->photo)
                                        <img src="{{ asset('storage/' . $product->photo) }}" alt="{{ $product->name }}" class="w-full h-full object-contain p-0.5" />
                                    @else
                                        <span class="material-symbols-outlined text-sm text-outline-variant">inventory_2</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono font-bold text-on-surface">
                                {{ $product->sku }}
                            </td>
                            <td class="px-4 py-3 font-bold text-on-surface">
                                <a href="{{ route('inventory.stock-card', $product) }}" class="hover:text-primary transition-colors no-underline text-inherit">
                                    {{ $product->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-on-surface-variant">
                                @if($product->category || $product->variant)
                                    <div class="inline-flex items-center text-xs">
                                        @if($product->category)
                                            <span class="font-bold text-on-surface">{{ $product->category }}</span>
                                        @endif
                                        @if($product->category && $product->variant)
                                            <span class="text-outline-variant font-bold mx-1.5">|</span>
                                        @endif
                                        @if($product->variant)
                                            <span class="text-on-surface-variant font-medium">{{ $product->variant }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-on-surface-variant italic">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-on-surface-variant">
                                Rp {{ number_format($product->purchase_price, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-primary">
                                Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-mono text-xs font-black {{ $isOutOfStock ? 'text-rose-600' : ($isLowStock ? 'text-amber-600' : 'text-primary') }}">
                                    {{ number_format($product->stock, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($isOutOfStock)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Habis
                                    </span>
                                @elseif($isLowStock)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Menipis
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        Aman
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a 
                                    href="{{ route('inventory.stock-card', $product) }}" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container-low border border-outline-variant hover:bg-surface-container-high text-on-surface text-xs font-bold transition shadow-xs no-underline"
                                >
                                    <span class="material-symbols-outlined text-sm text-primary">receipt_long</span>
                                    <span>Buka Kartu Stok</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl text-outline-variant block mb-2">inventory_2</span>
                                <p class="font-bold text-xs text-on-surface">Tidak ada produk ditemukan</p>
                                <p class="text-[11px] text-on-surface-variant mt-0.5">Coba cari dengan kata kunci lain atau periksa filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
        <div class="px-4 py-3.5 border-t border-outline-variant flex flex-col sm:flex-row justify-between items-center gap-3 bg-surface-container-low/40">
            <span class="text-xs text-on-surface-variant font-semibold">
                Menampilkan {{ $products->firstItem() }} – {{ $products->lastItem() }} dari {{ $products->total() }} produk
            </span>
            <div class="pagination-wrap">
                {{ $products->links() }}
            </div>
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof TomSelect !== 'undefined') {
            const catEl = document.getElementById('stock_card_category');
            if (catEl) {
                new TomSelect(catEl, {
                    create: false,
                    allowEmptyOption: true,
                    placeholder: 'Semua Kategori',
                    maxOptions: 100
                });
            }

            const varEl = document.getElementById('stock_card_variant');
            if (varEl) {
                new TomSelect(varEl, {
                    create: false,
                    allowEmptyOption: true,
                    placeholder: 'Semua Varian',
                    maxOptions: 100
                });
            }
        }
    });
</script>
@endpush
