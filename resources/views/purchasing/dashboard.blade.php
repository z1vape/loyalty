@extends('layouts.app')

@section('title', 'Z1 VapeOS | Dashboard Purchasing')

@section('content')
<div class="space-y-6">

    {{-- Header & Filter Bar --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container">
                    <span class="material-symbols-outlined text-sm">shopping_bag</span>
                    Modul Purchasing
                </span>
                <span class="text-xs text-on-surface-variant font-medium">Pengadaan & Hutang Supplier</span>
            </div>
            <h1 class="font-headline-lg text-2xl sm:text-3xl font-extrabold text-on-surface">Dashboard Purchasing</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant">Monitoring volume belanja stok, saldo hutang dagang, dan jadwal pembayaran supplier.</p>
        </div>

        {{-- Period Filter Pills --}}
        <div class="flex flex-wrap items-center gap-1.5 bg-surface-container-low p-1.5 rounded-2xl border border-outline-variant/60 shadow-2xs">
            <a href="{{ route('purchasing.dashboard', ['filter' => 'today']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'today' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                Hari Ini
            </a>
            <a href="{{ route('purchasing.dashboard', ['filter' => 'this_week']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'this_week' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                Minggu Ini
            </a>
            <a href="{{ route('purchasing.dashboard', ['filter' => 'mtd']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'mtd' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                Bulan Ini
            </a>
            <a href="{{ route('purchasing.dashboard', ['filter' => 'last_month']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'last_month' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                Bulan Lalu
            </a>
            <a href="{{ route('purchasing.dashboard', ['filter' => 'ytd']) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all no-underline {{ $filter === 'ytd' ? 'bg-[#006c49] text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high' }}">
                YTD
            </a>
        </div>
    </div>

    {{-- KPI Cards Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Belanja Stok --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <span class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">shopping_cart</span>
                </span>
                <span class="text-[10px] uppercase font-bold text-on-surface-variant px-2 py-0.5 rounded-full bg-surface-container">
                    {{ $filterLabel }}
                </span>
            </div>
            <div class="mt-4">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Belanja Stok</p>
                <h3 class="text-xl sm:text-2xl font-black text-on-surface font-mono mt-0.5">
                    Rp {{ number_format($totalPurchases, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-on-surface-variant mt-1">
                    <span class="font-bold text-[#006c49]">{{ $totalInvoicesCount }}</span> faktur &bull; <span class="font-bold text-[#006c49]">{{ number_format($totalPurchasedQty, 0, ',', '.') }}</span> unit
                </p>
            </div>
        </div>

        {{-- Card 2: Sisa Hutang Supplier (Total Outstanding) --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <span class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">pending_actions</span>
                </span>
                <span class="text-[10px] uppercase font-bold text-amber-800 px-2 py-0.5 rounded-full bg-amber-500/10">
                    Real-Time
                </span>
            </div>
            <div class="mt-4">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Sisa Hutang Supplier</p>
                <h3 class="text-xl sm:text-2xl font-black text-amber-600 font-mono mt-0.5">
                    Rp {{ number_format($totalRemainingDebt, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-on-surface-variant mt-1">Total kewajiban tempo belum lunas</p>
            </div>
        </div>

        {{-- Card 3: Hutang Lewat Jatuh Tempo (Overdue) --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <span class="w-10 h-10 rounded-xl {{ $overdueCount > 0 ? 'bg-rose-500/10 text-rose-700' : 'bg-surface-container text-on-surface-variant' }} flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">{{ $overdueCount > 0 ? 'warning' : 'check_circle' }}</span>
                </span>
                @if($overdueCount > 0)
                    <span class="text-[10px] uppercase font-bold text-rose-800 px-2 py-0.5 rounded-full bg-rose-500/10">
                        {{ $overdueCount }} Tagihan Overdue
                    </span>
                @else
                    <span class="text-[10px] uppercase font-bold text-emerald-800 px-2 py-0.5 rounded-full bg-emerald-500/10">
                        Aman
                    </span>
                @endif
            </div>
            <div class="mt-4">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Hutang Lewat Tempo</p>
                <h3 class="text-xl sm:text-2xl font-black {{ $overdueCount > 0 ? 'text-rose-600' : 'text-on-surface' }} font-mono mt-0.5">
                    Rp {{ number_format($overdueDebt, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-on-surface-variant mt-1">Tagihan melewati tanggal jatuh tempo</p>
            </div>
        </div>

        {{-- Card 4: Jatuh Tempo 7 Hari Mendatang --}}
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3">
                <span class="w-10 h-10 rounded-xl bg-sky-500/10 text-sky-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">event_upcoming</span>
                </span>
                <span class="text-[10px] uppercase font-bold text-sky-800 px-2 py-0.5 rounded-full bg-sky-500/10">
                    7 Hari Mendatang
                </span>
            </div>
            <div class="mt-4">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Jatuh Tempo Dekat</p>
                <h3 class="text-xl sm:text-2xl font-black text-sky-700 font-mono mt-0.5">
                    Rp {{ number_format($dueSoonDebt, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-on-surface-variant mt-1">{{ $dueSoonCount }} faktur perlu disiapkan dananya</p>
            </div>
        </div>
    </div>

    {{-- Main Analytics Layout: 2 Columns (8 / 4) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Left Column: Urgent Payables & Top Suppliers (8 cols) --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- Urgent Payables Table --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-5 border-b border-outline-variant/60 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-700 flex items-center justify-center">
                            <span class="material-symbols-outlined text-base">priority_high</span>
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-on-surface">Daftar Tagihan Supplier Mendesak</h3>
                            <p class="text-[11px] text-on-surface-variant">Faktur tempo yang sudah jatuh tempo atau akan jatuh tempo terdekat</p>
                        </div>
                    </div>
                    <a href="{{ route('accounting.payables.index') }}" class="text-xs font-bold text-[#006c49] hover:underline flex items-center gap-1">
                        Buku Hutang Lengkap
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-surface-container-low text-on-surface-variant font-bold uppercase tracking-wider text-[10px] border-b border-outline-variant/60">
                                <th class="px-5 py-3">No. Faktur</th>
                                <th class="px-5 py-3">Supplier</th>
                                <th class="px-5 py-3">Jatuh Tempo</th>
                                <th class="px-5 py-3 text-right">Sisa Hutang</th>
                                <th class="px-5 py-3 text-center">Status</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            @forelse($urgentPayables as $bill)
                                @php
                                    $isOverdue = $bill->due_date && \Carbon\Carbon::parse($bill->due_date)->lt(\Carbon\Carbon::today());
                                @endphp
                                <tr class="hover:bg-surface-container-low/50 transition-colors">
                                    <td class="px-5 py-3 font-mono font-bold text-on-surface">
                                        {{ $bill->reference_number ?: "IN-{$bill->id}" }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="font-bold text-on-surface">{{ $bill->supplier_name ?: 'Supplier Umum' }}</div>
                                        <div class="text-[10px] text-on-surface-variant truncate max-w-[140px]">{{ $bill->product?->name ?? 'Stok Barang' }}</div>
                                    </td>
                                    <td class="px-5 py-3 whitespace-nowrap">
                                        @if($bill->due_date)
                                            <span class="font-semibold {{ $isOverdue ? 'text-rose-600 font-bold' : 'text-on-surface' }}">
                                                {{ \Carbon\Carbon::parse($bill->due_date)->format('d M Y') }}
                                            </span>
                                            @if($isOverdue)
                                                <span class="block text-[9px] font-bold text-rose-600 uppercase">Terlambat</span>
                                            @endif
                                        @else
                                            <span class="text-on-surface-variant">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono font-bold text-on-surface whitespace-nowrap">
                                        Rp {{ number_format($bill->remaining_debt, 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $bill->payment_status_badge }}">
                                            {{ match($bill->payment_status) {
                                                'paid' => 'Lunas',
                                                'partial' => 'Dicicil',
                                                'pending' => 'Belum Bayar',
                                                default => ucfirst($bill->payment_status)
                                            } }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('accounting.payables.pay', $bill) }}" 
                                           class="inline-flex items-center gap-1 px-3 py-1 bg-[#006c49] text-white font-bold rounded-lg text-[11px] hover:opacity-90 transition no-underline">
                                            <span>Bayar</span>
                                            <span class="material-symbols-outlined text-xs">payments</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-on-surface-variant">
                                        <span class="material-symbols-outlined text-3xl text-emerald-600 block mb-1">verified</span>
                                        <span class="font-bold text-sm">Semua Hutang Supplier Aman</span>
                                        <p class="text-xs mt-0.5">Tidak ada tagihan supplier yang mendesak atau menunggak saat ini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Top Suppliers by Volume --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs p-5">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-outline-variant/60">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-secondary-container/60 text-secondary flex items-center justify-center">
                            <span class="material-symbols-outlined text-base">partner_exchange</span>
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-on-surface">Top 5 Supplier Pengadaan</h3>
                            <p class="text-[11px] text-on-surface-variant">Berdasarkan total nominal belanja pada periode terpilih</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    @forelse($topSuppliers as $sup)
                        @php
                            $percentage = $totalPurchases > 0 ? ($sup->total_spent / $totalPurchases) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                <span class="font-bold text-on-surface flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-[#006c49]"></span>
                                    {{ $sup->supplier }}
                                    <span class="text-[10px] text-on-surface-variant font-normal">({{ $sup->total_orders }} order / {{ number_format($sup->total_qty) }} unit)</span>
                                </span>
                                <span class="font-mono font-bold text-on-surface">
                                    Rp {{ number_format($sup->total_spent, 0, ',', '.') }}
                                    <span class="text-[10px] text-on-surface-variant font-normal">({{ round($percentage, 1) }}%)</span>
                                </span>
                            </div>
                            <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden">
                                <div class="bg-[#006c49] h-2 rounded-full transition-all duration-500" style="width: {{ min(100, max(2, $percentage)) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-on-surface-variant text-center py-4">Belum ada data pengadaan pada periode ini.</p>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Right Column: Status Breakdown & Recent Movements (4 cols) --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- Payment Status Breakdown Card --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs p-5">
                <h3 class="text-sm font-extrabold text-on-surface mb-1 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#006c49]">pie_chart</span>
                    Status Pelunasan Faktur
                </h3>
                <p class="text-[11px] text-on-surface-variant mb-4">Proporsi pelunasan tagihan periode {{ $filterLabel }}</p>

                <div class="grid grid-cols-3 gap-2 text-center pt-2">
                    <div class="p-3 bg-emerald-500/10 rounded-xl">
                        <p class="text-[10px] font-bold text-emerald-800 uppercase">Lunas</p>
                        <h4 class="text-xl font-black text-emerald-700 mt-1">{{ $paidCount }}</h4>
                        <span class="text-[9px] text-emerald-800">Faktur</span>
                    </div>
                    <div class="p-3 bg-amber-500/10 rounded-xl">
                        <p class="text-[10px] font-bold text-amber-800 uppercase">Dicicil</p>
                        <h4 class="text-xl font-black text-amber-700 mt-1">{{ $partialCount }}</h4>
                        <span class="text-[9px] text-amber-800">Faktur</span>
                    </div>
                    <div class="p-3 bg-rose-500/10 rounded-xl">
                        <p class="text-[10px] font-bold text-rose-800 uppercase">Belum</p>
                        <h4 class="text-xl font-black text-rose-700 mt-1">{{ $unpaidCount }}</h4>
                        <span class="text-[9px] text-rose-800">Faktur</span>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-outline-variant/60 flex flex-col gap-2">
                    <a href="{{ route('movements.index', ['type' => 'in']) }}" 
                       class="w-full py-2 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-bold text-xs rounded-xl text-center no-underline transition">
                        + Input Stok Masuk Baru
                    </a>
                </div>
            </div>

            {{-- Recent Restock Feed --}}
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs p-5">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-outline-variant/60">
                    <h3 class="text-sm font-extrabold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#006c49]">history</span>
                        Aktivitas Restock Terbaru
                    </h3>
                </div>

                <div class="space-y-3">
                    @forelse($recentMovements as $mv)
                        <div class="flex items-start justify-between gap-2 p-2.5 rounded-xl bg-surface-container-low/60 text-xs">
                            <div class="min-w-0">
                                <p class="font-bold text-on-surface truncate">{{ $mv->product?->name ?? 'Produk' }}</p>
                                <div class="text-[10px] text-on-surface-variant flex items-center gap-1.5 mt-0.5">
                                    <span class="font-bold text-[#006c49]">+{{ $mv->quantity }} unit</span>
                                    <span>&bull;</span>
                                    <span class="truncate">{{ $mv->supplier_name ?: 'Umum' }}</span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="font-mono font-bold text-on-surface">
                                    Rp {{ number_format($mv->total_price ?: ($mv->quantity * ($mv->purchase_price ?? 0)), 0, ',', '.') }}
                                </span>
                                <span class="block text-[9px] text-on-surface-variant">
                                    {{ \Carbon\Carbon::parse($mv->movement_date ?: $mv->created_at)->format('d/m/y') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-on-surface-variant text-center py-4">Belum ada aktivitas stok masuk terbaru.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
