@extends('layouts.app')

@section('title', 'Z1 VapeOS | Laporan Laba Rugi')

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
        <h1 class="font-headline-lg text-headline-lg text-on-surface">Laporan Laba Rugi (Profit & Loss)</h1>
        <p class="text-on-surface-variant text-body-lg">Kinerja pendapatan, beban pokok penjualan (HPP), dan laba bersih toko.</p>
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
    <a href="{{ route('accounting.reports.profit-and-loss', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-surface-container-lowest text-primary shadow-xs no-underline whitespace-nowrap">
        Laba Rugi (P&L)
    </a>
    <a href="{{ route('accounting.reports.balance-sheet', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high no-underline whitespace-nowrap transition-colors">
        Neraca (Balance Sheet)
    </a>
    <a href="{{ route('accounting.reports.trial-balance', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high no-underline whitespace-nowrap transition-colors">
        Neraca Saldo (Trial Balance)
    </a>
    <a href="{{ route('accounting.reports.cash-flow', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high no-underline whitespace-nowrap transition-colors">
        Arus Kas (Cash Flow)
    </a>
</div>

{{-- Filter Form --}}
<div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 shadow-xs print:hidden">
    <form method="GET" action="{{ route('accounting.reports.profit-and-loss') }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
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
        <h2 class="text-xl sm:text-2xl font-extrabold text-on-surface">LAPORAN LABA RUGI</h2>
        <p class="text-xs sm:text-sm font-bold text-primary mt-0.5">{{ $storeName }}</p>
        <p class="text-xs sm:text-sm font-semibold text-on-surface-variant mt-1">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</p>
    </div>

    {{-- Statement Body --}}
    <div class="space-y-6 text-xs sm:text-sm">
        {{-- 1. Pendapatan Usaha --}}
        <div>
            <div class="flex justify-between items-center font-bold text-on-surface uppercase tracking-wider py-1.5 border-b border-outline-variant/60">
                <span>1. Pendapatan Penjualan (Revenue)</span>
            </div>
            <div class="divide-y divide-outline-variant/30 font-medium">
                <div class="flex justify-between py-2 pl-4 text-on-surface-variant">
                    <span>Penjualan Kotor (Gross Sales)</span>
                    <span class="font-mono text-on-surface">Rp {{ number_format($grossSales, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between py-2 pl-4 text-rose-700">
                    <span>Potongan / Diskon Penjualan</span>
                    <span class="font-mono">(Rp {{ number_format($salesDiscounts, 0, ',', '.') }})</span>
                </div>
                <div class="flex justify-between py-2.5 font-bold bg-surface-container-low/60 px-3 rounded-lg text-on-surface">
                    <span>Total Pendapatan Bersih (Net Revenue)</span>
                    <span class="font-mono">Rp {{ number_format($netSales, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- 2. Beban Pokok Penjualan --}}
        <div>
            <div class="flex justify-between items-center font-bold text-on-surface uppercase tracking-wider py-1.5 border-b border-outline-variant/60">
                <span>2. Beban Pokok Penjualan (Cost of Goods Sold / COGS)</span>
            </div>
            <div class="divide-y divide-outline-variant/30 font-medium">
                <div class="flex justify-between py-2 pl-4 text-rose-700">
                    <span>Beban Pokok Penjualan (HPP)</span>
                    <span class="font-mono">(Rp {{ number_format($totalCogs, 0, ',', '.') }})</span>
                </div>
                <div class="flex justify-between py-2.5 font-bold bg-emerald-500/10 text-emerald-800 px-3 rounded-lg">
                    <span>LABA KOTOR (GROSS PROFIT)</span>
                    <span class="font-mono">Rp {{ number_format($grossProfit, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- 3. Beban Operasional --}}
        <div>
            <div class="flex justify-between items-center font-bold text-on-surface uppercase tracking-wider py-1.5 border-b border-outline-variant/60">
                <span>3. Beban Operasional (Operating Expenses)</span>
            </div>
            <div class="divide-y divide-outline-variant/30 font-medium">
                @forelse($expenseAccounts as $exp)
                <div class="flex justify-between py-2 pl-4 text-on-surface-variant">
                    <span>{{ $exp->name }} ({{ $exp->code }})</span>
                    <span class="font-mono text-on-surface">Rp {{ number_format($exp->period_balance ?? 0, 0, ',', '.') }}</span>
                </div>
                @empty
                <div class="py-2 pl-4 text-on-surface-variant italic">Tidak ada beban operasional tercatat pada periode ini.</div>
                @endforelse
                <div class="flex justify-between py-2.5 font-bold bg-surface-container-low/60 px-3 rounded-lg text-on-surface">
                    <span>Total Beban Operasional</span>
                    <span class="font-mono text-rose-700">(Rp {{ number_format($totalOperatingExpenses, 0, ',', '.') }})</span>
                </div>
            </div>
        </div>

        {{-- 4. Laba Operasional & Pendapatan Lain-lain --}}
        <div class="pt-2 border-t border-outline-variant">
            <div class="flex justify-between py-2 font-bold text-on-surface">
                <span>Laba Operasional Toko</span>
                <span class="font-mono {{ $operatingProfit >= 0 ? 'text-on-surface' : 'text-error' }}">
                    Rp {{ number_format($operatingProfit, 0, ',', '.') }}
                </span>
            </div>
            @if($otherIncomes > 0)
            <div class="flex justify-between py-2 text-on-surface-variant">
                <span>Pendapatan Lain-lain (Non-Operasional)</span>
                <span class="font-mono text-emerald-700">+ Rp {{ number_format($otherIncomes, 0, ',', '.') }}</span>
            </div>
            @endif

            {{-- 5. Laba Bersih Akhir --}}
            <div class="mt-4 p-4 rounded-xl flex justify-between items-center font-extrabold text-sm sm:text-base {{ $netProfit >= 0 ? 'bg-primary text-on-primary shadow-sm' : 'bg-error text-on-error shadow-sm' }}">
                <span class="uppercase tracking-wider">LABA BERSIH (NET PROFIT)</span>
                <span class="font-mono">Rp {{ number_format($netProfit, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>

@endsection
