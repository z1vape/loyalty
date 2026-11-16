@extends('layouts.app')

@section('title', 'Z1 VapeOS | Kartu Stok: ' . $product->name)

@section('content')
@php
    $isOutOfStock = $product->stock <= 0;
    $isLowStock = !$isOutOfStock && $product->stock <= $product->min_stock;
    $stockSynced = (float) $product->stock === (float) $endingBalance;
@endphp

<div class="flex flex-col gap-6">

    {{-- Header Section with Action Buttons (Accounting Style, No Breadcrumbs) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 print:hidden">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Kartu Stok (Stock Ledger)</h1>
            <p class="text-on-surface-variant text-body-lg">
                Rincian mutasi keluar-masuk barang, dokumen referensi, dan saldo akhir berjalan per produk.
            </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
            <a 
                href="{{ route('inventory.stock-card.export', array_merge(['product' => $product->id], request()->query())) }}" 
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline"
                title="Download Kartu Stok ke Excel"
            >
                <span class="material-symbols-outlined text-base text-primary">download</span>
                <span>Export Excel</span>
            </a>

            <button 
                type="button" 
                onclick="window.print()" 
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer"
                title="Cetak Lembar Kartu Stok"
            >
                <span class="material-symbols-outlined text-base">print</span>
                <span>Cetak</span>
            </button>

            <a 
                href="{{ route('stock-card.index') }}" 
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline"
                title="Kembali ke Daftar Kartu Stok Semua Produk"
            >
                <span class="material-symbols-outlined text-base text-secondary">menu_book</span>
                <span>Hub Kartu Stok</span>
            </a>

            <a 
                href="{{ route('inventory.index') }}" 
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline"
            >
                <span class="material-symbols-outlined text-base">inventory_2</span>
                <span>Daftar Produk</span>
            </a>
        </div>
    </div>

    {{-- Print Header Only (Official Document Layout) --}}
    <div class="hidden print:block border-b-2 border-gray-900 pb-4 mb-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-black uppercase tracking-wider text-black">KARTU STOK PRODUK (STOCK LEDGER)</h1>
                <p class="text-xs font-semibold text-gray-700 mt-0.5">
                    Toko: {{ $product->store ? $product->store->name : (session('active_store_name') ?: 'Pusat') }} &bull; Tanggal Cetak: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
                </p>
            </div>
            <div class="text-right">
                <span class="text-xs font-mono font-bold px-2 py-1 border border-gray-400 rounded">
                    SKU: {{ $product->sku }}
                </span>
            </div>
        </div>
        <div class="grid grid-cols-4 gap-2 mt-3 text-xs bg-gray-50 p-2 border border-gray-300 rounded">
            <div><strong>Nama Produk:</strong> {{ $product->name }}</div>
            <div><strong>Kategori/Varian:</strong> {{ $product->category ?: '-' }} / {{ $product->variant ?: '-' }}</div>
            <div><strong>Harga Modal:</strong> Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</div>
            <div><strong>Periode:</strong> {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : 'Awal' }} s/d {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : 'Sekarang' }}</div>
        </div>
    </div>

    {{-- Filter Form (Accounting Style) --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 shadow-xs print:hidden">
        <form method="GET" action="{{ route('inventory.stock-card', $product) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            {{-- 1. Pilih Produk --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Pilih Produk</label>
                <select 
                    name="product_selector" 
                    id="stock_card_detail_product_id"
                    class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary cursor-pointer"
                >
                    @foreach($allProducts as $p)
                        <option value="{{ route('inventory.stock-card', array_merge(['product' => $p->id], request()->only(['date_from', 'date_to', 'type', 'category', 'variant']))) }}" {{ $p->id === $product->id ? 'selected' : '' }}>
                            {{ $p->sku }} - {{ $p->name }} {{ $p->category ? '[' . $p->category . ']' : '' }} {{ $p->variant ? '(' . $p->variant . ')' : '' }} - Sisa: {{ $p->stock }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 2. Filter Kategori --}}
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Filter Kategori</label>
                <select 
                    name="category" 
                    id="stock_card_detail_category"
                    class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary cursor-pointer"
                    onchange="this.form.submit()"
                >
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                            {{ $cat }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 3. Filter Varian --}}
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Filter Varian</label>
                <select 
                    name="variant" 
                    id="stock_card_detail_variant"
                    class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary cursor-pointer"
                    onchange="this.form.submit()"
                >
                    <option value="">Semua Varian</option>
                    @foreach($variants as $var)
                        <option value="{{ $var }}" {{ request('variant') === $var ? 'selected' : '' }}>
                            {{ $var }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 4. Dari Tanggal --}}
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Dari Tanggal</label>
                <input 
                    type="date" 
                    name="date_from" 
                    value="{{ $dateFrom }}" 
                    class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary"
                >
            </div>

            {{-- 5. Sampai Tanggal --}}
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Sampai Tanggal</label>
                <input 
                    type="date" 
                    name="date_to" 
                    value="{{ $dateTo }}" 
                    class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary"
                >
            </div>

            {{-- 6. Jenis Mutasi --}}
            <div class="sm:col-span-2 lg:col-span-2">
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Jenis Mutasi</label>
                <select 
                    name="type" 
                    class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary cursor-pointer"
                >
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Semua Jenis Mutasi (Masuk & Keluar)</option>
                    <option value="in" {{ $type === 'in' ? 'selected' : '' }}>Hanya Stok Masuk (Restock / In)</option>
                    <option value="out" {{ $type === 'out' ? 'selected' : '' }}>Hanya Stok Keluar (Penjualan / Out)</option>
                </select>
            </div>

            {{-- Preset & Action Row --}}
            <div class="sm:col-span-2 lg:col-span-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-outline-variant/60">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs text-on-surface-variant font-medium mr-1">Preset:</span>
                    <a 
                        href="{{ route('inventory.stock-card', array_merge(['product' => $product->id, 'date_from' => \Carbon\Carbon::today()->startOfMonth()->toDateString(), 'date_to' => \Carbon\Carbon::today()->toDateString(), 'type' => $type], request()->only(['category', 'variant']))) }}" 
                        class="px-2.5 py-1 rounded-lg bg-surface-container-low hover:bg-surface-container-high text-on-surface font-semibold text-xs transition no-underline"
                    >
                        Bulan Ini
                    </a>
                    <a 
                        href="{{ route('inventory.stock-card', array_merge(['product' => $product->id, 'date_from' => \Carbon\Carbon::today()->subDays(30)->toDateString(), 'date_to' => \Carbon\Carbon::today()->toDateString(), 'type' => $type], request()->only(['category', 'variant']))) }}" 
                        class="px-2.5 py-1 rounded-lg bg-surface-container-low hover:bg-surface-container-high text-on-surface font-semibold text-xs transition no-underline"
                    >
                        30 Hari Terakhir
                    </a>
                    <a 
                        href="{{ route('inventory.stock-card', array_merge(['product' => $product->id, 'date_from' => '', 'date_to' => \Carbon\Carbon::today()->toDateString(), 'type' => $type], request()->only(['category', 'variant']))) }}" 
                        class="px-2.5 py-1 rounded-lg bg-surface-container-low hover:bg-surface-container-high text-on-surface font-semibold text-xs transition no-underline"
                    >
                        Semua Histori
                    </a>

                    @if(request('category') || request('variant'))
                        <span class="text-outline-variant mx-1">|</span>
                        @if(request('category'))
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-[#006c49] border border-emerald-200 font-bold text-[11px]">
                                Kategori: {{ request('category') }}
                            </span>
                        @endif
                        @if(request('variant'))
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 font-bold text-[11px]">
                                Varian: {{ request('variant') }}
                            </span>
                        @endif
                    @endif
                </div>

                <div class="flex items-center gap-2 self-end sm:self-auto">
                    @if($dateFrom || $dateTo !== \Carbon\Carbon::today()->toDateString() || $type !== 'all' || request('category') || request('variant'))
                        <a 
                            href="{{ route('inventory.stock-card', $product) }}" 
                            class="px-3.5 py-2 bg-surface-container-low border border-outline-variant hover:bg-surface-container-high text-on-surface font-semibold text-xs rounded-xl transition-all flex items-center justify-center no-underline"
                            title="Reset Semua Filter"
                        >
                            <span class="material-symbols-outlined text-sm mr-1">restart_alt</span>
                            Reset
                        </a>
                    @endif
                    <button 
                        type="submit" 
                        class="px-5 py-2 bg-secondary text-on-secondary text-xs font-bold rounded-xl hover:opacity-90 active:scale-95 transition-all cursor-pointer inline-flex items-center gap-1.5"
                    >
                        <span class="material-symbols-outlined text-sm">filter_alt</span>
                        <span>Tampilkan Kartu Stok</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Product Summary Header Card (Exact Accounting Ledger Style) --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 mb-6 shadow-xs print:hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-outline-variant/60">
            <div class="flex items-center gap-4">
                @if($product->photo)
                    <div class="w-12 h-12 rounded-xl overflow-hidden bg-surface-container-low border border-outline-variant flex items-center justify-center shrink-0">
                        <img src="{{ asset('storage/' . $product->photo) }}" alt="{{ $product->name }}" class="w-full h-full object-contain p-0.5" />
                    </div>
                @endif
                <div>
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="font-mono text-xs font-bold bg-surface-container-high px-2 py-0.5 rounded text-on-surface">{{ $product->sku }}</span>
                        @if($product->category)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md border border-outline-variant bg-surface-container-low text-[10px] font-bold text-on-surface-variant">
                                {{ $product->category }}
                            </span>
                        @endif
                        @if($product->variant)
                            <span class="text-xs text-on-surface-variant font-medium">Varian: {{ $product->variant }}</span>
                        @endif
                        @if($isOutOfStock)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Habis</span>
                        @elseif($isLowStock)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Menipis</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">Aman</span>
                        @endif
                    </div>
                    <h2 class="text-xl font-headline-md font-extrabold text-on-surface">{{ $product->name }}</h2>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        Harga Modal (HPP): <strong class="font-mono text-on-surface">Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</strong> &bull; 
                        Harga Jual: <strong class="font-mono text-primary">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</strong>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-4 text-right">
                <div>
                    <p class="text-[10px] uppercase font-bold text-on-surface-variant tracking-wider">Batas Minimum</p>
                    <p class="font-mono text-xs font-extrabold text-on-surface uppercase">{{ $product->min_stock }} Unit</p>
                </div>
                <div class="h-8 border-r border-outline-variant"></div>
                <div>
                    <p class="text-[10px] uppercase font-bold text-on-surface-variant tracking-wider">Stok Fisik Saat Ini</p>
                    <p class="font-mono text-base font-extrabold {{ $isOutOfStock ? 'text-error' : ($isLowStock ? 'text-amber-600' : 'text-emerald-800') }}">
                        {{ number_format($product->stock, 0, ',', '.') }} Unit
                    </p>
                </div>
            </div>
        </div>

        {{-- Metrics (Identical to Accounting Ledger) --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4">
            <div>
                <p class="text-[10px] uppercase font-bold text-on-surface-variant tracking-wider">Saldo Awal</p>
                <p class="font-mono text-sm font-bold text-on-surface mt-0.5">
                    {{ number_format($openingBalance, 0, ',', '.') }} Unit
                </p>
                <p class="text-[10px] text-on-surface-variant mt-0.5">
                    {{ $dateFrom ? 'Sebelum ' . \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : 'Awal Pembukuan' }}
                </p>
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-emerald-700 tracking-wider">Total Masuk Periode</p>
                <p class="font-mono text-sm font-bold text-emerald-800 mt-0.5">
                    +{{ number_format($totalIn, 0, ',', '.') }} Unit
                </p>
                <p class="text-[10px] text-on-surface-variant mt-0.5 font-mono">
                    Rp {{ number_format($totalValueIn, 0, ',', '.') }}
                </p>
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-rose-700 tracking-wider">Total Keluar Periode</p>
                <p class="font-mono text-sm font-bold text-rose-800 mt-0.5">
                    -{{ number_format($totalOut, 0, ',', '.') }} Unit
                </p>
                <p class="text-[10px] text-on-surface-variant mt-0.5 font-mono">
                    Rp {{ number_format($totalValueOut, 0, ',', '.') }}
                </p>
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-on-surface-variant tracking-wider">Saldo Akhir Periode</p>
                <p class="font-mono text-sm font-bold text-primary mt-0.5">
                    {{ number_format($endingBalance, 0, ',', '.') }} Unit
                </p>
                <p class="text-[10px] text-on-surface-variant mt-0.5">
                    @if($stockSynced)
                        <span class="text-emerald-700 font-bold inline-flex items-center gap-0.5">
                            <span class="material-symbols-outlined text-xs">check_circle</span> Sesuai Stok Fisik
                        </span>
                    @else
                        <span class="text-amber-700 font-bold inline-flex items-center gap-0.5">
                            <span class="material-symbols-outlined text-xs">info</span> Sisa Periode
                        </span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Stock Ledger Table Card (Accounting Ledger Style) --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-xs">
        <div class="p-4 border-b border-outline-variant flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-lg text-primary">table_rows</span>
                <div>
                    <h3 class="font-bold text-sm text-on-surface">Buku Kas Mutasi Stok (Ledger)</h3>
                    <p class="text-[11px] text-on-surface-variant">Menampilkan {{ count($rows) }} catatan mutasi dalam urutan kronologis.</p>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant">
                    <tr>
                        <th class="px-4 py-3.5">Tanggal</th>
                        <th class="px-4 py-3.5">No. Bukti / Ref</th>
                        <th class="px-4 py-3.5">Tipe & Sumber</th>
                        <th class="px-4 py-3.5">Keterangan / Memo</th>
                        <th class="px-4 py-3.5 text-right w-28">Harga Satuan</th>
                        <th class="px-4 py-3.5 text-center w-28">Masuk (+)</th>
                        <th class="px-4 py-3.5 text-center w-28">Keluar (-)</th>
                        <th class="px-4 py-3.5 text-center w-32">Saldo Berjalan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    {{-- Opening Balance Row --}}
                    <tr class="bg-surface-container-low/40 font-semibold italic text-on-surface-variant">
                        <td class="px-4 py-2.5">{{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '-' }}</td>
                        <td class="px-4 py-2.5 font-mono italic">-</td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex items-center gap-1 font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-full text-[10px]">
                                Saldo Awal
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-on-surface-variant italic">
                            Saldo awal akumulatif sebelum periode {{ $dateFrom ?: 'pencarian' }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono">-</td>
                        <td class="px-4 py-2.5 text-center font-mono">-</td>
                        <td class="px-4 py-2.5 text-center font-mono">-</td>
                        <td class="px-4 py-2.5 text-center font-mono font-bold text-on-surface">
                            {{ number_format($openingBalance, 0, ',', '.') }}
                        </td>
                    </tr>

                    @forelse($rows as $idx => $row)
                        @php
                            $m = $row['movement'];
                            $isIn = $m->type === 'in';
                        @endphp
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-4 py-3 font-mono">
                                <div class="font-bold text-on-surface">{{ \Carbon\Carbon::parse($m->movement_date)->format('d/m/Y') }}</div>
                                <div class="text-[10px] text-on-surface-variant">{{ $m->created_at ? $m->created_at->format('H:i') : '' }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono font-bold text-on-surface">
                                {{ $m->reference_number ?: '-' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($isIn)
                                    <span class="inline-flex items-center gap-1 font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200 text-[10px]">
                                        <span class="material-symbols-outlined text-xs">arrow_downward</span>
                                        Masuk
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 font-bold text-rose-800 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200 text-[10px]">
                                        <span class="material-symbols-outlined text-xs">arrow_upward</span>
                                        Keluar
                                    </span>
                                @endif
                                <div class="text-[10px] text-on-surface-variant mt-0.5">{{ $row['source_label'] ?? ($isIn ? 'Stok Masuk' : 'Stok Keluar') }}</div>
                            </td>
                            <td class="px-4 py-3 text-on-surface-variant">
                                <div>{{ $m->notes ?: '-' }}</div>
                                @if($m->user)
                                    <div class="text-[10px] text-on-surface-variant/70">Oleh: {{ $m->user->name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-on-surface-variant">
                                Rp {{ number_format($row['unit_price'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono font-bold text-emerald-800">
                                @if(($row['qty_in'] ?? 0) > 0)
                                    +{{ number_format($row['qty_in'], 0, ',', '.') }}
                                @else
                                    <span class="text-on-surface-variant/40">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-mono font-bold text-rose-800">
                                @if(($row['qty_out'] ?? 0) > 0)
                                    -{{ number_format($row['qty_out'], 0, ',', '.') }}
                                @else
                                    <span class="text-on-surface-variant/40">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-mono font-extrabold text-on-surface bg-surface-container-low/30">
                                {{ number_format($row['running_balance'] ?? $row['balance'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-3xl text-outline-variant block mb-1">history_toggle_off</span>
                                <p class="font-bold text-xs text-on-surface">Tidak ada mutasi barang pada periode ini</p>
                                <p class="text-[11px] text-on-surface-variant mt-0.5">Saldo stok awal tetap berjalan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-surface-container-low font-bold text-on-surface border-t-2 border-outline-variant">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right text-xs uppercase tracking-wider">Total Periode:</td>
                        <td class="px-4 py-3 text-center font-mono text-emerald-800 font-extrabold text-xs">
                            +{{ number_format($totalIn, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-rose-800 font-extrabold text-xs">
                            -{{ number_format($totalOut, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center font-mono font-black text-primary text-xs bg-surface-container-high/50">
                            {{ number_format($endingBalance, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const prodSelector = document.getElementById('stock_card_detail_product_id');
        if (prodSelector) {
            new TomSelect('#stock_card_detail_product_id', {
                create: false,
                maxOptions: 1000,
                onChange: function(url) {
                    if (url) window.location.href = url;
                }
            });
        }
    });
</script>
@endpush
