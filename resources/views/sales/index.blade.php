@extends('layouts.app')

@section('title', 'Z1 VapeOS | Sales History')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Header & Action Button --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">POS Sales History</h1>
            <p class="text-on-surface-variant text-body-lg">
                Riwayat dan log transaksi penjualan kasir (Point of Sale) toko aktif.
            </p>
        </div>
    </div>

    {{-- Metric Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Card 1: Total Transaksi --}}
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-surface-container-high text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">receipt</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Struk</p>
                <h3 class="text-xl font-headline-md font-extrabold text-on-surface mt-0.5 font-mono truncate">
                    {{ number_format($totalTransactions) }}
                </h3>
            </div>
        </div>

        {{-- Card 2: Total Omzet Penjualan --}}
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">trending_up</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Omzet</p>
                <h3 class="text-xl font-headline-md font-extrabold text-emerald-800 mt-0.5 font-mono truncate">
                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </h3>
            </div>
        </div>

        {{-- Card 3: Total Barang Terjual --}}
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">shopping_bag</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Barang Terjual</p>
                <h3 class="text-xl font-headline-md font-extrabold text-on-surface mt-0.5 font-mono truncate">
                    {{ number_format($totalItemsSold) }} <span class="text-xs font-normal text-on-surface-variant">Unit</span>
                </h3>
            </div>
        </div>

        {{-- Card 4: Total Diskon --}}
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">sell</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Diskon</p>
                <h3 class="text-xl font-headline-md font-extrabold text-rose-600 mt-0.5 font-mono truncate">
                    Rp {{ number_format($totalDiscount, 0, ',', '.') }}
                </h3>
            </div>
        </div>
    </div>

    {{-- Rincian Akumulasi Metode Pembayaran --}}
    @if(!empty($paymentBreakdown))
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3 pb-2.5 border-b border-outline-variant/60">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-base">account_balance_wallet</span>
                </div>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-on-surface flex items-center gap-2">
                        <span>Rincian Kalkulasi Metode Pembayaran</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-primary/10 text-primary">Real-Time Filter</span>
                    </h2>
                </div>
            </div>
            <div class="inline-flex items-center gap-2 text-xs">
                <span class="text-on-surface-variant font-medium">Total Akumulasi:</span>
                <span class="text-primary font-mono font-extrabold text-sm">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
            @php
                $colorStyles = [
                    'emerald' => ['active' => 'border-emerald-300 bg-emerald-50/70', 'icon' => 'bg-emerald-100 text-emerald-800', 'val' => 'text-emerald-800'],
                    'blue'    => ['active' => 'border-blue-300 bg-blue-50/70', 'icon' => 'bg-blue-100 text-blue-800', 'val' => 'text-blue-800'],
                    'purple'  => ['active' => 'border-purple-300 bg-purple-50/70', 'icon' => 'bg-purple-100 text-purple-800', 'val' => 'text-purple-800'],
                    'cyan'    => ['active' => 'border-cyan-300 bg-cyan-50/70', 'icon' => 'bg-cyan-100 text-cyan-800', 'val' => 'text-cyan-800'],
                    'amber'   => ['active' => 'border-amber-300 bg-amber-50/70', 'icon' => 'bg-amber-100 text-amber-800', 'val' => 'text-amber-800'],
                    'gray'    => ['active' => 'border-gray-300 bg-gray-50/70', 'icon' => 'bg-gray-100 text-gray-800', 'val' => 'text-gray-800'],
                ];
            @endphp
            @foreach($paymentBreakdown as $key => $info)
            @php
                $hasAmt = ($info['amount'] ?? 0) > 0;
                $cStyle = $colorStyles[$info['color'] ?? 'gray'] ?? $colorStyles['gray'];
            @endphp
            <div class="rounded-xl border p-2.5 flex flex-col justify-between transition-all {{ $hasAmt ? $cStyle['active'] . ' shadow-xs' : 'border-outline-variant/50 bg-surface-container-low/30 opacity-60' }}">
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant truncate">{{ $info['label'] }}</span>
                    <span class="w-5 h-5 rounded-md flex items-center justify-center text-xs {{ $hasAmt ? $cStyle['icon'] : 'bg-surface-container text-on-surface-variant' }}">
                        <span class="material-symbols-outlined text-[13px]">{{ $info['icon'] }}</span>
                    </span>
                </div>
                <div>
                    <p class="text-xs font-mono font-extrabold truncate {{ $hasAmt ? $cStyle['val'] : 'text-on-surface-variant' }}">
                        Rp {{ number_format($info['amount'] ?? 0, 0, ',', '.') }}
                    </p>
                    <p class="text-[10px] text-on-surface-variant font-medium mt-0.5">
                        {{ number_format($info['count'] ?? 0) }} Struk
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filter Panel --}}
    <form method="GET" action="{{ route('sales.index') }}"
          class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 flex flex-col gap-3 shadow-xs">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            {{-- Search Box --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Cari Struk / Pelanggan</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-2 text-on-surface-variant text-base">search</span>
                    <input type="text" name="search" value="{{ $search ?? '' }}"
                           placeholder="Contoh: POS-2609... / Budi"
                           class="w-full border border-outline-variant bg-surface-container-low rounded-xl pl-9 pr-3 py-2 text-xs text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary transition-all font-medium">
                </div>
            </div>

            {{-- Tanggal Mulai --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ $startDate ?? '' }}"
                       class="w-full border border-outline-variant bg-surface-container-low rounded-xl px-3 py-2 text-xs text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary transition-all font-medium">
            </div>

            {{-- Tanggal Akhir --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Tanggal Akhir</label>
                <input type="date" name="end_date" value="{{ $endDate ?? '' }}"
                       class="w-full border border-outline-variant bg-surface-container-low rounded-xl px-3 py-2 text-xs text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary transition-all font-medium">
            </div>

            {{-- Kasir Dropdown --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Kasir</label>
                <select name="user_id" id="sales_user_id" class="w-full">
                    <option value="all">Semua Kasir</option>
                    @foreach($cashiers as $c)
                        <option value="{{ $c->id }}" {{ ($userId ?? '') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Metode Bayar Dropdown --}}
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Metode Bayar</label>
                <select name="payment_method" id="sales_payment_method" class="w-full">
                    <option value="all">Semua Metode</option>
                    <option value="cash" {{ ($paymentMethod ?? '') == 'cash' ? 'selected' : '' }}>Tunai (Cash)</option>
                    <option value="split" {{ ($paymentMethod ?? '') == 'split' ? 'selected' : '' }}>Split Payment</option>
                    <option value="transfer" {{ ($paymentMethod ?? '') == 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                    <option value="qris" {{ ($paymentMethod ?? '') == 'qris' ? 'selected' : '' }}>QRIS</option>
                    <option value="debit" {{ ($paymentMethod ?? '') == 'debit' ? 'selected' : '' }}>Kartu Debit</option>
                    <option value="credit" {{ ($paymentMethod ?? '') == 'credit' ? 'selected' : '' }}>Kartu Kredit</option>
                    <option value="other" {{ ($paymentMethod ?? '') == 'other' ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-outline-variant/60 pt-3 mt-1">
            <span class="text-xs text-on-surface-variant font-medium">
                Menampilkan <strong class="text-on-surface">{{ $sales->total() }}</strong> transaksi ditemukan
            </span>

            <div class="flex items-center gap-2">
                @if(!empty($search) || !empty($startDate) || !empty($endDate) || (!empty($userId) && $userId !== 'all') || (!empty($paymentMethod) && $paymentMethod !== 'all'))
                <a href="{{ route('sales.index') }}"
                   class="inline-flex items-center justify-center gap-1 px-3.5 py-2 rounded-xl bg-surface-container-low border border-outline-variant hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-all no-underline h-[38px]">
                    <span class="material-symbols-outlined text-sm">restart_alt</span>
                    <span>Reset</span>
                </a>
                @endif
                <button type="submit"
                        class="inline-flex items-center justify-center gap-1.5 bg-secondary text-on-secondary hover:opacity-90 px-4 py-2 rounded-xl text-xs font-bold active:scale-95 transition-all shadow-xs cursor-pointer border-0 h-[38px]">
                    <span class="material-symbols-outlined text-sm">filter_alt</span>
                    <span>Filter</span>
                </button>
            </div>
        </div>
    </form>

    {{-- Transactions Table Card --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-xs">
        {{-- Bulk Action Bar --}}
        @canany(['Sales Destroy', 'Movement Destroy'])
        <div id="salesBulkActionBar" class="hidden px-4 py-3 bg-emerald-500/10 border-b border-emerald-500/30 flex flex-wrap items-center justify-between gap-3 transition-all">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-primary/15 text-primary">
                    <span class="material-symbols-outlined text-sm">check_box</span>
                </span>
                <span id="salesSelectedCountBadge" class="text-xs font-bold text-primary">0 transaksi dipilih</span>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" id="btnCancelSalesSelection" class="px-3 py-1.5 rounded-xl border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-high text-xs font-semibold text-on-surface-variant transition active:scale-95 shadow-xs cursor-pointer">
                    Batal
                </button>
                <button type="button" id="btnDeleteSelectedSales" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-base">delete</span>
                    <span id="btnDeleteSelectedSalesText">Hapus Terpilih</span>
                </button>
            </div>
        </div>
        @endcanany

        @if($sales->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant uppercase tracking-wider">
                    <tr>
                        @canany(['Sales Destroy', 'Movement Destroy'])
                        <th class="px-4 py-3.5 w-10 text-center">
                            <input 
                                type="checkbox" 
                                id="selectAllSales" 
                                class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary/30 cursor-pointer accent-primary"
                                title="Pilih semua di halaman ini"
                            />
                        </th>
                        @endcanany
                        <th class="px-4 py-3.5 font-bold">No. Struk</th>
                        <th class="px-4 py-3.5 font-bold">Waktu & Tanggal</th>
                        <th class="px-4 py-3.5 font-bold">Kasir & Cabang</th>
                        <th class="px-4 py-3.5 font-bold">Pelanggan</th>
                        <th class="px-4 py-3.5 font-bold">Metode Bayar</th>
                        <th class="px-4 py-3.5 font-bold text-center">Barang</th>
                        <th class="px-4 py-3.5 font-bold text-right">Total Transaksi</th>
                        <th class="px-4 py-3.5 font-bold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @foreach($sales as $sale)
                    @php
                        // Payment method parsing
                        $payMethodKey = strtolower($sale->payment_method ?? 'cash');
                        $payMethod = strtoupper($payMethodKey);
                        $badgeClass = $sale->payment_badge_class;

                        $saleQty = $sale->items->sum('quantity');
                        $saleDisc = $sale->items->sum('discount');
                        $isCancelled = ($sale->status ?? 'completed') === 'cancelled';
                    @endphp
                    <tr id="sale-row-{{ $sale->id }}" class="hover:bg-surface-container-low/50 transition-colors {{ $isCancelled ? 'bg-rose-50/20' : '' }}">
                        {{-- Row Checkbox --}}
                        @canany(['Sales Destroy', 'Movement Destroy'])
                        <td class="px-4 py-3 text-center">
                            <input 
                                type="checkbox" 
                                name="sale_ids[]" 
                                value="{{ $sale->id }}" 
                                class="sale-checkbox w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary/30 cursor-pointer accent-primary"
                            />
                        </td>
                        @endcanany

                        {{-- No. Struk --}}
                        <td class="px-4 py-3 font-mono text-xs">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <a href="{{ route('sales.show', $sale->id) }}" class="font-extrabold text-primary hover:underline flex items-center gap-1.5 {{ $isCancelled ? 'line-through text-on-surface-variant/60' : '' }}">
                                    <span class="material-symbols-outlined text-sm text-primary">receipt</span>
                                    #{{ $sale->reference_number }}
                                </a>
                                @if($isCancelled)
                                    <span class="inline-flex items-center gap-0.5 bg-rose-100 text-rose-800 border border-rose-300 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase" title="{{ $sale->cancellation_reason ?: 'Dibatalkan oleh Kasir' }}">
                                        <span class="material-symbols-outlined text-[10px]">cancel</span> Dibatalkan
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- Waktu & Tanggal --}}
                        <td class="px-4 py-3">
                            <div class="font-bold text-on-surface text-xs">
                                {{ ($sale->sale_date ?? $sale->created_at)->format('d M Y') }}
                            </div>
                            <div class="text-[10px] text-on-surface-variant font-mono mt-0.5">
                                {{ $sale->created_at ? $sale->created_at->format('H:i') . ' WIB' : '-' }}
                            </div>
                        </td>

                        {{-- Kasir & Toko --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 group/cashier">
                                <span id="cashier-initial-{{ $sale->id }}" class="w-6 h-6 rounded-md bg-primary-container text-on-primary-container text-[10px] font-black flex items-center justify-center uppercase shrink-0">
                                    {{ substr($sale->user?->name ?? 'S', 0, 1) }}
                                </span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span id="cashier-name-{{ $sale->id }}" class="font-bold text-on-surface text-xs truncate max-w-[120px]">
                                            {{ $sale->user?->name ?? 'Kasir Staff' }}
                                        </span>
                                        <button type="button" 
                                                id="cashier-edit-link-{{ $sale->id }}"
                                                onclick="openChangeCashierModal('{{ $sale->id }}', '{{ $sale->reference_number }}', '{{ $sale->user_id }}', '{{ addslashes($sale->user?->name ?? 'Kasir Staff') }}')"
                                                class="opacity-60 hover:opacity-100 text-on-surface-variant hover:text-primary transition-all p-0.5 rounded hover:bg-surface-container cursor-pointer"
                                                title="Ubah Kasir Transaksi Ini">
                                            <span class="material-symbols-outlined text-[13px]">edit</span>
                                        </button>
                                    </div>
                                    <div class="text-[10px] text-on-surface-variant truncate">
                                        {{ $sale->store?->name ?? 'Toko Pusat' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Pelanggan --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1 text-xs font-medium text-on-surface">
                                <span class="material-symbols-outlined text-xs text-on-surface-variant">person</span>
                                <span class="truncate max-w-[110px]">{{ $sale->customer_name ?: 'Pelanggan Umum' }}</span>
                            </div>
                        </td>

                        {{-- Metode Bayar --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5 group/pay">
                                <span id="pay-badge-{{ $sale->id }}" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold border {{ $badgeClass }}">
                                    {{ $payMethod }}
                                </span>
                                @canany(['Sales Edit', 'Sales List'])
                                <button type="button" 
                                        id="pay-edit-btn-{{ $sale->id }}"
                                        onclick="openChangePaymentMethodModal('{{ $sale->id }}', '{{ $sale->reference_number }}', '{{ $payMethodKey }}')"
                                        class="opacity-60 hover:opacity-100 text-on-surface-variant hover:text-emerald-700 transition-all p-0.5 rounded hover:bg-surface-container cursor-pointer"
                                        title="Ubah Metode Pembayaran">
                                    <span class="material-symbols-outlined text-[13px]">edit</span>
                                </button>
                                @endcanany
                            </div>
                        </td>

                        {{-- Jumlah Barang --}}
                        <td class="px-4 py-3 text-center">
                            <span class="font-mono font-bold text-on-surface text-xs">
                                {{ $saleQty }}
                            </span>
                            <span class="text-[10px] text-on-surface-variant block font-normal">
                                ({{ $sale->items->count() }} item)
                            </span>
                        </td>

                        {{-- Total Nilai --}}
                        <td class="px-4 py-3 text-right">
                            <div id="sale-total-{{ $sale->id }}" class="font-mono font-extrabold text-on-surface text-xs">
                                Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                            </div>
                            <div class="flex items-center justify-end gap-1 group/disc mt-0.5">
                                <span id="sale-disc-text-{{ $sale->id }}" class="text-[10px] font-mono text-rose-600 font-semibold {{ $saleDisc > 0 ? '' : 'hidden' }}">
                                    Disc: -Rp {{ number_format($saleDisc, 0, ',', '.') }}
                                </span>
                                @canany(['Sales Edit', 'Sales List'])
                                <button type="button" 
                                        id="sale-disc-btn-{{ $sale->id }}"
                                        onclick="openChangeDiscountModal('{{ $sale->id }}', '{{ $sale->reference_number }}')"
                                        class="opacity-60 hover:opacity-100 text-on-surface-variant hover:text-rose-600 transition-all p-0.5 rounded hover:bg-surface-container cursor-pointer"
                                        title="Ubah Diskon Penjualan">
                                    <span class="material-symbols-outlined text-[12px]">sell</span>
                                </button>
                                @endcanany
                            </div>
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @canany(['Sales Edit', 'Sales List'])
                                <button type="button" 
                                        id="btn-edit-disc-{{ $sale->id }}"
                                        onclick="openChangeDiscountModal('{{ $sale->id }}', '{{ $sale->reference_number }}')" 
                                        class="p-1.5 text-on-surface-variant hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all active:scale-95 cursor-pointer" 
                                        title="Ubah Diskon Penjualan">
                                    <span class="material-symbols-outlined text-base">sell</span>
                                </button>
                                @endcanany

                                @canany(['Sales Edit', 'Sales List'])
                                <button type="button" 
                                        id="btn-edit-pay-{{ $sale->id }}"
                                        onclick="openChangePaymentMethodModal('{{ $sale->id }}', '{{ $sale->reference_number }}', '{{ $payMethodKey }}')" 
                                        class="p-1.5 text-on-surface-variant hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-all active:scale-95 cursor-pointer" 
                                        title="Ubah Metode Pembayaran">
                                    <span class="material-symbols-outlined text-base">payments</span>
                                </button>
                                @endcanany

                                <button type="button" 
                                        id="btn-edit-cashier-{{ $sale->id }}"
                                        onclick="openChangeCashierModal('{{ $sale->id }}', '{{ $sale->reference_number }}', '{{ $sale->user_id }}', '{{ addslashes($sale->user?->name ?? 'Kasir Staff') }}')" 
                                        class="p-1.5 text-on-surface-variant hover:text-primary hover:bg-primary/10 rounded-lg transition-all active:scale-95 cursor-pointer" 
                                        title="Ubah Kasir Transaksi">
                                    <span class="material-symbols-outlined text-base">manage_accounts</span>
                                </button>

                                @can('Sales Show')
                                <a href="{{ route('sales.show', $sale->id) }}" 
                                   class="p-1.5 text-on-surface-variant hover:text-primary hover:bg-surface-container-high rounded-lg transition-all active:scale-95 no-underline"
                                   title="Lihat Detail Transaksi">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                </a>
                                @endcan

                                @canany(['Sales Destroy', 'Movement Destroy'])
                                <button type="button" 
                                        onclick="confirmDeleteSale('{{ $sale->id }}', '{{ $sale->reference_number }}')" 
                                        class="p-1.5 text-on-surface-variant hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all active:scale-95 cursor-pointer" 
                                        title="Hapus Transaksi Penjualan">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                                @endcanany
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($sales->hasPages())
        <div class="px-4 py-3.5 border-t border-outline-variant flex flex-col sm:flex-row justify-between items-center gap-3 bg-surface-container-low/40">
            <span class="text-xs text-on-surface-variant font-semibold">
                Menampilkan {{ $sales->firstItem() }} – {{ $sales->lastItem() }} dari {{ $sales->total() }} transaksi
            </span>
            <div class="pagination-wrap">
                {{ $sales->links() }}
            </div>
        </div>
        @endif

        @else
        <div class="py-16 text-center">
            <div class="w-16 h-16 rounded-2xl bg-surface-container-high text-on-surface-variant flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-3xl opacity-60">receipt_long</span>
            </div>
            <p class="font-extrabold text-on-surface text-sm">Tidak ada riwayat transaksi penjualan ditemukan</p>
            <p class="text-on-surface-variant text-xs mt-1 max-w-sm mx-auto">
                Transaksi baru dicatat langsung melalui aplikasi POS Kasir. Sesuaikan filter pencarian jika ingin mencari struk lama.
            </p>
            @if(!empty($search) || !empty($startDate) || !empty($endDate) || (!empty($userId) && $userId !== 'all'))
            <a href="{{ route('sales.index') }}"
               class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 rounded-xl bg-primary text-on-primary text-xs font-bold transition-all shadow-sm">
                <span class="material-symbols-outlined text-sm">restart_alt</span>
                Reset Filter
            </a>
            @endif
        </div>
        @endif
    </div>

</div>

{{-- Delete Confirmation Modal --}}
<div id="deleteSalesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden transition-opacity">
    <div class="bg-white rounded-2xl border border-outline-variant shadow-2xl max-w-md w-full mx-4 p-6 flex flex-col gap-5">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">delete_forever</span>
            </div>
            <div class="flex-1 min-w-0">
                <h3 id="deleteSalesModalTitle" class="text-lg font-bold text-on-surface">Konfirmasi Hapus Penjualan</h3>
                <p id="deleteSalesModalDesc" class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                    Apakah Anda yakin ingin menghapus transaksi penjualan ini? Stok barang akan dikembalikan secara otomatis.
                </p>
            </div>
        </div>

        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-rose-600 shrink-0">warning</span>
            <span>Tindakan ini tidak dapat dibatalkan.</span>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2">
            <button type="button" onclick="closeDeleteSalesModal()" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low text-xs font-bold text-on-surface transition cursor-pointer">
                Batal
            </button>
            <button type="button" id="confirmDeleteSalesBtn" onclick="submitDeleteSalesForm()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-base">delete</span>
                <span id="confirmDeleteSalesBtnText">Ya, Hapus</span>
            </button>
        </div>
    </div>
</div>

{{-- Hidden Form for bulk delete sales --}}
<form id="bulkDeleteSalesForm" action="{{ route('sales.bulk-destroy') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="delete_all" id="deleteAllSalesInput" value="0">
    <div id="bulkDeleteSalesContainer"></div>
</form>

{{-- Hidden Form for single delete sale --}}
<form id="singleDeleteSaleForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

{{-- Change Cashier Modal --}}
<div id="changeCashierModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden transition-opacity">
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant shadow-2xl max-w-md w-full mx-4 p-6 flex flex-col gap-5">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">manage_accounts</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-on-surface">Ubah Kasir Transaksi</h3>
                    <p id="changeCashierModalSubtitle" class="text-xs text-on-surface-variant font-mono font-bold mt-0.5 text-primary">#POS-0000</p>
                </div>
            </div>
            <button type="button" onclick="closeChangeCashierModal()" class="w-8 h-8 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface-variant flex items-center justify-center transition cursor-pointer">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <div class="space-y-4">
            {{-- Current Cashier Info --}}
            <div class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/60 flex items-center justify-between">
                <span class="text-xs text-on-surface-variant font-medium">Kasir Saat Ini:</span>
                <span id="currentCashierBadge" class="text-xs font-bold text-on-surface font-mono bg-surface-container px-2 py-0.5 rounded-lg">-</span>
            </div>

            {{-- New Cashier Selector --}}
            <div class="space-y-1.5">
                <label for="newCashierSelect" class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">
                    Pilih Kasir / Staf Baru <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant text-lg pointer-events-none">person</span>
                    <select id="newCashierSelect" class="w-full border border-outline-variant bg-surface-container-low rounded-xl pl-9 pr-8 py-2.5 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all font-medium appearance-none cursor-pointer">
                        @foreach($cashiers as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->name }} ({{ $c->position ?: ($c->roles->first()?->name ?? 'Staff') }})
                            </option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-2.5 text-on-surface-variant text-lg pointer-events-none">arrow_drop_down</span>
                </div>
            </div>

            {{-- Sync Info Notice --}}
            <div class="p-3 bg-primary/5 border border-primary/20 rounded-xl text-xs text-on-surface flex items-start gap-2.5">
                <span class="material-symbols-outlined text-primary text-base shrink-0 mt-0.5">sync_saved_locally</span>
                <div class="leading-relaxed">
                    Perubahan kasir akan otomatis tersinkronisasi ke riwayat <strong>Point of Sale (POS)</strong>, cetak ulang struk, dan <strong>laporan performa per kasir</strong>.
                </div>
            </div>
            
            <div id="changeCashierErrorMsg" class="hidden p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 font-medium"></div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-outline-variant/60">
            <button type="button" onclick="closeChangeCashierModal()" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low text-xs font-bold text-on-surface transition cursor-pointer">
                Batal
            </button>
            <button type="button" id="btnSubmitChangeCashier" onclick="submitChangeCashier()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary hover:opacity-90 text-on-primary text-xs font-bold shadow-sm transition active:scale-95 cursor-pointer">
                <span id="btnSubmitChangeCashierSpinner" class="hidden animate-spin material-symbols-outlined text-base">progress_activity</span>
                <span id="btnSubmitChangeCashierIcon" class="material-symbols-outlined text-base">check</span>
                <span id="btnSubmitChangeCashierText">Simpan Kasir Baru</span>
            </button>
        </div>
    </div>
</div>

{{-- Change Payment Method Modal --}}
<div id="changePaymentMethodModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-surface-container-lowest border border-outline-variant rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in duration-200">
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-700">
                    <span class="material-symbols-outlined text-xl">payments</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-on-surface">Ubah Metode Pembayaran</h3>
                    <p id="changePaymentMethodModalSubtitle" class="text-xs text-on-surface-variant font-mono font-bold mt-0.5 text-primary">#POS-0000</p>
                </div>
            </div>
            <button type="button" onclick="closeChangePaymentMethodModal()" class="w-8 h-8 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface-variant flex items-center justify-center transition cursor-pointer">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <div class="space-y-4">
            {{-- Current Payment Method Info --}}
            <div class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/60 flex items-center justify-between">
                <span class="text-xs text-on-surface-variant font-medium">Metode Saat Ini:</span>
                <span id="currentPaymentMethodBadge" class="text-xs font-bold font-mono px-2 py-0.5 rounded-lg">-</span>
            </div>

            {{-- New Payment Method Selector --}}
            <div class="space-y-1.5">
                <label for="newPaymentMethodSelect" class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">
                    Pilih Metode Pembayaran Baru <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant text-lg pointer-events-none">credit_card</span>
                    <select id="newPaymentMethodSelect" class="w-full border border-outline-variant bg-surface-container-low rounded-xl pl-9 pr-8 py-2.5 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all font-medium appearance-none cursor-pointer">
                        <option value="cash">Tunai (Cash)</option>
                        <option value="split">Split Payment</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="qris">QRIS</option>
                        <option value="debit">Kartu Debit</option>
                        <option value="credit">Kartu Kredit</option>
                        <option value="other">Lainnya</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-2.5 text-on-surface-variant text-lg pointer-events-none">arrow_drop_down</span>
                </div>
            </div>

            {{-- Sync Info Notice --}}
            <div class="p-3 bg-emerald-500/5 border border-emerald-500/20 rounded-xl text-xs text-on-surface flex items-start gap-2.5">
                <span class="material-symbols-outlined text-emerald-600 text-base shrink-0 mt-0.5">sync_saved_locally</span>
                <div class="leading-relaxed">
                    Perubahan metode bayar akan otomatis tersinkronisasi ke <strong>Stock Out</strong>, ringkasan shift kasir <strong>POS</strong>, kartu stok, dan laporan omset harian.
                </div>
            </div>
            
            <div id="changePaymentMethodErrorMsg" class="hidden p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 font-medium"></div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-outline-variant/60">
            <button type="button" onclick="closeChangePaymentMethodModal()" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low text-xs font-bold text-on-surface transition cursor-pointer">
                Batal
            </button>
            <button type="button" id="btnSubmitChangePaymentMethod" onclick="submitChangePaymentMethod()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary hover:opacity-90 text-on-primary text-xs font-bold shadow-sm transition active:scale-95 cursor-pointer">
                <span id="btnSubmitChangePaymentMethodSpinner" class="hidden animate-spin material-symbols-outlined text-base">progress_activity</span>
                <span id="btnSubmitChangePaymentMethodIcon" class="material-symbols-outlined text-base">check</span>
                <span id="btnSubmitChangePaymentMethodText">Simpan Perubahan</span>
            </button>
        </div>
    </div>
</div>

{{-- Change Discount Modal --}}
<div id="changeDiscountModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-surface-container-lowest border border-outline-variant rounded-3xl max-w-2xl w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in duration-200 flex flex-col max-h-[90vh]">
        {{-- Modal Header --}}
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-500/10 flex items-center justify-center text-rose-600">
                    <span class="material-symbols-outlined text-xl">sell</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-on-surface">Ubah Diskon Penjualan</h3>
                    <p id="changeDiscountModalSubtitle" class="text-xs font-mono font-bold mt-0.5 text-primary">#POS-0000</p>
                </div>
            </div>
            <button type="button" onclick="closeChangeDiscountModal()" class="w-8 h-8 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface-variant flex items-center justify-center transition cursor-pointer">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        {{-- Modal Loading Spinner --}}
        <div id="changeDiscountLoading" class="py-12 flex flex-col items-center justify-center gap-3 text-on-surface-variant">
            <span class="animate-spin material-symbols-outlined text-3xl text-primary">progress_activity</span>
            <span class="text-xs font-medium">Memuat rincian produk...</span>
        </div>

        {{-- Modal Content Area --}}
        <div id="changeDiscountContent" class="space-y-4 overflow-y-auto pr-1 flex-1 hidden">
            <div class="border border-outline-variant rounded-2xl overflow-hidden">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant">
                        <tr>
                            <th class="p-3">Produk</th>
                            <th class="p-3 text-right">Harga x Qty</th>
                            <th class="p-3 text-right w-44">Diskon (Rp)</th>
                            <th class="p-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="discountItemsTableBody" class="divide-y divide-outline-variant/60 bg-surface-container-lowest">
                        <!-- Rendered by JS -->
                    </tbody>
                </table>
            </div>

            {{-- Summary Breakdown Cards --}}
            <div class="grid grid-cols-3 gap-3 p-3.5 bg-surface-container-low rounded-2xl border border-outline-variant/60">
                <div>
                    <span class="text-[11px] font-semibold text-on-surface-variant block">Subtotal Kotor</span>
                    <span id="modalGrossTotal" class="text-xs font-bold font-mono text-on-surface">Rp 0</span>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-rose-600 block">Total Potongan Diskon</span>
                    <span id="modalDiscountTotal" class="text-xs font-bold font-mono text-rose-600">Rp 0</span>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-primary block">Total Akhir Bayar</span>
                    <span id="modalNetTotal" class="text-sm font-extrabold font-mono text-primary">Rp 0</span>
                </div>
            </div>

            {{-- Notice Box --}}
            <div class="p-3 bg-rose-500/5 border border-rose-500/20 rounded-xl text-xs text-on-surface flex items-start gap-2.5">
                <span class="material-symbols-outlined text-rose-600 text-base shrink-0 mt-0.5">verified</span>
                <div class="leading-relaxed text-[11px]">
                    Perubahan nilai diskon akan langsung mengkalkulasi ulang total transaksi, subtotal item, serta otomatis tersinkronisasi ke <strong>Stock Out</strong>, kartu stok, dan laporan omset & laba bersih.
                </div>
            </div>

            <div id="changeDiscountErrorMsg" class="hidden p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 font-medium"></div>
        </div>

        {{-- Modal Footer --}}
        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-outline-variant/60 shrink-0">
            <button type="button" onclick="closeChangeDiscountModal()" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low text-xs font-bold text-on-surface transition cursor-pointer">
                Batal
            </button>
            <button type="button" id="btnSubmitChangeDiscount" onclick="submitChangeDiscount()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary hover:opacity-90 text-on-primary text-xs font-bold shadow-sm transition active:scale-95 cursor-pointer">
                <span id="btnSubmitChangeDiscountSpinner" class="hidden animate-spin material-symbols-outlined text-base">progress_activity</span>
                <span id="btnSubmitChangeDiscountIcon" class="material-symbols-outlined text-base">check</span>
                <span id="btnSubmitChangeDiscountText">Simpan Perubahan Diskon</span>
            </button>
        </div>
    </div>
</div>

{{-- Global Toast Notification --}}
<div id="salesToast" class="fixed bottom-6 right-6 z-50 hidden transition-all duration-300 transform translate-y-4">
    <div id="salesToastContent" class="flex items-center gap-2.5 px-4 py-3 rounded-2xl bg-slate-900 text-white shadow-xl border border-slate-700 text-xs font-semibold">
        <span id="salesToastIcon" class="material-symbols-outlined text-emerald-400 text-lg">check_circle</span>
        <span id="salesToastMessage">Pesan notifikasi</span>
    </div>
</div>

<script>
    let isDeletingAllSales = false;
    let singleSaleDeleteUrl = null;
    let currentEditSaleId = null;
    let currentEditRefNumber = null;

    function getSaleCheckboxes() {
        return document.querySelectorAll('.sale-checkbox');
    }

    function showSalesToast(message, isSuccess = true) {
        const toast = document.getElementById('salesToast');
        const toastIcon = document.getElementById('salesToastIcon');
        const toastMsg = document.getElementById('salesToastMessage');

        if (!toast) return;

        toastMsg.textContent = message;
        if (isSuccess) {
            toastIcon.textContent = 'check_circle';
            toastIcon.className = 'material-symbols-outlined text-emerald-400 text-lg';
        } else {
            toastIcon.textContent = 'error';
            toastIcon.className = 'material-symbols-outlined text-rose-400 text-lg';
        }

        toast.classList.remove('hidden');
        setTimeout(() => {
            toast.classList.remove('translate-y-4', 'opacity-0');
        }, 10);

        setTimeout(() => {
            toast.classList.add('translate-y-4', 'opacity-0');
            setTimeout(() => toast.classList.add('hidden'), 300);
        }, 4000);
    }

    let currentEditPaySaleId = null;
    let currentEditPayRefNumber = null;

    function openChangePaymentMethodModal(saleId, refNumber, currentMethod) {
        currentEditPaySaleId = saleId;
        currentEditPayRefNumber = refNumber;

        const modal = document.getElementById('changePaymentMethodModal');
        const subtitle = document.getElementById('changePaymentMethodModalSubtitle');
        const badge = document.getElementById('currentPaymentMethodBadge');
        const select = document.getElementById('newPaymentMethodSelect');
        const errorMsg = document.getElementById('changePaymentMethodErrorMsg');

        if (errorMsg) errorMsg.classList.add('hidden');
        if (subtitle) subtitle.textContent = '#' + refNumber;
        
        const curMethodLower = (currentMethod || 'cash').toLowerCase();
        const methodLabels = {
            'cash': 'Tunai (Cash)',
            'split': 'Split Payment',
            'transfer': 'Transfer Bank',
            'qris': 'QRIS',
            'debit': 'Kartu Debit',
            'credit': 'Kartu Kredit',
            'other': 'Lainnya'
        };
        if (badge) {
            badge.textContent = methodLabels[curMethodLower] || curMethodLower.toUpperCase();
            badge.className = 'text-xs font-bold font-mono px-2 py-0.5 rounded-lg ' + 
                (curMethodLower === 'cash' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                 curMethodLower === 'split' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                 curMethodLower === 'transfer' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' :
                 curMethodLower === 'qris' ? 'bg-sky-50 text-sky-700 border border-sky-200' :
                 curMethodLower === 'debit' ? 'bg-purple-50 text-purple-700 border border-purple-200' :
                 curMethodLower === 'credit' ? 'bg-pink-50 text-pink-700 border border-pink-200' :
                 'bg-gray-100 text-gray-700 border border-gray-200');
        }
        if (select) {
            select.value = curMethodLower;
        }

        modal.classList.remove('hidden');
    }

    function closeChangePaymentMethodModal() {
        const modal = document.getElementById('changePaymentMethodModal');
        if (modal) modal.classList.add('hidden');
        currentEditPaySaleId = null;
        currentEditPayRefNumber = null;
    }

    async function submitChangePaymentMethod() {
        if (!currentEditPaySaleId) return;

        const select = document.getElementById('newPaymentMethodSelect');
        const newMethod = select ? select.value : null;
        if (!newMethod) {
            alert('Silakan pilih metode pembayaran terlebih dahulu.');
            return;
        }

        const btn = document.getElementById('btnSubmitChangePaymentMethod');
        const spinner = document.getElementById('btnSubmitChangePaymentMethodSpinner');
        const icon = document.getElementById('btnSubmitChangePaymentMethodIcon');
        const btnText = document.getElementById('btnSubmitChangePaymentMethodText');
        const errorMsg = document.getElementById('changePaymentMethodErrorMsg');

        if (btn) btn.disabled = true;
        if (spinner) spinner.classList.remove('hidden');
        if (icon) icon.classList.add('hidden');
        if (btnText) btnText.textContent = 'Menyimpan...';
        if (errorMsg) errorMsg.classList.add('hidden');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                || '{{ csrf_token() }}';

            const response = await fetch(`{{ url('/sales') }}/${currentEditPaySaleId}/payment-method`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    _method: 'PUT',
                    payment_method: newMethod
                })
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Gagal mengubah metode pembayaran.');
            }

            // Update table DOM elements immediately
            const badgeEl = document.getElementById(`pay-badge-${currentEditPaySaleId}`);
            const editBtn = document.getElementById(`pay-edit-btn-${currentEditPaySaleId}`);
            const actionBtn = document.getElementById(`btn-edit-pay-${currentEditPaySaleId}`);

            if (badgeEl) {
                badgeEl.textContent = (result.payment_method || newMethod).toUpperCase();
                badgeEl.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-extrabold border ${result.badge_class}`;
            }

            if (editBtn) {
                editBtn.setAttribute('onclick', `openChangePaymentMethodModal('${currentEditPaySaleId}', '${currentEditPayRefNumber}', '${result.payment_method}')`);
            }
            if (actionBtn) {
                actionBtn.setAttribute('onclick', `openChangePaymentMethodModal('${currentEditPaySaleId}', '${currentEditPayRefNumber}', '${result.payment_method}')`);
            }

            closeChangePaymentMethodModal();
            showSalesToast(`Metode pembayaran #${currentEditPayRefNumber} berhasil diubah ke ${result.payment_method_label}! Memperbarui kalkulasi...`);
            setTimeout(() => {
                window.location.reload();
            }, 600);

        } catch (err) {
            if (errorMsg) {
                errorMsg.textContent = err.message || 'Terjadi kesalahan sistem.';
                errorMsg.classList.remove('hidden');
            } else {
                alert(err.message || 'Gagal mengubah metode pembayaran.');
            }
        } finally {
            if (btn) btn.disabled = false;
            if (spinner) spinner.classList.add('hidden');
            if (icon) icon.classList.remove('hidden');
            if (btnText) btnText.textContent = 'Simpan Perubahan';
        }
    }

    function openChangeCashierModal(saleId, refNumber, currentUserId, currentUserName) {
        currentEditSaleId = saleId;
        currentEditRefNumber = refNumber;

        const modal = document.getElementById('changeCashierModal');
        const subtitle = document.getElementById('changeCashierModalSubtitle');
        const badge = document.getElementById('currentCashierBadge');
        const select = document.getElementById('newCashierSelect');
        const errorMsg = document.getElementById('changeCashierErrorMsg');

        if (errorMsg) errorMsg.classList.add('hidden');
        if (subtitle) subtitle.textContent = '#' + refNumber;
        if (badge) badge.textContent = currentUserName || 'Kasir Staff';
        if (select) {
            select.value = currentUserId || '';
        }

        modal.classList.remove('hidden');
    }

    function closeChangeCashierModal() {
        const modal = document.getElementById('changeCashierModal');
        if (modal) modal.classList.add('hidden');
        currentEditSaleId = null;
        currentEditRefNumber = null;
    }

    async function submitChangeCashier() {
        if (!currentEditSaleId) return;

        const select = document.getElementById('newCashierSelect');
        const newUserId = select ? select.value : null;
        if (!newUserId) {
            alert('Silakan pilih kasir terlebih dahulu.');
            return;
        }

        const btn = document.getElementById('btnSubmitChangeCashier');
        const spinner = document.getElementById('btnSubmitChangeCashierSpinner');
        const icon = document.getElementById('btnSubmitChangeCashierIcon');
        const btnText = document.getElementById('btnSubmitChangeCashierText');
        const errorMsg = document.getElementById('changeCashierErrorMsg');

        if (btn) btn.disabled = true;
        if (spinner) spinner.classList.remove('hidden');
        if (icon) icon.classList.add('hidden');
        if (btnText) btnText.textContent = 'Menyimpan...';
        if (errorMsg) errorMsg.classList.add('hidden');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                || '{{ csrf_token() }}';

            const response = await fetch(`{{ url('/sales') }}/${currentEditSaleId}/cashier`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    _method: 'PUT',
                    user_id: newUserId
                })
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Gagal mengubah kasir.');
            }

            const newCashier = result.cashier;

            // Update table DOM elements immediately
            const nameEl = document.getElementById(`cashier-name-${currentEditSaleId}`);
            const initialEl = document.getElementById(`cashier-initial-${currentEditSaleId}`);
            const editLink = document.getElementById(`cashier-edit-link-${currentEditSaleId}`);
            const actionBtn = document.getElementById(`btn-edit-cashier-${currentEditSaleId}`);

            if (nameEl) nameEl.textContent = newCashier.name;
            if (initialEl) initialEl.textContent = newCashier.initial;

            const escapedName = newCashier.name.replace(/'/g, "\\'");
            if (editLink) {
                editLink.setAttribute('onclick', `openChangeCashierModal('${currentEditSaleId}', '${currentEditRefNumber}', '${newCashier.id}', '${escapedName}')`);
            }
            if (actionBtn) {
                actionBtn.setAttribute('onclick', `openChangeCashierModal('${currentEditSaleId}', '${currentEditRefNumber}', '${newCashier.id}', '${escapedName}')`);
            }

            closeChangeCashierModal();
            showSalesToast(`Kasir untuk #${currentEditRefNumber} berhasil diubah ke ${newCashier.name} & tersinkronisasi ke POS!`);

        } catch (err) {
            if (errorMsg) {
                errorMsg.textContent = err.message || 'Terjadi kesalahan sistem.';
                errorMsg.classList.remove('hidden');
            } else {
                alert(err.message || 'Gagal mengubah kasir.');
            }
        } finally {
            if (btn) btn.disabled = false;
            if (spinner) spinner.classList.add('hidden');
            if (icon) icon.classList.remove('hidden');
            if (btnText) btnText.textContent = 'Simpan Kasir Baru';
        }
    }

    /* Change Discount Logic */
    let currentEditDiscSaleId = null;
    let currentEditDiscRefNumber = null;
    let currentSaleItems = [];

    async function openChangeDiscountModal(saleId, refNumber) {
        currentEditDiscSaleId = saleId;
        currentEditDiscRefNumber = refNumber;
        currentSaleItems = [];

        const modal = document.getElementById('changeDiscountModal');
        const subtitle = document.getElementById('changeDiscountModalSubtitle');
        const loading = document.getElementById('changeDiscountLoading');
        const content = document.getElementById('changeDiscountContent');
        const errorMsg = document.getElementById('changeDiscountErrorMsg');
        const submitBtn = document.getElementById('btnSubmitChangeDiscount');

        if (errorMsg) errorMsg.classList.add('hidden');
        if (subtitle) subtitle.textContent = '#' + refNumber;
        if (loading) loading.classList.remove('hidden');
        if (content) content.classList.add('hidden');
        if (submitBtn) submitBtn.disabled = true;

        modal.classList.remove('hidden');

        try {
            const response = await fetch(`{{ url('/sales') }}/${saleId}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();

            if (!response.ok || data.status !== 'success' || !data.sale) {
                throw new Error(data.message || 'Gagal memuat rincian transaksi.');
            }

            currentSaleItems = (data.sale.items || []).map(item => ({
                id: item.id,
                product_name: item.product_name,
                sku: item.sku,
                quantity: item.quantity,
                selling_price: item.selling_price,
                gross_total: item.gross_total,
                discount: item.discount,
                subtotal: item.subtotal
            }));

            renderDiscountItemsTable();
            recalculateDiscountTotals();

            if (loading) loading.classList.add('hidden');
            if (content) content.classList.remove('hidden');
            if (submitBtn) submitBtn.disabled = false;

        } catch (err) {
            if (loading) loading.classList.add('hidden');
            if (errorMsg) {
                errorMsg.textContent = err.message || 'Terjadi kesalahan saat memuat data.';
                errorMsg.classList.remove('hidden');
            }
            if (content) content.classList.remove('hidden');
        }
    }

    function renderDiscountItemsTable() {
        const tbody = document.getElementById('discountItemsTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';

        currentSaleItems.forEach((item) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-surface-container-low/40 transition-colors';
            
            tr.innerHTML = `
                <td class="p-3">
                    <div class="font-bold text-on-surface text-xs leading-snug">${item.product_name}</div>
                    <div class="text-[10px] font-mono text-on-surface-variant">SKU: ${item.sku}</div>
                </td>
                <td class="p-3 text-right font-mono text-xs text-on-surface-variant">
                    <div>${item.quantity} × Rp ${Number(item.selling_price).toLocaleString('id-ID')}</div>
                    <div class="font-bold text-on-surface text-[11px]">= Rp ${Number(item.gross_total).toLocaleString('id-ID')}</div>
                </td>
                <td class="p-3 text-right">
                    <div class="relative inline-flex items-center w-full max-w-[150px]">
                        <span class="absolute left-2.5 text-[11px] font-mono font-bold text-rose-600 pointer-events-none">Rp</span>
                        <input type="number" 
                               id="item-disc-input-${item.id}"
                               value="${item.discount}"
                               min="0"
                               max="${item.gross_total}"
                               step="500"
                               oninput="onItemDiscountChange(${item.id}, this.value)"
                               class="w-full pl-8 pr-2 py-1.5 text-right font-mono font-bold text-xs bg-surface-container-low border border-outline-variant rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500/30 focus:border-rose-500 text-rose-600 transition"
                               placeholder="0">
                    </div>
                </td>
                <td class="p-3 text-right font-mono font-bold text-xs text-on-surface" id="item-subtotal-disp-${item.id}">
                    Rp ${Number(item.subtotal).toLocaleString('id-ID')}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function onItemDiscountChange(itemId, value) {
        const item = currentSaleItems.find(it => it.id === itemId);
        if (!item) return;

        let discVal = parseFloat(value);
        if (isNaN(discVal) || discVal < 0) discVal = 0;
        if (discVal > item.gross_total) discVal = item.gross_total;

        item.discount = discVal;
        item.subtotal = Math.max(0, item.gross_total - discVal);

        const subtotalEl = document.getElementById(`item-subtotal-disp-${itemId}`);
        if (subtotalEl) {
            subtotalEl.textContent = 'Rp ' + Number(item.subtotal).toLocaleString('id-ID');
        }

        recalculateDiscountTotals();
    }

    function recalculateDiscountTotals() {
        let grossTotal = 0;
        let discountTotal = 0;
        let netTotal = 0;

        currentSaleItems.forEach(item => {
            grossTotal += item.gross_total;
            discountTotal += item.discount;
            netTotal += item.subtotal;
        });

        const grossEl = document.getElementById('modalGrossTotal');
        const discEl = document.getElementById('modalDiscountTotal');
        const netEl = document.getElementById('modalNetTotal');

        if (grossEl) grossEl.textContent = 'Rp ' + Number(grossTotal).toLocaleString('id-ID');
        if (discEl) discEl.textContent = '-Rp ' + Number(discountTotal).toLocaleString('id-ID');
        if (netEl) netEl.textContent = 'Rp ' + Number(netTotal).toLocaleString('id-ID');
    }

    function closeChangeDiscountModal() {
        const modal = document.getElementById('changeDiscountModal');
        if (modal) modal.classList.add('hidden');
        currentEditDiscSaleId = null;
        currentEditDiscRefNumber = null;
        currentSaleItems = [];
    }

    async function submitChangeDiscount() {
        if (!currentEditDiscSaleId || currentSaleItems.length === 0) return;

        const btn = document.getElementById('btnSubmitChangeDiscount');
        const spinner = document.getElementById('btnSubmitChangeDiscountSpinner');
        const icon = document.getElementById('btnSubmitChangeDiscountIcon');
        const btnText = document.getElementById('btnSubmitChangeDiscountText');
        const errorMsg = document.getElementById('changeDiscountErrorMsg');

        if (btn) btn.disabled = true;
        if (spinner) spinner.classList.remove('hidden');
        if (icon) icon.classList.add('hidden');
        if (btnText) btnText.textContent = 'Menyimpan...';
        if (errorMsg) errorMsg.classList.add('hidden');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                || '{{ csrf_token() }}';

            const payload = {
                _method: 'PUT',
                items: currentSaleItems.map(it => ({
                    id: it.id,
                    discount: it.discount
                }))
            };

            const response = await fetch(`{{ url('/sales') }}/${currentEditDiscSaleId}/discount`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Gagal menyimpan perubahan diskon.');
            }

            // Update row in table (on index page)
            const totalEl = document.getElementById(`sale-total-${currentEditDiscSaleId}`);
            const discTextEl = document.getElementById(`sale-disc-text-${currentEditDiscSaleId}`);

            if (totalEl) {
                totalEl.textContent = `Rp ${result.total_amount_formatted}`;
            }

            if (discTextEl) {
                if (result.total_discount > 0) {
                    discTextEl.textContent = `Disc: -Rp ${result.total_discount_formatted}`;
                    discTextEl.classList.remove('hidden');
                } else {
                    discTextEl.classList.add('hidden');
                }
            }

            closeChangeDiscountModal();
            showSalesToast(`Diskon untuk #${currentEditDiscRefNumber} berhasil diperbarui! Nilai transaksi & Stock Out telah disinkronkan.`);

        } catch (err) {
            if (errorMsg) {
                errorMsg.textContent = err.message || 'Terjadi kesalahan sistem.';
                errorMsg.classList.remove('hidden');
            } else {
                alert(err.message || 'Gagal menyimpan perubahan diskon.');
            }
        } finally {
            if (btn) btn.disabled = false;
            if (spinner) spinner.classList.add('hidden');
            if (icon) icon.classList.remove('hidden');
            if (btnText) btnText.textContent = 'Simpan Perubahan Diskon';
        }
    }

    function updateSalesBulkUI() {
        const checkboxes = getSaleCheckboxes();
        const checkedCheckboxes = Array.from(checkboxes).filter(cb => cb.checked);
        const checkedCount = checkedCheckboxes.length;
        const totalCount = checkboxes.length;
        const selectAllSales = document.getElementById('selectAllSales');
        const bulkActionBar = document.getElementById('salesBulkActionBar');
        const selectedCountBadge = document.getElementById('salesSelectedCountBadge');
        const btnDeleteSelectedSalesText = document.getElementById('btnDeleteSelectedSalesText');

        if (selectAllSales) {
            if (checkedCount === 0) {
                selectAllSales.checked = false;
                selectAllSales.indeterminate = false;
            } else if (checkedCount === totalCount && totalCount > 0) {
                selectAllSales.checked = true;
                selectAllSales.indeterminate = false;
            } else {
                selectAllSales.checked = false;
                selectAllSales.indeterminate = true;
            }
        }

        if (checkedCount > 0) {
            if (bulkActionBar) bulkActionBar.classList.remove('hidden');
            if (selectedCountBadge) selectedCountBadge.textContent = checkedCount + ' transaksi dipilih';
            if (btnDeleteSelectedSalesText) btnDeleteSelectedSalesText.textContent = 'Hapus Terpilih (' + checkedCount + ')';
        } else {
            if (bulkActionBar) bulkActionBar.classList.add('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('sales_user_id')) {
            new TomSelect('#sales_user_id', {
                create: false,
                allowEmptyOption: true,
                maxOptions: 200,
                placeholder: 'Semua Kasir',
            });
        }
        if (document.getElementById('sales_payment_method')) {
            new TomSelect('#sales_payment_method', {
                create: false,
                allowEmptyOption: true,
                maxOptions: 50,
                placeholder: 'Semua Metode',
            });
        }

        const selectAllSales = document.getElementById('selectAllSales');
        const btnCancelSelection = document.getElementById('btnCancelSalesSelection');
        const btnDeleteSelected = document.getElementById('btnDeleteSelectedSales');

        if (selectAllSales) {
            selectAllSales.addEventListener('change', function () {
                const checkboxes = getSaleCheckboxes();
                checkboxes.forEach(cb => {
                    cb.checked = selectAllSales.checked;
                });
                updateSalesBulkUI();
            });
        }

        document.querySelectorAll('.sale-checkbox').forEach(cb => {
            cb.addEventListener('change', updateSalesBulkUI);
        });

        if (btnCancelSelection) {
            btnCancelSelection.addEventListener('click', function () {
                if (selectAllSales) {
                    selectAllSales.checked = false;
                    selectAllSales.indeterminate = false;
                }
                getSaleCheckboxes().forEach(cb => cb.checked = false);
                updateSalesBulkUI();
            });
        }

        if (btnDeleteSelected) {
            btnDeleteSelected.addEventListener('click', function () {
                openBulkDeleteSalesModal(false);
            });
        }

        window.addEventListener('click', function(e) {
            const modal = document.getElementById('deleteSalesModal');
            if (e.target === modal) {
                closeDeleteSalesModal();
            }
            const modalCashier = document.getElementById('changeCashierModal');
            if (e.target === modalCashier) {
                closeChangeCashierModal();
            }
            const modalPay = document.getElementById('changePaymentMethodModal');
            if (e.target === modalPay) {
                closeChangePaymentMethodModal();
            }
            const modalDisc = document.getElementById('changeDiscountModal');
            if (e.target === modalDisc) {
                closeChangeDiscountModal();
            }
        });
    });

    function confirmDeleteSale(id, refNumber) {
        singleSaleDeleteUrl = `{{ url('/sales') }}/${id}`;
        isDeletingAllSales = false;
        const modal = document.getElementById('deleteSalesModal');
        const title = document.getElementById('deleteSalesModalTitle');
        const desc = document.getElementById('deleteSalesModalDesc');
        const btnText = document.getElementById('confirmDeleteSalesBtnText');

        title.textContent = `Hapus Transaksi #${refNumber}`;
        desc.innerHTML = `Apakah Anda yakin ingin menghapus transaksi penjualan <strong>#${refNumber}</strong>? Stok barang yang terjual akan dikembalikan ke inventaris.`;
        btnText.textContent = 'Hapus Transaksi';

        modal.classList.remove('hidden');
    }

    function openBulkDeleteSalesModal(deleteAll = false) {
        singleSaleDeleteUrl = null;
        isDeletingAllSales = deleteAll;
        const modal = document.getElementById('deleteSalesModal');
        const title = document.getElementById('deleteSalesModalTitle');
        const desc = document.getElementById('deleteSalesModalDesc');
        const btnText = document.getElementById('confirmDeleteSalesBtnText');

        if (deleteAll) {
            title.textContent = 'Hapus Seluruh Riwayat Penjualan';
            desc.innerHTML = 'Apakah Anda yakin ingin menghapus <strong>seluruh {{ $totalTransactions }} transaksi penjualan</strong>? Seluruh stok barang akan dikembalikan ke inventaris.';
            btnText.textContent = 'Hapus Seluruh Penjualan';
        } else {
            const checkedCount = Array.from(getSaleCheckboxes()).filter(cb => cb.checked).length;
            if (checkedCount === 0) {
                alert('Pilih minimal satu transaksi penjualan untuk dihapus.');
                return;
            }
            title.textContent = 'Hapus Penjualan Terpilih';
            desc.innerHTML = `Apakah Anda yakin ingin menghapus <strong>${checkedCount} transaksi terpilih</strong>? Seluruh stok barang terkait akan dikembalikan ke inventaris.`;
            btnText.textContent = `Hapus ${checkedCount} Transaksi`;
        }

        modal.classList.remove('hidden');
    }

    function closeDeleteSalesModal() {
        const modal = document.getElementById('deleteSalesModal');
        if (modal) modal.classList.add('hidden');
    }

    function submitDeleteSalesForm() {
        if (singleSaleDeleteUrl) {
            const form = document.getElementById('singleDeleteSaleForm');
            form.action = singleSaleDeleteUrl;
            form.submit();
        } else {
            const form = document.getElementById('bulkDeleteSalesForm');
            const deleteAllInput = document.getElementById('deleteAllSalesInput');
            const container = document.getElementById('bulkDeleteSalesContainer');
            container.innerHTML = '';

            if (isDeletingAllSales) {
                deleteAllInput.value = '1';
            } else {
                deleteAllInput.value = '0';
                const checked = Array.from(getSaleCheckboxes()).filter(cb => cb.checked);
                checked.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = cb.value;
                    container.appendChild(input);
                });
            }

            form.submit();
        }
    }
</script>
@endsection
