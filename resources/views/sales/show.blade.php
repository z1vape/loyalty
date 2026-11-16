@extends('layouts.app')

@section('title', 'Z1 VapeOS | Detail Transaksi #' . $sale->reference_number)

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.index') }}" 
               class="p-2 rounded-xl bg-surface-container hover:bg-surface-container-high transition-colors text-on-surface flex items-center justify-center shrink-0"
               title="Kembali ke Sales History">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
            </a>
            <div>
                <h1 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2.5">
                        <span>#{{ $sale->reference_number }}</span>
                        @if(($sale->status ?? 'completed') === 'cancelled')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/10 text-rose-700 border border-rose-500/20" title="{{ $sale->cancellation_reason ?: 'Dibatalkan' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                            Dibatalkan
                        </span>
                        @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-700 border border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                            Selesai (Paid)
                        </span>
                        @endif
                    </h1>
                    <div class="flex items-center gap-2 text-xs text-on-surface-variant mt-0.5">
                        <span class="material-symbols-outlined text-xs">calendar_today</span>
                        <span>{{ ($sale->sale_date ?? $sale->created_at)->format('d F Y') . ($sale->created_at ? ', ' . $sale->created_at->format('H:i') . ' WIB' : '') }}</span>
                        <span>•</span>
                        <span class="material-symbols-outlined text-xs">person</span>
                        <span>Kasir: <strong id="header-cashier-name" class="text-on-surface">{{ $sale->user?->name ?? 'Kasir Staff' }}</strong></span>
                        <span>•</span>
                        <span class="material-symbols-outlined text-xs">storefront</span>
                        <span>{{ $sale->store?->name ?? 'Toko Pusat' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
            <button type="button" 
                    id="btn-edit-cashier-header"
                    onclick="openChangeCashierModal('{{ $sale->id }}', '{{ $sale->reference_number }}', '{{ $sale->user_id }}', '{{ addslashes($sale->user?->name ?? 'Kasir Staff') }}')" 
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-bold transition-all shadow-sm cursor-pointer active:scale-95">
                <span class="material-symbols-outlined text-base text-primary">manage_accounts</span>
                <span>Ubah Kasir</span>
            </button>

            @canany(['Sales Edit', 'Sales List'])
            <button type="button" 
                    id="btn-edit-disc-header"
                    onclick="openChangeDiscountModal('{{ $sale->id }}', '{{ $sale->reference_number }}')" 
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-bold transition-all shadow-sm cursor-pointer active:scale-95">
                <span class="material-symbols-outlined text-base text-rose-600">sell</span>
                <span>Ubah Diskon</span>
            </button>

            <button type="button" 
                    id="btn-edit-pay-header"
                    onclick="openChangePaymentMethodModal('{{ $sale->id }}', '{{ $sale->reference_number }}', '{{ strtolower($sale->payment_method ?? 'cash') }}')" 
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-bold transition-all shadow-sm cursor-pointer active:scale-95">
                <span class="material-symbols-outlined text-base text-emerald-600">payments</span>
                <span>Ubah Metode Bayar</span>
            </button>
            @endcanany

            @canany(['Sales Destroy', 'Movement Destroy'])
            <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi #{{ $sale->reference_number }}? Stok produk yang terjual akan dikembalikan ke inventaris.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition-colors shadow-sm cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined text-base text-rose-600">delete</span>
                    <span>Hapus Transaksi</span>
                </button>
            </form>
            @endcanany

            <button type="button" onclick="window.print()" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container hover:bg-surface-container-high text-on-surface font-bold rounded-xl text-xs transition-colors border border-outline-variant shadow-sm cursor-pointer active:scale-95">
                <span class="material-symbols-outlined text-base">print</span>
                <span>Cetak Struk / Invoice</span>
            </button>
            <a href="{{ route('sales.index') }}" 
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs transition-all shadow-sm hover:opacity-90 active:scale-95">
                <span>Daftar Transaksi</span>
            </a>
        </div>
    </div>

    @php
        // Payment method parsing
        $payMethodKey = strtolower($sale->payment_method ?? 'cash');
        $payMethod = strtoupper($payMethodKey);
        $badgeClass = $sale->payment_badge_class;
        $paidFormatted = null;
        $changeFormatted = null;

        if ($sale->notes) {
            if (preg_match('/Uang:\s*Rp([\d\.]+)/i', $sale->notes, $matches)) {
                $paidFormatted = $matches[1];
            }
            if (preg_match('/Kembali:\s*Rp([\d\.]+)/i', $sale->notes, $matches)) {
                $changeFormatted = $matches[1];
            }
        }

        $grossSubtotal = $sale->items->sum(function($item) {
            return $item->selling_price * $item->quantity;
        });

        $totalDiscount = $sale->items->sum(function($item) {
            $disc = (float) $item->discount;
            return $disc > 0 ? $disc : max(0, ($item->quantity * $item->selling_price) - $item->subtotal);
        });
    @endphp

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 printable-sales-detail">
        
        {{-- Left Column: Items Breakdown --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- Items Table Card --}}
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-outline-variant bg-surface-container-low/50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">shopping_cart</span>
                        <h2 class="text-sm font-bold text-on-surface">Rincian Barang yang Dibeli</h2>
                    </div>
                    <span class="text-xs text-on-surface-variant font-medium">
                        {{ $sale->items->sum('quantity') }} Unit ({{ $sale->items->count() }} Jenis Produk)
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="text-[11px] text-on-surface-variant uppercase tracking-wider border-b border-outline-variant bg-surface-container-low/20">
                            <tr>
                                <th class="px-5 py-3.5 font-bold w-12 text-center">No</th>
                                <th class="px-5 py-3.5 font-bold">Produk</th>
                                <th class="px-5 py-3.5 font-bold text-right">Harga Satuan</th>
                                <th class="px-5 py-3.5 font-bold text-center">Qty</th>
                                <th class="px-5 py-3.5 font-bold text-right">Potongan Diskon</th>
                                <th class="px-5 py-3.5 font-bold text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-outline-variant">
                            @foreach($sale->items as $idx => $item)
                            @php
                                $itemGross = $item->quantity * $item->selling_price;
                                $itemDisc = (float) $item->discount > 0 
                                    ? (float) $item->discount 
                                    : max(0, $itemGross - (float) $item->subtotal);
                            @endphp
                            <tr class="hover:bg-surface-container-low/30 transition-colors">
                                <td class="px-5 py-4 text-center font-mono text-xs text-on-surface-variant">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-extrabold text-on-surface text-sm">
                                        {{ $item->product?->name ?? 'Produk Dihapus' }}
                                    </div>
                                    <div class="text-[11px] font-mono text-on-surface-variant mt-0.5 flex items-center gap-1.5">
                                        <span class="bg-surface-container-high px-1.5 py-0.5 rounded text-[10px]">
                                            SKU: {{ $item->product?->sku ?? '-' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right font-mono text-xs text-on-surface">
                                    Rp {{ number_format($item->selling_price, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4 text-center font-mono font-bold text-on-surface text-sm">
                                    {{ $item->quantity }}
                                </td>
                                <td class="px-5 py-4 text-right font-mono text-xs" id="show-item-disc-wrap-{{ $item->id }}">
                                    @if($itemDisc > 0)
                                    <span id="show-item-disc-val-{{ $item->id }}" class="inline-flex items-center text-rose-600 font-bold bg-rose-500/10 px-2 py-0.5 rounded border border-rose-500/20">
                                        -Rp {{ number_format($itemDisc, 0, ',', '.') }}
                                    </span>
                                    @else
                                    <span id="show-item-disc-val-{{ $item->id }}" class="text-on-surface-variant">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right font-mono font-extrabold text-on-surface text-sm" id="show-item-subtotal-val-{{ $item->id }}">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-surface-container-low/40 border-t border-outline-variant font-mono text-xs">
                            <tr>
                                <td colspan="3" class="px-5 py-3 text-right font-bold text-on-surface-variant uppercase">Total Barang:</td>
                                <td class="px-5 py-3 text-center font-extrabold text-on-surface">{{ $sale->items->sum('quantity') }} Unit</td>
                                <td id="show-tfoot-disc-val" class="px-5 py-3 text-right font-extrabold text-rose-600">
                                    {{ $totalDiscount > 0 ? '-Rp ' . number_format($totalDiscount, 0, ',', '.') : '-' }}
                                </td>
                                <td id="show-tfoot-total-val" class="px-5 py-3 text-right font-extrabold text-primary text-sm">
                                    Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Notes & Log Card --}}
            @if($sale->notes)
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-primary text-base">sticky_note_2</span>
                    <h3 class="text-xs font-bold text-on-surface uppercase tracking-wider">Catatan & Log Transaksi POS</h3>
                </div>
                <div id="sale-notes-box" class="p-3.5 bg-surface-container-low rounded-xl border border-outline-variant/60 font-mono text-xs text-on-surface leading-relaxed">
                    {{ $sale->notes }}
                </div>
            </div>
            @endif

        </div>

        {{-- Right Column: Summary & Info --}}
        <div class="space-y-6">

            {{-- Payment Summary Card --}}
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 shadow-sm space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-outline-variant">
                    <span class="material-symbols-outlined text-primary text-xl">payments</span>
                    <h2 class="text-sm font-bold text-on-surface">Ringkasan Pembayaran</h2>
                </div>

                <div class="space-y-2.5 text-xs">
                    {{-- Subtotal Kotor --}}
                    <div class="flex justify-between items-center text-on-surface-variant font-medium">
                        <span>Subtotal Kotor</span>
                        <span class="font-mono font-bold text-on-surface">
                            Rp {{ number_format($grossSubtotal, 0, ',', '.') }}
                        </span>
                    </div>

                    {{-- Total Diskon --}}
                    <div class="flex justify-between items-center text-on-surface-variant font-medium">
                        <span>Total Potongan Diskon</span>
                        <div class="flex items-center gap-2">
                            <span id="show-card-discount-val" class="font-mono font-bold text-rose-600">
                                {{ $totalDiscount > 0 ? '-Rp ' . number_format($totalDiscount, 0, ',', '.') : 'Rp 0' }}
                            </span>
                            @canany(['Sales Edit', 'Sales List'])
                            <button type="button"
                                    id="btn-edit-disc-card"
                                    onclick="openChangeDiscountModal('{{ $sale->id }}', '{{ $sale->reference_number }}')"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-surface-container hover:bg-rose-50 text-on-surface-variant hover:text-rose-600 border border-outline-variant transition-all cursor-pointer"
                                    title="Ubah Diskon Penjualan">
                                <span class="material-symbols-outlined text-xs">edit</span>
                                <span>Ubah</span>
                            </button>
                            @endcanany
                        </div>
                    </div>

                    {{-- Metode Bayar --}}
                    <div class="flex justify-between items-center text-on-surface-variant font-medium">
                        <span>Metode Pembayaran</span>
                        <div class="flex items-center gap-2">
                            <span id="show-pay-badge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-extrabold border {{ $badgeClass }}">
                                {{ $payMethod }}
                            </span>
                            @canany(['Sales Edit', 'Sales List'])
                            <button type="button"
                                    id="btn-edit-pay-card"
                                    onclick="openChangePaymentMethodModal('{{ $sale->id }}', '{{ $sale->reference_number }}', '{{ $payMethodKey }}')"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-surface-container hover:bg-emerald-50 text-on-surface-variant hover:text-emerald-700 border border-outline-variant transition-all cursor-pointer"
                                    title="Ubah Metode Pembayaran">
                                <span class="material-symbols-outlined text-xs">edit</span>
                                <span>Ubah</span>
                            </button>
                            @endcanany
                        </div>
                    </div>

                    {{-- Split Payment Details --}}
                    @if($payMethodKey === 'split')
                        @if(!empty($sale->payment_splits) && is_array($sale->payment_splits))
                        <div class="py-2.5 border-y border-outline-variant/60 my-1 space-y-1.5 bg-surface-container-low/50 rounded-xl px-3.5">
                            <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider block">Rincian Split Payment</span>
                            @foreach($sale->payment_splits as $sp)
                                @php
                                    $spMethod = strtolower($sp['method'] ?? 'cash');
                                    $spLabels = ['cash' => 'Tunai (Cash)', 'qris' => 'QRIS', 'transfer' => 'Transfer Bank', 'debit' => 'Kartu Debit', 'credit' => 'Kartu Kredit'];
                                    $spName = $spLabels[$spMethod] ?? strtoupper($spMethod);
                                @endphp
                                <div class="flex justify-between items-center text-xs text-on-surface">
                                    <span class="font-medium">• {{ $spName }}</span>
                                    <span class="font-mono font-bold">Rp {{ number_format($sp['amount'] ?? 0, 0, ',', '.') }}</span>
                                </div>
                                @if($spMethod === 'cash' && !empty($sp['amount_paid']) && $sp['amount_paid'] > ($sp['amount'] ?? 0))
                                <div class="flex justify-between items-center text-[10px] text-on-surface-variant pl-2">
                                    <span>Uang Diterima: Rp {{ number_format($sp['amount_paid'], 0, ',', '.') }}</span>
                                    <span class="text-emerald-600 font-semibold">Kembali: Rp {{ number_format($sp['change_amount'] ?? 0, 0, ',', '.') }}</span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @endif
                    @endif

                    {{-- Cash Paid & Change if recorded --}}
                    @if($paidFormatted)
                    <div class="flex justify-between items-center text-on-surface-variant font-medium">
                        <span>{{ $payMethodKey === 'split' ? 'Total Uang Diterima' : 'Uang Diterima (Tunai)' }}</span>
                        <span class="font-mono font-bold text-on-surface">
                            Rp {{ $paidFormatted }}
                        </span>
                    </div>
                    @endif

                    @if($changeFormatted)
                    <div class="flex justify-between items-center text-on-surface-variant font-medium">
                        <span>Kembalian</span>
                        <span class="font-mono font-bold text-emerald-600">
                            Rp {{ $changeFormatted }}
                        </span>
                    </div>
                    @endif
                </div>

                {{-- Total Net --}}
                <div class="pt-4 border-t border-outline-variant flex justify-between items-center">
                    <div>
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-on-surface-variant block">Total Bayar</span>
                        <span class="text-xs text-on-surface-variant">Lunas</span>
                    </div>
                    <span id="show-card-total-val" class="text-2xl font-black text-primary font-mono">
                        Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- Store & Customer Details Card --}}
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 shadow-sm space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-outline-variant">
                    <span class="material-symbols-outlined text-primary text-xl">info</span>
                    <h2 class="text-sm font-bold text-on-surface">Informasi Transaksi</h2>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-on-surface-variant text-[11px] font-bold uppercase tracking-wider block mb-0.5">Pelanggan</span>
                        <div class="font-extrabold text-on-surface text-sm flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm text-primary">person</span>
                            <span>{{ $sale->customer_name ?: 'Pelanggan Umum' }}</span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-outline-variant/60">
                        <div class="flex items-center justify-between mb-0.5">
                            <span class="text-on-surface-variant text-[11px] font-bold uppercase tracking-wider block">Kasir Bertugas</span>
                            <button type="button" 
                                    id="btn-edit-cashier-card"
                                    onclick="openChangeCashierModal('{{ $sale->id }}', '{{ $sale->reference_number }}', '{{ $sale->user_id }}', '{{ addslashes($sale->user?->name ?? 'Kasir Staff') }}')"
                                    class="text-xs text-primary hover:underline font-semibold inline-flex items-center gap-0.5 cursor-pointer">
                                <span class="material-symbols-outlined text-xs">edit</span> Ubah
                            </button>
                        </div>
                        <div class="font-bold text-on-surface text-xs flex items-center gap-1.5">
                            <span id="show-cashier-initial" class="w-5 h-5 rounded-md bg-primary-container text-on-primary-container text-[10px] font-black flex items-center justify-center uppercase">
                                {{ substr($sale->user?->name ?? 'K', 0, 1) }}
                            </span>
                            <span id="show-cashier-name">{{ $sale->user?->name ?? 'Kasir Staff' }}</span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-outline-variant/60">
                        <span class="text-on-surface-variant text-[11px] font-bold uppercase tracking-wider block mb-0.5">Cabang Toko</span>
                        <div class="font-bold text-on-surface text-xs flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm text-on-surface-variant">storefront</span>
                            <span>{{ $sale->store?->name ?? 'Toko Pusat' }}</span>
                        </div>
                        @if($sale->store?->address)
                        <div class="text-[11px] text-on-surface-variant mt-0.5 pl-5">
                            {{ $sale->store->address }}
                        </div>
                        @endif
                    </div>

                    <div class="pt-2 border-t border-outline-variant/60">
                        <span class="text-on-surface-variant text-[11px] font-bold uppercase tracking-wider block mb-0.5">Waktu Dicatat</span>
                        <div class="font-mono text-xs text-on-surface">
                            {{ ($sale->sale_date ?? $sale->created_at)->format('d/m/Y') . ($sale->created_at ? ' ' . $sale->created_at->format('H:i:s') : '') }}
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

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
    let currentEditSaleId = null;
    let currentEditRefNumber = null;
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

            // Update DOM elements on show page
            const showBadge = document.getElementById('show-pay-badge');
            const notesBox = document.getElementById('sale-notes-box');
            const headerBtn = document.getElementById('btn-edit-pay-header');
            const cardBtn = document.getElementById('btn-edit-pay-card');

            if (showBadge) {
                showBadge.textContent = (result.payment_method || newMethod).toUpperCase();
                showBadge.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-extrabold border ${result.badge_class}`;
            }

            if (notesBox && result.notes) {
                notesBox.textContent = result.notes;
            }

            if (headerBtn) {
                headerBtn.setAttribute('onclick', `openChangePaymentMethodModal('${currentEditPaySaleId}', '${currentEditPayRefNumber}', '${result.payment_method}')`);
            }
            if (cardBtn) {
                cardBtn.setAttribute('onclick', `openChangePaymentMethodModal('${currentEditPaySaleId}', '${currentEditPayRefNumber}', '${result.payment_method}')`);
            }

            closeChangePaymentMethodModal();
            showSalesToast(`Metode pembayaran untuk #${currentEditPayRefNumber} berhasil diubah ke ${result.payment_method_label}! Memperbarui rincian...`);
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

            // Update DOM elements on show page
            const showName = document.getElementById('show-cashier-name');
            const showInitial = document.getElementById('show-cashier-initial');
            const headerName = document.getElementById('header-cashier-name');
            const notesBox = document.getElementById('sale-notes-box');
            const headerBtn = document.getElementById('btn-edit-cashier-header');
            const cardBtn = document.getElementById('btn-edit-cashier-card');

            if (showName) showName.textContent = newCashier.name;
            if (showInitial) showInitial.textContent = newCashier.initial;
            if (headerName) headerName.textContent = newCashier.name;
            if (notesBox && result.notes) notesBox.textContent = result.notes;

            const escapedName = newCashier.name.replace(/'/g, "\\'");
            if (headerBtn) {
                headerBtn.setAttribute('onclick', `openChangeCashierModal('${currentEditSaleId}', '${currentEditRefNumber}', '${newCashier.id}', '${escapedName}')`);
            }
            if (cardBtn) {
                cardBtn.setAttribute('onclick', `openChangeCashierModal('${currentEditSaleId}', '${currentEditRefNumber}', '${newCashier.id}', '${escapedName}')`);
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

            // Update DOM on show page
            const cardTotalEl = document.getElementById('show-card-total-val');
            const cardDiscEl = document.getElementById('show-card-discount-val');
            const tfootTotalEl = document.getElementById('show-tfoot-total-val');
            const tfootDiscEl = document.getElementById('show-tfoot-disc-val');

            if (cardTotalEl) cardTotalEl.textContent = `Rp ${result.total_amount_formatted}`;
            if (tfootTotalEl) tfootTotalEl.textContent = `Rp ${result.total_amount_formatted}`;

            if (cardDiscEl) {
                cardDiscEl.textContent = result.total_discount > 0 ? `-Rp ${result.total_discount_formatted}` : 'Rp 0';
            }
            if (tfootDiscEl) {
                tfootDiscEl.textContent = result.total_discount > 0 ? `-Rp ${result.total_discount_formatted}` : '-';
            }

            // Update item rows
            (result.items || []).forEach(it => {
                const subtotalEl = document.getElementById(`show-item-subtotal-val-${it.id}`);
                const discWrap = document.getElementById(`show-item-disc-wrap-${it.id}`);

                if (subtotalEl) {
                    subtotalEl.textContent = `Rp ${it.subtotal_formatted}`;
                }

                if (discWrap) {
                    if (it.discount > 0) {
                        discWrap.innerHTML = `<span id="show-item-disc-val-${it.id}" class="inline-flex items-center text-rose-600 font-bold bg-rose-500/10 px-2 py-0.5 rounded border border-rose-500/20">-Rp ${it.discount_formatted}</span>`;
                    } else {
                        discWrap.innerHTML = `<span id="show-item-disc-val-${it.id}" class="text-on-surface-variant">-</span>`;
                    }
                }
            });

            closeChangeDiscountModal();
            showSalesToast(`Diskon untuk #${currentEditDiscRefNumber} berhasil diperbarui! Seluruh laporan & Stock Out telah disinkronkan.`);

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

    window.addEventListener('click', function(e) {
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
</script>

{{-- Print Styles --}}
<style>
@media print {
    /* Hide layout chrome */
    header, aside, nav, button, a[href], .material-symbols-outlined {
        display: none !important;
    }
    body {
        background: #ffffff !important;
        color: #000000 !important;
        font-family: monospace, sans-serif !important;
    }
    .printable-sales-detail {
        display: block !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .printable-sales-detail * {
        background: transparent !important;
        color: #000000 !important;
        box-shadow: none !important;
        border-color: #cccccc !important;
    }
}
</style>
@endsection
