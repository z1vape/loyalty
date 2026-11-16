@extends('layouts.app')

@section('title', 'Laporan Stok Kritis (Low Stock) | Z1 VapeOS')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                    <span class="material-symbols-outlined text-sm">warning</span>
                    Penjualan & Stok
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-surface-container-high text-on-surface">
                    <span class="material-symbols-outlined text-xs text-[#006c49]">store</span>
                    {{ $storeName }}
                </span>
                <span class="text-xs font-semibold text-on-surface-variant">• Stok Kritis (Low Stock & Habis)</span>
            </div>
            <h1 class="font-headline-lg text-2xl sm:text-3xl font-extrabold text-on-surface">Laporan Stok Kritis</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">
                Daftar barang dengan stok di bawah batas minimum (min. stock) atau telah habis untuk percepatan pengadaan restock di {{ $storeName }}.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('reports.low-stock.export', request()->all()) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-[#006c49] hover:bg-[#005236] text-white transition-all shadow-xs active:scale-95 no-underline">
                <span class="material-symbols-outlined text-[18px]">table_view</span>
                <span>Export Excel</span>
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 sm:p-5 shadow-xs">
        <form method="GET" action="{{ route('reports.low-stock') }}" class="flex flex-col gap-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                {{-- Kategori --}}
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Kategori</label>
                    <select name="category" class="w-full border border-outline-variant bg-surface-container-low rounded-xl px-3.5 py-2 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary/40 transition-all cursor-pointer">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Cari Produk / SKU --}}
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Cari Produk / SKU</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama produk atau SKU..."
                               class="w-full border border-outline-variant bg-surface-container-low rounded-xl pl-9 pr-3.5 py-2 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary/40 transition-all">
                        <span class="material-symbols-outlined absolute left-2.5 top-2.5 text-on-surface-variant text-sm">search</span>
                    </div>
                </div>

                {{-- Filter OOS Checkbox --}}
                <div class="flex flex-col justify-end pb-1.5">
                    <label class="inline-flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="include_out_of_stock" value="1" {{ $includeOutOfStock ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-[#006c49] focus:ring-[#006c49] border-outline-variant">
                        <span class="text-xs font-bold text-on-surface select-none">Termasuk Produk Habis (Stok 0)</span>
                    </label>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-outline-variant/40">
                <div>
                    @if(request('category') || request('search') || request()->has('include_out_of_stock'))
                        <a href="{{ route('reports.low-stock') }}" class="text-xs text-on-surface-variant hover:text-error transition-colors inline-flex items-center gap-1 font-bold no-underline">
                            <span class="material-symbols-outlined text-xs">close</span>
                            Reset Filter
                        </a>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-secondary text-on-secondary text-xs font-bold hover:bg-secondary/90 transition-all cursor-pointer active:scale-95 shadow-xs">
                        <span class="material-symbols-outlined text-sm">filter_alt</span>
                        <span>Tampilkan</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        {{-- Total Produk Kritis --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">warning</span>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] sm:text-[11px] font-bold text-on-surface-variant uppercase tracking-wider truncate">Total Produk Kritis</p>
                <h3 class="text-xl font-extrabold text-on-surface mt-0.5 truncate">{{ $totalItems }}</h3>
            </div>
        </div>

        {{-- Stok Habis (0 Pcs) --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">block</span>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] sm:text-[11px] font-bold text-on-surface-variant uppercase tracking-wider truncate">Stok Habis (0 Pcs)</p>
                <h3 class="text-xl font-extrabold text-rose-600 mt-0.5 truncate">{{ $outOfStockCount }}</h3>
            </div>
        </div>

        {{-- Stok Menipis (> 0) --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">inventory_2</span>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] sm:text-[11px] font-bold text-on-surface-variant uppercase tracking-wider truncate">Stok Menipis</p>
                <h3 class="text-xl font-extrabold text-orange-600 mt-0.5 truncate">{{ $lowStockCount }}</h3>
            </div>
        </div>

        {{-- Total Defisit Unit --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">shopping_cart_checkout</span>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] sm:text-[11px] font-bold text-on-surface-variant uppercase tracking-wider truncate">Defisit Restock</p>
                <h3 class="text-xl font-extrabold text-blue-700 mt-0.5 truncate">{{ number_format($totalShortage) }} Pcs</h3>
            </div>
        </div>

        {{-- Estimasi Biaya Restock --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs flex items-center gap-3 col-span-2 sm:col-span-1">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#006c49] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">payments</span>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] sm:text-[11px] font-bold text-on-surface-variant uppercase tracking-wider truncate">Estimasi Biaya Restock</p>
                <h3 class="text-base sm:text-lg font-extrabold text-[#006c49] mt-0.5 truncate">Rp {{ number_format($totalRestockCost, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    {{-- Main Table --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-outline-variant/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wider flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-600 text-[20px]">notification_important</span>
                    <span>Daftar Produk di Bawah Batas Minimum</span>
                </h2>
                <p class="text-xs text-on-surface-variant mt-0.5">
                    Produk diurutkan berdasarkan kekurangan unit terbesar (prioritas restock utama).
                </p>
            </div>
            <div class="text-xs text-on-surface-variant font-medium">
                Total {{ $totalItems }} produk perlu restock
            </div>
        </div>

        @if($items->isEmpty())
        <div class="p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-[#006c49] mx-auto flex items-center justify-center mb-3">
                <span class="material-symbols-outlined text-3xl">check_circle</span>
            </div>
            <h3 class="text-base font-bold text-on-surface">Semua Stok Aman</h3>
            <p class="text-xs text-on-surface-variant max-w-md mx-auto mt-1">
                Tidak ada produk dengan stok di bawah batas minimum pada cabang {{ $storeName }}.
            </p>
        </div>
        @else
        <div class="overflow-x-auto custom-slim-scroll">
            <table class="w-full text-left border-collapse text-xs whitespace-nowrap">
                <thead>
                    <tr class="bg-surface-container-low/70 border-b border-outline-variant/80 text-on-surface font-extrabold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-3 text-center w-12 border-r border-outline-variant/30">#</th>
                        <th class="py-3 px-3 border-r border-outline-variant/30 min-w-[130px]">SKU</th>
                        <th class="py-3 px-4 border-r border-outline-variant/30 min-w-[240px]">Nama Produk</th>
                        <th class="py-3 px-3 border-r border-outline-variant/30 min-w-[110px]">Kategori</th>
                        <th class="py-3 px-3 text-right border-r border-outline-variant/30 min-w-[100px]">Stok Toko</th>
                        <th class="py-3 px-3 text-right border-r border-outline-variant/30 min-w-[100px]">Batas Min</th>
                        <th class="py-3 px-3 text-right border-r border-outline-variant/30 min-w-[110px] bg-rose-50/50 text-rose-900">Defisit Kurang</th>
                        <th class="py-3 px-3 text-right border-r border-outline-variant/30 min-w-[120px]">HPP Beli</th>
                        <th class="py-3 px-3 text-right border-r border-outline-variant/30 min-w-[130px] font-black">Estimasi Biaya</th>
                        <th class="py-3 px-3 text-center min-w-[110px]">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/30">
                    @foreach($items as $index => $item)
                    @php
                        $isOutOfStock = $item['stock'] <= 0;
                    @endphp
                    <tr class="hover:bg-surface-container-low/50 transition-colors {{ $isOutOfStock ? 'bg-rose-50/20' : '' }}">
                        {{-- No --}}
                        <td class="py-2.5 px-3 text-center text-on-surface-variant font-semibold border-r border-outline-variant/30">
                            {{ $loop->iteration }}
                        </td>

                        {{-- SKU --}}
                        <td class="py-2.5 px-3 font-mono font-bold text-on-surface border-r border-outline-variant/30">
                            {{ $item['sku'] }}
                        </td>

                        {{-- Nama Produk --}}
                        <td class="py-2.5 px-4 font-semibold text-on-surface border-r border-outline-variant/30">
                            {{ $item['name'] }}
                        </td>

                        {{-- Kategori --}}
                        <td class="py-2.5 px-3 border-r border-outline-variant/30">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-surface-container text-on-surface">
                                {{ $item['category'] ?? 'Tanpa Kategori' }}
                            </span>
                        </td>

                        {{-- Stok Saat Ini --}}
                        <td class="py-2.5 px-3 text-right font-mono font-black border-r border-outline-variant/30 {{ $isOutOfStock ? 'text-rose-600' : 'text-amber-700' }}">
                            {{ $item['stock'] }}
                        </td>

                        {{-- Batas Min --}}
                        <td class="py-2.5 px-3 text-right font-mono text-on-surface-variant border-r border-outline-variant/30">
                            {{ $item['min_stock'] }}
                        </td>

                        {{-- Defisit --}}
                        <td class="py-2.5 px-3 text-right font-mono font-black border-r border-outline-variant/30 text-rose-700 bg-rose-50/30">
                            -{{ $item['shortage'] }}
                        </td>

                        {{-- HPP Beli --}}
                        <td class="py-2.5 px-3 text-right font-mono text-on-surface-variant border-r border-outline-variant/30">
                            Rp {{ number_format($item['purchase_price'], 0, ',', '.') }}
                        </td>

                        {{-- Estimasi Biaya --}}
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-[#006c49] border-r border-outline-variant/30">
                            Rp {{ number_format($item['estimated_restock_cost'], 0, ',', '.') }}
                        </td>

                        {{-- Status Badge --}}
                        <td class="py-2.5 px-3 text-center">
                            @if($isOutOfStock)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                    Habis (0 Pcs)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                    Menipis
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                {{-- Table Footer --}}
                <tfoot>
                    <tr class="bg-surface-container-low/90 border-t-2 border-outline-variant/80 font-black text-xs text-on-surface">
                        <td colspan="4" class="py-3 px-4 uppercase tracking-wider text-right border-r border-outline-variant/30">
                            TOTAL
                        </td>
                        <td colspan="2" class="py-3 px-3 text-right border-r border-outline-variant/30 text-on-surface-variant"></td>
                        <td class="py-3 px-3 text-right font-mono font-black text-rose-700 border-r border-outline-variant/30 bg-rose-50/50">
                            {{ number_format($totalShortage) }} Pcs
                        </td>
                        <td class="py-3 px-3 border-r border-outline-variant/30"></td>
                        <td class="py-3 px-3 text-right font-mono font-black text-[#006c49] border-r border-outline-variant/30 text-sm">
                            Rp {{ number_format($totalRestockCost, 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

</div>
@endsection

