@extends('layouts.app')

@section('title', 'Daftar Inventaris Produk | InvenTrack')

@section('content')
<div class="flex flex-col gap-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Daftar Inventaris Produk</h1>
            <p class="text-on-surface-variant text-body-lg">Kelola katalog barang, penetapan harga jual, peringatan batas minimum stok, dan ketersediaan fisik.</p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
            <!-- Import Button -->
            <a href="{{ route('inventory.import.view') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base text-on-surface-variant">upload_file</span>
                <span>Import Excel</span>
            </a>

            <!-- Export Button -->
            <a href="{{ route('inventory.export', request()->all()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base text-primary">download</span>
                <span>Export Excel</span>
            </a>

            @can('Inventory Create')
            <!-- Add Product Button -->
            <a href="{{ route('inventory.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base">add_box</span>
                <span>Tambah Produk</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Flash Alert Messages -->
    @if(session('success'))
        <div class="p-4 bg-[#e8f5e9] border border-[#006c49]/30 rounded-2xl flex items-center justify-between gap-3 text-[#006c49]">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-xl">check_circle</span>
                <span class="text-sm font-bold">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="hover:opacity-75 cursor-pointer">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    @if($errors->has('error'))
        <div class="p-4 bg-error-container/40 border border-error/30 rounded-2xl flex items-center justify-between gap-3 text-error">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-xl">error</span>
                <span class="text-sm font-bold">{{ $errors->first('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-error hover:opacity-75 cursor-pointer">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    <!-- Metric Cards (Accounting Style) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <!-- Total Products -->
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-surface-container-high text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">inventory_2</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Produk</p>
                <h3 class="text-lg sm:text-xl font-headline-md font-extrabold text-on-surface mt-0.5 truncate">{{ number_format($stats['total_products'] ?? 0) }}</h3>
                <p class="text-[10px] text-on-surface-variant truncate">{{ $stats['total_categories'] ?? 0 }} Kategori</p>
            </div>
        </div>

        <!-- Total Stock Physical -->
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-secondary-container text-on-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">warehouse</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Stok Fisik</p>
                <h3 class="text-lg sm:text-xl font-headline-md font-extrabold text-on-surface mt-0.5 truncate">{{ number_format($stats['total_stock'] ?? 0, 0, ',', '.') }}</h3>
                <p class="text-[10px] text-on-surface-variant">Unit / Pcs</p>
            </div>
        </div>

        <!-- Total Nilai Aset Modal -->
        <div class="p-5 bg-surface-container-lowest border border-emerald-500/30 bg-emerald-500/[0.02] rounded-2xl shadow-xs flex items-center gap-3.5 border-l-4 border-l-emerald-600 col-span-2 sm:col-span-1">
            <div class="w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider truncate">Total Nilai Aset (HPP)</p>
                <h3 class="text-base sm:text-lg font-headline-md font-extrabold text-emerald-900 font-mono mt-0.5 truncate">
                    Rp {{ number_format($stats['total_asset_value'] ?? 0, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-emerald-700 font-medium truncate">Modal Persediaan</p>
            </div>
        </div>

        <!-- Potensi Nilai Jual -->
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider truncate">Potensi Nilai Jual</p>
                <h3 class="text-base sm:text-lg font-headline-md font-extrabold text-on-surface font-mono mt-0.5 truncate">
                    Rp {{ number_format($stats['total_retail_value'] ?? 0, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-on-surface-variant truncate">Estimasi Bruto</p>
            </div>
        </div>

        <!-- Low Stock Warning -->
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-amber-500/10 text-amber-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">warning_amber</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider truncate">Stok Menipis</p>
                <h3 class="text-lg sm:text-xl font-headline-md font-extrabold text-amber-800 mt-0.5 truncate">{{ $stats['low_stock'] ?? 0 }}</h3>
                <p class="text-[10px] text-amber-700 font-medium truncate">Perlu Restock</p>
            </div>
        </div>
    </div>

    <!-- Separated Filter & Search Card (Accounting Style) -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 shadow-xs">
        <form action="{{ route('inventory.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Cari Produk</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-base">search</span>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Cari SKU atau Nama Produk..." 
                        class="w-full pl-9 pr-3.5 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary"
                    />
                </div>
            </div>

            <!-- Stock Level Filter -->
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Level Stok</label>
                <select 
                    name="filter" 
                    id="filter_stock_level"
                    class="w-full"
                >
                    <option value="">Semua Level Stok</option>
                    <option value="low_stock" {{ request('filter') === 'low_stock' ? 'selected' : '' }}>⚠️ Stok Menipis / Kritis</option>
                </select>
            </div>

            <!-- Category Filter -->
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Kategori</label>
                <select 
                    name="category" 
                    id="filter_category"
                    class="w-full"
                >
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Variant Filter -->
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Varian</label>
                <select 
                    name="variant" 
                    id="filter_variant"
                    class="w-full"
                >
                    <option value="">Semua Varian</option>
                    @foreach($variants as $var)
                        <option value="{{ $var }}" {{ request('variant') === $var ? 'selected' : '' }}>{{ $var }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-secondary text-on-secondary text-xs font-bold rounded-xl hover:opacity-90 active:scale-95 transition-all cursor-pointer h-[38px] flex items-center justify-center gap-1.5 shadow-xs border-0">
                    <span class="material-symbols-outlined text-sm">filter_alt</span>
                    Filter
                </button>
                @if(request('search') || request('filter') || request('category') || request('variant'))
                    <a href="{{ route('inventory.index') }}" class="px-3.5 py-2 bg-surface-container-low border border-outline-variant text-on-surface text-xs font-semibold rounded-xl hover:bg-surface-container-high transition-all text-center no-underline h-[38px] flex items-center justify-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Main Products Table Card (Accounting Style) -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-xs flex flex-col">

        <!-- Bulk Action Bar -->
        <div id="bulkActionBar" class="hidden px-5 py-3.5 bg-[#e8f5e9]/70 border-b border-[#006c49]/20 flex flex-wrap items-center justify-between gap-3 transition-all">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#006c49]/15 text-[#006c49]">
                    <span class="material-symbols-outlined text-base">check_box</span>
                </span>
                <span id="selectedCountBadge" class="text-sm font-bold text-[#006c49]">
                    0 produk dipilih
                </span>

                @if($products->total() > $products->count())
                    <span id="selectAllGlobalPrompt" class="text-xs text-on-surface-variant">
                        &bull;
                        <button type="button" id="btnSelectAllGlobal" class="text-[#006c49] font-bold hover:underline cursor-pointer">
                            Pilih seluruh {{ number_format($products->total(), 0, ',', '.') }} produk di katalog
                        </button>
                    </span>
                    <span id="allGlobalNotice" class="text-xs font-bold text-[#006c49] bg-white px-2.5 py-0.5 rounded-full border border-[#006c49]/30 hidden">
                        ✓ Seluruh {{ number_format($products->total(), 0, ',', '.') }} produk di katalog dipilih
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <button type="button" id="btnCancelSelection" class="px-3.5 py-1.5 rounded-xl border border-outline-variant bg-white hover:bg-surface-container-high text-xs font-semibold text-on-surface-variant transition active:scale-95 shadow-sm cursor-pointer">
                    Batal
                </button>
                @can('Inventory Destroy')
                <button type="button" id="btnDeleteSelected" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-base">delete</span>
                    <span id="btnDeleteSelectedText">Hapus Terpilih</span>
                </button>
                @endcan
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low/60 border-b border-outline-variant text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
                        <!-- Select All Checkbox -->
                        <th class="py-3.5 px-4 w-10 text-center">
                            <input 
                                type="checkbox" 
                                id="selectAllCheckbox" 
                                class="w-4 h-4 rounded border-outline-variant text-[#006c49] focus:ring-[#006c49]/30 cursor-pointer accent-[#006c49] transition"
                                title="Pilih semua di halaman ini"
                            />
                        </th>
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4 w-14 text-center">Foto</th>
                        <th class="py-3.5 px-4">SKU</th>
                        <th class="py-3.5 px-4">Nama Produk</th>
                        <th class="py-3.5 px-4">Kategori & Varian</th>
                        <th class="py-3.5 px-4 text-right">Harga Beli</th>
                        <th class="py-3.5 px-4 text-right">Harga Jual</th>
                        <th class="py-3.5 px-4 text-center">Sisa Stok</th>
                        <th class="py-3.5 px-4 text-center">Ambang Min.</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60 text-sm">
                    @forelse($products as $product)
                        @php
                            $isOutOfStock = $product->stock <= 0;
                            $isLowStock = !$isOutOfStock && $product->stock <= $product->min_stock;
                        @endphp
                        <tr id="product-row-{{ $product->id }}" class="hover:bg-surface-container-low/40 transition-colors group">
                            <!-- Row Checkbox -->
                            <td class="py-3 px-4 text-center">
                                <input 
                                    type="checkbox" 
                                    name="product_ids[]" 
                                    value="{{ $product->id }}" 
                                    data-name="{{ $product->name }}"
                                    class="product-checkbox w-4 h-4 rounded border-outline-variant text-[#006c49] focus:ring-[#006c49]/30 cursor-pointer accent-[#006c49] transition"
                                    title="Pilih {{ $product->name }}"
                                />
                            </td>

                            <!-- Row Number -->
                            <td class="py-3 px-4 text-center font-mono text-xs text-on-surface-variant font-bold">
                                {{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}
                            </td>

                            <!-- Photo Thumbnail -->
                            <td class="py-3 px-4 text-center">
                                <div class="w-10 h-10 rounded-xl overflow-hidden bg-surface-container-high border border-outline-variant/60 flex items-center justify-center shrink-0 mx-auto shadow-inner">
                                    @if($product->photo)
                                        <img src="{{ asset('storage/' . $product->photo) }}" alt="{{ $product->name }}" class="w-full h-full object-contain p-0.5 bg-white" />
                                    @else
                                        <span class="material-symbols-outlined text-lg text-outline-variant">inventory_2</span>
                                    @endif
                                </div>
                            </td>

                            <!-- SKU -->
                            <td class="py-3 px-4">
                                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-md bg-surface-container text-on-surface border border-outline-variant/50">
                                    {{ $product->sku }}
                                </span>
                            </td>

                            <!-- Product Name & Description -->
                            <td class="py-3 px-4">
                                <a href="{{ route('inventory.show', $product) }}" class="font-bold text-on-surface hover:text-[#006c49] transition-colors block">
                                    {{ $product->name }}
                                </a>
                                @if($product->description)
                                    <p class="text-xs text-on-surface-variant truncate max-w-xs mt-0.5" title="{{ $product->description }}">
                                        {{ $product->description }}
                                    </p>
                                @endif
                            </td>

                            <!-- Category & Variant -->
                            <td class="py-3 px-4 whitespace-nowrap">
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

                            <!-- Harga Beli -->
                            <td class="py-3 px-4 text-right font-mono text-xs text-on-surface-variant">
                                Rp {{ number_format($product->purchase_price, 0, ',', '.') }}
                            </td>

                            <!-- Harga Jual -->
                            <td class="py-3 px-4 text-right font-mono text-xs font-bold text-on-surface">
                                Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                            </td>

                            <!-- Current Stock -->
                            <td class="py-3 px-4 text-center">
                                <span class="font-mono text-sm font-extrabold {{ $isOutOfStock ? 'text-rose-600' : ($isLowStock ? 'text-amber-600' : 'text-[#006c49]') }}">
                                    {{ number_format($product->stock, 0, ',', '.') }}
                                </span>
                            </td>

                            <!-- Min Threshold -->
                            <td class="py-3 px-4 text-center font-mono text-xs text-on-surface-variant">
                                {{ number_format($product->min_stock, 0, ',', '.') }}
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3 px-4 text-center">
                                @if($isOutOfStock)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                        Habis
                                    </span>
                                @elseif($isLowStock)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Menipis
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#e8f5e9] text-[#006c49] border border-[#006c49]/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#006c49]"></span>
                                        Aman
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1 opacity-90 group-hover:opacity-100 transition-opacity">
                                    <!-- View Detail -->
                                    <a href="{{ route('inventory.show', $product) }}" class="p-1.5 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-[#006c49] transition" title="Lihat Detail Produk">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                    </a>

                                    @can('Inventory Edit')
                                    <!-- Edit -->
                                    <a href="{{ route('inventory.edit', $product) }}" class="p-1.5 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-[#006c49] transition" title="Edit Produk">
                                        <span class="material-symbols-outlined text-lg">edit</span>
                                    </a>
                                    @endcan

                                    @can('Inventory Destroy')
                                    <!-- Delete -->
                                    <form action="{{ route('inventory.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg hover:bg-rose-50 text-on-surface-variant hover:text-rose-600 transition" title="Hapus Produk">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-14 text-center">
                                <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                    <div class="w-16 h-16 rounded-2xl bg-surface-container-high flex items-center justify-center text-outline-variant mb-4">
                                        <span class="material-symbols-outlined text-3xl">inventory_2</span>
                                    </div>
                                    <h3 class="text-base font-bold text-on-surface">Tidak ada data produk ditemukan</h3>
                                    <p class="text-xs text-on-surface-variant mt-1 text-center">
                                        Coba sesuaikan kata kunci pencarian atau filter yang Anda pilih.
                                    </p>
                                    @can('Inventory Create')
                                    <a href="{{ route('inventory.create') }}" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-[#006c49] text-white hover:bg-[#00553a] font-bold rounded-xl text-xs transition shadow-sm">
                                        <span class="material-symbols-outlined text-base">add</span>
                                        <span>Tambah Produk Baru</span>
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        @if($products->hasPages())
            <div class="p-4 border-t border-outline-variant bg-surface-container-lowest/50">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <div id="bulkDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden transition-opacity">
        <div class="bg-white rounded-2xl border border-outline-variant shadow-2xl max-w-md w-full mx-4 p-6 flex flex-col gap-5 animate-scale-in">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">delete_forever</span>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 id="modalTitle" class="text-lg font-bold text-on-surface">Konfirmasi Hapus Produk</h3>
                    <p id="modalDescription" class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Apakah Anda yakin ingin menghapus produk yang dipilih? Data transaksi dan histori stok produk akan ikut terhapus.
                    </p>
                </div>
            </div>

            <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-rose-600 shrink-0">warning</span>
                <span>Tindakan ini permanen dan tidak dapat dibatalkan.</span>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button type="button" onclick="closeBulkDeleteModal()" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low text-xs font-bold text-on-surface transition cursor-pointer">
                    Batal
                </button>
                <button type="button" id="modalConfirmBtn" onclick="submitBulkDelete()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-base">delete</span>
                    <span id="modalConfirmBtnText">Ya, Hapus</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden form for bulk delete submission -->
    <form id="bulkDeleteForm" action="{{ route('inventory.bulk-destroy') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="delete_all" id="deleteAllInput" value="0">
        <div id="bulkDeleteInputsContainer"></div>
    </form>

</div>

<script>
    let isAllGlobalSelected = false;
    let isModalDeleteAll = false;

    function getProductCheckboxes() {
        return document.querySelectorAll('.product-checkbox');
    }

    function updateBulkUI() {
        const checkboxes = getProductCheckboxes();
        const checkedCheckboxes = Array.from(checkboxes).filter(cb => cb.checked);
        const totalCount = checkboxes.length;
        const checkedCount = checkedCheckboxes.length;
        const selectAllHeader = document.getElementById('selectAllCheckbox');
        const bulkActionBar = document.getElementById('bulkActionBar');
        const selectedCountBadge = document.getElementById('selectedCountBadge');
        const selectAllGlobalPrompt = document.getElementById('selectAllGlobalPrompt');
        const allGlobalNotice = document.getElementById('allGlobalNotice');
        const btnDeleteSelectedText = document.getElementById('btnDeleteSelectedText');

        if (selectAllHeader) {
            if (checkedCount === 0) {
                selectAllHeader.checked = false;
                selectAllHeader.indeterminate = false;
            } else if (checkedCount === totalCount && totalCount > 0) {
                selectAllHeader.checked = true;
                selectAllHeader.indeterminate = false;
            } else {
                selectAllHeader.checked = false;
                selectAllHeader.indeterminate = true;
            }
        }

        // Highlight selected rows
        checkboxes.forEach(cb => {
            const tr = cb.closest('tr');
            if (tr) {
                if (cb.checked) {
                    tr.classList.add('bg-[#006c49]/5');
                } else {
                    tr.classList.remove('bg-[#006c49]/5');
                }
            }
        });

        if (checkedCount > 0 || isAllGlobalSelected) {
            if (bulkActionBar) bulkActionBar.classList.remove('hidden');
            if (isAllGlobalSelected) {
                if (selectedCountBadge) selectedCountBadge.textContent = 'Semua {{ number_format($products->total() ?? 0, 0, ",", ".") }} produk dipilih';
                if (selectAllGlobalPrompt) selectAllGlobalPrompt.classList.add('hidden');
                if (allGlobalNotice) allGlobalNotice.classList.remove('hidden');
                if (btnDeleteSelectedText) btnDeleteSelectedText.textContent = 'Hapus Seluruh Produk';
            } else {
                if (selectedCountBadge) selectedCountBadge.textContent = checkedCount + ' produk dipilih';
                if (selectAllGlobalPrompt) selectAllGlobalPrompt.classList.remove('hidden');
                if (allGlobalNotice) allGlobalNotice.classList.add('hidden');
                if (btnDeleteSelectedText) btnDeleteSelectedText.textContent = 'Hapus Terpilih (' + checkedCount + ')';
            }
        } else {
            if (bulkActionBar) bulkActionBar.classList.add('hidden');
            isAllGlobalSelected = false;
            if (allGlobalNotice) allGlobalNotice.classList.add('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const selectAllHeader = document.getElementById('selectAllCheckbox');
        const btnCancelSelection = document.getElementById('btnCancelSelection');
        const btnDeleteSelected = document.getElementById('btnDeleteSelected');
        const btnSelectAllGlobal = document.getElementById('btnSelectAllGlobal');

        if (selectAllHeader) {
            selectAllHeader.addEventListener('change', function () {
                isAllGlobalSelected = false;
                const checkboxes = getProductCheckboxes();
                checkboxes.forEach(cb => {
                    cb.checked = selectAllHeader.checked;
                });
                updateBulkUI();
            });
        }

        document.querySelectorAll('.product-checkbox').forEach(cb => {
            cb.addEventListener('change', function () {
                isAllGlobalSelected = false;
                updateBulkUI();
            });
        });

        if (btnCancelSelection) {
            btnCancelSelection.addEventListener('click', function () {
                isAllGlobalSelected = false;
                const selectAllHeader = document.getElementById('selectAllCheckbox');
                if (selectAllHeader) {
                    selectAllHeader.checked = false;
                    selectAllHeader.indeterminate = false;
                }
                const checkboxes = getProductCheckboxes();
                checkboxes.forEach(cb => {
                    cb.checked = false;
                });
                updateBulkUI();
            });
        }

        if (btnSelectAllGlobal) {
            btnSelectAllGlobal.addEventListener('click', function () {
                isAllGlobalSelected = true;
                const selectAllHeader = document.getElementById('selectAllCheckbox');
                if (selectAllHeader) {
                    selectAllHeader.checked = true;
                    selectAllHeader.indeterminate = false;
                }
                const checkboxes = getProductCheckboxes();
                checkboxes.forEach(cb => {
                    cb.checked = true;
                });
                updateBulkUI();
            });
        }

        if (btnDeleteSelected) {
            btnDeleteSelected.addEventListener('click', function () {
                openBulkDeleteModal(isAllGlobalSelected);
            });
        }

        // Initialize TomSelect for Inventory Filter Dropdowns
        if (typeof TomSelect !== 'undefined') {
            const stockLevelEl = document.getElementById('filter_stock_level');
            if (stockLevelEl) {
                new TomSelect(stockLevelEl, {
                    create: false,
                    allowEmptyOption: true,
                    placeholder: 'Semua Level Stok',
                    controlInput: null
                });
            }

            const categoryEl = document.getElementById('filter_category');
            if (categoryEl) {
                new TomSelect(categoryEl, {
                    create: false,
                    allowEmptyOption: true,
                    placeholder: 'Semua Kategori',
                    maxOptions: 100
                });
            }

            const variantEl = document.getElementById('filter_variant');
            if (variantEl) {
                new TomSelect(variantEl, {
                    create: false,
                    allowEmptyOption: true,
                    placeholder: 'Semua Varian',
                    maxOptions: 100
                });
            }
        }

        window.addEventListener('click', function(e) {
            const modal = document.getElementById('bulkDeleteModal');
            if (e.target === modal) {
                closeBulkDeleteModal();
            }
        });

        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeBulkDeleteModal();
            }
        });
    });

    function openBulkDeleteModal(deleteAll = false) {
        isModalDeleteAll = deleteAll;
        const modal = document.getElementById('bulkDeleteModal');
        const title = document.getElementById('modalTitle');
        const desc = document.getElementById('modalDescription');
        const confirmBtnText = document.getElementById('modalConfirmBtnText');

        if (deleteAll) {
            title.textContent = 'Hapus Seluruh Produk';
            desc.innerHTML = 'Apakah Anda yakin ingin menghapus <strong>seluruh {{ number_format($stats["total_products"] ?? 0, 0, ",", ".") }} produk</strong> di katalog inventaris? Semua data stok fisik, gambar, dan riwayat terkait akan dihapus secara permanen!';
            confirmBtnText.textContent = 'Hapus Seluruh Produk';
        } else {
            const checkedCount = Array.from(getProductCheckboxes()).filter(cb => cb.checked).length;
            if (checkedCount === 0) {
                alert('Pilih minimal satu produk untuk dihapus.');
                return;
            }
            title.textContent = 'Hapus Produk Terpilih';
            desc.innerHTML = `Apakah Anda yakin ingin menghapus <strong>${checkedCount} produk terpilih</strong>? Riwayat stok fisik, gambar, dan data terkait produk ini akan dihapus secara permanen.`;
            confirmBtnText.textContent = `Hapus ${checkedCount} Produk`;
        }

        modal.classList.remove('hidden');
    }

    function closeBulkDeleteModal() {
        const modal = document.getElementById('bulkDeleteModal');
        if (modal) modal.classList.add('hidden');
    }

    function submitBulkDelete() {
        const form = document.getElementById('bulkDeleteForm');
        const deleteAllInput = document.getElementById('deleteAllInput');
        const container = document.getElementById('bulkDeleteInputsContainer');
        container.innerHTML = '';

        if (isModalDeleteAll) {
            deleteAllInput.value = '1';
        } else {
            deleteAllInput.value = '0';
            const checked = Array.from(getProductCheckboxes()).filter(cb => cb.checked);
            if (checked.length === 0) {
                alert('Pilih minimal satu produk untuk dihapus.');
                return;
            }
            checked.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });
        }

        form.submit();
    }
</script>
@endsection
