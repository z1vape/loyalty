@extends('layouts.app')

@section('title', 'Z1 VapeOS | Reports & Analytics')

@section('content')
<div class="space-y-6">

    {{-- Header Banner --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container">
                    <span class="material-symbols-outlined text-sm">analytics</span>
                    Pusat Analitik
                </span>
                <span class="text-xs text-on-surface-variant font-medium">Penjualan & Keuangan</span>
            </div>
            <h1 class="font-headline-lg text-2xl sm:text-3xl font-extrabold text-on-surface">Katalog Laporan</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">
                Pusat data pelaporan operasional retail, pergerakan persediaan barang, serta laporan keuangan akuntansi.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('reports.store-performance') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-extrabold bg-[#006c49] text-white hover:bg-[#005a3c] transition-all shadow-sm hover:shadow no-underline">
                <span class="material-symbols-outlined text-[18px]">query_stats</span>
                <span>Buka Dashboard BI Toko</span>
            </a>
        </div>
    </div>

    {{-- Featured Executive BI Card --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-emerald-950 via-slate-900 to-[#004d34] rounded-3xl p-6 sm:p-7 text-white shadow-md border border-emerald-800/40">
        <div class="absolute -right-8 -bottom-8 w-60 h-60 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-extrabold tracking-wide uppercase border border-emerald-400/30">
                    <span class="material-symbols-outlined text-sm animate-pulse">insights</span>
                    Executive Business Intelligence
                </div>
                <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                    Dashboard BI: Performa Seluruh Cabang Toko
                </h2>
                <p class="text-xs sm:text-sm text-emerald-100/80 leading-relaxed">
                    Lihat komparasi real-time omzet antar toko, margin laba bersih, kontribusi cabang, komposisi pembayaran tunai vs digital, serta valuasi aset persediaan barang secara komprehensif.
                </p>
                <div class="flex flex-wrap items-center gap-4 pt-1 text-xs text-emerald-200/90 font-medium">
                    <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-sm text-emerald-400">check_circle</span> Multi-Store Trajectory</span>
                    <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-sm text-emerald-400">check_circle</span> Profitability Leaderboard</span>
                    <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-sm text-emerald-400">check_circle</span> Export Excel Multi-Metrik</span>
                </div>
            </div>
            <div class="shrink-0 flex items-center gap-3">
                <a href="{{ route('reports.store-performance') }}" class="inline-flex items-center justify-center gap-2.5 px-5 py-3.5 rounded-2xl bg-white text-emerald-950 font-black text-xs sm:text-sm hover:bg-emerald-50 transition-all shadow-lg active:scale-95 no-underline">
                    <span class="material-symbols-outlined text-lg text-[#006c49]">monitoring</span>
                    <span>Analisis Performa Cabang</span>
                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Section 1: Laporan Penjualan & Stok --}}
    <div>
        <div class="flex items-center gap-2 mb-3 px-1">
            <span class="material-symbols-outlined text-primary text-[20px]">shopping_cart</span>
            <h2 class="text-sm font-bold text-on-surface uppercase tracking-wider">Laporan Penjualan & Stok Barang</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- 0. BI Performa Toko --}}
            <a href="{{ route('reports.store-performance') }}" class="group bg-gradient-to-b from-emerald-50/50 to-surface-container-lowest border-2 border-[#006c49]/40 hover:border-[#006c49] hover:shadow-lg transition-all rounded-2xl p-5 flex flex-col justify-between no-underline relative overflow-hidden">
                <span class="absolute top-3 right-3 text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-[#006c49] text-white tracking-wider">
                    Executive BI
                </span>
                <div>
                    <div class="w-12 h-12 rounded-xl bg-[#006c49] text-white flex items-center justify-center mb-4 shadow-sm group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-2xl">monitoring</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-primary transition-colors">Performa Seluruh Toko</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Dashboard BI komparasi omzet cabang, laba bersih, kontribusi penjualan, basket size (AOV), dan aset inventori.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-black text-primary">
                    <span>Buka Dashboard BI</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1.5 transition-transform">arrow_forward</span>
                </div>
            </a>

            {{-- 1. Penjualan per SKU --}}
            @if(auth()->user()->can('Report SKU') || auth()->user()->can('Report List'))
            <a href="{{ route('reports.by-sku') }}" class="group bg-surface-container-lowest border border-outline-variant/80 hover:border-primary/50 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-[#006c49] group-hover:bg-[#006c49] group-hover:text-white transition-colors flex items-center justify-center mb-4 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">qr_code_2</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-primary transition-colors">Penjualan per SKU</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Analisis data penjualan per produk, total kuantiti terjual, omset kotor, modal HPP, dan laba kotor per SKU.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-primary">
                    <span>Lihat Laporan</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>
            @endif

            {{-- 2. Penjualan per Kategori --}}
            @if(auth()->user()->can('Report Category') || auth()->user()->can('Report List'))
            <a href="{{ route('reports.by-category') }}" class="group bg-surface-container-lowest border border-outline-variant/80 hover:border-primary/50 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors flex items-center justify-center mb-4 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">donut_large</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-blue-700 transition-colors">Penjualan per Kategori</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Performa penjualan berdasarkan kelompok/kategori barang untuk melihat kontribusi kategori terhadap total revenue.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-blue-600">
                    <span>Lihat Laporan</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>
            @endif

            {{-- 3. Penjualan per Staff --}}
            @if(auth()->user()->can('Report Staff') || auth()->user()->can('Report List'))
            <a href="{{ route('reports.by-staff') }}" class="group bg-surface-container-lowest border border-outline-variant/80 hover:border-primary/50 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors flex items-center justify-center mb-4 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">badge</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-purple-700 transition-colors">Penjualan per Staff</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Rekapitulasi pencapaian transaksi dan volume penjualan yang dihasilkan oleh masing-masing staff kasir toko.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-purple-600">
                    <span>Lihat Laporan</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>
            @endif

            {{-- 4. Rekap 10 Supplier Terbesar --}}
            @if(auth()->user()->can('Report SKU') || auth()->user()->can('Report List'))
            <a href="{{ route('reports.top-suppliers') }}" class="group bg-surface-container-lowest border border-outline-variant/80 hover:border-primary/50 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-700 group-hover:bg-teal-700 group-hover:text-white transition-colors flex items-center justify-center mb-4 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">local_shipping</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-teal-800 transition-colors">10 Supplier Terbesar</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Rekapitulasi 10 pemasok/supplier dengan akumulasi biaya belanja stok tertinggi secara bulanan maupun tahunan.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-teal-700">
                    <span>Lihat Laporan</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>
            @endif

            {{-- 4. Stok Kritis (Low Stock) --}}
            @can('Inventory List')
            <a href="{{ route('reports.low-stock') }}" class="group bg-surface-container-lowest border border-outline-variant/80 hover:border-amber-400/50 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition-colors flex items-center justify-center mb-4 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">warning</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-amber-700 transition-colors">Stok Kritis (Low Stock)</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Daftar barang dengan stok di bawah batas minimum (min. stock) atau habis untuk percepatan pengadaan restock.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-amber-600">
                    <span>Lihat Laporan</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>
            @endcan
        </div>
    </div>

    {{-- Section 2: Laporan Keuangan (Accounting) --}}
    @can('Accounting Report View')
    <div>
        <div class="flex items-center gap-2 mb-3 px-1 pt-2">
            <span class="material-symbols-outlined text-primary text-[20px]">account_balance</span>
            <h2 class="text-sm font-bold text-on-surface uppercase tracking-wider">Laporan Keuangan & Akuntansi</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- 5. Laba Rugi --}}
            <a href="{{ route('accounting.reports.profit-loss') }}" class="group bg-surface-container-lowest border border-outline-variant/80 hover:border-primary/50 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-colors flex items-center justify-center mb-4 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">trending_up</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-teal-700 transition-colors">Laba Rugi (P&L)</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Laporan pendapatan usaha, beban pokok penjualan, pengeluaran operasional, serta hasil laba/rugi bersih.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-teal-600">
                    <span>Lihat Laporan</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>

            {{-- 6. Neraca Keuangan --}}
            <a href="{{ route('accounting.reports.balance-sheet') }}" class="group bg-surface-container-lowest border border-outline-variant/80 hover:border-primary/50 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors flex items-center justify-center mb-4 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-indigo-700 transition-colors">Neraca Keuangan</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Posisi kesehatan finansial perusahaan yang mencakup aset/aktiva, kewajiban/hutang, dan ekuitas modal.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-indigo-600">
                    <span>Lihat Laporan</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>

            {{-- 7. Neraca Saldo --}}
            <a href="{{ route('accounting.reports.trial-balance') }}" class="group bg-surface-container-lowest border border-outline-variant/80 hover:border-primary/50 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-600 group-hover:bg-cyan-600 group-hover:text-white transition-colors flex items-center justify-center mb-4 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">balance</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-cyan-700 transition-colors">Neraca Saldo</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Kompilasi saldo akhir debit dan kredit dari seluruh bagan akun (CoA) untuk memastikan keseimbangan jurnal.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-cyan-600">
                    <span>Lihat Laporan</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>

            {{-- 8. Arus Kas --}}
            <a href="{{ route('accounting.reports.cash-flow') }}" class="group bg-surface-container-lowest border border-outline-variant/80 hover:border-primary/50 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-[#006c49] group-hover:bg-[#006c49] group-hover:text-white transition-colors flex items-center justify-center mb-4 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">payments</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-primary transition-colors">Arus Kas (Cash Flow)</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Rekapitulasi keluar masuk kas tunai dan saldo bank dari transaksi operasional, beban, dan mutasi dana.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-primary">
                    <span>Lihat Laporan</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>
        </div>
    </div>
    @endcan

</div>
@endsection

