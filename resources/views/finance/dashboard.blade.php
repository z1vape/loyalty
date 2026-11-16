@extends('layouts.app')

@section('title', 'Z1 VapeOS | Dashboard Finance')

@section('content')
<div class="space-y-6">

    {{-- Header & Filter Bar --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container">
                    <span class="material-symbols-outlined text-sm">account_balance_wallet</span>
                    Modul Finance
                </span>
                <span class="text-xs text-on-surface-variant font-medium">Likuiditas Kas & Bank &bull; Arus Kas</span>
            </div>
            <h1 class="font-headline-lg text-2xl sm:text-3xl font-extrabold text-on-surface">Dashboard Finance</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant">Monitoring saldo kas fisik, mutasi rekening bank, arus kas operasional, dan rincian beban pengeluaran toko.</p>
        </div>

        {{-- Period Filter Pills --}}
        <div class="flex flex-wrap items-center gap-1.5 bg-surface-container-low p-1.5 rounded-2xl border border-outline-variant/60 shadow-2xs">
            <a href="{{ route('finance.dashboard', ['filter' => 'today']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'today' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                Hari Ini
            </a>
            <a href="{{ route('finance.dashboard', ['filter' => 'this_week']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'this_week' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                Minggu Ini
            </a>
            <a href="{{ route('finance.dashboard', ['filter' => 'mtd']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'mtd' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                Bulan Ini
            </a>
            <a href="{{ route('finance.dashboard', ['filter' => 'last_month']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'last_month' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                Bulan Lalu
            </a>
            <a href="{{ route('finance.dashboard', ['filter' => 'ytd']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'ytd' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                YTD
            </a>
        </div>
    </div>

    {{-- SECTION 1: Real-Time Liquidity Breakdown (Kas & Bank) --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-6 shadow-xs">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-outline-variant/60">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">account_balance</span>
                </span>
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-on-surface">Posisi Likuiditas Kas & Bank (Real-Time)</h2>
                    <p class="text-xs text-on-surface-variant">Rincian saldo riil per rekening buku besar kas dan bank</p>
                </div>
            </div>
            <a href="{{ route('accounting.transfers.create') }}" 
               class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#006c49] text-white rounded-xl text-xs font-bold hover:opacity-90 transition no-underline">
                <span class="material-symbols-outlined text-sm">swap_horiz</span>
                <span>Transfer Kas / Bank</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Saldo Toko Berjalan --}}
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider block">Total Saldo Toko Berjalan</span>
                <h3 class="text-xl sm:text-2xl font-black text-emerald-800 font-mono mt-1">
                    Rp {{ number_format($storeEndingBalance, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-emerald-800/80 mt-1">Akumulatif Kas Fisik + Bank Toko</p>
            </div>

            {{-- Rekening Bank Toko (BCA) --}}
            <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant/60 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">1102 - Bank Toko</span>
                        <a href="{{ route('accounting.reconciliations.create') }}" class="text-[10px] font-bold text-[#006c49] bg-emerald-500/10 hover:bg-[#006c49] hover:text-white px-2 py-0.5 rounded-full transition-colors no-underline flex items-center gap-1" title="Samakan Saldo dengan Rekening ATM">
                            <span class="material-symbols-outlined text-[13px]">sync_alt</span>
                            <span>Samakan ATM</span>
                        </a>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-on-surface font-mono mt-1">
                        Rp {{ number_format($bankBalance, 0, ',', '.') }}
                    </h3>
                </div>
                <p class="text-[11px] text-on-surface-variant mt-1 truncate" title="{{ $bankSubtitle }}">{{ $bankSubtitle }}</p>
            </div>

            {{-- Kas Toko (Tunai) --}}
            <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant/60">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">1101 - Kas Toko</span>
                <h3 class="text-xl sm:text-2xl font-black font-mono mt-1 {{ $cashBalance < 0 ? 'text-rose-600' : 'text-on-surface' }}">
                    Rp {{ number_format($cashBalance, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-on-surface-variant mt-1">Kas Tunai Fisik di Toko</p>
            </div>

            {{-- Piutang QRIS & EDC --}}
            <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant/60">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">1103 - Piutang QRIS & EDC</span>
                <h3 class="text-xl sm:text-2xl font-black text-on-surface font-mono mt-1">
                    Rp {{ number_format($qrisBalance, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-on-surface-variant mt-1">Menunggu Settlement Bank</p>
            </div>
        </div>
    </div>

    {{-- SECTION 2: Arus Kas Periode (Inflow vs Outflow vs Net Cash Flow) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Total Uang Masuk --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">arrow_downward</span>
                </span>
                <div>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Kas Masuk (Inflow)</span>
                    <h3 class="text-xl sm:text-2xl font-black text-emerald-700 font-mono mt-0.5">
                        Rp {{ number_format($totalInflow, 0, ',', '.') }}
                    </h3>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-outline-variant/50 text-xs text-on-surface-variant flex justify-between">
                <span>Penjualan: Rp {{ number_format($periodSalesInflow, 0, ',', '.') }}</span>
                <span>Lainnya: Rp {{ number_format($periodIncomes, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- Total Uang Keluar --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">arrow_upward</span>
                </span>
                <div>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Kas Keluar (Outflow)</span>
                    <h3 class="text-xl sm:text-2xl font-black text-rose-600 font-mono mt-0.5">
                        Rp {{ number_format($totalOutflow, 0, ',', '.') }}
                    </h3>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-outline-variant/50 text-xs text-on-surface-variant flex justify-between">
                <span>Beban: Rp {{ number_format($periodExpenses, 0, ',', '.') }}</span>
                <span>Belanja Stok: Rp {{ number_format($periodDirectStockOutflow + $periodSupplierPayments, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- Arus Kas Bersih (Net Cash Flow) --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl {{ $netCashFlow >= 0 ? 'bg-[#006c49]/10 text-[#006c49]' : 'bg-rose-500/10 text-rose-700' }} flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">{{ $netCashFlow >= 0 ? 'trending_up' : 'trending_down' }}</span>
                </span>
                <div>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Arus Kas Bersih (Net Flow)</span>
                    <h3 class="text-xl sm:text-2xl font-black font-mono mt-0.5 {{ $netCashFlow >= 0 ? 'text-[#006c49]' : 'text-rose-600' }}">
                        Rp {{ number_format($netCashFlow, 0, ',', '.') }}
                    </h3>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-outline-variant/50 text-xs text-on-surface-variant">
                <span>{{ $netCashFlow >= 0 ? 'Surplus dana operasional pada periode ini' : 'Defisit kas (pengeluaran melampaui kas masuk)' }}</span>
            </div>
        </div>
    </div>

    {{-- SECTION 3: Breakdown Pengeluaran & Riwayat Mutasi --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Left: Breakdown Beban Operasional (8 cols) --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- Breakdown per Kategori --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-outline-variant/60">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-700 flex items-center justify-center">
                            <span class="material-symbols-outlined text-base">donut_large</span>
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-on-surface">Distribusi Beban Operasional per Kategori</h3>
                            <p class="text-[11px] text-on-surface-variant">Rincian pengeluaran toko pada periode {{ $filterLabel }}</p>
                        </div>
                    </div>
                    <a href="{{ route('expenses.index') }}" class="text-xs font-bold text-[#006c49] hover:underline flex items-center gap-1">
                        Kelola Pengeluaran
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>

                <div class="space-y-4">
                    @forelse($expensesByCategory as $cat)
                        @php
                            $percentage = $periodExpenses > 0 ? ($cat->total_amount / $periodExpenses) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                <span class="font-bold text-on-surface flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                    {{ $cat->category }}
                                    <span class="text-[10px] text-on-surface-variant font-normal">({{ $cat->count }} transaksi)</span>
                                </span>
                                <span class="font-mono font-bold text-on-surface">
                                    Rp {{ number_format($cat->total_amount, 0, ',', '.') }}
                                    <span class="text-[10px] text-on-surface-variant font-normal">({{ round($percentage, 1) }}%)</span>
                                </span>
                            </div>
                            <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden">
                                <div class="bg-rose-500 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, max(2, $percentage)) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-on-surface-variant text-center py-6">Tidak ada pengeluaran yang tercatat pada periode ini.</p>
                    @endforelse
                </div>
            </div>

            {{-- Recent Expenses Feed --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-outline-variant/60">
                    <h3 class="text-sm font-extrabold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-rose-600">receipt</span>
                        Pengeluaran Toko Terakhir
                    </h3>
                    <a href="{{ route('expenses.create') }}" class="text-xs font-bold text-[#006c49] hover:underline flex items-center gap-1">
                        + Tambah Pengeluaran
                    </a>
                </div>

                <div class="divide-y divide-outline-variant/40">
                    @forelse($recentExpenses as $exp)
                        <div class="py-2.5 flex items-center justify-between gap-3 text-xs">
                            <div class="min-w-0">
                                <p class="font-bold text-on-surface truncate">{{ $exp->title }}</p>
                                <span class="inline-block px-2 py-0.5 rounded-md bg-surface-container text-[10px] font-semibold text-on-surface-variant mt-0.5">
                                    {{ $exp->category }}
                                </span>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="font-mono font-bold text-rose-600 block">
                                    -Rp {{ number_format($exp->amount, 0, ',', '.') }}
                                </span>
                                <span class="text-[10px] text-on-surface-variant">
                                    {{ \Carbon\Carbon::parse($exp->expense_date)->format('d M Y') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-on-surface-variant text-center py-4">Belum ada pengeluaran tercatat.</p>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Right: Incomes & Transfers (4 cols) --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- Recent Incomes --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-outline-variant/60">
                    <h3 class="text-sm font-extrabold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600">savings</span>
                        Pemasukan Non-Penjualan
                    </h3>
                    <a href="{{ route('incomes.create') }}" class="text-xs font-bold text-[#006c49] hover:underline">
                        + Tambah
                    </a>
                </div>

                <div class="space-y-2.5">
                    @forelse($recentIncomes as $inc)
                        <div class="p-2.5 rounded-xl bg-surface-container-low/60 text-xs flex justify-between items-center">
                            <div>
                                <p class="font-bold text-on-surface truncate">{{ $inc->title }}</p>
                                <span class="text-[10px] text-on-surface-variant">{{ \Carbon\Carbon::parse($inc->income_date)->format('d M Y') }}</span>
                            </div>
                            <span class="font-mono font-bold text-emerald-700">
                                +Rp {{ number_format($inc->amount, 0, ',', '.') }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-on-surface-variant text-center py-4">Belum ada pemasukan non-sales.</p>
                    @endforelse
                </div>
            </div>

            {{-- Recent Fund Transfers --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-outline-variant/60">
                    <h3 class="text-sm font-extrabold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#006c49]">swap_horiz</span>
                        Mutasi Kas / Bank Terkini
                    </h3>
                    <a href="{{ route('accounting.transfers.index') }}" class="text-xs font-bold text-[#006c49] hover:underline">
                        Semua
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse($recentTransfers as $trf)
                        <div class="p-2.5 rounded-xl bg-surface-container-low/60 text-xs">
                            <div class="flex items-center justify-between font-bold text-on-surface">
                                <span class="truncate">{{ $trf->fromAccount?->name ?? 'Kas' }} &rarr; {{ $trf->toAccount?->name ?? 'Bank' }}</span>
                                <span class="font-mono text-[#006c49] shrink-0">Rp {{ number_format($trf->amount, 0, ',', '.') }}</span>
                            </div>
                            <div class="text-[10px] text-on-surface-variant flex justify-between mt-1">
                                <span>{{ $trf->transfer_number }}</span>
                                <span>{{ \Carbon\Carbon::parse($trf->transfer_date)->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-on-surface-variant text-center py-4">Belum ada transaksi transfer dana.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
