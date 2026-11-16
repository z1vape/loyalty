@extends('layouts.app')

@section('title', 'Laporan Penjualan per SKU | InvenTrack')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Penjualan berdasarkan SKU</h1>
            <p class="text-on-surface-variant text-body-lg">Ringkasan penjualan per produk dalam periode tertentu</p>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('reports.by-sku') }}"
          class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex flex-col gap-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="w-full border border-outline-variant bg-surface-container-low rounded-xl px-3 py-2 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary/40 transition-all">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Tanggal Akhir</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="w-full border border-outline-variant bg-surface-container-low rounded-xl px-3 py-2 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary/40 transition-all">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Kategori</label>
                <select name="category"
                        class="w-full border border-outline-variant bg-surface-container-low rounded-xl px-3 py-2 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary/40 transition-all cursor-pointer">
                    <option value="">Semua Kategori</option>
                    @if(isset($categories))
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Cari Produk / SKU</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama atau SKU..."
                           class="w-full border border-outline-variant bg-surface-container-low rounded-xl pl-9 pr-3 py-2 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary/40 transition-all">
                    <span class="material-symbols-outlined absolute left-2.5 top-2.5 text-on-surface-variant text-sm">search</span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-outline-variant/40">
            <div class="flex items-center gap-2">
                @if(request('category') || request('search') || request('start_date') || request('end_date'))
                    <a href="{{ route('reports.by-sku') }}" class="text-xs text-on-surface-variant hover:text-error transition-colors flex items-center gap-1 font-medium">
                        <span class="material-symbols-outlined text-xs">close</span>
                        Reset Filter
                    </a>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" formaction="{{ route('reports.by-sku.export') }}" formmethod="GET"
                        class="inline-flex items-center justify-center gap-1.5 border border-outline-variant bg-surface-container-low hover:bg-surface-container text-on-surface px-4 py-2 rounded-xl text-xs font-semibold active:scale-95 transition-all"
                        title="Export ringkasan seluruh produk per SKU">
                    <span class="material-symbols-outlined text-emerald-600 text-sm">table_view</span>
                    <span>Export Excel All</span>
                </button>
                <button type="submit" formaction="{{ route('reports.by-sku.export-detail') }}" formmethod="GET"
                        class="inline-flex items-center justify-center gap-1.5 border border-outline-variant bg-surface-container-low hover:bg-surface-container text-on-surface px-4 py-2 rounded-xl text-xs font-semibold active:scale-95 transition-all"
                        title="Export detail penjualan produk breakdown per 1 unit qty">
                    <span class="material-symbols-outlined text-emerald-600 text-sm">format_list_bulleted</span>
                    <span>Export Detail Produk</span>
                </button>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 bg-secondary text-on-secondary px-5 py-2 rounded-xl text-xs font-semibold hover:opacity-90 active:scale-95 transition-all">
                    <span class="material-symbols-outlined text-sm">filter_alt</span>
                    <span>Tampilkan</span>
                </button>
            </div>
        </div>
    </form>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-secondary/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-secondary text-xl">inventory_2</span>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-on-surface-variant font-semibold uppercase tracking-wider truncate">Total Produk</p>
                <p class="text-xl font-extrabold text-on-surface mt-0.5">{{ $totalProducts }}</p>
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
        <div class="px-5 py-4 border-b border-outline-variant/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-on-surface">Detail per SKU</h2>
                <div class="flex flex-wrap items-center gap-1.5 mt-1">
                    <span class="text-xs text-on-surface-variant">
                        Periode: {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} &ndash;
                        {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}
                    </span>
                    @if(request('category'))
                        <span class="text-[11px] bg-secondary/10 text-secondary px-2 py-0.5 rounded-full font-semibold">
                            Kategori: {{ request('category') }}
                        </span>
                    @endif
                    @if(request('search'))
                        <span class="text-[11px] bg-primary/10 text-primary px-2 py-0.5 rounded-full font-semibold">
                            Pencarian: "{{ request('search') }}"
                        </span>
                    @endif
                </div>
            </div>
        </div>

        @if($items->isEmpty())
            <div class="py-16 flex flex-col items-center gap-3 text-on-surface-variant">
                <span class="material-symbols-outlined text-5xl opacity-30">bar_chart</span>
                <p class="text-sm font-medium">Belum ada data penjualan pada periode ini</p>
            </div>
        @else
            {{-- Desktop Table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant text-xs font-semibold uppercase tracking-wider">
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">SKU</th>
                            <th class="px-4 py-3 text-left">Nama Produk</th>
                            <th class="px-4 py-3 text-left">Kategori</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                            <th class="px-4 py-3 text-right">Harga Beli</th>
                            <th class="px-4 py-3 text-right">Diskon</th>
                            <th class="px-4 py-3 text-right">Total Penjualan</th>
                            <th class="px-4 py-3 text-right">Laba</th>
                            <th class="px-4 py-3 text-right">% Kontribusi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        @foreach($items as $i => $item)
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-4 py-3.5 text-on-surface-variant font-mono text-xs">{{ $i + 1 }}</td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 bg-surface-container text-on-surface-variant text-label-sm font-label-sm rounded font-mono">
                                    {{ $item['sku'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-on-surface">
                                {{ $item['name'] }}
                                @if(!empty($item['variant']))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-secondary/10 text-secondary text-xs font-semibold ml-1">
                                        {{ $item['variant'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-secondary/10 text-secondary text-xs font-semibold">
                                    {{ $item['category'] }}
                                </span>
                            </td>
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
                                @php $pct = $grandTotal > 0 ? round(($item['total_amount'] / $grandTotal) * 100, 1) : 0; @endphp
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-12 bg-surface-container rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-secondary h-1.5 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-on-surface-variant w-9 text-right">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-surface-container-low font-bold text-on-surface border-t-2 border-outline-variant">
                            <td colspan="4" class="px-4 py-3.5 text-sm uppercase tracking-wider text-on-surface-variant">Total</td>
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
            <div class="block md:hidden divide-y divide-outline-variant/60">
                @foreach($items as $i => $item)
                @php $pct = $grandTotal > 0 ? round(($item['total_amount'] / $grandTotal) * 100, 1) : 0; @endphp
                <div class="p-4 flex flex-col gap-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <span class="inline-flex items-center px-2 py-0.5 bg-surface-container text-on-surface-variant text-[11px] rounded font-mono font-bold mb-1">{{ $item['sku'] }}</span>
                            <div class="font-bold text-on-surface text-sm">
                                {{ $item['name'] }}
                                @if(!empty($item['variant']))
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full bg-secondary/10 text-secondary text-[10px] font-semibold ml-1">{{ $item['variant'] }}</span>
                                @endif
                            </div>
                            @if($item['category'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-secondary/10 text-secondary text-[10px] font-semibold mt-1">{{ $item['category'] }}</span>
                            @endif
                        </div>
                        <span class="text-[10px] font-bold text-on-surface-variant bg-surface-container px-2 py-1 rounded-full shrink-0">#{{ $i + 1 }}</span>
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
                            <span class="text-[10px] font-bold text-secondary">{{ $pct }}%</span>
                        </div>
                        <div class="w-full bg-surface-container rounded-full h-1.5 overflow-hidden">
                            <div class="bg-secondary h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
                {{-- Total footer --}}
                <div class="px-4 py-3 bg-surface-container-low flex justify-between items-center">
                    <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Total</span>
                    <div class="text-right">
                        <div class="text-xs text-on-surface-variant font-semibold">{{ number_format($totalQty) }} items</div>
                        <div class="font-bold text-on-surface">Rp {{ number_format($grandTotal, 0, ',', '.') }}</div>
                        <div class="text-[11px] text-emerald-600 font-bold">Laba: Rp {{ number_format($totalProfit, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
