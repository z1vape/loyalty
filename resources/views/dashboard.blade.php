@extends('layouts.app')

@section('title', 'Z1 VapeOS | Operations Dashboard')

@section('content')
@push('styles')
<style>
    /* ───────────────────────── InvenTrack Design System Tokens ───────────────────────── */
    .dash {
        --dash-primary: #006c49;
        --dash-primary-hover: #00553a;
        --dash-primary-container: #131b2e;
        --dash-secondary: #006c49;
        --dash-secondary-container: #6cf8bb;
        --dash-on-secondary-container: #00714d;
        --dash-surface: #f8f9ff;
        --dash-surface-lowest: #ffffff;
        --dash-surface-low: #eff4ff;
        --dash-surface-container: #e5eeff;
        --dash-surface-high: #dce9ff;
        --dash-on-surface: #0b1c30;
        --dash-on-surface-variant: #45464d;
        --dash-outline: #76777d;
        --dash-outline-variant: #c6c6cd;
        --dash-error: #ba1a1a;
        --dash-error-container: #ffdad6;
        --dash-on-error-container: #93000a;
        color: var(--dash-on-surface);
    }

    .dash .tnum { font-variant-numeric: tabular-nums; letter-spacing: -0.02em; }
    .dash .ms { font-family: 'Material Symbols Outlined'; font-size: 20px; line-height: 1; }

    /* ───────────────────────── Smooth Micro-animations ───────────────────────── */
    @keyframes dashFadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes dashScaleX {
        from { transform: scaleX(0); }
        to { transform: scaleX(1); }
    }
    @keyframes pulseEmerald {
        0%, 100% { box-shadow: 0 0 0 0 rgba(0, 108, 73, 0.4); }
        50% { box-shadow: 0 0 0 6px rgba(0, 108, 73, 0); }
    }

    .dash .animate-reveal {
        animation: dashFadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        animation-delay: calc(var(--delay, 0) * 60ms);
    }
    .dash .grow-bar {
        transform-origin: left center;
        animation: dashScaleX 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        animation-delay: calc(var(--delay, 0) * 60ms + 200ms);
    }
    .dash .live-pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: var(--dash-primary);
        animation: pulseEmerald 2s infinite;
    }

    /* ───────────────────────── Hero Banner (Design System Compliant) ───────────────────────── */
    .dash-hero-card {
        background: linear-gradient(135deg, #ffffff 0%, #f4f8ff 60%, #eefbf4 100%);
        border: 1px solid var(--dash-outline-variant);
        border-radius: 24px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 20px -6px rgba(11, 28, 48, 0.06);
    }
    .dash-hero-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #006c49 0%, #6cf8bb 50%, #006c49 100%);
    }

    /* ───────────────────────── Cards & Surfaces ───────────────────────── */
    .dash-card {
        background: var(--dash-surface-lowest);
        border: 1px solid var(--dash-outline-variant);
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(11, 28, 48, 0.04);
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    }
    .dash-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px -8px rgba(11, 28, 48, 0.1);
        border-color: rgba(0, 108, 73, 0.3);
    }

    /* ───────────────────────── Badges & Chips ───────────────────────── */
    .ds-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 600;
    }
    .ds-chip-primary {
        background-color: #e8f5e9;
        color: #006c49;
        border: 1px solid rgba(0, 108, 73, 0.2);
    }
    .ds-chip-neutral {
        background-color: var(--dash-surface-low);
        color: var(--dash-on-surface-variant);
        border: 1px solid rgba(198, 198, 205, 0.6);
    }
    .ds-chip-warning {
        background-color: #fff8e1;
        color: #b45309;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }
    .ds-chip-danger {
        background-color: var(--dash-error-container);
        color: var(--dash-error);
        border: 1px solid rgba(186, 26, 26, 0.25);
    }

    /* ───────────────────────── Icon Containers ───────────────────────── */
    .ds-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .ds-icon-primary {
        background-color: #e8f5e9;
        color: #006c49;
    }
    .ds-icon-surface {
        background-color: var(--dash-surface-high);
        color: #006c49;
    }
    .ds-icon-danger {
        background-color: var(--dash-error-container);
        color: var(--dash-error);
    }
    .ds-icon-warning {
        background-color: #fef3c7;
        color: #b45309;
    }

    /* ───────────────────────── Buttons ───────────────────────── */
    .btn-ds-primary {
        background-color: var(--dash-primary);
        color: #ffffff;
        font-weight: 700;
        font-size: 14px;
        padding: 10px 18px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.15s ease;
        box-shadow: 0 2px 6px rgba(0, 108, 73, 0.25);
    }
    .btn-ds-primary:hover {
        background-color: var(--dash-primary-hover);
        box-shadow: 0 4px 12px rgba(0, 108, 73, 0.35);
        transform: translateY(-1px);
    }
    .btn-ds-primary:active {
        transform: scale(0.97);
    }

    .btn-ds-outline {
        background-color: #ffffff;
        color: var(--dash-primary);
        border: 1px solid var(--dash-outline-variant);
        font-weight: 700;
        font-size: 14px;
        padding: 10px 18px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.15s ease;
    }
    .btn-ds-outline:hover {
        background-color: var(--dash-surface-low);
        border-color: var(--dash-primary);
        transform: translateY(-1px);
    }
    .btn-ds-outline:active {
        transform: scale(0.97);
    }

    /* ───────────────────────── Filter Tabs ───────────────────────── */
    .filter-tab {
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        color: var(--dash-on-surface-variant);
        background-color: transparent;
        transition: all 0.18s ease;
        cursor: pointer;
    }
    .filter-tab:hover {
        background-color: var(--dash-surface-container);
        color: var(--dash-on-surface);
    }
    .filter-tab.is-active {
        background-color: var(--dash-primary);
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 108, 73, 0.28);
    }

    /* ───────────────────────── Progress Bar ───────────────────────── */
    .bar-track {
        height: 7px;
        border-radius: 9999px;
        background-color: var(--dash-surface-container);
        overflow: hidden;
    }
    .bar-fill {
        height: 100%;
        border-radius: 9999px;
    }

    /* Accessibility reduce motion */
    @media (prefers-reduced-motion: reduce) {
        .dash .animate-reveal, .dash .grow-bar, .dash .live-pulse-dot {
            animation: none !important;
        }
        .dash-card:hover {
            transform: none !important;
        }
    }
</style>
@endpush

@php
    $firstName = explode(' ', trim(auth()->user()->name ?? 'Admin'))[0];

    // Profit composition
    $profitPositive = max($netProfit, 0);
    $compTotal = max($totalPurchaseCost + $monthlyExpenses + $profitPositive, 1);
    $cogsPct   = round(($totalPurchaseCost / $compTotal) * 100, 1);
    $expPct    = round(($monthlyExpenses / $compTotal) * 100, 1);
    $profitPct = round(($profitPositive / $compTotal) * 100, 1);
    $margin    = $monthlySales > 0 ? ($netProfit / $monthlySales) * 100 : 0;

    $healthyPct = $uniqueSKUs > 0 ? (int) round((($uniqueSKUs - $lowStockAlerts) / $uniqueSKUs) * 100) : 100;

    // Palette strictly matching InvenTrack theme
    $palette  = ['#006c49', '#00875a', '#2e7d32', '#d95f00', '#ba1a1a'];
    $maxSold  = max((float) $topSellingProducts->max('total_sold'), 1);
    $maxSales = max((float) $salesByCreator->max('total_sales'), 1);
    $trendIn  = (int) $stockTrend->sum('stock_in');
    $trendOut = (int) $stockTrend->sum('stock_out');
    $peakDay  = $dailyRevenue->sortByDesc('revenue')->first();
@endphp

<div class="dash space-y-6">

    {{-- ═══════════════════════════ HERO BANNER (MATCHES DESIGN SYSTEM) ═══════════════════════════ --}}
    <section class="dash-hero-card animate-reveal p-6 md:p-8" style="--delay:0" aria-label="Ringkasan Operasional">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
            
            {{-- Left column: Headline & Quick Actions --}}
            <div class="lg:col-span-7 flex flex-col justify-between space-y-5">
                <div>
                    {{-- Live Store Badge --}}
                    <div class="flex items-center gap-2 mb-3">
                        <span class="ds-chip ds-chip-primary">
                            <span class="live-pulse-dot"></span>
                            <span>Toko Aktif &middot; {{ $storeBalanceScope }}</span>
                        </span>
                        <span class="text-xs text-on-surface-variant font-medium hidden sm:inline" id="today-label"></span>
                    </div>

                    {{-- Greeting --}}
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-on-surface tracking-tight">
                        <span id="greeting">Selamat datang</span>, <span class="text-[#006c49]">{{ $firstName }}</span>
                    </h1>
                    <p class="text-sm md:text-base text-on-surface-variant mt-1.5 max-w-xl leading-relaxed">
                        Laporan aktivitas dan ringkasan operasional periode <strong class="text-on-surface font-bold">{{ $filterLabel }}</strong>.
                    </p>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('movements.create') }}" class="btn-ds-primary">
                        <span class="ms">add_box</span>
                        <span>New Stock Entry</span>
                    </a>
                    <a href="{{ route('inventory.index') }}" class="btn-ds-outline">
                        <span class="ms">inventory_2</span>
                        <span>View Inventory</span>
                    </a>
                </div>

                {{-- Key Chips --}}
                <div class="flex flex-wrap gap-2 pt-1">
                    <span class="ds-chip ds-chip-neutral">
                        <span class="ms text-sm text-[#006c49]">percent</span>
                        <span>Margin {{ number_format($margin, 1, ',', '.') }}%</span>
                    </span>
                    <span class="ds-chip ds-chip-neutral">
                        <span class="ms text-sm text-[#006c49]">category</span>
                        <span>{{ number_format($uniqueSKUs) }} Total SKU</span>
                    </span>
                    <span class="ds-chip {{ $lowStockAlerts > 0 ? 'ds-chip-danger' : 'ds-chip-primary' }}">
                        <span class="ms text-sm">{{ $lowStockAlerts > 0 ? 'warning' : 'check_circle' }}</span>
                        <span>{{ $lowStockAlerts > 0 ? $lowStockAlerts . ' Stok Kritis' : 'Semua Stok Aman' }}</span>
                    </span>
                </div>
            </div>

            {{-- Right column: Real-Time Saldo Toko Berjalan Card --}}
            <div class="lg:col-span-5">
                <div class="bg-white border-2 border-[#006c49]/20 rounded-2xl p-6 shadow-sm relative overflow-hidden">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="ds-icon-box ds-icon-primary shadow-xs">
                                <span class="material-symbols-outlined text-2xl">account_balance</span>
                            </span>
                            <div>
                                <h2 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Saldo Toko Berjalan</h2>
                                <p class="text-xs font-medium text-on-surface truncate max-w-[190px]" title="{{ $storeBankSub }}">{{ $storeBankSub }}</p>
                            </div>
                        </div>
                        <span class="ds-chip ds-chip-primary text-[10px] uppercase font-bold py-0.5 px-2">
                            Real-Time
                        </span>
                    </div>

                    {{-- Saldo Amount --}}
                    <div class="mt-4">
                        <div class="text-2xl sm:text-3xl font-extrabold text-[#006c49] font-mono tracking-tight tnum"
                             data-count="{{ round($storeEndingBalance) }}" data-prefix="Rp ">
                            Rp {{ number_format($storeEndingBalance, 0, ',', '.') }}
                        </div>
                        <p class="text-[11px] text-on-surface-variant mt-1">Saldo Awal + Penjualan + Pemasukan &minus; Pengeluaran &minus; Belanja Stok</p>
                    </div>

                    {{-- Mini breakdown --}}
                    <div class="grid grid-cols-2 gap-3 pt-4 mt-4 border-t border-outline-variant/60">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Penjualan Masuk</span>
                            <p class="text-sm font-extrabold text-on-surface font-mono mt-0.5 tnum">
                                Rp {{ number_format($monthlySales, 0, ',', '.') }}
                            </p>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Laba Bersih</span>
                            <p class="text-sm font-extrabold font-mono mt-0.5 tnum {{ $netProfit >= 0 ? 'text-[#006c49]' : 'text-error' }}">
                                Rp {{ number_format($netProfit, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- ═══════════════════════════ PERIODE FILTER ═══════════════════════════ --}}
    <div class="dash-card animate-reveal p-3 md:px-5" style="--delay:1">
        <form action="{{ route('dashboard') }}" method="GET" id="filter-form" class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <input type="hidden" name="filter" id="filter-input" value="{{ $filter }}">

            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mr-2 flex items-center gap-1.5">
                    <span class="ms text-base text-[#006c49]">calendar_month</span> Filter Periode:
                </span>
                <div class="bg-surface-container-low p-1 rounded-xl flex flex-wrap gap-1 border border-outline-variant/40">
                    <button type="button" onclick="setFilter('today')" class="filter-tab {{ $filter === 'today' ? 'is-active' : '' }}">
                        Hari Ini
                    </button>
                    <button type="button" onclick="setFilter('yesterday')" class="filter-tab {{ $filter === 'yesterday' ? 'is-active' : '' }}">
                        Kemarin
                    </button>
                    <button type="button" onclick="setFilter('mtd')" class="filter-tab {{ $filter === 'mtd' ? 'is-active' : '' }}">
                        Bulan Ini
                    </button>
                    <button type="button" onclick="toggleCustomRange()" class="filter-tab {{ $filter === 'custom' ? 'is-active' : '' }}">
                        Custom Range
                    </button>
                </div>
            </div>

            {{-- Custom Range Form --}}
            <div id="custom-range-inputs" class="{{ $filter === 'custom' ? 'flex' : 'hidden' }} flex-wrap items-center gap-2">
                <input type="date" name="start_date" id="start_date" value="{{ request('start_date', date('Y-m-d')) }}"
                       class="px-3 py-1.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-[#006c49]">
                <span class="text-xs font-bold text-on-surface-variant">s/d</span>
                <input type="date" name="end_date" id="end_date" value="{{ request('end_date', date('Y-m-d')) }}"
                       class="px-3 py-1.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-[#006c49]">
                <button type="submit" class="btn-ds-primary py-1.5 px-4 text-xs font-bold">
                    Terapkan
                </button>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════════ KPI SUMMARY CARDS (6 CARDS) ═══════════════════════════ --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" aria-label="Key Performance Indicators">

        {{-- 1. Penjualan (Omzet) --}}
        <article class="dash-card animate-reveal p-5 flex flex-col justify-between" style="--delay:2">
            <div>
                <div class="flex items-center justify-between">
                    <span class="ds-icon-box ds-icon-primary">
                        <span class="material-symbols-outlined text-2xl">payments</span>
                    </span>
                    <span class="ds-chip ds-chip-primary text-[11px] font-bold">Omzet</span>
                </div>
                <p class="text-xs font-semibold text-on-surface-variant mt-4">Penjualan &middot; {{ $filterLabel }}</p>
                <div class="text-2xl font-extrabold text-[#006c49] font-mono mt-1 tnum" data-count="{{ round($monthlySales) }}" data-prefix="Rp ">
                    Rp {{ number_format($monthlySales, 0, ',', '.') }}
                </div>
                <p class="text-xs text-on-surface-variant mt-0.5">Total transaksi keluar toko</p>
            </div>
            <div class="h-12 mt-3 -mx-2 -mb-2">
                <canvas id="sparkSales" aria-label="Grafik mini tren penjualan harian"></canvas>
            </div>
        </article>

        {{-- 2. Pengeluaran Toko --}}
        <article class="dash-card animate-reveal p-5 flex flex-col justify-between" style="--delay:3">
            <div>
                <div class="flex items-center justify-between">
                    <span class="ds-icon-box ds-icon-danger">
                        <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
                    </span>
                    <span class="ds-chip ds-chip-danger text-[11px] font-bold">Beban Operasional</span>
                </div>
                <p class="text-xs font-semibold text-on-surface-variant mt-4">Pengeluaran &middot; {{ $filterLabel }}</p>
                <div class="text-2xl font-extrabold text-error font-mono mt-1 tnum" data-count="{{ round($monthlyExpenses) }}" data-prefix="Rp ">
                    Rp {{ number_format($monthlyExpenses, 0, ',', '.') }}
                </div>
                <p class="text-xs text-on-surface-variant mt-0.5">Beban & operasional toko</p>
            </div>
            <div class="mt-4 pt-2 border-t border-outline-variant/40">
                <div class="flex justify-between text-[11px] font-bold text-on-surface-variant mb-1.5">
                    <span>Porsi terhadap omzet</span>
                    <span class="tnum">{{ $monthlySales > 0 ? number_format(min(($monthlyExpenses / $monthlySales) * 100, 999), 1, ',', '.') : '0' }}%</span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill grow-bar bg-error" style="--delay:3;width:{{ $monthlySales > 0 ? min(($monthlyExpenses / $monthlySales) * 100, 100) : 0 }}%;"></div>
                </div>
            </div>
        </article>

        {{-- 3. Laba Bersih --}}
        <article class="dash-card animate-reveal p-5 flex flex-col justify-between sm:col-span-2 lg:col-span-1" style="--delay:4">
            <div>
                <div class="flex items-center justify-between">
                    <span class="ds-icon-box ds-icon-primary">
                        <span class="material-symbols-outlined text-2xl">trending_up</span>
                    </span>
                    <span class="ds-chip {{ $netProfit >= 0 ? 'ds-chip-primary' : 'ds-chip-danger' }} text-[11px] font-bold">
                        {{ $netProfit >= 0 ? 'Net Profit' : 'Rugi' }}
                    </span>
                </div>
                <p class="text-xs font-semibold text-on-surface-variant mt-4">Laba Bersih &middot; {{ $filterLabel }}</p>
                <div class="text-2xl font-extrabold font-mono mt-1 tnum {{ $netProfit >= 0 ? 'text-[#006c49]' : 'text-error' }}"
                     data-count="{{ round($netProfit) }}" data-prefix="Rp ">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </div>
                <p class="text-xs text-on-surface-variant mt-0.5">(Penjualan + Pemasukan) &minus; HPP &minus; Pengeluaran</p>
            </div>
            <div class="mt-4 pt-2 border-t border-outline-variant/40">
                <div class="flex gap-1 h-2 rounded-full overflow-hidden bg-surface-container" role="img" aria-label="Komposisi Penjualan">
                    <span class="grow-bar" style="--delay:4;width:{{ $cogsPct }}%;background-color:#76777d" title="HPP: {{ $cogsPct }}%"></span>
                    <span class="grow-bar" style="--delay:5;width:{{ $expPct }}%;background-color:#ba1a1a" title="Beban: {{ $expPct }}%"></span>
                    <span class="grow-bar" style="--delay:6;width:{{ $profitPct }}%;background-color:#006c49" title="Laba: {{ $profitPct }}%"></span>
                </div>
                <div class="flex justify-between text-[10px] font-bold text-on-surface-variant mt-2">
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-[#76777d]"></span>HPP {{ $cogsPct }}%</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-[#ba1a1a]"></span>Beban {{ $expPct }}%</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-[#006c49]"></span>Laba {{ $profitPct }}%</span>
                </div>
            </div>
        </article>

        {{-- 4. Total Stok Fisik --}}
        <article class="dash-card animate-reveal p-5 flex flex-col justify-between" style="--delay:5">
            <div class="flex items-center justify-between">
                <span class="ds-icon-box ds-icon-surface">
                    <span class="material-symbols-outlined text-2xl">warehouse</span>
                </span>
                <span class="ds-chip ds-chip-neutral text-[11px] font-bold">{{ number_format($uniqueSKUs) }} SKU</span>
            </div>
            <div class="mt-4">
                <p class="text-xs font-semibold text-on-surface-variant">Total Item Fisik</p>
                <div class="text-2xl font-extrabold text-on-surface font-mono mt-1 tnum" data-count="{{ (int) $totalItems }}">
                    {{ number_format($totalItems) }}
                </div>
                <p class="text-xs text-on-surface-variant mt-0.5">Total unit stok di seluruh katalog</p>
            </div>
        </article>

        {{-- 5. Stok Kritis / Menipis --}}
        <article class="dash-card animate-reveal p-5 flex flex-col justify-between" style="--delay:6">
            <div class="flex items-center justify-between">
                <span class="ds-icon-box {{ $lowStockAlerts > 0 ? 'ds-icon-danger' : 'ds-icon-primary' }}">
                    <span class="material-symbols-outlined text-2xl">{{ $lowStockAlerts > 0 ? 'warning' : 'verified' }}</span>
                </span>
                <span class="ds-chip {{ $lowStockAlerts > 0 ? 'ds-chip-danger' : 'ds-chip-primary' }} text-[11px] font-bold">
                    {{ $lowStockAlerts > 0 ? 'Critical' : 'Healthy' }}
                </span>
            </div>
            <div class="flex items-end justify-between gap-2 mt-4">
                <div>
                    <p class="text-xs font-semibold text-on-surface-variant">Stok Kritis</p>
                    <div class="text-2xl font-extrabold font-mono mt-1 tnum {{ $lowStockAlerts > 0 ? 'text-error' : 'text-on-surface' }}" data-count="{{ $lowStockAlerts }}">
                        {{ $lowStockAlerts }}
                    </div>
                    <p class="text-xs text-on-surface-variant mt-0.5">{{ $lowStockAlerts > 0 ? 'Item di bawah minimum' : 'Katalog stok sehat' }}</p>
                </div>
                {{-- Healthy ring indicator --}}
                <div class="relative shrink-0 w-12 h-12" title="{{ $healthyPct }}% SKU sehat">
                    <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e5eeff" stroke-width="4"/>
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="{{ $healthyPct >= 80 ? '#006c49' : '#ba1a1a' }}" stroke-width="4" stroke-linecap="round"
                                stroke-dasharray="{{ round(97.4 * $healthyPct / 100, 1) }} 97.4"/>
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-[10px] font-extrabold tnum text-on-surface">{{ $healthyPct }}%</span>
                </div>
            </div>
        </article>

        {{-- 6. Total Stock In --}}
        <article class="dash-card animate-reveal p-5 flex flex-col justify-between" style="--delay:7">
            <div>
                <div class="flex items-center justify-between">
                    <span class="ds-icon-box ds-icon-primary">
                        <span class="material-symbols-outlined text-2xl">add_box</span>
                    </span>
                    <span class="ds-chip ds-chip-primary text-[11px] font-bold">Barang Masuk</span>
                </div>
                <p class="text-xs font-semibold text-on-surface-variant mt-4">Stock In &middot; {{ $filterLabel }}</p>
                <div class="text-2xl font-extrabold text-on-surface font-mono mt-1 tnum" data-count="{{ (int) $monthlyStockIn }}">
                    {{ number_format($monthlyStockIn) }}
                </div>
                <p class="text-xs text-on-surface-variant mt-0.5">Total unit restock yang diterima</p>
            </div>
            <div class="h-12 mt-3 -mx-2 -mb-2">
                <canvas id="sparkIn" aria-label="Grafik mini stok masuk"></canvas>
            </div>
        </article>

    </section>

    {{-- ═══════════════════════════ CHARTS SECTION ═══════════════════════════ --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Chart 1: Stock Movement Trend (2 Columns) --}}
        <article class="dash-card animate-reveal lg:col-span-2 flex flex-col overflow-hidden" style="--delay:8">
            <header class="p-5 border-b border-outline-variant flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="ds-icon-box ds-icon-primary w-9 h-9">
                        <span class="material-symbols-outlined text-xl">show_chart</span>
                    </span>
                    <div>
                        <h2 class="text-base font-extrabold text-on-surface">Tren Pergerakan Stok</h2>
                        <p class="text-xs text-on-surface-variant">Grafik perbandingan unit masuk & keluar harian</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="ds-chip ds-chip-primary text-xs">
                        +{{ number_format($trendIn) }} Masuk
                    </span>
                    <span class="ds-chip ds-chip-danger text-xs">
                        &minus;{{ number_format($trendOut) }} Keluar
                    </span>
                </div>
            </header>
            <div class="p-5 flex-1 min-h-[280px]">
                <div class="relative w-full h-[270px]">
                    <canvas id="stockTrendChart" aria-label="Grafik pergerakan stok harian"></canvas>
                </div>
            </div>
        </article>

        {{-- Chart 2: Top Selling Donut (1 Column) --}}
        <article class="dash-card animate-reveal flex flex-col overflow-hidden" style="--delay:9">
            <header class="p-5 border-b border-outline-variant flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="ds-icon-box ds-icon-surface w-9 h-9">
                        <span class="material-symbols-outlined text-xl">donut_large</span>
                    </span>
                    <h2 class="text-base font-extrabold text-on-surface">Produk Terlaris</h2>
                </div>
                <span class="ds-chip ds-chip-neutral text-xs">Top 5</span>
            </header>
            <div class="p-5 flex-1 flex flex-col justify-between">
                @if($topSellingProducts->isNotEmpty())
                    <div class="relative mx-auto my-2 w-[180px] h-[180px]">
                        <canvas id="topProductsChart" aria-label="Diagram lingkaran produk terlaris"></canvas>
                    </div>
                    <ol class="space-y-2 mt-4">
                        @foreach($topSellingProducts as $i => $top)
                            <li class="flex items-center justify-between gap-3 text-xs p-1.5 rounded-xl hover:bg-surface-container-low transition-colors">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $palette[$i % count($palette)] }}"></span>
                                    <span class="font-bold text-on-surface truncate max-w-[140px]" title="{{ $top->product?->name }}">
                                        {{ $top->product?->name ?? 'Deleted Product' }}
                                    </span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-extrabold text-on-surface font-mono">{{ number_format($top->total_sold) }} unit</span>
                                    <span class="text-[10px] text-on-surface-variant block font-mono">Rp {{ number_format($top->total_revenue, 0, ',', '.') }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="flex flex-col items-center justify-center p-8 text-center text-on-surface-variant flex-1">
                        <span class="material-symbols-outlined text-4xl text-outline mb-2">shopping_bag</span>
                        <p class="text-xs">Belum ada penjualan produk tercatat pada periode ini.</p>
                    </div>
                @endif
            </div>
        </article>

    </section>

    {{-- ═══════════════════════════ REVENUE BAR & STAFF PERFORMANCE ═══════════════════════════ --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Daily Revenue Bar Chart --}}
        <article class="dash-card animate-reveal lg:col-span-2 flex flex-col overflow-hidden" style="--delay:10">
            <header class="p-5 border-b border-outline-variant flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2.5">
                    <span class="ds-icon-box ds-icon-primary w-9 h-9">
                        <span class="material-symbols-outlined text-xl">bar_chart</span>
                    </span>
                    <div>
                        <h2 class="text-base font-extrabold text-on-surface">Pendapatan Harian (Omzet)</h2>
                        <p class="text-xs text-on-surface-variant">Statistik nominal omzet transaksi keluar per hari</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($peakDay && $peakDay['revenue'] > 0)
                        <span class="ds-chip ds-chip-warning text-xs">
                            <span class="ms text-sm">bolt</span> Puncak: {{ $peakDay['date'] }}
                        </span>
                    @endif
                    <span class="ds-chip ds-chip-neutral text-xs">{{ $filterLabel }}</span>
                </div>
            </header>
            <div class="p-5 flex-1 min-h-[250px]">
                <div class="relative w-full h-[240px]">
                    <canvas id="dailyRevenueChart" aria-label="Grafik batang pendapatan harian"></canvas>
                </div>
            </div>
        </article>

        {{-- Staff Leaderboard --}}
        <article class="dash-card animate-reveal flex flex-col overflow-hidden" style="--delay:11">
            <header class="p-5 border-b border-outline-variant flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="ds-icon-box ds-icon-surface w-9 h-9">
                        <span class="material-symbols-outlined text-xl">emoji_events</span>
                    </span>
                    <h2 class="text-base font-extrabold text-on-surface">Kinerja Staff / Kasir</h2>
                </div>
                <span class="ds-chip ds-chip-neutral text-xs">Leaderboard</span>
            </header>
            <div class="divide-y divide-outline-variant/60 flex-1 overflow-y-auto max-h-[340px]">
                @forelse($salesByCreator as $i => $creator)
                    <div class="p-4 hover:bg-surface-container-low transition-colors flex items-center gap-3">
                        {{-- Avatar --}}
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#006c49] to-[#00553a] text-white flex items-center justify-center font-extrabold text-sm uppercase shadow-xs shrink-0">
                            {{ substr($creator->user?->name ?? 'S', 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <h3 class="text-xs font-bold text-on-surface truncate" title="{{ $creator->user?->name ?? 'System' }}">
                                    {{ $creator->user?->name ?? 'System' }}
                                    @if($i === 0 && $creator->total_sales > 0)
                                        <span class="material-symbols-outlined text-amber-500 text-sm align-middle">workspace_premium</span>
                                    @endif
                                </h3>
                                <span class="text-xs font-extrabold text-[#006c49] font-mono whitespace-nowrap">
                                    Rp {{ number_format($creator->total_sales, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="bar-track mt-1.5 h-1.5">
                                <div class="bar-fill grow-bar bg-[#006c49]" style="--delay:{{ 11 + $i }};width:{{ round(($creator->total_sales / $maxSales) * 100) }}%;"></div>
                            </div>
                            <div class="flex items-center gap-2 mt-1.5 text-[10px] font-bold text-on-surface-variant">
                                <span class="text-[#006c49]">+{{ number_format($creator->total_in_qty) }} Masuk</span>
                                <span>&middot;</span>
                                <span class="text-error">&minus;{{ number_format($creator->total_out_qty) }} Keluar</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-on-surface-variant text-xs flex flex-col items-center justify-center">
                        <span class="material-symbols-outlined text-4xl text-outline mb-2">groups</span>
                        Belum ada aktivitas penjualan atau movement oleh staf.
                    </div>
                @endforelse
            </div>
        </article>

    </section>

    {{-- ═══════════════════════════ RECENT MOVEMENTS & LOW STOCK TABLES ═══════════════════════════ --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        {{-- Recent Stock Movements (2 Columns) --}}
        <article class="dash-card animate-reveal lg:col-span-2 flex flex-col overflow-hidden" style="--delay:12">
            <header class="p-5 border-b border-outline-variant flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2.5">
                    <span class="ds-icon-box ds-icon-surface w-9 h-9">
                        <span class="material-symbols-outlined text-xl">swap_vert</span>
                    </span>
                    <h2 class="text-base font-extrabold text-on-surface">Pergerakan Stok Terkini</h2>
                </div>
                <a href="{{ route('movements.index') }}" class="btn-ds-outline py-1.5 px-3 text-xs font-bold inline-flex items-center gap-1">
                    <span>Lihat Semua</span>
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </header>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low text-on-surface-variant text-xs uppercase tracking-wider border-b border-outline-variant font-bold">
                        <tr>
                            <th class="px-5 py-3">No. Referensi</th>
                            <th class="px-5 py-3">Produk / SKU</th>
                            <th class="px-5 py-3 text-center">Tipe</th>
                            <th class="px-5 py-3 text-right">Jumlah</th>
                            <th class="px-5 py-3 text-right">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/60 text-xs">
                        @forelse($recentMovements as $movement)
                            @php $isIn = $movement->type === 'in'; @endphp
                            <tr class="hover:bg-surface-container-low/60 transition-colors cursor-pointer" onclick="window.location='{{ route('inventory.index', ['search' => $movement->product?->sku]) }}'">
                                <td class="px-5 py-3 font-mono font-bold text-on-surface-variant">
                                    #{{ $movement->reference_number }}
                                </td>
                                <td class="px-5 py-3">
                                    <div class="font-extrabold text-on-surface">{{ $movement->product?->name ?? 'Deleted Product' }}</div>
                                    <div class="font-mono text-[11px] text-on-surface-variant">SKU: {{ $movement->product?->sku ?? '—' }}</div>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="ds-chip {{ $isIn ? 'ds-chip-primary' : 'ds-chip-neutral' }} text-[10px] font-bold py-0.5 px-2">
                                        <span class="material-symbols-outlined text-xs">{{ $isIn ? 'south_west' : 'north_east' }}</span>
                                        {{ $isIn ? 'Masuk' : 'Keluar' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right font-mono font-extrabold {{ $isIn ? 'text-[#006c49]' : 'text-error' }}">
                                    {{ $isIn ? '+' : '−' }}{{ number_format($movement->quantity) }} unit
                                </td>
                                <td class="px-5 py-3 text-right text-on-surface-variant">
                                    {{ $movement->created_at->format('d M Y · H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-on-surface-variant text-xs">
                                    Belum ada catatan pergerakan stok.
                                    <a href="{{ route('movements.create') }}" class="text-[#006c49] font-bold underline ml-1">Catat Sekarang</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        {{-- Low Stock Details (1 Column) --}}
        <article class="dash-card animate-reveal flex flex-col overflow-hidden" style="--delay:13">
            <header class="p-5 border-b border-outline-variant flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="ds-icon-box ds-icon-danger w-9 h-9">
                        <span class="material-symbols-outlined text-xl">warning</span>
                    </span>
                    <h2 class="text-base font-extrabold text-on-surface">Peringatan Stok</h2>
                </div>
                @if($lowStockAlerts > 0)
                    <span class="ds-chip ds-chip-danger text-xs font-bold">Action Needed</span>
                @endif
            </header>
            <div class="divide-y divide-outline-variant/60 flex-1 overflow-y-auto max-h-[380px]">
                @forelse($lowStockProducts as $prod)
                    @php
                        $pct = min(100, (int) round(($prod->stock / max($prod->min_stock, 1)) * 100));
                        $barColor = $pct <= 25 ? '#ba1a1a' : '#d95f00';
                    @endphp
                    <div class="p-4 hover:bg-surface-container-low transition-colors">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="text-xs font-extrabold text-on-surface truncate" title="{{ $prod->name }}">{{ $prod->name }}</h3>
                                <p class="text-[11px] font-mono text-on-surface-variant">{{ $prod->sku }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-xs font-mono font-extrabold text-error">{{ number_format($prod->stock) }}</span>
                                <span class="text-[10px] text-on-surface-variant"> / {{ number_format($prod->min_stock) }}</span>
                            </div>
                        </div>
                        <div class="bar-track mt-2 h-1.5">
                            <div class="bar-fill grow-bar" style="--delay:13;width:{{ max($pct, 6) }}%;background-color:{{ $barColor }};"></div>
                        </div>
                        <div class="mt-2 text-right">
                            @can('Stock In Create')
                                <a href="{{ route('movements.create', ['product_id' => $prod->id, 'type' => 'in']) }}"
                                   class="inline-flex items-center gap-1 text-[11px] font-bold text-[#006c49] hover:underline">
                                    <span class="material-symbols-outlined text-xs">add_box</span> Restock Produk
                                </a>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-on-surface-variant text-xs flex flex-col items-center justify-center">
                        <span class="material-symbols-outlined text-4xl text-[#006c49] mb-2">check_circle</span>
                        Semua item berada di atas batas minimum stok. Aman!
                    </div>
                @endforelse
            </div>
        </article>

    </section>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    // ── Filter helpers ───────────────────────────────────────────────────────
    function setFilter(val) {
        document.getElementById('filter-input').value = val;
        if (val !== 'custom') {
            document.getElementById('filter-form').submit();
        } else {
            toggleCustomRange();
        }
    }

    function toggleCustomRange() {
        const customDiv = document.getElementById('custom-range-inputs');
        document.getElementById('filter-input').value = 'custom';
        customDiv.classList.remove('hidden');
        customDiv.classList.add('flex');
    }

    // ── Client Time Greeting ────────────────────────────────────────────────
    (function () {
        const h = new Date().getHours();
        const g = h < 11 ? 'Selamat pagi' : h < 15 ? 'Selamat siang' : h < 18 ? 'Selamat sore' : 'Selamat malam';
        const el = document.getElementById('greeting');
        if (el) el.textContent = g;
        const d = document.getElementById('today-label');
        if (d) d.textContent = '&middot; ' + new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    })();

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ── Number Count-up Animation ───────────────────────────────────────────
    (function () {
        if (reduceMotion) return;
        const fmt = new Intl.NumberFormat('id-ID');
        document.querySelectorAll('[data-count]').forEach(function (el) {
            const target = Number(el.dataset.count);
            if (!isFinite(target) || target === 0) return;
            const prefix = el.dataset.prefix || '';
            const dur = 1000, t0 = performance.now();
            (function tick(now) {
                const p = Math.min((now - t0) / dur, 1);
                const v = Math.round(target * (1 - Math.pow(1 - p, 3)));
                el.textContent = prefix + (v < 0 ? '-' : '') + fmt.format(Math.abs(v));
                if (p < 1) requestAnimationFrame(tick);
            })(t0);
        });
    })();

    // ── Chart.js Configurations (InvenTrack Design System) ─────────────────
    const stockTrendData   = @json($stockTrend->values());
    const dailyRevenueData = @json($dailyRevenue->values());
    const topProductsData  = @json($topSellingProducts);
    const palette          = @json($palette);

    const gridLineColor = 'rgba(198, 198, 205, 0.45)'; // InvenTrack outline-variant
    const labelColor    = '#45464d';                   // InvenTrack on-surface-variant
    const animOpts      = reduceMotion ? false : { duration: 900, easing: 'easeOutQuart' };

    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size   = 11;
    Chart.defaults.color       = labelColor;

    const tooltipConfig = {
        backgroundColor: '#0b1c30', // InvenTrack on-surface dark
        titleColor: '#ffffff',
        bodyColor: '#e5eeff',
        padding: 10,
        cornerRadius: 10,
        boxPadding: 4,
        usePointStyle: true,
    };

    function createLinearGradient(ctx, height, colorHex, opacityHex) {
        const g = ctx.createLinearGradient(0, 0, 0, height);
        g.addColorStop(0, colorHex + opacityHex);
        g.addColorStop(1, colorHex + '00');
        return g;
    }

    // ── Sparklines ──────────────────────────────────────────────────────────
    function drawSparkline(id, data, hex) {
        const canvas = document.getElementById(id);
        if (!canvas || !data.length) return;
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.map(function (_, i) { return i; }),
                datasets: [{
                    data: data,
                    borderColor: hex,
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    backgroundColor: createLinearGradient(canvas.getContext('2d'), 50, hex, '33'),
                    pointRadius: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: animOpts,
                events: [],
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false, beginAtZero: true } },
            },
        });
    }
    drawSparkline('sparkSales', dailyRevenueData.map(function (d) { return d.revenue; }), '#006c49');
    drawSparkline('sparkIn',    stockTrendData.map(function (d) { return d.stock_in; }),   '#006c49');

    // ── 1. Stock Movement Trend (Line Chart) ────────────────────────────────
    (function () {
        const canvas = document.getElementById('stockTrendChart');
        if (!canvas) return;
        const ctx    = canvas.getContext('2d');
        const labels = stockTrendData.map(function (d) { return d.date; });
        const isDense= labels.length > 14;

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Stock In (Masuk)',
                        data: stockTrendData.map(function (d) { return d.stock_in; }),
                        borderColor: '#006c49', // Primary green
                        backgroundColor: createLinearGradient(ctx, 270, '#006c49', '28'),
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: true,
                        pointRadius: isDense ? 0 : 3.5,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#006c49',
                        pointBorderWidth: 2,
                    },
                    {
                        label: 'Stock Out (Keluar)',
                        data: stockTrendData.map(function (d) { return d.stock_out; }),
                        borderColor: '#ba1a1a', // Error red
                        backgroundColor: createLinearGradient(ctx, 270, '#ba1a1a', '18'),
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: true,
                        pointRadius: isDense ? 0 : 3.5,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#ba1a1a',
                        pointBorderWidth: 2,
                    }
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: animOpts,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { usePointStyle: true, boxWidth: 8, padding: 16, color: labelColor },
                    },
                    tooltip: Object.assign({}, tooltipConfig, {
                        callbacks: {
                            label: function (c) { return ' ' + c.dataset.label + ': ' + Number(c.parsed.y).toLocaleString('id-ID') + ' unit'; },
                        },
                    }),
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { maxTicksLimit: 10, color: labelColor },
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: gridLineColor },
                        ticks: { precision: 0, padding: 6, color: labelColor },
                    },
                },
            },
        });
    })();

    // ── 2. Daily Revenue (Bar Chart) ─────────────────────────────────────────
    (function () {
        const canvas = document.getElementById('dailyRevenueChart');
        if (!canvas) return;
        const ctx    = canvas.getContext('2d');
        const labels = dailyRevenueData.map(function (d) { return d.date; });
        const values = dailyRevenueData.map(function (d) { return d.revenue; });
        const maxVal = Math.max.apply(null, values.concat([1]));

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Omzet Penjualan',
                    data: values,
                    backgroundColor: values.map(function (v) {
                        return v === maxVal && v > 0 ? '#004e35' : '#006c49';
                    }),
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 34,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: animOpts,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: Object.assign({}, tooltipConfig, {
                        displayColors: false,
                        callbacks: {
                            label: function (c) { return 'Rp ' + Number(c.parsed.y).toLocaleString('id-ID'); },
                        },
                    }),
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { maxTicksLimit: 12, color: labelColor },
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: gridLineColor },
                        ticks: {
                            padding: 6,
                            color: labelColor,
                            callback: function (v) {
                                if (v >= 1000000) return (v / 1000000).toFixed(1).replace('.0', '') + ' jt';
                                if (v >= 1000)    return (v / 1000).toFixed(0) + ' rb';
                                return v;
                            },
                        },
                    },
                },
            },
        });
    })();

    // ── 3. Top Products Donut Chart ─────────────────────────────────────────
    (function () {
        const canvas = document.getElementById('topProductsChart');
        if (!canvas || !topProductsData || topProductsData.length === 0) return;

        const labels = topProductsData.map(function (p) { return p.product ? p.product.name : 'Deleted Product'; });
        const values = topProductsData.map(function (p) { return Number(p.total_sold); });
        const total  = values.reduce(function (a, b) { return a + b; }, 0);

        const centerTextPlugin = {
            id: 'centerTextPlugin',
            afterDraw: function (chart) {
                const area = chart.chartArea, ctx = chart.ctx;
                const x = (area.left + area.right) / 2, y = (area.top + area.bottom) / 2;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = '#0b1c30';
                ctx.font = '800 20px Inter, sans-serif';
                ctx.fillText(total.toLocaleString('id-ID'), x, y - 6);
                ctx.fillStyle = '#45464d';
                ctx.font = '600 11px Inter, sans-serif';
                ctx.fillText('unit terjual', x, y + 14);
                ctx.restore();
            },
        };

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: palette.slice(0, labels.length),
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    borderRadius: 4,
                    spacing: 2,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                animation: animOpts,
                plugins: {
                    legend: { display: false },
                    tooltip: Object.assign({}, tooltipConfig, {
                        callbacks: {
                            label: function (c) { return ' ' + c.label + ': ' + Number(c.parsed).toLocaleString('id-ID') + ' unit'; },
                        },
                    }),
                },
            },
            plugins: [centerTextPlugin],
        });
    })();
</script>
@endpush
@endsection
