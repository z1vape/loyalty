@extends('layouts.app')

@section('title', 'Z1 VapeOS | Laporan Neraca')

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
        <h1 class="font-headline-lg text-headline-lg text-on-surface">Laporan Neraca (Balance Sheet)</h1>
        <p class="text-on-surface-variant text-body-lg">Posisi aktiva (aset), kewajiban (hutang), dan modal toko per rentang tanggal tertentu.</p>
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
    <a href="{{ route('accounting.reports.balance-sheet', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-surface-container-lowest text-primary shadow-xs no-underline whitespace-nowrap">
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
    <form method="GET" action="{{ route('accounting.reports.balance-sheet') }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
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
                Tampilkan Neraca
            </button>
        </div>
    </form>
</div>

{{-- Printable Statement Container --}}
<div class="printable-report bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 sm:p-8 shadow-xs max-w-4xl mx-auto print:border-none print:shadow-none print:p-0 print:max-w-full">
    {{-- Header for Print --}}
    <div class="text-center pb-6 border-b border-outline-variant mb-6">
        <h2 class="text-xl sm:text-2xl font-extrabold text-on-surface">LAPORAN NERACA KEUANGAN</h2>
        <p class="text-xs sm:text-sm font-bold text-primary mt-0.5">{{ $storeName }}</p>
        <p class="text-xs sm:text-sm font-semibold text-on-surface-variant mt-1">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }} (Posisi per {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }})</p>
    </div>

    {{-- Equilibrium Alert --}}
    @if($isBalanced)
    <div class="p-3 mb-6 bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 rounded-xl text-xs font-bold flex items-center justify-between print:hidden">
        <div class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base">verified</span>
            <span>Neraca Seimbang: Total Aset = Total Kewajiban & Ekuitas</span>
        </div>
        <span class="font-mono">Rp {{ number_format($totalAssets, 0, ',', '.') }}</span>
    </div>
    @else
    <div class="p-3 mb-6 bg-error-container text-on-error-container rounded-xl text-xs font-bold flex items-center justify-between print:hidden">
        <div class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base">warning</span>
            <span>Perhatian: Terdapat selisih antara Aset dan Kewajiban+Ekuitas</span>
        </div>
        <span class="font-mono">Selisih: Rp {{ number_format(abs($totalAssets - $totalLiabilitiesAndEquity), 0, ',', '.') }}</span>
    </div>
    @endif

    {{-- Two Column Structure (Aset vs Kewajiban & Ekuitas) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 print:grid-cols-2 gap-8 print:gap-6 text-xs sm:text-sm">
        {{-- SISI KIRI: ASET / AKTIVA --}}
        <div class="space-y-4">
            <div class="font-extrabold text-sm sm:text-base text-on-surface uppercase tracking-wider pb-2 border-b-2 border-primary">
                ASET (AKTIVA)
            </div>

            <div class="divide-y divide-outline-variant/40 font-medium">
                @forelse($assetAccounts as $acc)
                <div class="flex justify-between py-2 text-on-surface-variant">
                    <div>
                        <span class="font-mono text-[11px] text-on-surface-variant mr-1">{{ $acc->code }}</span>
                        <span class="text-on-surface font-semibold">{{ $acc->name }}</span>
                    </div>
                    <span class="font-mono text-on-surface">Rp {{ number_format($acc->balance ?? 0, 0, ',', '.') }}</span>
                </div>
                @empty
                <div class="py-2 text-on-surface-variant italic">Belum ada akun aset terdaftar.</div>
                @endforelse
            </div>

            <div class="mt-6 p-4 rounded-xl bg-surface-container-high border border-outline-variant flex justify-between items-center font-extrabold text-sm sm:text-base text-on-surface">
                <span>TOTAL ASET</span>
                <span class="font-mono text-primary">Rp {{ number_format($totalAssets, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- SISI KANAN: KEWAJIBAN & EKUITAS --}}
        <div class="space-y-6">
            {{-- Kewajiban / Hutang --}}
            <div class="space-y-3">
                <div class="font-extrabold text-sm sm:text-base text-on-surface uppercase tracking-wider pb-2 border-b-2 border-amber-600">
                    KEWAJIBAN (HUTANG)
                </div>

                <div class="divide-y divide-outline-variant/40 font-medium">
                    @forelse($liabilityAccounts as $acc)
                    <div class="flex justify-between py-2 text-on-surface-variant">
                        <div>
                            <span class="font-mono text-[11px] text-on-surface-variant mr-1">{{ $acc->code }}</span>
                            <span class="text-on-surface font-semibold">{{ $acc->name }}</span>
                        </div>
                        <span class="font-mono text-on-surface">Rp {{ number_format($acc->balance ?? 0, 0, ',', '.') }}</span>
                    </div>
                    @empty
                    <div class="py-2 text-on-surface-variant italic">Tidak ada hutang aktif tercatat.</div>
                    @endforelse

                    <div class="flex justify-between py-2 font-bold bg-surface-container-low px-2 rounded-lg text-on-surface">
                        <span>Total Kewajiban</span>
                        <span class="font-mono">Rp {{ number_format($totalLiabilities, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Ekuitas / Modal --}}
            <div class="space-y-3">
                <div class="font-extrabold text-sm sm:text-base text-on-surface uppercase tracking-wider pb-2 border-b-2 border-purple-600">
                    EKUITAS (MODAL)
                </div>

                <div class="divide-y divide-outline-variant/40 font-medium">
                    @forelse($equityAccounts as $acc)
                    <div class="flex justify-between py-2 text-on-surface-variant">
                        <div>
                            <span class="font-mono text-[11px] text-on-surface-variant mr-1">{{ $acc->code }}</span>
                            <span class="text-on-surface font-semibold">{{ $acc->name }}</span>
                        </div>
                        <span class="font-mono text-on-surface">Rp {{ number_format($acc->balance ?? 0, 0, ',', '.') }}</span>
                    </div>
                    @empty
                    @endforelse

                    {{-- Saldo Laba Periode Lalu (Retained Earnings) jika ada sebelum startDate --}}
                    @if($retainedEarnings != 0)
                    <div class="flex justify-between py-2 text-on-surface-variant px-2">
                        <div>
                            <span class="font-mono text-[11px] text-on-surface-variant mr-1">3201</span>
                            <span class="text-on-surface font-semibold">Saldo Laba Periode Lalu (s/d {{ \Carbon\Carbon::parse($startDate)->subDay()->format('d/m/Y') }})</span>
                        </div>
                        <span class="font-mono {{ $retainedEarnings >= 0 ? 'text-on-surface' : 'text-rose-600' }}">Rp {{ number_format($retainedEarnings, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    {{-- Dynamic Current Period Net Earnings --}}
                    <div class="flex justify-between py-2 {{ $currentPeriodEarnings >= 0 ? 'text-emerald-800 bg-emerald-500/10' : 'text-rose-800 bg-rose-500/10' }} px-2 rounded-lg font-bold">
                        <span>Laba Bersih Periode Berjalan ({{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }})</span>
                        <span class="font-mono">Rp {{ number_format($currentPeriodEarnings, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between py-2 font-bold bg-surface-container-low px-2 rounded-lg text-on-surface">
                        <span>Total Ekuitas</span>
                        <span class="font-mono">Rp {{ number_format($totalEquity, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Total Kewajiban + Ekuitas --}}
            <div class="p-4 rounded-xl bg-surface-container-high border border-outline-variant flex justify-between items-center font-extrabold text-sm sm:text-base text-on-surface">
                <span>TOTAL KEWAJIBAN & EKUITAS</span>
                <span class="font-mono text-primary">Rp {{ number_format($totalLiabilitiesAndEquity, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>

@endsection
