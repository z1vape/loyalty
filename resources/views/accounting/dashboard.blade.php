@extends('layouts.app')

@section('title', 'Z1 VapeOS | Dashboard Accounting')

@section('content')
<div class="space-y-6">

    {{-- Header & Filter Bar --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container">
                    <span class="material-symbols-outlined text-sm">account_balance</span>
                    Modul Accounting
                </span>
                <span class="text-xs text-on-surface-variant font-medium">Buku Besar &bull; Double Entry &bull; Laporan Finansial</span>
            </div>
            <h1 class="font-headline-lg text-2xl sm:text-3xl font-extrabold text-on-surface">Dashboard Accounting</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant">Monitoring performa laba rugi akrual, posisi neraca toko, audit keseimbangan jurnal umum, dan kepatuhan periode akuntansi.</p>
        </div>

        {{-- Period Filter Pills --}}
        <div class="flex flex-wrap items-center gap-1.5 bg-surface-container-low p-1.5 rounded-2xl border border-outline-variant/60 shadow-2xs">
            <a href="{{ route('accounting.dashboard', ['filter' => 'today']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'today' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                Hari Ini
            </a>
            <a href="{{ route('accounting.dashboard', ['filter' => 'this_week']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'this_week' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                Minggu Ini
            </a>
            <a href="{{ route('accounting.dashboard', ['filter' => 'mtd']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'mtd' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                Bulan Ini
            </a>
            <a href="{{ route('accounting.dashboard', ['filter' => 'last_month']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'last_month' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                Bulan Lalu
            </a>
            <a href="{{ route('accounting.dashboard', ['filter' => 'ytd']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'ytd' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                YTD
            </a>
        </div>
    </div>

    {{-- SECTION 1: System Integrity & Audit Bar --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl {{ $isBalanced ? 'bg-emerald-500/10 text-emerald-700' : 'bg-rose-500/10 text-rose-700' }} flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">{{ $isBalanced ? 'verified_user' : 'error' }}</span>
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-xs sm:text-sm font-extrabold text-on-surface">Integritas Jurnal Umum (Audit Trail)</h3>
                    @if($isBalanced)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500/10 text-emerald-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                            Debit & Kredit Seimbang (100%)
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-500/10 text-rose-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                            Selisih: Rp {{ number_format(abs($totalDebits - $totalCredits), 0, ',', '.') }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-on-surface-variant font-mono mt-0.5">
                    Total Debit: <span class="font-bold text-on-surface">Rp {{ number_format($totalDebits, 0, ',', '.') }}</span> &bull; 
                    Total Kredit: <span class="font-bold text-on-surface">Rp {{ number_format($totalCredits, 0, ',', '.') }}</span>
                </p>
            </div>
        </div>

        {{-- Period Status --}}
        <div class="flex items-center gap-3 shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-outline-variant/60">
            <div class="text-left md:text-right">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">Status Tutup Buku</span>
                @if($currentPeriod)
                    <span class="text-xs font-bold text-on-surface">{{ $currentPeriod->name }}</span>
                    <span class="text-[10px] text-emerald-700 font-bold block">Periode Aktif (Terbuka)</span>
                @else
                    <span class="text-xs font-bold text-on-surface-variant">Periode Berjalan Terbuka</span>
                    <span class="text-[10px] text-on-surface-variant block">Siap tutup buku berkala</span>
                @endif
            </div>
            <a href="{{ route('accounting.periods.index') }}" 
               class="px-3 py-1.5 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-bold rounded-xl transition no-underline flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">lock_clock</span>
                <span>Tutup Buku</span>
            </a>
        </div>
    </div>

    {{-- SECTION 2: Income Statement (Laba Rugi Periode) KPI Grid --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-extrabold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-[#006c49]">trending_up</span>
                Ringkasan Laba Rugi Akuntansi (Periode: {{ $filterLabel }})
            </h2>
            <a href="{{ route('accounting.reports.profit-loss') }}" class="text-xs font-bold text-[#006c49] hover:underline flex items-center gap-1">
                Laporan P&L Lengkap
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
            {{-- 1. Pendapatan Penjualan --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">1. Pendapatan Bersih</span>
                <h3 class="text-lg sm:text-xl font-black text-on-surface font-mono mt-1">
                    Rp {{ number_format($grossRevenue, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-on-surface-variant mt-1 truncate">
                    Bruto: Rp {{ number_format($salesRevenue, 0, ',', '.') }}
                </p>
            </div>

            {{-- 2. HPP (COGS) --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">2. HPP / Modal Barang</span>
                <h3 class="text-lg sm:text-xl font-black text-on-surface font-mono mt-1">
                    Rp {{ number_format($cogs, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-on-surface-variant mt-1">
                    Akun 5101 Beban Pokok
                </p>
            </div>

            {{-- 3. Laba Kotor --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">3. Laba Kotor</span>
                    <span class="text-[10px] font-bold text-[#006c49] bg-emerald-500/10 px-1.5 py-0.5 rounded">
                        {{ number_format($grossMargin, 1) }}%
                    </span>
                </div>
                <h3 class="text-lg sm:text-xl font-black text-emerald-700 font-mono mt-1">
                    Rp {{ number_format($grossProfit, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-on-surface-variant mt-1">Gross Profit Margin</p>
            </div>

            {{-- 4. Beban Operasional --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">4. Beban Operasional</span>
                <h3 class="text-lg sm:text-xl font-black text-rose-600 font-mono mt-1">
                    Rp {{ number_format($operatingExpenses, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-on-surface-variant mt-1 truncate">
                    {{ count($expenseBreakdown) }} akun beban terpakai
                </p>
            </div>

            {{-- 5. Laba Bersih --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">5. Laba Bersih</span>
                    <span class="text-[10px] font-bold {{ $netProfit >= 0 ? 'text-[#006c49] bg-emerald-500/10' : 'text-rose-700 bg-rose-500/10' }} px-1.5 py-0.5 rounded">
                        {{ number_format($netMargin, 1) }}%
                    </span>
                </div>
                <h3 class="text-lg sm:text-xl font-black font-mono mt-1 {{ $netProfit >= 0 ? 'text-[#006c49]' : 'text-rose-600' }}">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </h3>
                <p class="text-[10px] text-on-surface-variant mt-1">Net Profit Margin</p>
            </div>
        </div>
    </div>

    {{-- SECTION 3: Balance Sheet Snapshot (Posisi Neraca Toko Real-Time) --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 sm:p-6 shadow-xs">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-outline-variant/60">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-secondary-container/60 text-secondary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
                </span>
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-on-surface">Snapshot Neraca Toko (Balance Sheet)</h2>
                    <p class="text-xs text-on-surface-variant">Posisi aset lancar, liabilitas jangka pendek, dan ekuitas toko saat ini</p>
                </div>
            </div>
            <a href="{{ route('accounting.reports.balance-sheet') }}" 
               class="text-xs font-bold text-[#006c49] hover:underline flex items-center gap-1">
                Laporan Neraca Lengkap
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Kolom 1: Total Aset --}}
            <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant/60 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">TOTAL ASET</span>
                    <span class="material-symbols-outlined text-emerald-700 text-lg">check_circle</span>
                </div>
                <div>
                    <h3 class="text-xl sm:text-2xl font-black text-on-surface font-mono">
                        Rp {{ number_format($totalAssets, 0, ',', '.') }}
                    </h3>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">Seluruh sumber daya ekonomi toko</p>
                </div>
                <div class="pt-2 border-t border-outline-variant/50 space-y-1.5 text-xs">
                    <div class="flex justify-between text-on-surface-variant">
                        <span>1101 - Kas Fisik Toko:</span>
                        <span class="font-mono font-semibold text-on-surface">Rp {{ number_format($kasToko, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-on-surface-variant">
                        <span>1102 - Bank Toko (BCA):</span>
                        <span class="font-mono font-semibold text-on-surface">Rp {{ number_format($bankToko, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-on-surface-variant">
                        <span>1103 - Piutang QRIS/EDC:</span>
                        <span class="font-mono font-semibold text-on-surface">Rp {{ number_format($piutangQris, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-on-surface-variant">
                        <span>1201 - Persediaan Barang:</span>
                        <span class="font-mono font-semibold text-on-surface">Rp {{ number_format($persediaan, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Kolom 2: Total Liabilitas --}}
            <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant/60 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">TOTAL LIABILITAS</span>
                    <span class="material-symbols-outlined text-amber-700 text-lg">pending_actions</span>
                </div>
                <div>
                    <h3 class="text-xl sm:text-2xl font-black text-amber-600 font-mono">
                        Rp {{ number_format($totalLiabilities, 0, ',', '.') }}
                    </h3>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">Kewajiban pembayaran kepada pihak ketiga</p>
                </div>
                <div class="pt-2 border-t border-outline-variant/50 space-y-1.5 text-xs">
                    <div class="flex justify-between text-on-surface-variant">
                        <span>2101 - Hutang Dagang Supplier:</span>
                        <span class="font-mono font-semibold text-on-surface">Rp {{ number_format($totalLiabilities, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-on-surface-variant">
                        <span>Hutang Jatuh Tempo:</span>
                        <span class="font-mono font-semibold text-on-surface">Termasuk Tempo</span>
                    </div>
                </div>
            </div>

            {{-- Kolom 3: Total Ekuitas --}}
            <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant/60 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">TOTAL EKUITAS</span>
                    <span class="material-symbols-outlined text-[#006c49] text-lg">pie_chart</span>
                </div>
                <div>
                    <h3 class="text-xl sm:text-2xl font-black text-[#006c49] font-mono">
                        Rp {{ number_format($totalEquity, 0, ',', '.') }}
                    </h3>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">Hak pemilik atas modal & akumulasi laba</p>
                </div>
                <div class="pt-2 border-t border-outline-variant/50 space-y-1.5 text-xs">
                    <div class="flex justify-between text-on-surface-variant">
                        <span>Modal Awal & Laba Ditahan:</span>
                        <span class="font-mono font-semibold text-on-surface">Akumulasi</span>
                    </div>
                    <div class="flex justify-between text-on-surface-variant">
                        <span>Laba Bersih Periode Berjalan:</span>
                        <span class="font-mono font-semibold text-on-surface">Rp {{ number_format($netProfit, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 4: Two-Column Analytics Layout (8 cols / 4 cols) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Left: Beban Operasional Breakdown & Recent Journals (8 cols) --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- Beban Breakdown --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-outline-variant/60">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-700 flex items-center justify-center">
                            <span class="material-symbols-outlined text-base">format_list_bulleted</span>
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-on-surface">Beban Operasional per Akun Buku Besar</h3>
                            <p class="text-[11px] text-on-surface-variant">Rincian beban (Akun Kepala 6) pada periode {{ $filterLabel }}</p>
                        </div>
                    </div>
                    <a href="{{ route('accounting.journals.ledger') }}" class="text-xs font-bold text-[#006c49] hover:underline flex items-center gap-1">
                        Buku Besar
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>

                <div class="space-y-3.5">
                    @forelse($expenseBreakdown as $exp)
                        @php
                            $pct = $operatingExpenses > 0 ? ($exp['amount'] / $operatingExpenses) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                <span class="font-bold text-on-surface flex items-center gap-1.5">
                                    <span class="font-mono text-on-surface-variant text-[11px] bg-surface-container px-1.5 py-0.5 rounded">{{ $exp['code'] }}</span>
                                    <span>{{ $exp['name'] }}</span>
                                </span>
                                <span class="font-mono font-bold text-on-surface">
                                    Rp {{ number_format($exp['amount'], 0, ',', '.') }}
                                    <span class="text-[10px] text-on-surface-variant font-normal">({{ round($pct, 1) }}%)</span>
                                </span>
                            </div>
                            <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden">
                                <div class="bg-rose-500 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, max(2, $pct)) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-on-surface-variant text-center py-6">Tidak ada saldo beban operasional pada periode ini.</p>
                    @endforelse
                </div>
            </div>

            {{-- Recent Journal Entries Table --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-5 border-b border-outline-variant/60 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-secondary-container/60 text-secondary flex items-center justify-center">
                            <span class="material-symbols-outlined text-base">receipt_long</span>
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-on-surface">Entri Jurnal Umum Terkini</h3>
                            <p class="text-[11px] text-on-surface-variant">Transaksi akuntansi otomatis & manual terbaru</p>
                        </div>
                    </div>
                    <a href="{{ route('accounting.journals.index') }}" class="text-xs font-bold text-[#006c49] hover:underline flex items-center gap-1">
                        Semua Jurnal
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-surface-container-low text-on-surface-variant font-bold uppercase tracking-wider text-[10px] border-b border-outline-variant/60">
                                <th class="px-5 py-3">No. Jurnal</th>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Keterangan</th>
                                <th class="px-5 py-3 text-right">Total Debit/Kredit</th>
                                <th class="px-5 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            @forelse($recentJournals as $j)
                                @php
                                    $entryTotal = $j->items->sum('debit');
                                @endphp
                                <tr class="hover:bg-surface-container-low/50 transition-colors">
                                    <td class="px-5 py-3 font-mono font-bold text-on-surface whitespace-nowrap">
                                        <a href="{{ route('accounting.journals.index', ['search' => $j->entry_number]) }}" class="text-[#006c49] hover:underline" title="Lihat rincian di Jurnal Transaksi">
                                            {{ $j->entry_number }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 whitespace-nowrap text-on-surface-variant">
                                        {{ \Carbon\Carbon::parse($j->entry_date)->format('d M Y') }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="font-semibold text-on-surface max-w-xs truncate" title="{{ $j->description }}">{{ $j->description }}</div>
                                        <div class="text-[10px] text-on-surface-variant">
                                            {{ $j->items->count() }} pos akun
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono font-bold text-on-surface whitespace-nowrap">
                                        Rp {{ number_format($entryTotal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-800">
                                            Posted
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-6 text-center text-on-surface-variant">
                                        Belum ada entri jurnal yang tercatat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- Right: Quick Navigation & Financial Reports (4 cols) --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- Laporan Finansial Resmi --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs">
                <h3 class="text-sm font-extrabold text-on-surface mb-1 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#006c49]">analytics</span>
                    Laporan Finansial Standar
                </h3>
                <p class="text-[11px] text-on-surface-variant mb-4">Akses cepat laporan akuntansi terverifikasi</p>

                <div class="space-y-2">
                    <a href="{{ route('accounting.reports.profit-loss') }}" 
                       class="flex items-center justify-between p-3 rounded-xl bg-surface-container-low hover:bg-surface-container transition no-underline text-on-surface group">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-lg text-[#006c49]">trending_up</span>
                            <div>
                                <span class="text-xs font-bold block">Laporan Laba Rugi</span>
                                <span class="text-[10px] text-on-surface-variant">Profit & Loss Statement</span>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-sm text-on-surface-variant group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                    </a>

                    <a href="{{ route('accounting.reports.balance-sheet') }}" 
                       class="flex items-center justify-between p-3 rounded-xl bg-surface-container-low hover:bg-surface-container transition no-underline text-on-surface group">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-lg text-[#006c49]">account_balance_wallet</span>
                            <div>
                                <span class="text-xs font-bold block">Neraca Keuangan</span>
                                <span class="text-[10px] text-on-surface-variant">Balance Sheet Statement</span>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-sm text-on-surface-variant group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                    </a>

                    <a href="{{ route('accounting.reports.trial-balance') }}" 
                       class="flex items-center justify-between p-3 rounded-xl bg-surface-container-low hover:bg-surface-container transition no-underline text-on-surface group">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-lg text-[#006c49]">balance</span>
                            <div>
                                <span class="text-xs font-bold block">Neraca Saldo</span>
                                <span class="text-[10px] text-on-surface-variant">Trial Balance</span>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-sm text-on-surface-variant group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                    </a>

                    <a href="{{ route('accounting.reports.cash-flow') }}" 
                       class="flex items-center justify-between p-3 rounded-xl bg-surface-container-low hover:bg-surface-container transition no-underline text-on-surface group">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-lg text-[#006c49]">payments</span>
                            <div>
                                <span class="text-xs font-bold block">Arus Kas</span>
                                <span class="text-[10px] text-on-surface-variant">Cash Flow Statement</span>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-sm text-on-surface-variant group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                    </a>
                </div>
            </div>

            {{-- Navigasi Pembukuan & Pengaturan --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs">
                <h3 class="text-sm font-extrabold text-on-surface mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">tune</span>
                    Administrasi Pembukuan
                </h3>

                <div class="space-y-1.5 text-xs">
                    <a href="{{ route('accounting.accounts.index') }}" 
                       class="flex items-center gap-2 px-3 py-2 rounded-xl text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition no-underline font-medium">
                        <span class="material-symbols-outlined text-base text-secondary">account_tree</span>
                        <span>Bagan Akun (Chart of Accounts)</span>
                    </a>
                    <a href="{{ route('accounting.journals.ledger') }}" 
                       class="flex items-center gap-2 px-3 py-2 rounded-xl text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition no-underline font-medium">
                        <span class="material-symbols-outlined text-base text-secondary">menu_book</span>
                        <span>Buku Besar per Akun</span>
                    </a>
                    <a href="{{ route('accounting.transfers.index') }}" 
                       class="flex items-center gap-2 px-3 py-2 rounded-xl text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition no-underline font-medium">
                        <span class="material-symbols-outlined text-base text-secondary">swap_horiz</span>
                        <span>Transfer Kas Antar Rekening</span>
                    </a>
                    <a href="{{ route('accounting.periods.index') }}" 
                       class="flex items-center gap-2 px-3 py-2 rounded-xl text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition no-underline font-medium">
                        <span class="material-symbols-outlined text-base text-secondary">lock_clock</span>
                        <span>Kelola Tutup Buku Periode</span>
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
