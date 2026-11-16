@php
    $activeTab = 'purchase_request';
@endphp
@extends('layouts.z1_employee')

@section('title', 'Detail Order Barang (Purchase Request) - ' . $pr->pr_number)

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb / Back button --}}
    <div>
        <a href="{{ route('z1-app.index', ['tab' => 'purchase_request']) }}" class="inline-flex items-center gap-2 text-xs font-bold text-on-surface-variant hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            <span>Kembali ke Daftar Order Barang</span>
        </a>
    </div>

    {{-- Header Section (Persis screenshot) --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <!-- Title & Status Badge -->
            <div class="flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-2xl text-primary">calendar_today</span>
                    <h1 class="font-headline-lg text-xl sm:text-2xl font-black text-on-surface">
                        {{ \Carbon\Carbon::parse($pr->request_date)->isoFormat('D MMMM Y') }}
                    </h1>
                </div>
                <span class="px-2.5 py-1 rounded-md text-xs font-black bg-emerald-500/10 text-emerald-800 border border-emerald-500/20 flex items-center gap-1 uppercase tracking-wider">
                    <span class="material-symbols-outlined text-xs">south_west</span>
                    ORDER BARANG
                </span>
                <span class="px-2.5 py-1 rounded-md text-xs font-bold border {{ $pr->status_badge }} uppercase">
                    {{ $pr->status }}
                </span>
            </div>

            <!-- Subtitle Info -->
            <div class="flex items-center gap-3 text-xs text-on-surface-variant mt-2 flex-wrap font-medium">
                <span class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">person</span>
                    <span>Dibuat oleh: <strong>{{ $pr->user?->name ?? 'Staff Toko' }}</strong></span>
                </span>
                <span>•</span>
                <span class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">schedule</span>
                    <span>{{ $pr->created_at ? $pr->created_at->isoFormat('D MMM Y, HH:mm') . ' WIB' : '-' }}</span>
                </span>
            </div>

            <!-- Metadata Badges -->
            <div class="flex items-center gap-2 mt-3 flex-wrap text-xs">
                <!-- Cabang Toko -->
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-semibold bg-surface-container-low text-on-surface border border-outline-variant shadow-xs">
                    <span class="material-symbols-outlined text-sm text-secondary">storefront</span>
                    <span>Cabang: <strong>{{ $pr->store?->name ?? 'Toko Utama' }}</strong></span>
                </span>

                <!-- Nomor PR -->
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-semibold bg-surface-container-low text-on-surface border border-outline-variant shadow-xs">
                    <span class="material-symbols-outlined text-sm text-primary">tag</span>
                    <span>Nomor: <strong class="font-mono">{{ $pr->pr_number }}</strong></span>
                </span>

                <!-- Tingkat Urgensi -->
                @if($pr->priority === 'urgent')
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-bold bg-rose-500/10 text-rose-700 border border-rose-500/20 shadow-xs">
                    <span class="material-symbols-outlined text-sm text-rose-600">bolt</span>
                    <span>Urgensi: <strong class="uppercase">Mendesak</strong></span>
                </span>
                @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-semibold bg-emerald-500/10 text-emerald-800 border border-emerald-500/20 shadow-xs">
                    <span class="material-symbols-outlined text-sm text-emerald-600">schedule</span>
                    <span>Urgensi: <strong>Normal</strong></span>
                </span>
                @endif

                @if($pr->notes)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-semibold bg-surface-container-low text-on-surface border border-outline-variant shadow-xs italic" title="{{ $pr->notes }}">
                    <span class="material-symbols-outlined text-sm text-secondary">edit_note</span>
                    <span class="truncate max-w-[250px]">Catatan: "{{ $pr->notes }}"</span>
                </span>
                @endif
            </div>
        </div>

        <!-- Action Buttons (Right) -->
        <div class="flex items-center gap-2 w-full md:w-auto">
            {{-- Tambah Barang Button --}}
            @if($pr->status !== 'completed' && $pr->status !== 'approved')
            <button
                type="button"
                onclick="document.getElementById('modal-add-item-to-pr').showModal()"
                class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-[#00553a] text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-95 active:scale-95 transition-all shadow-xs cursor-pointer border-0"
            >
                <span class="material-symbols-outlined text-base">add</span>
                <span>Tambah Barang</span>
            </button>
            @endif

            <!-- Revisi ke Pending Button (Khusus Super Admin jika status Approved) -->
            @if($pr->status === 'approved' && $isSuperAdmin)
                <form action="{{ route('purchasing.orders.update-status', $pr->id) }}" method="POST" onsubmit="return confirm('Kembalikan status order #{{ $pr->pr_number }} ke Pending untuk revisi barang/order?');">
                    @csrf
                    <input type="hidden" name="status" value="pending">
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-900 text-xs font-bold transition-all shadow-xs cursor-pointer" title="Revisi ke Pending">
                        <span class="material-symbols-outlined text-base text-amber-700">restart_alt</span>
                        <span>Revisi ke Pending</span>
                    </button>
                </form>
            @endif

            {{-- Hapus Order Button (Hanya jika status belum approved & belum completed) --}}
            @if(!in_array($pr->status, ['approved', 'completed']))
                @if(($pr->status === 'pending' && ($totalUnits == 0 || $totalItemsCount == 0)) || $isSuperAdmin || $pr->user_id === auth()->id())
                <form action="{{ route('z1-app.purchase-request.destroy', $pr->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan order #{{ $pr->pr_number }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition-all shadow-xs cursor-pointer" title="Hapus Order">
                        <span class="material-symbols-outlined text-base">delete</span>
                        <span>Hapus Order</span>
                    </button>
                </form>
                @endif
            @endif
        </div>
    </div>

    {{-- Metric Cards (3 Cards persis screenshot) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <!-- Card 1: Total Logs -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-surface-container-high text-on-surface-variant flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl text-primary">receipt_long</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Item</p>
                <h3 class="text-xl font-headline-md font-extrabold text-on-surface mt-0.5 truncate">{{ $totalItemsCount }} Item</h3>
                <span class="text-[10px] text-on-surface-variant block mt-0.5">Seluruh item dalam order ini</span>
            </div>
        </div>

        <!-- Card 2: Total Harga -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Estimasi Nilai</p>
                    <span class="text-[9px] font-bold text-on-surface-variant bg-surface-container-high px-2 py-0.5 rounded-full">Semua Data</span>
                </div>
                <h3 class="text-xl font-headline-md font-extrabold text-emerald-800 font-mono mt-0.5 truncate">
                    Rp {{ number_format($totalAmount, 0, ',', '.') }}
                </h3>
                <span class="text-[10px] text-on-surface-variant block mt-0.5">Akumulasi estimasi nilai order</span>
            </div>
        </div>

        <!-- Card 3: Total Unit Masuk / Dipesan -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-secondary-container/40 text-on-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">inventory_2</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Unit Dipesan</p>
                <h3 class="text-xl font-headline-md font-extrabold text-on-surface mt-0.5 truncate">{{ number_format($totalUnits) }} pcs</h3>
                <span class="text-[10px] text-on-surface-variant block mt-0.5">Akumulasi kuantitas request</span>
            </div>
        </div>
    </div>

    {{-- Filter Product Box (Persis screenshot) --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-3 sm:p-4 shadow-xs flex items-center justify-between gap-3 sm:gap-4">
        <div class="flex items-center gap-2 sm:gap-3 flex-1 max-w-md">
            <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant whitespace-nowrap hidden sm:inline">Filter Product:</span>
            <div class="relative flex-1">
                <input
                    type="text"
                    id="table-filter-product"
                    placeholder="Cari nama produk / SKU..."
                    class="w-full pl-8 sm:pl-9 pr-8 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                    oninput="filterTableProducts(this.value)"
                >
                <span class="material-symbols-outlined text-base text-on-surface-variant absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none">search</span>
            </div>
        </div>

        <button
            type="button"
            onclick="clearProductFilter()"
            class="text-xs font-bold text-red-600 hover:text-red-700 cursor-pointer shrink-0"
        >
            Clear
        </button>
    </div>

    {{-- Items Listing (Desktop: Table, Mobile: Cards) --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs overflow-hidden">
        <!-- Desktop Table (hidden on mobile) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse" id="pr-items-table">
                <thead class="bg-surface-container-low border-b border-outline-variant/60 text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                    <tr>
                        <th class="py-3 px-4 w-10 text-center">
                            <input type="checkbox" disabled class="rounded border-outline-variant text-primary focus:ring-primary/20">
                        </th>
                        <th class="py-3 px-4">Ref No.</th>
                        <th class="py-3 px-4">Item / SKU</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Quantity</th>
                        <th class="py-3 px-4 text-right">Harga Beli</th>
                        <th class="py-3 px-4 text-right">Total Harga</th>
                        <th class="py-3 px-4">Created By</th>
                        <th class="py-3 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60 font-medium" id="pr-items-tbody">
                    @forelse($pr->items as $item)
                    @php
                        $buyPrice = (float) $item->item_price;
                        $subtotal = $buyPrice * $item->quantity_requested;
                    @endphp
                    <tr class="pr-item-table-row hover:bg-surface-container-low/70 transition-colors" data-product-name="{{ strtolower($item->item_name) }}" data-sku="{{ strtolower($item->item_sku) }}">
                        <td class="py-3.5 px-4 text-center">
                            <input type="checkbox" class="rounded border-outline-variant text-primary focus:ring-primary/20">
                        </td>

                        <!-- Ref No -->
                        <td class="py-3.5 px-4 font-mono font-bold text-on-surface">
                            #TXN-{{ $item->id }}
                        </td>

                        <!-- Item / SKU -->
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-on-surface">{{ $item->item_name }}</span>
                                @if($item->product)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-800 border border-emerald-500/20">
                                        Stok Toko: {{ $item->product->stock ?? 0 }} pcs
                                    </span>
                                @else
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-800 border border-amber-500/20 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[12px] text-amber-600">edit_note</span>
                                        Manual (Non-Inventory)
                                    </span>
                                @endif
                            </div>
                            <span class="text-[10px] font-mono text-on-surface-variant block mt-0.5">
                                SKU: {{ $item->item_sku }}
                            </span>
                        </td>

                        <!-- Status Badge (e.g. DRAFT / PENDING) -->
                        <td class="py-3.5 px-4 text-center">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $pr->status_badge }} uppercase">
                                {{ $pr->status }}
                            </span>
                        </td>

                        <!-- Quantity -->
                        <td class="py-3.5 px-4 text-center font-bold text-primary font-mono text-xs">
                            +{{ $item->quantity_requested }} units
                        </td>

                        <!-- Harga Beli -->
                        <td class="py-3.5 px-4 text-right font-mono text-on-surface">
                            Rp {{ number_format($buyPrice, 0, ',', '.') }}
                        </td>

                        <!-- Total Harga -->
                        <td class="py-3.5 px-4 text-right font-mono font-bold text-primary">
                            Rp {{ number_format($subtotal, 0, ',', '.') }}
                        </td>

                        <!-- Created By -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-[#006c49] text-white flex items-center justify-center font-bold text-[10px]">
                                    {{ strtoupper(substr($pr->user?->name ?? 'Staff', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-bold text-on-surface text-xs block truncate">{{ $pr->user?->name ?? 'Staff Toko' }}</span>
                                    <span class="text-[10px] text-on-surface-variant block">
                                        {{ $pr->created_at ? $pr->created_at->format('H:i') . ' WIB' : '—' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Action -->
                        <td class="py-3.5 px-4 text-center">
                            @if(!in_array($pr->status, ['approved', 'completed']))
                            <form action="{{ route('z1-app.purchase-request.destroy-item', $item->id) }}" method="POST" onsubmit="return confirm('Hapus barang {{ addslashes($item->item_name) }} dari order ini?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 rounded text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors cursor-pointer" title="Hapus Item">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </form>
                            @else
                            <span class="text-on-surface-variant text-[11px]">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr id="empty-table-row">
                        <td colspan="9" class="text-center py-12 text-on-surface-variant text-xs">
                            <span class="material-symbols-outlined text-3xl text-outline-variant block mb-1">inventory_2</span>
                            <span>Belum ada barang di dalam order ini. Silakan klik tombol <strong>+ Tambah Barang</strong>.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <!-- Table Footer (Persis bar biru bawah di screenshot) -->
                <tfoot class="bg-surface-container-low border-t border-outline-variant/60 font-bold text-xs">
                    <tr>
                        <td colspan="7" class="py-3 px-4 text-right font-bold uppercase text-on-surface-variant">
                            TOTAL KESELURUHAN ({{ $totalItemsCount }} ITEM • SEMUA HALAMAN):
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-black text-emerald-800 text-sm">
                            Rp {{ number_format($totalAmount, 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Mobile Cards List (hidden on desktop) -->
        <div class="block md:hidden p-3 space-y-3" id="pr-items-mobile-list">
            @forelse($pr->items as $item)
            @php
                $buyPrice = (float) $item->item_price;
                $subtotal = $buyPrice * $item->quantity_requested;
            @endphp
            <div class="pr-item-mobile-card bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 shadow-xs space-y-3" data-product-name="{{ strtolower($item->item_name) }}" data-sku="{{ strtolower($item->item_sku) }}">
                <!-- Top Row: Name, SKU, Type Badge, Delete -->
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <h4 class="font-extrabold text-xs sm:text-sm text-on-surface leading-snug">{{ $item->item_name }}</h4>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <span class="font-mono text-[10px] text-on-surface-variant">SKU: {{ $item->item_sku }}</span>
                            @if($item->product)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-800 border border-emerald-500/20">
                                    Stok: {{ $item->product->stock ?? 0 }} pcs
                                </span>
                            @else
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-800 border border-amber-500/20">
                                    Manual
                                </span>
                            @endif
                        </div>
                    </div>

                    @if(!in_array($pr->status, ['approved', 'completed']))
                    <form action="{{ route('z1-app.purchase-request.destroy-item', $item->id) }}" method="POST" onsubmit="return confirm('Hapus barang {{ addslashes($item->item_name) }} dari order ini?');" class="shrink-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-1.5 rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors cursor-pointer" title="Hapus Item">
                            <span class="material-symbols-outlined text-base">delete</span>
                        </button>
                    </form>
                    @endif
                </div>

                <!-- Middle: Stats Box -->
                <div class="grid grid-cols-3 gap-2 p-2.5 rounded-xl bg-surface-container-low border border-outline-variant/60 text-xs">
                    <div>
                        <span class="text-[10px] text-on-surface-variant block">Kuantitas</span>
                        <span class="font-black font-mono text-primary text-xs sm:text-sm">+{{ $item->quantity_requested }} pcs</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-on-surface-variant block">Harga Beli</span>
                        <span class="font-mono font-semibold text-on-surface text-[11px] sm:text-xs">Rp {{ number_format($buyPrice, 0, ',', '.') }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-on-surface-variant block">Subtotal</span>
                        <span class="font-mono font-black text-emerald-800 text-xs sm:text-sm">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Bottom: Ref & Created by -->
                <div class="flex items-center justify-between text-[11px] text-on-surface-variant pt-2 border-t border-outline-variant/50">
                    <span class="font-mono font-bold text-on-surface">#TXN-{{ $item->id }}</span>
                    <div class="flex items-center gap-1.5 truncate">
                        <span class="material-symbols-outlined text-xs">person</span>
                        <span class="truncate max-w-[120px]">{{ $pr->user?->name ?? 'Staff' }}</span>
                        <span>•</span>
                        <span>{{ $pr->created_at ? $pr->created_at->format('H:i') : '' }}</span>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-10 text-on-surface-variant text-xs space-y-2">
                <span class="material-symbols-outlined text-3xl text-outline-variant block">inventory_2</span>
                <p class="font-bold">Belum ada barang di dalam order ini.</p>
                @if(!in_array($pr->status, ['approved', 'completed']))
                <button type="button" onclick="document.getElementById('modal-add-item-to-pr').showModal()" class="text-xs text-primary font-bold hover:underline cursor-pointer">
                    + Tambah Barang Sekarang
                </button>
                @endif
            </div>
            @endforelse

            <!-- Mobile Summary Footer -->
            @if($pr->items->isNotEmpty())
            <div class="p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/70 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">Total Keseluruhan</span>
                    <span class="text-xs font-bold text-on-surface">{{ $totalItemsCount }} Item ({{ $totalUnits }} Pcs)</span>
                </div>
                <div class="text-right font-mono font-black text-emerald-800 text-base">
                    Rp {{ number_format($totalAmount, 0, ',', '.') }}
                </div>
            </div>
            @endif
        </div>

        <!-- Approval / Action Buttons at bottom of table -->
        @if($pr->status === 'pending')
        <div class="p-4 sm:p-5 bg-surface-container-low/70 border-t border-outline-variant/70 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-base">pending_actions</span>
                </span>
                <div>
                    <h4 class="font-bold text-xs text-on-surface">Persetujuan Order Barang</h4>
                    <p class="text-[11px] text-on-surface-variant">Pastikan rincian item sudah sesuai sebelum melakukan persetujuan.</p>
                </div>
            </div>
            @if($isSuperAdmin)
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <!-- Reject Button (Modal Trigger) -->
                <button 
                    type="button" 
                    onclick="document.getElementById('modal-reject-pr-z1').showModal()"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition-all shadow-xs cursor-pointer"
                >
                    <span class="material-symbols-outlined text-base">cancel</span>
                    <span>Tolak</span>
                </button>

                <!-- Approve Button -->
                <form action="{{ route('purchasing.orders.update-status', $pr->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin MENYETUJUI order restock ini?');">
                    @csrf
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-xs cursor-pointer">
                        <span class="material-symbols-outlined text-base">check_circle</span>
                        <span>Setujui Order</span>
                    </button>
                </form>
            </div>
            @else
            <div class="text-[11px] text-on-surface-variant italic font-medium">
                Persetujuan order ini hanya dapat dilakukan oleh role Super Admin.
            </div>
            @endif
        </div>
        @elseif($pr->status === 'approved')
        <div class="p-4 sm:p-5 bg-surface-container-low/70 border-t border-outline-variant/70 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-base">task_alt</span>
                </span>
                <div>
                    <h4 class="font-bold text-xs text-on-surface">Order Telah Disetujui</h4>
                    <p class="text-[11px] text-on-surface-variant">Order ini telah disetujui dan siap diproses ke Purchase Order (PO Supplier).</p>
                </div>
            </div>
            @if($isSuperAdmin)
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <form action="{{ route('purchasing.orders.update-status', $pr->id) }}" method="POST" onsubmit="return confirm('Kembalikan status order ini ke Pending untuk revisi barang/order?');">
                    @csrf
                    <input type="hidden" name="status" value="pending">
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-900 text-xs font-bold transition-all shadow-xs cursor-pointer" title="Revisi ke Pending">
                        <span class="material-symbols-outlined text-base text-amber-700">restart_alt</span>
                        <span>Revisi ke Pending</span>
                    </button>
                </form>
            </div>
            @endif
        </div>
        @endif
    </div>

    {{-- MODAL REJECT ORDER --}}
    <dialog id="modal-reject-pr-z1" class="rounded-2xl p-0 bg-surface-container-lowest border border-outline-variant shadow-2xl backdrop:bg-black/40 backdrop:backdrop-blur-xs w-[94vw] max-w-md m-auto">
        <div class="flex flex-col">
            <div class="p-4 sm:p-5 border-b border-outline-variant/60 flex items-center justify-between">
                <div class="flex items-center gap-2 text-rose-600 font-black text-sm">
                    <span class="material-symbols-outlined text-xl">warning</span>
                    <span>Tolak Pengajuan Order Barang</span>
                </div>
                <button type="button" onclick="document.getElementById('modal-reject-pr-z1').close()" class="text-on-surface-variant hover:text-on-surface cursor-pointer">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form action="{{ route('purchasing.orders.update-status', $pr->id) }}" method="POST" class="p-4 sm:p-5 space-y-4">
                @csrf
                <input type="hidden" name="status" value="rejected">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Alasan Penolakan / Catatan Admin:</label>
                    <textarea name="admin_notes" required rows="3" placeholder="Sebutkan alasan penolakan agar staff toko mengetahui tindak lanjutnya..." class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-rose-500 resize-none"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-outline-variant/60">
                    <button type="button" onclick="document.getElementById('modal-reject-pr-z1').close()" class="px-4 py-2 rounded-xl border border-outline-variant text-xs font-bold text-on-surface-variant hover:bg-surface-container cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs cursor-pointer">
                        Konfirmasi Tolak Order
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- MODAL: TAMBAH BARANG KE ORDER INI --}}
    <dialog id="modal-add-item-to-pr" class="rounded-2xl p-0 bg-surface-container-lowest border border-outline-variant shadow-2xl backdrop:bg-black/40 backdrop:backdrop-blur-xs w-[94vw] max-w-md m-auto">
        <div class="flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between p-4 sm:p-5 border-b border-outline-variant/60">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">add_shopping_cart</span>
                    </span>
                    <div>
                        <h3 class="font-black text-sm sm:text-base text-on-surface">Tambah Barang ke Order</h3>
                        <p class="text-[11px] sm:text-xs text-on-surface-variant">{{ $pr->pr_number }}</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modal-add-item-to-pr').close()" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low cursor-pointer">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form id="form-add-item-pr" action="{{ route('z1-app.purchase-request.add-item', $pr->id) }}" method="POST" onsubmit="return validateAddItemForm(event)" class="p-4 sm:p-5 space-y-4 text-xs overflow-y-auto">
                @csrf

                <!-- COMBOBOX PRODUK & MANUAL ITEM -->
                <div class="relative" id="combobox-container">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-on-surface">Pilih / Ketik Produk:</label>
                        <span class="text-[10px] text-on-surface-variant">Ketik untuk mencari atau manual</span>
                    </div>

                    <!-- Search Input Field -->
                    <div class="relative">
                        <input
                            type="text"
                            id="combobox-search-input"
                            placeholder="Cari di inventory atau ketik barang baru..."
                            autocomplete="off"
                            class="w-full pl-9 pr-8 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary"
                            oninput="onComboboxInput(this.value)"
                            onfocus="openComboboxDropdown()"
                        />
                        <span class="material-symbols-outlined text-base text-on-surface-variant absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none">search</span>
                        <button type="button" onclick="clearComboboxSelection()" id="combobox-clear-btn" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface cursor-pointer p-0.5">
                            <span class="material-symbols-outlined text-base">close</span>
                        </button>
                    </div>

                    <!-- Hidden Inputs for Submission -->
                    <input type="hidden" name="product_id" id="modal-product-id" value="">
                    <input type="hidden" name="custom_item_name" id="modal-custom-item-name" value="">

                    <!-- Active Selection Banner -->
                    <div id="combobox-selected-badge" class="mt-2 hidden p-2.5 rounded-xl border flex items-center justify-between text-xs transition-all">
                        <div class="flex items-center gap-2 min-w-0">
                            <div id="combobox-badge-icon-box" class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-base" id="combobox-badge-icon">check_circle</span>
                            </div>
                            <div class="min-w-0">
                                <span class="font-extrabold text-xs block truncate text-on-surface" id="combobox-badge-title"></span>
                                <span class="text-[10px] block" id="combobox-badge-subtitle"></span>
                            </div>
                        </div>
                        <button type="button" onclick="clearComboboxSelection()" class="text-[11px] font-bold text-on-surface-variant hover:text-rose-600 underline ml-2 shrink-0 cursor-pointer">
                            Ganti
                        </button>
                    </div>

                    <!-- Dropdown List of Options -->
                    <div id="combobox-dropdown" class="absolute left-0 right-0 top-full mt-1.5 max-h-56 overflow-y-auto bg-surface-container-lowest border border-outline-variant rounded-xl shadow-2xl z-50 hidden divide-y divide-outline-variant/40 custom-slim-scroll">
                        
                        <!-- Manual Typing Option (TIDAK Menambah ke Master Inventory) -->
                        <div id="combobox-manual-option" class="hidden p-3 hover:bg-amber-500/10 bg-amber-500/5 cursor-pointer transition-colors" onclick="chooseManualOption()">
                            <div class="flex items-start gap-2.5 text-amber-900">
                                <div class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
                                    <span class="material-symbols-outlined text-base">edit_note</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-xs text-amber-950 flex items-center gap-1.5">
                                        <span>Gunakan Barang Manual:</span>
                                        <span id="combobox-manual-preview" class="underline truncate font-black text-primary"></span>
                                    </div>
                                    <span class="text-[10px] text-amber-800/90 block mt-0.5">
                                        Item non-inventory (tidak ada di list dan <strong>tidak akan menambahkan otomatis ke master inventory</strong>).
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Store Products List -->
                        <div id="combobox-products-list" class="divide-y divide-outline-variant/30">
                            @foreach($storeProducts as $p)
                            <div
                                class="combobox-product-row p-2.5 hover:bg-surface-container-low cursor-pointer transition-colors flex items-center justify-between gap-2"
                                data-id="{{ $p->id }}"
                                data-name="{{ strtolower($p->name) }}"
                                data-sku="{{ strtolower($p->sku ?? '') }}"
                                data-display-name="{{ $p->name }}"
                                data-display-sku="{{ $p->sku ?? '-' }}"
                                data-stock="{{ $p->stock ?? 0 }}"
                                data-price="{{ $p->purchase_price ?? $p->price ?? 0 }}"
                                onclick="chooseProductOption({{ $p->id }}, '{{ addslashes($p->name) }}', '{{ $p->sku ?? '-' }}', {{ $p->stock ?? 0 }}, {{ $p->purchase_price ?? $p->price ?? 0 }})"
                            >
                                <div class="min-w-0">
                                    <span class="font-bold text-xs text-on-surface block truncate">{{ $p->name }}</span>
                                    <span class="text-[10px] text-on-surface-variant block">SKU: {{ $p->sku ?? '-' }} • Stok Toko: {{ $p->stock ?? 0 }} pcs</span>
                                </div>
                                <span class="font-mono font-bold text-xs text-primary shrink-0">
                                    Rp {{ number_format($p->purchase_price ?? $p->price ?? 0, 0, ',', '.') }}
                                </span>
                            </div>
                            @endforeach
                        </div>

                        <div id="combobox-no-product-matched" class="hidden p-3 text-center text-xs text-on-surface-variant font-medium">
                            Tidak ada produk inventory yang cocok dengan kata kunci ini.
                        </div>
                    </div>
                </div>

                <!-- Input Estimasi Harga Beli (Muncul jika item manual) -->
                <div id="manual-price-wrapper" class="hidden p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-amber-900">Estimasi Harga Beli per Unit (Rp):</label>
                        <span class="text-[10px] text-amber-800">Item Non-Inventory</span>
                    </div>
                    <input
                        type="number"
                        min="0"
                        name="estimated_price"
                        id="modal-add-estimated-price"
                        value="0"
                        placeholder="Contoh: 85000"
                        oninput="updateAddModalPrice()"
                        class="w-full px-3 py-2 bg-surface-container-lowest border border-amber-500/30 rounded-lg text-xs font-mono font-bold text-on-surface focus:outline-none focus:border-primary"
                    />
                    <p class="text-[10px] text-amber-800/80">Masukkan perkiraan harga beli satuan untuk perhitungan total order.</p>
                </div>

                <!-- QTY & SUBTOTAL -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Jumlah (Qty):</label>
                        <input type="number" min="1" name="quantity_requested" id="modal-add-qty" required value="1" oninput="updateAddModalPrice()" class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-bold text-on-surface focus:outline-none focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Subtotal Estimasi:</label>
                        <div id="modal-add-subtotal" class="px-3 py-2.5 bg-surface-container-high rounded-xl text-xs font-mono font-bold text-emerald-800 flex items-center">
                            Rp 0
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Catatan Khusus Barang (Opsional):</label>
                    <input type="text" name="notes" placeholder="Contoh: Tolong pilih batch expired terbaru" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary" />
                </div>

                <div class="pt-3 border-t border-outline-variant/60 flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-add-item-to-pr').close()" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-bold text-on-surface hover:bg-surface-container-low cursor-pointer text-center">
                        Batal
                    </button>
                    <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-primary hover:bg-[#00553a] text-on-primary font-bold text-xs rounded-xl shadow-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-sm">save</span>
                        <span>Simpan Barang</span>
                    </button>
                </div>
            </form>
        </div>
    </dialog>

</div>

<script>
    let currentSelectedPrice = 0;
    let isCurrentSelectionManual = false;

    function openComboboxDropdown() {
        const dropdown = document.getElementById('combobox-dropdown');
        if (dropdown) dropdown.classList.remove('hidden');
    }

    function closeComboboxDropdown() {
        const dropdown = document.getElementById('combobox-dropdown');
        if (dropdown) dropdown.classList.add('hidden');
    }

    // Close combobox when clicking outside
    document.addEventListener('click', function(e) {
        const container = document.getElementById('combobox-container');
        if (container && !container.contains(e.target)) {
            closeComboboxDropdown();
        }
    });

    function onComboboxInput(val) {
        const query = (val || '').trim().toLowerCase();
        const clearBtn = document.getElementById('combobox-clear-btn');
        if (clearBtn) {
            clearBtn.classList.toggle('hidden', !val);
        }

        openComboboxDropdown();

        // 1. Filter existing products
        const rows = document.querySelectorAll('.combobox-product-row');
        let matchCount = 0;
        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const sku = row.getAttribute('data-sku') || '';
            if (!query || name.includes(query) || sku.includes(query)) {
                row.classList.remove('hidden');
                matchCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        const noMatchEl = document.getElementById('combobox-no-product-matched');
        if (noMatchEl) {
            noMatchEl.classList.toggle('hidden', matchCount > 0);
        }

        // 2. Manual option preview
        const manualOption = document.getElementById('combobox-manual-option');
        const manualPreview = document.getElementById('combobox-manual-preview');
        if (val.trim().length > 0) {
            manualOption.classList.remove('hidden');
            manualPreview.textContent = val.trim();
        } else {
            manualOption.classList.add('hidden');
        }

        // Auto update hidden custom item name if user is typing manual
        if (isCurrentSelectionManual || !document.getElementById('modal-product-id').value) {
            document.getElementById('modal-custom-item-name').value = val.trim();
        }
    }

    function chooseProductOption(id, name, sku, stock, price) {
        document.getElementById('modal-product-id').value = id;
        document.getElementById('modal-custom-item-name').value = '';
        document.getElementById('combobox-search-input').value = name;

        isCurrentSelectionManual = false;
        currentSelectedPrice = parseFloat(price) || 0;

        // Hide manual price field
        document.getElementById('manual-price-wrapper').classList.add('hidden');
        document.getElementById('modal-add-estimated-price').value = '0';

        // Show selection badge
        const badge = document.getElementById('combobox-selected-badge');
        const iconBox = document.getElementById('combobox-badge-icon-box');
        const icon = document.getElementById('combobox-badge-icon');
        const title = document.getElementById('combobox-badge-title');
        const subtitle = document.getElementById('combobox-badge-subtitle');

        badge.className = 'mt-2 p-2.5 rounded-xl border flex items-center justify-between text-xs bg-emerald-500/10 text-emerald-900 border-emerald-500/20';
        iconBox.className = 'w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-800 flex items-center justify-center shrink-0';
        icon.textContent = 'inventory_2';
        title.textContent = name;
        subtitle.textContent = `Produk Inventory • SKU: ${sku} • Stok Toko: ${stock} pcs • Rp ${Number(price).toLocaleString('id-ID')}`;
        badge.classList.remove('hidden');

        closeComboboxDropdown();
        updateAddModalPrice();
    }

    function chooseManualOption() {
        const val = document.getElementById('combobox-search-input').value.trim();
        if (!val) return;

        document.getElementById('modal-product-id').value = '';
        document.getElementById('modal-custom-item-name').value = val;

        isCurrentSelectionManual = true;

        // Show manual price field
        document.getElementById('manual-price-wrapper').classList.remove('hidden');

        // Show selection badge
        const badge = document.getElementById('combobox-selected-badge');
        const iconBox = document.getElementById('combobox-badge-icon-box');
        const icon = document.getElementById('combobox-badge-icon');
        const title = document.getElementById('combobox-badge-title');
        const subtitle = document.getElementById('combobox-badge-subtitle');

        badge.className = 'mt-2 p-2.5 rounded-xl border flex items-center justify-between text-xs bg-amber-500/10 text-amber-900 border-amber-500/20';
        iconBox.className = 'w-7 h-7 rounded-lg bg-amber-500/20 text-amber-800 flex items-center justify-center shrink-0';
        icon.textContent = 'edit_note';
        title.textContent = val;
        subtitle.textContent = 'Item Manual (Non-Inventory - Tidak menambahkan otomatis ke inventory)';
        badge.classList.remove('hidden');

        closeComboboxDropdown();
        updateAddModalPrice();
    }

    function clearComboboxSelection() {
        document.getElementById('modal-product-id').value = '';
        document.getElementById('modal-custom-item-name').value = '';
        document.getElementById('combobox-search-input').value = '';

        const clearBtn = document.getElementById('combobox-clear-btn');
        if (clearBtn) clearBtn.classList.add('hidden');

        const badge = document.getElementById('combobox-selected-badge');
        if (badge) badge.classList.add('hidden');

        const manualPriceWrapper = document.getElementById('manual-price-wrapper');
        if (manualPriceWrapper) manualPriceWrapper.classList.add('hidden');
        document.getElementById('modal-add-estimated-price').value = '0';

        isCurrentSelectionManual = false;
        currentSelectedPrice = 0;

        // Reset product rows
        const rows = document.querySelectorAll('.combobox-product-row');
        rows.forEach(r => r.classList.remove('hidden'));
        document.getElementById('combobox-no-product-matched').classList.add('hidden');
        document.getElementById('combobox-manual-option').classList.add('hidden');

        updateAddModalPrice();
    }

    function updateAddModalPrice() {
        const qtyInput = document.getElementById('modal-add-qty');
        const subtotalEl = document.getElementById('modal-add-subtotal');
        const qty = qtyInput ? parseInt(qtyInput.value || 0) : 0;

        let unitPrice = 0;
        if (isCurrentSelectionManual) {
            const manualPriceInput = document.getElementById('modal-add-estimated-price');
            unitPrice = manualPriceInput ? parseFloat(manualPriceInput.value || 0) : 0;
        } else {
            unitPrice = currentSelectedPrice;
        }

        const subtotal = unitPrice * qty;
        if (subtotalEl) {
            subtotalEl.textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
        }
    }

    function validateAddItemForm(event) {
        const productId = document.getElementById('modal-product-id').value;
        const customName = document.getElementById('modal-custom-item-name').value;
        const typedVal = document.getElementById('combobox-search-input').value.trim();

        if (!productId && !customName && !typedVal) {
            event.preventDefault();
            alert('Silakan pilih produk dari list atau ketik nama barang manual terlebih dahulu.');
            return false;
        }

        // If user typed something but did not click the manual button, auto set it as custom item
        if (!productId && !customName && typedVal) {
            document.getElementById('modal-custom-item-name').value = typedVal;
        }

        return true;
    }

    function filterTableProducts(query) {
        const q = (query || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.pr-item-table-row');
        rows.forEach(row => {
            const name = row.getAttribute('data-product-name') || '';
            const sku = row.getAttribute('data-sku') || '';
            if (!q || name.includes(q) || sku.includes(q)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        const cards = document.querySelectorAll('.pr-item-mobile-card');
        cards.forEach(card => {
            const name = card.getAttribute('data-product-name') || '';
            const sku = card.getAttribute('data-sku') || '';
            if (!q || name.includes(q) || sku.includes(q)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function clearProductFilter() {
        const input = document.getElementById('table-filter-product');
        if (input) {
            input.value = '';
            filterTableProducts('');
        }
    }
</script>
@endsection
