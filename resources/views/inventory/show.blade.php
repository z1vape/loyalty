@extends('layouts.app')

@section('title', 'Detail Produk: ' . $product->name . ' | InvenTrack')

@section('content')
<div class="max-w-5xl mx-auto flex flex-col gap-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Detail Inventaris Produk</h1>
            <p class="text-on-surface-variant text-body-lg">
                Informasi spesifikasi lengkap, margin keuntungan, dan histori mutasi barang masuk/keluar.
            </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
            <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                <span>Kembali</span>
            </a>
            <a href="{{ route('inventory.stock-card', $product) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base text-primary">receipt_long</span>
                <span>Kartu Stok</span>
            </a>
            @can('Inventory Edit')
            <a href="{{ route('inventory.edit', $product) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base">edit</span>
                <span>Ubah Produk</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Product Hero Card -->
    @php
        $isOutOfStock = $product->stock <= 0;
        $isLowStock = !$isOutOfStock && $product->stock <= $product->min_stock;
        $margin = $product->selling_price - $product->purchase_price;
        $marginPercent = $product->purchase_price > 0 ? round(($margin / $product->purchase_price) * 100, 1) : 0;
    @endphp

    <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-8 shadow-sm flex flex-col sm:flex-row items-center sm:items-start gap-6">
        <!-- Photo Avatar -->
        <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl overflow-hidden bg-surface-container-low border-2 border-outline-variant flex items-center justify-center shrink-0 shadow-inner">
            @if($product->photo)
                <img src="{{ asset('storage/' . $product->photo) }}" alt="{{ $product->name }}" class="w-full h-full object-contain p-1 bg-white" />
            @else
                <span class="material-symbols-outlined text-5xl text-outline-variant">inventory_2</span>
            @endif
        </div>

        <div class="flex-1 text-center sm:text-left">
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 mb-2">
                <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-lg bg-surface-container text-on-surface border border-outline-variant/60">
                    {{ $product->sku }}
                </span>
                
                @if($isOutOfStock)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                        <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                        Stok Habis
                    </span>
                @elseif($isLowStock)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Stok Menipis
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-[#e8f5e9] text-[#006c49] border border-[#006c49]/30">
                        <span class="w-2 h-2 rounded-full bg-[#006c49]"></span>
                        Stok Aman
                    </span>
                @endif
            </div>

            <h2 class="text-xl sm:text-2xl font-extrabold text-on-surface">{{ $product->name }}</h2>

            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 mt-2 text-xs text-on-surface-variant font-medium">
                @if($product->category)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-cyan-50 text-cyan-800 border border-cyan-200">
                        <span class="material-symbols-outlined text-xs">category</span>
                        {{ $product->category }}
                    </span>
                @endif

                @if($product->variant)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant">
                        <span class="material-symbols-outlined text-xs">style</span>
                        Varian: {{ $product->variant }}
                    </span>
                @endif
            </div>

            @if($product->description)
                <p class="text-xs text-on-surface-variant mt-3 leading-relaxed max-w-2xl">
                    {{ $product->description }}
                </p>
            @endif
        </div>
    </div>

    <!-- 4 Metrics Overview -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Harga Beli -->
        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex flex-col justify-between">
            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Harga Modal / Beli</span>
            <div class="mt-2">
                <span class="font-mono text-xl font-extrabold text-on-surface">
                    Rp {{ number_format($product->purchase_price, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- Harga Jual -->
        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex flex-col justify-between">
            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Harga Jual Konsumen</span>
            <div class="mt-2">
                <span class="font-mono text-xl font-extrabold text-[#006c49]">
                    Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- Margin Profit -->
        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex flex-col justify-between">
            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Estimasi Margin Laba</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="font-mono text-lg font-extrabold text-cyan-700">
                    Rp {{ number_format($margin, 0, ',', '.') }}
                </span>
                <span class="text-xs font-bold text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded-full border border-cyan-200">
                    +{{ $marginPercent }}%
                </span>
            </div>
        </div>

        <!-- Current Stock -->
        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex flex-col justify-between">
            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Sisa Stok Fisik</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="font-mono text-2xl font-black {{ $isOutOfStock ? 'text-rose-600' : ($isLowStock ? 'text-amber-600' : 'text-[#006c49]') }}">
                    {{ number_format($product->stock, 0, ',', '.') }}
                </span>
                <span class="text-xs text-on-surface-variant">unit (Min: {{ $product->min_stock }})</span>
            </div>
        </div>
    </div>

    <!-- Histori Mutasi Barang Terakhir -->
    <div class="bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-outline-variant flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-xl text-[#006c49]">history</span>
                <h3 class="font-bold text-base text-on-surface">Histori Mutasi Stok Terakhir</h3>
            </div>
            <div class="flex items-center gap-2.5 self-start sm:self-auto">
                <a href="{{ route('inventory.stock-card', $product) }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#006c49] hover:underline bg-[#e8f5e9] px-2.5 py-1 rounded-lg border border-[#006c49]/30">
                    <span class="material-symbols-outlined text-sm">receipt_long</span>
                    <span>Buka Kartu Stok Lengkap &rarr;</span>
                </a>
                <a href="{{ route('movements.index') }}" class="text-xs font-semibold text-on-surface-variant hover:text-[#006c49] transition">
                    Semua Mutasi
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-surface-container-low/60 border-b border-outline-variant text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Tipe</th>
                        <th class="py-3 px-4">No. Referensi</th>
                        <th class="py-3 px-4 text-center">Jumlah Qty</th>
                        <th class="py-3 px-4 text-right">Nilai Total</th>
                        <th class="py-3 px-4">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @forelse($product->movements as $movement)
                        <tr class="hover:bg-surface-container-low/30 transition">
                            <td class="py-3 px-4 font-mono">
                                {{ \Carbon\Carbon::parse($movement->movement_date)->format('d/m/Y') }}
                            </td>
                            <td class="py-3 px-4">
                                @if($movement->type === 'in')
                                    <span class="inline-flex items-center gap-1 font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                        <span class="material-symbols-outlined text-xs">arrow_downward</span>
                                        Masuk (In)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200">
                                        <span class="material-symbols-outlined text-xs">arrow_upward</span>
                                        Keluar (Out)
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-on-surface">
                                {{ $movement->reference_number ?: '-' }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-bold">
                                {{ $movement->quantity }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-on-surface">
                                Rp {{ number_format($movement->total_price, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-on-surface-variant truncate max-w-xs">
                                {{ $movement->notes ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-on-surface-variant">
                                Belum ada catatan mutasi stok untuk produk ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
