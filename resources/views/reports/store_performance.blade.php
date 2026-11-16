@extends('layouts.app')

@section('title', 'Z1 VapeOS | Executive BI: Performa Seluruh Toko')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16">

    {{-- ═══════════════════════════ HEADER & FILTER BAR ═══════════════════════════ --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-surface-container-lowest p-6 rounded-3xl border border-outline-variant/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container hover:bg-secondary-container/80 transition-colors no-underline">
                    <span class="material-symbols-outlined text-sm">analytics</span>
                    Katalog Laporan
                </a>
                <span class="text-xs text-on-surface-variant font-medium">/</span>
                <span class="text-xs text-[#006c49] font-black uppercase tracking-wider flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">insights</span>
                    Executive Business Intelligence
                </span>
            </div>
            <h1 class="font-headline-lg text-2xl sm:text-3xl font-extrabold text-on-surface flex items-center gap-2.5">
                <span>Performa Seluruh Cabang Toko</span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-600 animate-ping"></span>
                    BI Live
                </span>
            </h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-1">
                Dashboard komparasi performa multi-cabang: Omzet, Laba Bersih, Volume Transaksi, Nilai Persediaan, dan Saldo Berjalan.
            </p>
        </div>

        {{-- Action Buttons (Export & Print) --}}
        <div class="flex flex-wrap items-center gap-2.5 self-start lg:self-auto">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 text-on-surface font-bold rounded-xl text-xs hover:bg-surface-container-high transition-all shadow-2xs">
                <span class="material-symbols-outlined text-base">print</span>
                <span>Cetak / PDF</span>
            </button>
            <a href="{{ route('reports.store-performance.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#006c49] hover:bg-[#005a3c] text-white font-extrabold rounded-xl text-xs shadow-sm hover:shadow transition-all no-underline">
                <span class="material-symbols-outlined text-base">download</span>
                <span>Export Excel (.xlsx)</span>
            </a>
        </div>
    </div>

    {{-- Filter Presets Bar --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        {{-- Quick Date Range Pills --}}
        <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-xs font-bold text-on-surface-variant mr-1 flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">calendar_month</span>
                <span>Periode:</span>
            </span>

            @php
                $filters = [
                    'today' => 'Hari Ini',
                    'yesterday' => 'Kemarin',
                    'this_week' => '7 Hari',
                    'mtd' => 'Bulan Ini',
                    'last_month' => 'Bulan Lalu',
                    'ytd' => 'Tahun Ini',
                ];
            @endphp

            @foreach($filters as $fKey => $fLabel)
            <a href="{{ route('reports.store-performance', array_merge(request()->query(), ['filter' => $fKey, 'start_date' => null, 'end_date' => null])) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === $fKey ? 'bg-[#006c49] text-white shadow-2xs' : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high' }}">
                {{ $fLabel }}
            </a>
            @endforeach

            <button type="button" onclick="toggleCustomDateModal()" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $filter === 'custom' ? 'bg-[#006c49] text-white shadow-2xs' : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high' }}">
                Kustom...
            </button>
        </div>

        {{-- Active Filter Label Indicator --}}
        <div class="flex items-center gap-2 text-xs font-bold text-slate-700 bg-slate-100 px-3.5 py-1.5 rounded-xl border border-slate-200">
            <span class="material-symbols-outlined text-sm text-[#006c49]">date_range</span>
            <span>Rentang: <strong>{{ $filterLabel }}</strong></span>
        </div>
    </div>

    {{-- Custom Date Modal Form (Hidden by Default) --}}
    <div id="custom-date-modal" class="hidden bg-surface-container-lowest border border-[#006c49]/30 rounded-2xl p-5 shadow-md">
        <form action="{{ route('reports.store-performance') }}" method="GET" class="flex flex-col sm:flex-row items-end gap-3.5">
            <input type="hidden" name="filter" value="custom">
            <div class="flex-1 w-full sm:w-auto">
                <label class="block text-xs font-bold text-on-surface-variant mb-1">Mulai Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}" required class="w-full px-3 py-2 rounded-xl border border-outline-variant text-xs bg-surface-container-lowest">
            </div>
            <div class="flex-1 w-full sm:w-auto">
                <label class="block text-xs font-bold text-on-surface-variant mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}" required class="w-full px-3 py-2 rounded-xl border border-outline-variant text-xs bg-surface-container-lowest">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 bg-[#006c49] hover:bg-[#005a3c] text-white font-bold rounded-xl text-xs shadow-xs">
                    Terapkan
                </button>
                <button type="button" onclick="toggleCustomDateModal()" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs">
                    Batal
                </button>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════════ TOP CONSOLIDATED METRICS (5 CARDS) ═══════════════════════════ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        {{-- 1. Total Omzet Jaringan --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Omzet Jaringan</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-[#006c49] flex items-center justify-center">
                    <span class="material-symbols-outlined text-[19px]">payments</span>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl sm:text-2xl font-black text-on-surface font-mono">
                    Rp {{ number_format($totals['total_sales'], 0, ',', '.') }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-emerald-700 font-bold">
                    <span class="material-symbols-outlined text-sm">storefront</span>
                    <span>3 Cabang Retail Aktif</span>
                </div>
            </div>
        </div>

        {{-- 2. Total Laba Bersih --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Laba Bersih Konsolidasian</span>
                <span class="w-8 h-8 rounded-lg bg-teal-500/10 text-teal-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[19px]">trending_up</span>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl sm:text-2xl font-black font-mono {{ $totals['net_profit'] >= 0 ? 'text-[#006c49]' : 'text-red-600' }}">
                    Rp {{ number_format($totals['net_profit'], 0, ',', '.') }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-teal-800 font-bold">
                    <span>Net Margin: <strong>{{ round($totals['net_margin'], 1) }}%</strong></span>
                    <span class="text-slate-400">•</span>
                    <span>Gross: <strong>{{ round($totals['gross_margin'], 1) }}%</strong></span>
                </div>
            </div>
        </div>

        {{-- 3. Total Transaksi & AOV --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Volume Transaksi</span>
                <span class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[19px]">receipt_long</span>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl sm:text-2xl font-black text-on-surface font-mono">
                    {{ number_format($totals['trx_count'], 0, ',', '.') }} <span class="text-xs font-normal text-slate-500">Struk</span>
                </div>
                <div class="mt-1 text-[11px] text-blue-800 font-bold truncate">
                    Rata-rata (AOV): Rp {{ number_format($totals['aov'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- 4. Valuasi Aset Stok --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Valuasi Stok Jaringan</span>
                <span class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[19px]">inventory_2</span>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl sm:text-2xl font-black text-on-surface font-mono">
                    Rp {{ number_format($totals['inventory_valuation'], 0, ',', '.') }}
                </div>
                <div class="flex items-center justify-between mt-1 text-[11px] text-purple-800 font-bold">
                    <span>{{ number_format($totals['total_stock_units'], 0, ',', '.') }} unit fisik</span>
                    @if($totals['low_stock_count'] > 0)
                    <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px]">{{ $totals['low_stock_count'] }} kritis</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- 5. Total Kas & Bank Berjalan --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Kas & Bank Toko Berjalan</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[19px]">account_balance</span>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl sm:text-2xl font-black text-[#006c49] font-mono">
                    Rp {{ number_format($totals['total_ending_balance'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-[11px] text-slate-500 truncate">
                    Akumulasi Saldo Operasional Toko
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════ MULTI-STORE LEADERBOARD CARDS ═══════════════════════════ --}}
    <div>
        <div class="flex items-center justify-between mb-3 px-1">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[#006c49] text-[20px]">leaderboard</span>
                <h2 class="text-sm font-bold text-on-surface uppercase tracking-wider">Perbandingan Cabang Toko (Ranking & Kontribusi)</h2>
            </div>
            <span class="text-xs text-on-surface-variant">Diurutkan berdasarkan total omzet penjualan</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($storesData as $s)
            @php
                $isTopRev = $s['id'] === $topRevenueStoreId && $s['total_sales'] > 0;
                $isMostProfit = $s['id'] === $mostProfitableStoreId && $s['net_profit'] > 0;
            @endphp
            <div class="bg-surface-container-lowest border {{ $isTopRev ? 'border-[#006c49] ring-2 ring-[#006c49]/20 shadow-sm' : 'border-outline-variant/80' }} rounded-3xl p-5 sm:p-6 flex flex-col justify-between space-y-5 transition-all">
                {{-- Store Card Header --}}
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg {{ $s['rank'] === 1 ? 'bg-amber-400 text-slate-900' : 'bg-slate-200 text-slate-700' }} font-black text-xs flex items-center justify-center">
                                #{{ $s['rank'] }}
                            </span>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-surface-container text-on-surface-variant font-bold">
                                {{ $s['store_code'] }}
                            </span>
                        </div>

                        <div class="flex items-center gap-1.5 flex-wrap justify-end">
                            @if($isTopRev)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-900 border border-amber-300">
                                👑 Top Omzet
                            </span>
                            @endif
                            @if($isMostProfit)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                💎 Margin Tertinggi
                            </span>
                            @endif
                        </div>
                    </div>

                    <h3 class="text-lg font-black text-on-surface">{{ $s['store_name'] }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5 truncate">
                        {{ $s['bank_name'] ? "{$s['bank_name']} - {$s['bank_account_number']}" : 'Rekening Kasir Toko' }}
                    </p>

                    {{-- Sales Progress Bar against Chain --}}
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-xs mb-1 font-bold">
                            <span class="text-on-surface-variant">Kontribusi Omzet:</span>
                            <span class="text-[#006c49] font-mono">{{ round($s['sales_contribution'], 1) }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-[#006c49] h-2 rounded-full transition-all" style="width: {{ min(100, max(0, $s['sales_contribution'])) }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Key Financials Breakdown --}}
                <div class="space-y-2.5 pt-3 border-t border-outline-variant/50 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Omzet Penjualan:</span>
                        <span class="font-extrabold font-mono text-sm text-on-surface">Rp {{ number_format($s['total_sales'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Beban Operasional:</span>
                        <span class="font-bold font-mono text-red-600">-Rp {{ number_format($s['total_expenses'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Laba Bersih Toko:</span>
                        <span class="font-extrabold font-mono text-sm {{ $s['net_profit'] >= 0 ? 'text-[#006c49]' : 'text-red-600' }}">
                            Rp {{ number_format($s['net_profit'], 0, ',', '.') }} ({{ round($s['net_margin'], 1) }}%)
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Rata-rata Struk (AOV):</span>
                        <span class="font-bold font-mono text-slate-700">Rp {{ number_format($s['aov'], 0, ',', '.') }} ({{ $s['trx_count'] }} trx)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Valuasi Stok:</span>
                        <span class="font-bold font-mono text-purple-700">Rp {{ number_format($s['inventory_valuation'], 0, ',', '.') }} ({{ $s['total_stock_units'] }} unit)</span>
                    </div>
                </div>

                {{-- Card Footer (Saldo Berjalan & Top Kasir) --}}
                <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-between text-xs bg-slate-50/70 p-3 rounded-2xl">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-500 block">Saldo Toko Berjalan</span>
                        <span class="font-mono font-black text-sm text-[#006c49]">Rp {{ number_format($s['ending_balance'], 0, ',', '.') }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] uppercase font-bold text-slate-500 block">Top Kasir</span>
                        <span class="font-bold text-slate-800 truncate max-w-[120px] block" title="{{ $s['top_cashier_name'] }}">
                            {{ $s['top_cashier_name'] }}
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════════════════ VISUAL BI CHARTS SECTION ═══════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Chart 1: Daily Revenue Trajectory (2 Cols) --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-3xl p-5 sm:p-6 shadow-xs lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-[#006c49] flex items-center justify-center">
                        <span class="material-symbols-outlined text-[19px]">show_chart</span>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-on-surface">Tren Trajectory Penjualan Harian per Cabang</h3>
                        <p class="text-[11px] text-slate-500">Komparasi fluktuasi omzet harian antar outlet pada periode terpilih</p>
                    </div>
                </div>
            </div>
            <div class="relative w-full h-[280px]">
                <canvas id="dailyTrajectoryChart" aria-label="Grafik tren penjualan harian per cabang"></canvas>
            </div>
        </div>

        {{-- Chart 2: Revenue Market Share (1 Col) --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-3xl p-5 sm:p-6 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-teal-500/10 text-teal-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[19px]">donut_large</span>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-on-surface">Pangsa Pasar Cabang</h3>
                        <p class="text-[11px] text-slate-500">Kontribusi revenue terhadap seluruh omzet</p>
                    </div>
                </div>
            </div>
            <div class="relative w-full h-[220px] flex items-center justify-center">
                <canvas id="shareChart" aria-label="Diagram pangsa pasar cabang"></canvas>
            </div>
            <div class="mt-3 pt-3 border-t border-outline-variant/50 space-y-1.5 text-xs">
                @foreach($storesData as $s)
                <div class="flex items-center justify-between">
                    <span class="font-medium text-slate-600 truncate max-w-[160px]">{{ $s['store_name'] }}:</span>
                    <span class="font-mono font-bold text-on-surface">{{ round($s['sales_contribution'], 1) }}% (Rp {{ number_format($s['total_sales'], 0, ',', '.') }})</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Row 2 of Charts: Financial Breakdown & Payment Methods --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Chart 3: Omset vs Beban vs Laba --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-3xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[19px]">bar_chart</span>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-on-surface">Komparasi Omzet vs Beban vs Laba Bersih</h3>
                        <p class="text-[11px] text-slate-500">Evaluasi efisiensi operasional dan laba per cabang toko</p>
                    </div>
                </div>
            </div>
            <div class="relative w-full h-[260px]">
                <canvas id="financialComparisonChart" aria-label="Grafik komparasi omset beban laba"></canvas>
            </div>
        </div>

        {{-- Chart 4: Payment Methods Distribution per Store --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-3xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[19px]">credit_card</span>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-on-surface">Komposisi Metode Pembayaran (Cash / Transfer / QRIS)</h3>
                        <p class="text-[11px] text-slate-500">Distribusi cara bayar pelanggan di setiap cabang</p>
                    </div>
                </div>
            </div>
            <div class="relative w-full h-[260px]">
                <canvas id="paymentMethodsChart" aria-label="Grafik metode pembayaran"></canvas>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════ DETAILED MULTI-STORE PERFORMANCE MATRIX TABLE ═══════════════════════════ --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-3xl shadow-xs overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-outline-variant/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-headline-md text-base sm:text-lg font-black text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#006c49]">table_chart</span>
                    <span>Tabel Matriks Kinerja Finansial & Operasional Seluruh Toko</span>
                </h3>
                <p class="text-xs text-on-surface-variant mt-0.5">
                    Data lengkap terkonsolidasi periode {{ $filterLabel }}
                </p>
            </div>

            <span class="text-xs font-mono font-bold px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700">
                Total: {{ count($storesData) }} Cabang
            </span>
        </div>

        <div class="overflow-x-auto custom-slim-scroll">
            <table class="w-full text-xs text-left border-collapse min-w-[1000px]">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant/80">
                        <th class="py-3.5 px-4 text-center w-12">#</th>
                        <th class="py-3.5 px-4">Cabang Toko</th>
                        <th class="py-3.5 px-4 text-right">Total Omzet</th>
                        <th class="py-3.5 px-4 text-right">Kontribusi</th>
                        <th class="py-3.5 px-4 text-right">HPP (Modal)</th>
                        <th class="py-3.5 px-4 text-right">Beban Toko</th>
                        <th class="py-3.5 px-4 text-right">Laba Bersih</th>
                        <th class="py-3.5 px-4 text-center">Net Margin</th>
                        <th class="py-3.5 px-4 text-center">Struk / AOV</th>
                        <th class="py-3.5 px-4 text-right">Valuasi Stok</th>
                        <th class="py-3.5 px-4 text-center">Stok Kritis</th>
                        <th class="py-3.5 px-4 text-right">Saldo Berjalan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @foreach($storesData as $s)
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="py-3.5 px-4 text-center font-bold text-slate-500">
                            {{ $s['rank'] }}
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-extrabold text-on-surface">{{ $s['store_name'] }}</div>
                            <div class="text-[11px] text-slate-500">{{ $s['bank_name'] ?: 'Kas Toko' }} • {{ $s['staff_count'] }} staff</div>
                        </td>
                        <td class="py-3.5 px-4 text-right font-extrabold font-mono text-on-surface">
                            Rp {{ number_format($s['total_sales'], 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-bold text-[#006c49]">
                            {{ round($s['sales_contribution'], 1) }}%
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono text-slate-600">
                            Rp {{ number_format($s['total_cogs'], 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono text-red-600 font-bold">
                            Rp {{ number_format($s['total_expenses'], 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono font-black {{ $s['net_profit'] >= 0 ? 'text-[#006c49]' : 'text-red-600' }}">
                            Rp {{ number_format($s['net_profit'], 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-center font-bold">
                            <span class="px-2 py-0.5 rounded-full text-[11px] {{ $s['net_margin'] >= 15 ? 'bg-emerald-100 text-emerald-800' : ($s['net_margin'] > 0 ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                                {{ round($s['net_margin'], 1) }}%
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="font-bold text-slate-800">{{ $s['trx_count'] }}</span>
                            <span class="text-[11px] text-slate-500 block font-mono">Rp {{ number_format($s['aov'], 0, ',', '.') }}</span>
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono text-purple-700 font-bold">
                            Rp {{ number_format($s['inventory_valuation'], 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($s['low_stock_count'] > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                {{ $s['low_stock_count'] }} item
                            </span>
                            @else
                            <span class="text-slate-400">0</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono font-extrabold text-[#006c49]">
                            Rp {{ number_format($s['ending_balance'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-emerald-50/80 font-black text-slate-900 border-t-2 border-[#006c49]">
                        <td colspan="2" class="py-4 px-4 uppercase tracking-wider text-xs">
                            KONSOLIDASI SELURUH CABANG
                        </td>
                        <td class="py-4 px-4 text-right font-mono text-sm text-[#006c49]">
                            Rp {{ number_format($totals['total_sales'], 0, ',', '.') }}
                        </td>
                        <td class="py-4 px-4 text-right text-xs">
                            100%
                        </td>
                        <td class="py-4 px-4 text-right font-mono text-xs">
                            Rp {{ number_format($totals['total_cogs'], 0, ',', '.') }}
                        </td>
                        <td class="py-4 px-4 text-right font-mono text-xs text-red-700">
                            Rp {{ number_format($totals['total_expenses'], 0, ',', '.') }}
                        </td>
                        <td class="py-4 px-4 text-right font-mono text-sm {{ $totals['net_profit'] >= 0 ? 'text-[#006c49]' : 'text-red-700' }}">
                            Rp {{ number_format($totals['net_profit'], 0, ',', '.') }}
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="px-2 py-0.5 rounded-full bg-emerald-200 text-emerald-900 text-xs font-black">
                                {{ round($totals['net_margin'], 1) }}%
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center font-mono">
                            {{ number_format($totals['trx_count'], 0, ',', '.') }}
                            <span class="block text-[11px] font-normal text-slate-600">AOV: Rp {{ number_format($totals['aov'], 0, ',', '.') }}</span>
                        </td>
                        <td class="py-4 px-4 text-right font-mono text-purple-900">
                            Rp {{ number_format($totals['inventory_valuation'], 0, ',', '.') }}
                        </td>
                        <td class="py-4 px-4 text-center">
                            {{ $totals['low_stock_count'] }}
                        </td>
                        <td class="py-4 px-4 text-right font-mono text-sm text-[#006c49]">
                            Rp {{ number_format($totals['total_ending_balance'], 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

{{-- ═══════════════════════════ CHART.JS SCRIPTS ═══════════════════════════ --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    function toggleCustomDateModal() {
        const modal = document.getElementById('custom-date-modal');
        if (modal) {
            modal.classList.toggle('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const storesData = @json(array_values($storesData));
        const dateLabels = @json($dateLabels);
        const dailySeries = @json($dailySeries);

        const storeColors = [
            { border: '#006c49', bg: 'rgba(0, 108, 73, 0.15)' },
            { border: '#0284c7', bg: 'rgba(2, 132, 199, 0.15)' },
            { border: '#9333ea', bg: 'rgba(147, 51, 234, 0.15)' },
            { border: '#d97706', bg: 'rgba(217, 119, 6, 0.15)' },
            { border: '#e11d48', bg: 'rgba(225, 29, 72, 0.15)' }
        ];

        // 1. Chart Trajectory Daily Multi-Line
        const trajectoryCanvas = document.getElementById('dailyTrajectoryChart');
        if (trajectoryCanvas) {
            const datasets = dailySeries.map((s, idx) => {
                const c = storeColors[idx % storeColors.length];
                return {
                    label: s.name,
                    data: s.data,
                    borderColor: c.border,
                    backgroundColor: c.bg,
                    borderWidth: 2.5,
                    fill: false,
                    tension: 0.35,
                    pointRadius: dateLabels.length > 15 ? 1.5 : 3.5,
                    pointHoverRadius: 6,
                };
            });

            new Chart(trajectoryCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: dateLabels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11, weight: 'bold' } } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(val) {
                                    if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                    if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                                    return val;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Chart Share (Donut)
        const shareCanvas = document.getElementById('shareChart');
        if (shareCanvas) {
            new Chart(shareCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: storesData.map(s => s.store_name),
                    datasets: [{
                        data: storesData.map(s => s.total_sales),
                        backgroundColor: storeColors.map(c => c.border),
                        borderWidth: 2,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const val = context.raw;
                                    const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                    return context.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(val) + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 3. Chart Financial Comparison (Bar: Omzet vs Beban vs Laba)
        const financialCanvas = document.getElementById('financialComparisonChart');
        if (financialCanvas) {
            new Chart(financialCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: storesData.map(s => s.store_name),
                    datasets: [
                        {
                            label: 'Omzet',
                            data: storesData.map(s => s.total_sales),
                            backgroundColor: '#006c49',
                            borderRadius: 6
                        },
                        {
                            label: 'Beban Toko',
                            data: storesData.map(s => s.total_expenses),
                            backgroundColor: '#e11d48',
                            borderRadius: 6
                        },
                        {
                            label: 'Laba Bersih',
                            data: storesData.map(s => s.net_profit),
                            backgroundColor: '#0284c7',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11, weight: 'bold' } } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(val) {
                                    if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                    if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                                    return val;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 4. Chart Payment Methods (Stacked Bar: Cash vs Transfer vs QRIS)
        const paymentCanvas = document.getElementById('paymentMethodsChart');
        if (paymentCanvas) {
            new Chart(paymentCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: storesData.map(s => s.store_name),
                    datasets: [
                        {
                            label: 'Tunai (Cash)',
                            data: storesData.map(s => s.payment_cash),
                            backgroundColor: '#10b981',
                            borderRadius: 4
                        },
                        {
                            label: 'Bank Transfer',
                            data: storesData.map(s => s.payment_transfer),
                            backgroundColor: '#3b82f6',
                            borderRadius: 4
                        },
                        {
                            label: 'QRIS / EDC',
                            data: storesData.map(s => s.payment_qris),
                            backgroundColor: '#a855f7',
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11, weight: 'bold' } } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        x: { stacked: true },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                callback: function(val) {
                                    if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                    if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                                    return val;
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection

