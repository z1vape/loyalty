@extends('layouts.app')

@section('title', 'Laporan Penjualan per Staff | InvenTrack')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Laporan Penjualan & Movement per Staff</h1>
            <p class="text-on-surface-variant text-body-lg">Ringkasan performa penjualan dan aktivitas pergerakan stok oleh masing-masing staff/kasir</p>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('reports.by-staff') }}"
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-secondary/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-secondary">payments</span>
            </div>
            <div>
                <p class="text-xs text-on-surface-variant font-semibold uppercase tracking-wider">Total Penjualan</p>
                <p class="text-xl font-extrabold text-on-surface mt-0.5">Rp {{ number_format($grandTotalSales, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-secondary/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-secondary">receipt_long</span>
            </div>
            <div>
                <p class="text-xs text-on-surface-variant font-semibold uppercase tracking-wider">Total Transaksi</p>
                <p class="text-2xl font-extrabold text-on-surface mt-0.5">{{ number_format($grandTotalTransactions) }}</p>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-secondary/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-secondary">shopping_bag</span>
            </div>
            <div>
                <p class="text-xs text-on-surface-variant font-semibold uppercase tracking-wider">Items Terjual (Out)</p>
                <p class="text-2xl font-extrabold text-on-surface mt-0.5">{{ number_format($grandTotalOutQty) }}</p>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-amber-500/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-amber-600">military_tech</span>
            </div>
            <div>
                <p class="text-xs text-on-surface-variant font-semibold uppercase tracking-wider">Top Performer</p>
                <p class="text-base font-extrabold text-on-surface mt-0.5 truncate max-w-[150px]" title="{{ $topPerformer['name'] ?? '-' }}">
                    {{ $topPerformer['name'] ?? '-' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-outline-variant/60 flex items-center justify-between">
            <h2 class="text-sm font-bold text-on-surface">Performa Penjualan & Pergerakan Stok Staff</h2>
            <span class="text-xs text-on-surface-variant">
                {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} &ndash;
                {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}
            </span>
        </div>

        @if($items->isEmpty())
            <div class="py-16 flex flex-col items-center gap-3 text-on-surface-variant">
                <span class="material-symbols-outlined text-5xl opacity-30">badge</span>
                <p class="text-sm font-medium">Belum ada data aktivitas staff pada periode ini</p>
            </div>
        @else
            {{-- Desktop Table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant text-xs font-semibold uppercase tracking-wider">
                            <th class="px-5 py-3 text-left">#</th>
                            <th class="px-5 py-3 text-left">Staff / Kasir</th>
                            <th class="px-5 py-3 text-left">Posisi / Role</th>
                            <th class="px-5 py-3 text-right">Total Transaksi</th>
                            <th class="px-5 py-3 text-right">Qty In (Masuk)</th>
                            <th class="px-5 py-3 text-right">Qty Out (Keluar)</th>
                            <th class="px-5 py-3 text-right">Rata-rata / Tx</th>
                            <th class="px-5 py-3 text-right">Total Sales</th>
                            <th class="px-5 py-3 text-right">% Kontribusi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        @foreach($items as $i => $item)
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-5 py-3.5 text-on-surface-variant font-mono text-xs">{{ $i + 1 }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-bold text-xs shrink-0 overflow-hidden">
                                        @if(!empty($item['photo']))
                                            <img src="{{ asset('storage/' . $item['photo']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                                        @else
                                            {{ strtoupper(substr($item['name'], 0, 2)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-semibold text-on-surface">{{ $item['name'] }}</p>
                                        <p class="text-xs text-on-surface-variant">{{ $item['email'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-surface-container text-on-surface-variant text-xs font-medium">
                                    {{ $item['position'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-semibold text-on-surface">
                                {{ number_format($item['total_transactions']) }}
                            </td>
                            <td class="px-5 py-3.5 text-right text-emerald-600 font-medium">
                                +{{ number_format($item['total_in_qty']) }}
                            </td>
                            <td class="px-5 py-3.5 text-right text-rose-600 font-semibold">
                                {{ number_format($item['total_out_qty']) }}
                            </td>
                            <td class="px-5 py-3.5 text-right text-on-surface-variant">
                                Rp {{ number_format($item['avg_transaction'], 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-bold text-on-surface">
                                Rp {{ number_format($item['total_sales'], 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @php $pct = $grandTotalSales > 0 ? round(($item['total_sales'] / $grandTotalSales) * 100, 1) : 0; @endphp
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-16 bg-surface-container rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-secondary h-1.5 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-on-surface-variant w-10 text-right">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-surface-container-low font-bold text-on-surface border-t-2 border-outline-variant">
                            <td colspan="3" class="px-5 py-3.5 text-sm uppercase tracking-wider text-on-surface-variant">Total Overall</td>
                            <td class="px-5 py-3.5 text-right">{{ number_format($grandTotalTransactions) }}</td>
                            <td class="px-5 py-3.5 text-right text-emerald-700">+{{ number_format($grandTotalInQty) }}</td>
                            <td class="px-5 py-3.5 text-right text-rose-700">{{ number_format($grandTotalOutQty) }}</td>
                            <td class="px-5 py-3.5 text-right text-xs text-on-surface-variant">-</td>
                            <td class="px-5 py-3.5 text-right">Rp {{ number_format($grandTotalSales, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5 text-right text-xs text-on-surface-variant">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Mobile Card View --}}
            <div class="block md:hidden divide-y divide-outline-variant/60">
                @foreach($items as $i => $item)
                @php $pct = $grandTotalSales > 0 ? round(($item['total_sales'] / $grandTotalSales) * 100, 1) : 0; @endphp
                <div class="p-4 flex flex-col gap-3">
                    {{-- Staff info --}}
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-bold text-sm shrink-0 overflow-hidden border border-outline-variant">
                            @if(!empty($item['photo']))
                                <img src="{{ asset('storage/' . $item['photo']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                            @else
                                {{ strtoupper(substr($item['name'], 0, 2)) }}
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-on-surface text-sm">{{ $item['name'] }}</div>
                            <div class="text-xs text-on-surface-variant truncate">{{ $item['email'] }}</div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant text-[10px] font-medium mt-0.5">{{ $item['position'] }}</span>
                        </div>
                        <span class="text-[10px] font-bold text-on-surface-variant bg-surface-container px-2 py-1 rounded-full shrink-0">#{{ $i + 1 }}</span>
                    </div>

                    {{-- Metrics grid --}}
                    <div class="grid grid-cols-2 gap-2 border-t border-outline-variant/40 pt-3">
                        <div>
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block mb-0.5">Total Transaksi</span>
                            <span class="font-extrabold text-on-surface text-base">{{ number_format($item['total_transactions']) }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block mb-0.5">Total Sales</span>
                            <span class="font-bold text-primary text-sm">Rp {{ number_format($item['total_sales'], 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block mb-0.5">Qty In (Masuk)</span>
                            <span class="font-semibold text-emerald-600 text-sm">+{{ number_format($item['total_in_qty']) }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block mb-0.5">Qty Out (Keluar)</span>
                            <span class="font-semibold text-rose-600 text-sm">{{ number_format($item['total_out_qty']) }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block mb-0.5">Rata-rata / Transaksi</span>
                            <span class="font-medium text-on-surface-variant text-xs">Rp {{ number_format($item['avg_transaction'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    {{-- Contribution bar --}}
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
                <div class="px-4 py-3 bg-surface-container-low">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Total Overall</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase block mb-0.5">Total Transaksi</span>
                            <span class="font-bold text-on-surface">{{ number_format($grandTotalTransactions) }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase block mb-0.5">Total Penjualan</span>
                            <span class="font-bold text-on-surface">Rp {{ number_format($grandTotalSales, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase block mb-0.5">Total In</span>
                            <span class="font-bold text-emerald-700">+{{ number_format($grandTotalInQty) }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-on-surface-variant uppercase block mb-0.5">Total Out</span>
                            <span class="font-bold text-rose-700">{{ number_format($grandTotalOutQty) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
