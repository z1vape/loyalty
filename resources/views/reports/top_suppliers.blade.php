@extends('layouts.app')

@section('title', 'Rekap Belanja Supplier | Z1 VapeOS')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container">
                    <span class="material-symbols-outlined text-sm">local_shipping</span>
                    Penjualan & Stok
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-surface-container-high text-on-surface">
                    <span class="material-symbols-outlined text-xs text-[#006c49]">store</span>
                    {{ $storeName }}
                </span>
                <span class="text-xs font-semibold text-on-surface-variant">
                    • @if($mode === 'year') Rekapitulasi Multi-Tahun ({{ $startYear }} - {{ $endYear }}) @else Rekapitulasi Tahunan (January - December) @endif
                </span>
            </div>
            <h1 class="font-headline-lg text-2xl sm:text-3xl font-extrabold text-on-surface">Rekap Belanja Supplier</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">
                @if($mode === 'year')
                    Akumulasi biaya belanja stok per tahun ({{ $startYear }} s/d {{ $endYear }}) untuk seluruh supplier terdaftar di {{ $storeName }}.
                @else
                    Akumulasi biaya belanja stok per bulan (January - December) untuk seluruh supplier terdaftar di {{ $storeName }} tahun {{ $year }}.
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            @if($mode === 'year')
                <a href="{{ route('reports.top-suppliers.export', ['mode' => 'year', 'start_year' => $startYear, 'end_year' => $endYear]) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-[#006c49] hover:bg-[#005236] text-white transition-all shadow-xs active:scale-95 no-underline">
                    <span class="material-symbols-outlined text-[18px]">table_view</span>
                    <span>Export Excel (Per Tahun)</span>
                </a>
            @else
                <a href="{{ route('reports.top-suppliers.export', ['mode' => 'month', 'year' => $year]) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-[#006c49] hover:bg-[#005236] text-white transition-all shadow-xs active:scale-95 no-underline">
                    <span class="material-symbols-outlined text-[18px]">table_view</span>
                    <span>Export Excel (Per Bulan)</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 sm:p-5 shadow-xs">
        <form method="GET" action="{{ route('reports.top-suppliers') }}" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-4">
                {{-- Mode Switcher: Per Bulan vs Per Tahun --}}
                <div class="flex items-center p-1 bg-surface-container-low rounded-xl border border-outline-variant/60">
                    <a href="{{ route('reports.top-suppliers', ['mode' => 'month', 'year' => $year]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-extrabold transition-all {{ $mode === 'month' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface hover:text-[#006c49]' }} no-underline">
                        <span class="material-symbols-outlined text-[16px]">calendar_view_month</span>
                        <span>Per Bulan</span>
                    </a>
                    <a href="{{ route('reports.top-suppliers', ['mode' => 'year', 'start_year' => $startYear, 'end_year' => $endYear]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-extrabold transition-all {{ $mode === 'year' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface hover:text-[#006c49]' }} no-underline">
                        <span class="material-symbols-outlined text-[16px]">date_range</span>
                        <span>Per Tahun</span>
                    </a>
                </div>

                <input type="hidden" name="mode" value="{{ $mode }}">

                @if($mode === 'month')
                    {{-- Filter Tahun untuk Mode Bulan: Lebar dan lapang agar teks tidak mepet chevron --}}
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-secondary text-lg">calendar_today</span>
                        <label for="year-select" class="text-xs font-bold text-on-surface uppercase tracking-wider shrink-0">TAHUN:</label>
                        <select name="year" id="year-select" onchange="this.form.submit()" class="border border-outline-variant bg-surface-container-low rounded-xl px-4 py-2 pr-10 min-w-[170px] sm:min-w-[200px] text-xs sm:text-sm font-bold text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary/40 cursor-pointer shadow-2xs">
                            @foreach($availableYears as $yr)
                                <option value="{{ $yr }}" {{ (int)$year === (int)$yr ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    {{-- Filter Rentang Tahun untuk Mode Tahun: Lebar dan lapang --}}
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="material-symbols-outlined text-secondary text-lg">timeline</span>
                        <label class="text-xs font-bold text-on-surface uppercase tracking-wider shrink-0">RENTANG TAHUN:</label>
                        <select name="start_year" class="border border-outline-variant bg-surface-container-low rounded-xl px-4 py-2 pr-9 min-w-[120px] text-xs sm:text-sm font-bold text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary/40 cursor-pointer shadow-2xs">
                            @foreach($availableYears as $yr)
                                <option value="{{ $yr }}" {{ (int)$startYear === (int)$yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                        <span class="text-xs font-bold text-on-surface-variant">s/d</span>
                        <select name="end_year" class="border border-outline-variant bg-surface-container-low rounded-xl px-4 py-2 pr-9 min-w-[120px] text-xs sm:text-sm font-bold text-on-surface focus:outline-none focus:ring-2 focus:ring-secondary/40 cursor-pointer shadow-2xs">
                            @foreach($availableYears as $yr)
                                <option value="{{ $yr }}" {{ (int)$endYear === (int)$yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-secondary text-on-secondary text-xs font-bold hover:bg-secondary/90 transition-all cursor-pointer shadow-2xs">
                            Terapkan
                        </button>
                    </div>
                @endif
            </div>

            <div class="text-xs text-on-surface-variant font-medium">
                @if($mode === 'year')
                    * Menampilkan seluruh supplier terdaftar dengan perbandingan total belanja per tahun ({{ $startYear }} - {{ $endYear }}) pada {{ $storeName }}.
                @else
                    * Menampilkan seluruh supplier terdaftar dengan rincian per bulan (January - December) tahun {{ $year }} pada {{ $storeName }}.
                @endif
            </div>
        </form>
    </div>

    {{-- Summary Cards --}}
    @php
        $topOne = $topSuppliers->first();
        if ($mode === 'year') {
            $highestYear = 0;
            $highestYearAmount = 0;
            foreach ($yearlyTotals as $yr => $amt) {
                if ($amt > $highestYearAmount) {
                    $highestYearAmount = $amt;
                    $highestYear = $yr;
                }
            }
        } else {
            $highestMonthNum = 0;
            $highestMonthAmount = 0;
            foreach ($monthlyTotals as $mNum => $amt) {
                if ($amt > $highestMonthAmount) {
                    $highestMonthAmount = $amt;
                    $highestMonthNum = $mNum;
                }
            }
        }
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Card 1: Total Belanja Supplier --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 sm:p-5 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#006c49] flex items-center justify-center shrink-0 shadow-2xs">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider truncate">
                    Total Belanja Supplier ({{ $mode === 'year' ? "Tahun {$startYear}-{$endYear}" : "Tahun {$year}" }})
                </p>
                <h3 class="text-xl sm:text-2xl font-extrabold text-[#006c49] mt-0.5 truncate">
                    Rp {{ number_format($mode === 'year' ? $grandTotalMultiYear : $grandTotalYear, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5">
                    Dari seluruh supplier terdaftar di {{ $storeName }}
                </p>
            </div>
        </div>

        {{-- Card 2: Periode Belanja Tertinggi --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 sm:p-5 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 shadow-2xs">
                <span class="material-symbols-outlined text-2xl">trending_up</span>
            </div>
            <div class="min-w-0 flex-1">
                @if($mode === 'year')
                    <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider truncate">Tahun Belanja Tertinggi</p>
                    <h3 class="text-base font-extrabold text-on-surface mt-0.5 truncate">
                        {{ $highestYear > 0 ? "Tahun {$highestYear}" : '-' }}
                    </h3>
                    <p class="text-[11px] text-blue-700 font-bold mt-0.5">
                        Rp {{ number_format($highestYearAmount, 0, ',', '.') }}
                    </p>
                @else
                    <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider truncate">Bulan Belanja Tertinggi</p>
                    <h3 class="text-base font-extrabold text-on-surface mt-0.5 truncate">
                        {{ $highestMonthNum > 0 ? ($monthNames[$highestMonthNum] ?? '-') : '-' }}
                    </h3>
                    <p class="text-[11px] text-blue-700 font-bold mt-0.5">
                        Rp {{ number_format($highestMonthAmount, 0, ',', '.') }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Card 3: Supplier Terbesar (#1) --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 sm:p-5 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 shadow-2xs">
                <span class="material-symbols-outlined text-2xl">workspace_premium</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider truncate">Supplier Terbesar (#1)</p>
                <h3 class="text-base font-extrabold text-on-surface mt-0.5 truncate" title="{{ $topOne['supplier_name'] ?? '-' }}">
                    {{ $topOne['supplier_name'] ?? '-' }}
                </h3>
                <p class="text-[11px] text-amber-700 font-bold mt-0.5">
                    @if($topOne && ($mode === 'year' ? ($topOne['total_spent'] ?? 0) : ($topOne['total_year'] ?? 0)) > 0)
                        Total: Rp {{ number_format($mode === 'year' ? ($topOne['total_spent'] ?? 0) : ($topOne['total_year'] ?? 0), 0, ',', '.') }}
                    @else
                        Belum ada transaksi
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Main Spreadsheet-Style Matrix Table --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-outline-variant/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wider flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#006c49] text-[20px]">table_chart</span>
                    @if($mode === 'year')
                        <span>TABEL REKAPITULASI BELANJA SUPPLIER (PER TAHUN {{ $startYear }} - {{ $endYear }})</span>
                    @else
                        <span>TABEL REKAPITULASI BELANJA SUPPLIER (PER BULAN TAHUN {{ $year }})</span>
                    @endif
                </h2>
                <p class="text-xs text-on-surface-variant mt-0.5">
                    Format matriks spreadsheet persis sesuai laporan pengadaan supplier di {{ $storeName }}.
                </p>
            </div>
            <div class="text-xs text-on-surface-variant font-medium">
                Satuan: Rupiah (Rp)
            </div>
        </div>

        @if($topSuppliers->isEmpty())
        <div class="p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-surface-container-low text-on-surface-variant mx-auto flex items-center justify-center mb-3">
                <span class="material-symbols-outlined text-3xl">inbox</span>
            </div>
            <h3 class="text-base font-bold text-on-surface">Belum Ada Supplier Terdaftar</h3>
            <p class="text-xs text-on-surface-variant max-w-md mx-auto mt-1">
                Belum ditemukan supplier terdaftar pada sistem atau riwayat pengadaan di {{ $storeName }}.
            </p>
        </div>
        @else
        <div class="overflow-x-auto custom-slim-scroll">
            <table class="w-full text-left border-collapse text-xs whitespace-nowrap border border-slate-300">
                @if($mode === 'year')
                    {{-- THEAD: MODE PER TAHUN --}}
                    <thead>
                        <tr class="bg-white border-b border-slate-300 text-slate-900 font-bold text-[12px]">
                            <th class="py-2.5 px-3 text-center w-14 border border-slate-300">Rank</th>
                            <th class="py-2.5 px-4 min-w-[240px] border border-slate-300">Nama Supplier</th>
                            @foreach($yearsList as $yr)
                                <th class="py-2.5 px-4 text-center min-w-[130px] border border-slate-300">
                                    {{ $yr }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($topSuppliers as $sup)
                        <tr class="hover:bg-slate-50 transition-colors">
                            {{-- Rank --}}
                            <td class="py-2 px-3 text-center font-semibold text-slate-800 border border-slate-300">
                                {{ $sup['rank'] }}
                            </td>

                            {{-- Nama Supplier --}}
                            <td class="py-2 px-4 font-semibold text-slate-900 border border-slate-300">
                                {{ $sup['supplier_name'] }}
                            </td>

                            {{-- Year Columns --}}
                            @foreach($yearsList as $yr)
                            @php
                                $val = $sup['years'][$yr] ?? 0;
                            @endphp
                            <td class="py-2 px-4 text-center font-mono border border-slate-300 {{ $val > 0 ? 'text-slate-900 font-bold' : 'text-slate-600' }}">
                                {{ $val > 0 ? number_format($val, 0, ',', '.') : '0' }}
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                    {{-- TFOOT: TOTAL PEMBELIAN PER TAHUN --}}
                    <tfoot>
                        <tr class="bg-[#a9d08e] text-slate-950 font-black text-xs">
                            <td colspan="2" class="py-2.5 px-4 text-center border border-slate-400 font-black tracking-wider uppercase">
                                TOTAL PEMBELIAN
                            </td>
                            @foreach($yearsList as $yr)
                            @php
                                $colTotal = $yearlyTotals[$yr] ?? 0;
                            @endphp
                            <td class="py-2.5 px-4 text-center font-mono border border-slate-400 font-black text-slate-950">
                                {{ number_format($colTotal, 0, ',', '.') }}
                            </td>
                            @endforeach
                        </tr>
                    </tfoot>
                @else
                    {{-- THEAD: MODE PER BULAN --}}
                    <thead>
                        <tr class="bg-white border-b border-slate-300 text-slate-900 font-bold text-[12px]">
                            <th class="py-2.5 px-3 text-center w-14 border border-slate-300">Rank</th>
                            <th class="py-2.5 px-4 min-w-[240px] border border-slate-300">Nama Supplier</th>
                            @foreach($monthNames as $mNum => $mName)
                                <th class="py-2.5 px-3 text-center min-w-[105px] border border-slate-300">
                                    {{ $mName }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($topSuppliers as $sup)
                        <tr class="hover:bg-slate-50 transition-colors">
                            {{-- Rank --}}
                            <td class="py-2 px-3 text-center font-semibold text-slate-800 border border-slate-300">
                                {{ $sup['rank'] }}
                            </td>

                            {{-- Nama Supplier --}}
                            <td class="py-2 px-4 font-semibold text-slate-900 border border-slate-300">
                                {{ $sup['supplier_name'] }}
                            </td>

                            {{-- 12 Months --}}
                            @foreach($monthNames as $mNum => $mName)
                            @php
                                $val = $sup['months'][$mNum] ?? 0;
                            @endphp
                            <td class="py-2 px-3 text-right font-mono border border-slate-300 {{ $val > 0 ? 'text-slate-900 font-bold' : 'text-slate-300' }}">
                                {{ $val > 0 ? number_format($val, 0, ',', '.') : '' }}
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                    {{-- TFOOT: TOTAL PEMBELIAN PER BULAN --}}
                    <tfoot>
                        <tr class="bg-[#a9d08e] text-slate-950 font-black text-xs">
                            <td colspan="2" class="py-2.5 px-4 text-center border border-slate-400 font-black tracking-wider uppercase">
                                TOTAL PEMBELIAN
                            </td>
                            @foreach($monthNames as $mNum => $mName)
                            @php
                                $colTotal = $monthlyTotals[$mNum] ?? 0;
                            @endphp
                            <td class="py-2.5 px-3 text-right font-mono border border-slate-400 font-black text-slate-950">
                                {{ number_format($colTotal, 0, ',', '.') }}
                            </td>
                            @endforeach
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        @endif
    </div>

</div>
@endsection
