@extends('layouts.app')

@section('title', 'Z1 VapeOS | Neraca Saldo (Trial Balance)')

@push('styles')
<style>
@media print {
    @page {
        size: landscape;
        margin: 10mm 12mm;
    }
    .trial-balance-table {
        width: 100% !important;
        font-size: 10px !important;
    }
    .trial-balance-table th,
    .trial-balance-table td {
        padding: 5px 6px !important;
    }
}
</style>
@endpush

@section('content')

{{-- Header & Sub-navigation --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 print:hidden">
    <div>
        <h1 class="font-headline-lg text-headline-lg text-on-surface">Neraca Saldo (Trial Balance)</h1>
        <p class="text-on-surface-variant text-body-lg">Validasi pergerakan mutasi dan keseimbangan seluruh saldo debit dan kredit akun.</p>
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
    <a href="{{ route('accounting.reports.trial-balance', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-surface-container-lowest text-primary shadow-xs no-underline whitespace-nowrap">
        Neraca Saldo (Trial Balance)
    </a>
    <a href="{{ route('accounting.reports.cash-flow', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high no-underline whitespace-nowrap transition-colors">
        Arus Kas (Cash Flow)
    </a>
</div>

{{-- Filter Form --}}
<div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 shadow-xs print:hidden">
    <form method="GET" action="{{ route('accounting.reports.trial-balance') }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
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
                Tampilkan Neraca Saldo
            </button>
        </div>
    </form>
</div>

{{-- Printable Statement Container --}}
<div class="printable-report bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-xs print:border-none print:shadow-none print:p-0 print:max-w-full">
    {{-- Header for Print --}}
    <div class="text-center p-6 border-b border-outline-variant">
        <h2 class="text-xl sm:text-2xl font-extrabold text-on-surface">NERACA SALDO (TRIAL BALANCE)</h2>
        <p class="text-xs sm:text-sm font-bold text-primary mt-0.5">{{ $storeName }}</p>
        <p class="text-xs sm:text-sm font-semibold text-on-surface-variant mt-1">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</p>
    </div>

    {{-- Equilibrium Alert --}}
    @if($isBalanced && $isPeriodBalanced)
    <div class="px-6 py-3 bg-emerald-500/10 border-b border-emerald-500/20 text-emerald-800 text-xs font-bold flex flex-wrap items-center justify-between gap-2 print:hidden">
        <div class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base">verified</span>
            <span>Buku Besar Seimbang (Balance): Mutasi & Saldo Akhir Debit = Kredit</span>
        </div>
        <div class="flex items-center gap-4 font-mono text-[11px]">
            <span>Mutasi: Rp {{ number_format($totalPeriodDebit, 0, ',', '.') }}</span>
            <span class="text-outline-variant">|</span>
            <span>Saldo Akhir: Rp {{ number_format($totalDebitBalance, 0, ',', '.') }}</span>
        </div>
    </div>
    @elseif($isBalanced)
    <div class="px-6 py-3 bg-emerald-500/10 border-b border-emerald-500/20 text-emerald-800 text-xs font-bold flex items-center justify-between print:hidden">
        <div class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base">verified</span>
            <span>Saldo Akhir Seimbang (Balance): Total Debit sama dengan Total Kredit</span>
        </div>
        <span class="font-mono">Rp {{ number_format($totalDebitBalance, 0, ',', '.') }}</span>
    </div>
    @else
    <div class="px-6 py-3 bg-error-container text-on-error-container text-xs font-bold flex items-center justify-between print:hidden">
        <div class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base">warning</span>
            <span>Perhatian: Total Debit dan Kredit tidak seimbang!</span>
        </div>
        <span class="font-mono">Selisih: Rp {{ number_format(abs($totalDebitBalance - $totalCreditBalance), 0, ',', '.') }}</span>
    </div>
    @endif

    {{-- Accounts Table --}}
    <div class="overflow-x-auto print:overflow-visible">
        <table class="w-full text-left text-xs trial-balance-table">
            <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant text-[11px] sm:text-xs">
                <tr>
                    <th class="px-4 py-3.5 w-20">Kode</th>
                    <th class="px-4 py-3.5 min-w-[180px]">Nama Akun</th>
                    <th class="px-4 py-3.5 w-28">Klasifikasi</th>
                    <th class="px-4 py-3.5 text-right w-36">Saldo Awal</th>
                    <th class="px-4 py-3.5 text-right w-36 text-emerald-800">Mutasi Debit</th>
                    <th class="px-4 py-3.5 text-right w-36 text-sky-800">Mutasi Kredit</th>
                    <th class="px-4 py-3.5 text-right w-36 text-emerald-800">Saldo Akhir (D)</th>
                    <th class="px-4 py-3.5 text-right w-36 text-sky-800">Saldo Akhir (K)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/50">
                @foreach($accounts as $acc)
                <tr class="hover:bg-surface-container-low/50 transition-colors {{ ($acc->ending_debit > 0 || $acc->ending_credit > 0 || $acc->period_debit > 0 || $acc->period_credit > 0 || $acc->opening_debit > 0 || $acc->opening_credit > 0) ? 'font-medium' : 'opacity-50' }}">
                    <td class="px-4 py-3 font-mono font-bold text-on-surface">{{ $acc->code }}</td>
                    <td class="px-4 py-3">
                        <span class="font-bold text-on-surface">{{ $acc->name }}</span>
                        <span class="text-[10px] text-on-surface-variant block">{{ $acc->category }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold {{ $acc->type_badge_class }}">
                            {{ $acc->type_label }}
                        </span>
                    </td>
                    {{-- Saldo Awal --}}
                    <td class="px-4 py-3 text-right font-mono {{ ($acc->opening_debit > 0 || $acc->opening_credit > 0) ? 'text-on-surface font-semibold' : 'text-on-surface-variant' }}">
                        @if($acc->opening_debit > 0)
                            Rp {{ number_format($acc->opening_debit, 0, ',', '.') }} <span class="text-[10px] text-emerald-700 font-bold">(D)</span>
                        @elseif($acc->opening_credit > 0)
                            Rp {{ number_format($acc->opening_credit, 0, ',', '.') }} <span class="text-[10px] text-sky-700 font-bold">(K)</span>
                        @else
                            -
                        @endif
                    </td>
                    {{-- Mutasi Debit --}}
                    <td class="px-4 py-3 text-right font-mono {{ $acc->period_debit > 0 ? 'text-emerald-800 font-bold' : 'text-on-surface-variant' }}">
                        {{ $acc->period_debit > 0 ? 'Rp ' . number_format($acc->period_debit, 0, ',', '.') : '-' }}
                    </td>
                    {{-- Mutasi Kredit --}}
                    <td class="px-4 py-3 text-right font-mono {{ $acc->period_credit > 0 ? 'text-sky-800 font-bold' : 'text-on-surface-variant' }}">
                        {{ $acc->period_credit > 0 ? 'Rp ' . number_format($acc->period_credit, 0, ',', '.') : '-' }}
                    </td>
                    {{-- Saldo Akhir Debit --}}
                    <td class="px-4 py-3 text-right font-mono {{ $acc->ending_debit > 0 ? 'text-emerald-800 font-bold' : 'text-on-surface-variant' }}">
                        {{ $acc->ending_debit > 0 ? 'Rp ' . number_format($acc->ending_debit, 0, ',', '.') : '-' }}
                    </td>
                    {{-- Saldo Akhir Kredit --}}
                    <td class="px-4 py-3 text-right font-mono {{ $acc->ending_credit > 0 ? 'text-sky-800 font-bold' : 'text-on-surface-variant' }}">
                        {{ $acc->ending_credit > 0 ? 'Rp ' . number_format($acc->ending_credit, 0, ',', '.') : '-' }}
                    </td>
                </tr>
                @endforeach

                {{-- Totals Row --}}
                <tr class="bg-surface-container-high font-extrabold text-xs sm:text-sm border-t-2 border-outline-variant text-on-surface">
                    <td colspan="3" class="px-4 py-4 text-right uppercase tracking-wider text-xs">
                        TOTAL NERACA SALDO
                    </td>
                    <td class="px-4 py-4 text-right font-mono text-[11px] text-on-surface-variant">
                        @if($totalOpeningDebit > 0 || $totalOpeningCredit > 0)
                            D: {{ number_format($totalOpeningDebit, 0, ',', '.') }}<br>
                            K: {{ number_format($totalOpeningCredit, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-4 py-4 text-right font-mono text-emerald-800">
                        Rp {{ number_format($totalPeriodDebit, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-4 text-right font-mono text-sky-800">
                        Rp {{ number_format($totalPeriodCredit, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-4 text-right font-mono text-emerald-800">
                        Rp {{ number_format($totalDebitBalance, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-4 text-right font-mono text-sky-800">
                        Rp {{ number_format($totalCreditBalance, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection
