@extends('layouts.app')

@section('title', 'Laporan Penjualan per Kategori | InvenTrack')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Penjualan berdasarkan Kategori</h1>
            <p class="text-on-surface-variant text-body-lg">Ringkasan penjualan per kategori produk dalam periode tertentu</p>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('reports.by-category') }}"
          class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex flex-col gap-3">
        <div class="grid grid-cols-2 sm:flex sm:flex-row gap-3 sm:items-end">
            <div class="flex flex-col gap-1 sm:flex-1">
                <label class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="w-full border border-outline-variant bg-surface-container-low rounded-xl px-3 py-2 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary/40 transition-all">
            </div>
            <div class="flex flex-col gap-1 sm:flex-1">
                <label class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Tanggal Akhir</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="w-full border border-outline-variant bg-surface-container-low rounded-xl px-3 py-2 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary/40 transition-all">
            </div>
            <button type="submit"
                    class="col-span-2 sm:col-span-1 inline-flex items-center justify-center gap-2 bg-secondary text-on-secondary px-5 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90 active:scale-95 transition-all">
                <span class="material-symbols-outlined text-sm">filter_alt</span>
                Tampilkan
            </button>
        </div>
    </form>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-secondary/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-secondary text-xl">category</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-on-surface-variant font-semibold uppercase tracking-wider truncate">Total Kategori</p>
                <p class="text-xl font-extrabold text-on-surface mt-0.5">{{ $totalCategories }}</p>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-secondary/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-secondary text-xl">shopping_bag</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-on-surface-variant font-semibold uppercase tracking-wider truncate">Total Qty</p>
                <p class="text-xl font-extrabold text-on-surface mt-0.5">{{ number_format($totalQty) }}</p>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-amber-600 text-xl">shopping_cart</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-on-surface-variant font-semibold uppercase tracking-wider truncate">Total Harga Beli</p>
                <p class="text-base font-extrabold text-on-surface mt-0.5">Rp {{ number_format($totalPurchaseCost, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-rose-600 text-xl">sell</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-on-surface-variant font-semibold uppercase tracking-wider truncate">Total Diskon</p>
                <p class="text-base font-extrabold text-on-surface mt-0.5">Rp {{ number_format($totalDiscount, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-primary text-xl">payments</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-on-surface-variant font-semibold uppercase tracking-wider truncate">Total Penjualan</p>
                <p class="text-base font-extrabold text-on-surface mt-0.5">Rp {{ number_format($grandTotal, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-emerald-600 text-xl">trending_up</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-on-surface-variant font-semibold uppercase tracking-wider truncate">Laba Bersih</p>
                <p class="text-base font-extrabold text-emerald-600 mt-0.5">Rp {{ number_format($totalProfit, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-outline-variant/60 flex items-center justify-between">
            <h2 class="text-sm font-bold text-on-surface">Detail per Kategori</h2>
            <span class="text-xs text-on-surface-variant">
                {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} &ndash;
                {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}
            </span>
        </div>

        @if($items->isEmpty())
            <div class="py-16 flex flex-col items-center gap-3 text-on-surface-variant">
                <span class="material-symbols-outlined text-5xl opacity-30">donut_large</span>
                <p class="text-sm font-medium">Belum ada data penjualan pada periode ini</p>
            </div>
        @else
            {{-- Desktop Table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant text-xs font-semibold uppercase tracking-wider">
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Kategori</th>
                            <th class="px-4 py-3 text-right">Jumlah Produk</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                            <th class="px-4 py-3 text-right">Harga Beli</th>
                            <th class="px-4 py-3 text-right">Diskon</th>
                            <th class="px-4 py-3 text-right">Total Penjualan</th>
                            <th class="px-4 py-3 text-right">Laba</th>
                            <th class="px-4 py-3 text-right">% Kontribusi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        @php
                            $colors = ['bg-secondary','bg-[#e67e22]','bg-[#8e44ad]','bg-[#2980b9]','bg-[#c0392b]','bg-[#16a085]','bg-[#d35400]','bg-[#7f8c8d]'];
                        @endphp
                        @foreach($items as $i => $item)
                        @php $pct = $grandTotal > 0 ? round(($item['total_amount'] / $grandTotal) * 100, 1) : 0; @endphp
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-4 py-3.5 text-on-surface-variant font-mono text-xs">{{ $i + 1 }}</td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-3 h-3 rounded-full {{ $colors[$i % count($colors)] }}"></div>
                                    <span class="font-semibold text-on-surface">{{ $item['category'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-right text-on-surface-variant">{{ $item['product_count'] }} produk</td>
                            <td class="px-4 py-3.5 text-right font-semibold text-on-surface">{{ number_format($item['qty']) }}</td>
                            <td class="px-4 py-3.5 text-right text-on-surface-variant">
                                Rp {{ number_format($item['total_purchase_price'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-rose-600 font-medium">
                                Rp {{ number_format($item['total_discount'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold text-on-surface">
                                Rp {{ number_format($item['total_amount'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-emerald-600">
                                Rp {{ number_format($item['profit'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-16 bg-surface-container rounded-full h-2 overflow-hidden">
                                        <div class="{{ $colors[$i % count($colors)] }} h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-on-surface-variant w-9 text-right">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-surface-container-low font-bold text-on-surface border-t-2 border-outline-variant">
                            <td colspan="3" class="px-4 py-3.5 text-sm uppercase tracking-wider text-on-surface-variant">Total</td>
                            <td class="px-4 py-3.5 text-right">{{ number_format($totalQty) }}</td>
                            <td class="px-4 py-3.5 text-right text-on-surface-variant">Rp {{ number_format($totalPurchaseCost, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right text-rose-600">Rp {{ number_format($totalDiscount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right text-emerald-600">Rp {{ number_format($totalProfit, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right text-xs text-on-surface-variant">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Mobile Card View --}}
            @php $colors = ['bg-secondary','bg-[#e67e22]','bg-[#8e44ad]','bg-[#2980b9]','bg-[#c0392b]','bg-[#16a085]','bg-[#d35400]','bg-[#7f8c8d]']; @endphp
            <div class="block md:hidden divide-y divide-outline-variant/60">
                @foreach($items as $i => $item)
                @php $pct = $grandTotal > 0 ? round(($item['total_amount'] / $grandTotal) * 100, 1) : 0; @endphp
                <div class="p-4 flex flex-col gap-2.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-4 h-4 rounded-full {{ $colors[$i % count($colors)] }} shrink-0"></div>
                        <span class="font-bold text-on-surface text-sm flex-1">{{ $item['category'] }}</span>
                        <span class="text-[10px] font-bold text-on-surface-variant bg-surface-container px-2 py-0.5 rounded-full">{{ $item['product_count'] }} produk</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-outline-variant/40 text-xs">
                        <div>
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block mb-0.5">Qty Terjual</span>
                            <span class="font-extrabold text-on-surface text-sm">{{ number_format($item['qty']) }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block mb-0.5">Total Penjualan</span>
                            <span class="font-bold text-primary text-sm">Rp {{ number_format($item['total_amount'], 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block mb-0.5">Harga Beli</span>
                            <span class="font-medium text-on-surface-variant">Rp {{ number_format($item['total_purchase_price'], 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block mb-0.5">Diskon</span>
                            <span class="font-medium text-rose-600">Rp {{ number_format($item['total_discount'], 0, ',', '.') }}</span>
                        </div>
                        <div class="col-span-2 pt-1 border-t border-outline-variant/30 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Laba Bersih</span>
                            <span class="font-extrabold text-emerald-600 text-sm">Rp {{ number_format($item['profit'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider">Kontribusi</span>
                            <span class="text-[10px] font-bold text-on-surface-variant">{{ $pct }}%</span>
                        </div>
                        <div class="w-full bg-surface-container rounded-full h-2 overflow-hidden">
                            <div class="{{ $colors[$i % count($colors)] }} h-2 rounded-full" style="width: {{ max($pct, 2) }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
                <div class="px-4 py-3 bg-surface-container-low flex justify-between items-center">
                    <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Total</span>
                    <div class="text-right">
                        <div class="text-xs text-on-surface-variant font-semibold">{{ number_format($totalQty) }} items</div>
                        <div class="font-bold text-on-surface">Rp {{ number_format($grandTotal, 0, ',', '.') }}</div>
                        <div class="text-[11px] text-emerald-600 font-bold">Laba: Rp {{ number_format($totalProfit, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- Visual Bar Chart --}}
            <div class="px-5 py-5 border-t border-outline-variant/60">
                <h3 class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-4">Proporsi Penjualan</h3>
                <div class="space-y-3">
                    @foreach($items as $i => $item)
                    @php $pct = $grandTotal > 0 ? round(($item['total_amount'] / $grandTotal) * 100, 1) : 0; @endphp
                    <div class="flex items-center gap-3">
                        <div class="w-28 text-xs text-on-surface-variant font-medium truncate text-right shrink-0">{{ $item['category'] }}</div>
                        <div class="flex-1 bg-surface-container rounded-full h-5 overflow-hidden">
                            <div class="{{ $colors[$i % count($colors)] }} h-5 rounded-full flex items-center justify-end pr-2 transition-all"
                                 style="width: {{ max($pct, 2) }}%">
                                @if($pct >= 8)
                                    <span class="text-white text-[10px] font-bold">{{ $pct }}%</span>
                                @endif
                            </div>
                        </div>
                        @if($pct < 8)
                            <span class="text-[10px] font-bold text-on-surface-variant w-8">{{ $pct }}%</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
