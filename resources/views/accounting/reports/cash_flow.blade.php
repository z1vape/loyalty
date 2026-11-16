@extends('layouts.app')

@section('title', 'Z1 VapeOS | Laporan Arus Kas')

@push('styles')
<style>
@media print {
    @page {
        size: portrait;
        margin: 12mm 15mm;
    }
}
</style>
@endpush

@section('content')

{{-- Header & Sub-navigation --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 print:hidden">
    <div>
        <h1 class="font-headline-lg text-headline-lg text-on-surface">Laporan Arus Kas (Cash Flow)</h1>
        <p class="text-on-surface-variant text-body-lg">Mutasi arus kas masuk dan arus kas keluar pada kas & bank toko.</p>
    </div>

    <div class="flex items-center gap-2 self-start sm:self-auto">
        <a href="{{ route('accounting.reports.sync-journals', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-secondary text-on-secondary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 transition-all shadow-xs cursor-pointer no-underline">
            <span class="material-symbols-outlined text-base">sync</span>
            Sinkronkan Jurnal
        </a>
        <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer">
            <span class="material-symbols-outlined text-base">print</span>
            Cetak / PDF
        </button>
    </div>
</div>

@if(session('success'))
<div class="p-4 mb-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm flex items-center gap-2 font-medium print:hidden">
    <span class="material-symbols-outlined text-base">check_circle</span>
    {{ session('success') }}
</div>
@endif

{{-- Report Navigation Tabs --}}
<div class="flex items-center gap-1 p-1 bg-surface-container-low border border-outline-variant/60 rounded-2xl mb-6 overflow-x-auto print:hidden">
    <a href="{{ route('accounting.reports.profit-and-loss', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high no-underline whitespace-nowrap transition-colors">
        Laba Rugi (P&L)
    </a>
    <a href="{{ route('accounting.reports.balance-sheet', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high no-underline whitespace-nowrap transition-colors">
        Neraca (Balance Sheet)
    </a>
    <a href="{{ route('accounting.reports.trial-balance', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high no-underline whitespace-nowrap transition-colors">
        Neraca Saldo (Trial Balance)
    </a>
    <a href="{{ route('accounting.reports.cash-flow', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-surface-container-lowest text-primary shadow-xs no-underline whitespace-nowrap">
        Arus Kas (Cash Flow)
    </a>
</div>

{{-- Filter Form --}}
<div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 shadow-xs print:hidden">
    <form method="GET" action="{{ route('accounting.reports.cash-flow') }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
        <div class="flex-1">
            <label class="block text-xs font-semibold text-on-surface-variant mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
        </div>
        <div class="flex-1">
            <label class="block text-xs font-semibold text-on-surface-variant mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
        </div>
        <div>
            <button type="submit" class="px-5 py-2 bg-secondary text-on-secondary text-xs font-bold rounded-xl hover:opacity-90 transition-all cursor-pointer">
                Tampilkan
            </button>
        </div>
    </form>
</div>

{{-- Printable Statement Container --}}
<div class="printable-report bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 sm:p-8 shadow-xs max-w-4xl mx-auto print:border-none print:shadow-none print:p-0 print:max-w-full">
    {{-- Header for Print --}}
    <div class="text-center pb-6 border-b border-outline-variant mb-6">
        <h2 class="text-xl sm:text-2xl font-extrabold text-on-surface">LAPORAN ARUS KAS (CASH FLOW)</h2>
        <p class="text-xs sm:text-sm font-bold text-primary mt-0.5">{{ $storeName }}</p>
        <p class="text-xs sm:text-sm font-semibold text-on-surface-variant mt-1">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</p>
    </div>

    {{-- Statement Body --}}
    <div class="space-y-6 text-xs sm:text-sm">
        {{-- Saldo Awal Kas & Bank --}}
        <div class="p-4 bg-surface-container-low/60 rounded-xl flex justify-between items-center font-bold text-on-surface">
            <span>Saldo Awal Kas & Bank (Sebelum {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }})</span>
            <span class="font-mono text-base">Rp {{ number_format($openingCash, 0, ',', '.') }}</span>
        </div>

        {{-- 1. Arus Kas Masuk --}}
        <div>
            <div class="flex justify-between items-center font-bold text-on-surface uppercase tracking-wider py-1.5 border-b border-outline-variant/60">
                <span>1. Penerimaan Kas & Bank (Inflows)</span>
            </div>
            <div class="divide-y divide-outline-variant/30 font-medium">
                <div class="flex justify-between py-2 pl-4 text-on-surface-variant">
                    <span>Penerimaan dari Penjualan Toko (POS / Retail)</span>
                    <span class="font-mono text-emerald-700 font-bold">+ Rp {{ number_format($salesInflows, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between py-2 pl-4 text-on-surface-variant">
                    <span>Pemasukan Toko Lain-lain</span>
                    <span class="font-mono text-emerald-700 font-bold">+ Rp {{ number_format($otherInflows, 0, ',', '.') }}</span>
                </div>
                @if(isset($capitalInflows) && $capitalInflows > 0)
                <div class="flex justify-between py-2 pl-4 text-on-surface-variant">
                    <span>Penyetoran Modal / Saldo Awal Toko</span>
                    <span class="font-mono text-emerald-700 font-bold">+ Rp {{ number_format($capitalInflows, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="flex justify-between py-2.5 font-bold bg-surface-container-low/60 px-3 rounded-lg text-emerald-800">
                    <span>Total Kas Masuk</span>
                    <span class="font-mono">+ Rp {{ number_format($totalInflows, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- 2. Arus Kas Keluar --}}
        <div>
            <div class="flex justify-between items-center font-bold text-on-surface uppercase tracking-wider py-1.5 border-b border-outline-variant/60">
                <span>2. Pengeluaran Kas & Bank (Outflows)</span>
            </div>
            <div class="divide-y divide-outline-variant/30 font-medium">
                <div class="flex justify-between py-2 pl-4 text-on-surface-variant">
                    <span>Pengeluaran Operasional Toko (Beban)</span>
                    <span class="font-mono text-rose-700 font-bold">- Rp {{ number_format($expenseOutflows, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between py-2 pl-4 text-on-surface-variant">
                    <span>Pembelian Stok Persediaan Tunai (Stock In)</span>
                    <span class="font-mono text-rose-700 font-bold">- Rp {{ number_format($stockInPurchases, 0, ',', '.') }}</span>
                </div>
                @if($debtPayments > 0)
                <div class="flex justify-between py-2 pl-4 text-on-surface-variant">
                    <span>Pembayaran / Pelunasan Hutang Supplier</span>
                    <span class="font-mono text-rose-700 font-bold">- Rp {{ number_format($debtPayments, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="flex justify-between py-2.5 font-bold bg-surface-container-low/60 px-3 rounded-lg text-rose-800">
                    <span>Total Kas Keluar</span>
                    <span class="font-mono">- Rp {{ number_format($totalOutflows, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- 3. Rekonsiliasi Kas Bersih --}}
        <div class="pt-2 border-t border-outline-variant space-y-2">
            <div class="flex justify-between py-2 font-bold text-on-surface">
                <span>Kenaikan / (Penurunan) Kas Bersih Periode Ini</span>
                <span class="font-mono {{ $netCashFlow >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ $netCashFlow >= 0 ? '+' : '' }} Rp {{ number_format($netCashFlow, 0, ',', '.') }}
                </span>
            </div>

            <div class="p-4 rounded-xl flex justify-between items-center font-extrabold text-sm sm:text-base bg-primary text-on-primary shadow-sm">
                <span class="uppercase tracking-wider">SALDO AKHIR KAS & BANK</span>
                <span class="font-mono">Rp {{ number_format($endingCash, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>

@endsection
