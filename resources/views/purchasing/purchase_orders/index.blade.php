@extends('layouts.app')

@section('title', 'Z1 VapeOS | Purchase Order (PO Supplier)')

@section('content')
<div class="space-y-6">

    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container">
                    <span class="material-symbols-outlined text-sm">shopping_bag</span>
                    Modul Purchasing
                </span>
                <span class="text-xs text-on-surface-variant font-medium">Pengadaan Resmi Supplier</span>
            </div>
            <h1 class="font-headline-lg text-2xl sm:text-3xl font-black text-on-surface">Purchase Order (PO Supplier)</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">Surat pesanan pembelian resmi ke supplier / distributor sebagai kelanjutan order barang cabang.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a
                href="{{ route('purchasing.orders.index') }}"
                class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-low hover:bg-surface-container text-on-surface font-bold text-xs transition-all cursor-pointer"
            >
                <span class="material-symbols-outlined text-base">assignment</span>
                <span>Lihat Order Barang</span>
            </a>
            <a
                href="{{ route('purchasing.purchase-orders.create') }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-[#00553a] text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-95 active:scale-95 transition-all shadow-xs cursor-pointer border-0"
            >
                <span class="material-symbols-outlined text-base">add_box</span>
                <span>Buat Purchase Order</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 flex items-center justify-between gap-3 text-xs font-semibold">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-emerald-600 text-lg">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 cursor-pointer">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-800 flex items-center justify-between gap-3 text-xs font-semibold">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-rose-600 text-lg">error</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-700 hover:text-rose-900 cursor-pointer">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    {{-- Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 sm:gap-4">
        <!-- Card 1: Total PO -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-surface-container-high text-on-surface-variant flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl text-primary">receipt_long</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total PO Diterbitkan</p>
                <h3 class="text-xl sm:text-2xl font-black font-headline-md text-on-surface mt-0.5 truncate">{{ number_format($totalPoCount) }} PO</h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5">Surat pesanan ke supplier</p>
            </div>
        </div>

        <!-- Card 2: Total Nilai PO -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Nilai Pembelian</p>
                <h3 class="text-xl sm:text-2xl font-black font-headline-md text-emerald-800 font-mono mt-0.5 truncate">
                    Rp {{ number_format($totalPoAmount, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5">Total komitmen belanja supplier</p>
            </div>
        </div>

        <!-- Card 3: Total Unit Dipesan -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-secondary-container/40 text-on-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">inventory_2</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Unit Dipesan</p>
                <h3 class="text-xl sm:text-2xl font-black font-headline-md text-on-surface mt-0.5 truncate">{{ number_format($totalPoUnits) }} Pcs</h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5">Seluruh kuantitas fisik dipesan</p>
            </div>
        </div>

        <!-- Card 4: Status Tracker -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">local_shipping</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Status Pesanan</p>
                <div class="flex items-center gap-2 mt-1 flex-wrap text-[11px]">
                    <span class="font-bold text-blue-700">{{ $sentCount }} Dikirim</span> &bull;
                    <span class="font-bold text-amber-700">{{ $partialCount }} Parsial</span> &bull;
                    <span class="font-bold text-emerald-700">{{ $receivedCount }} Selesai</span>
                </div>
                <p class="text-[10px] text-on-surface-variant mt-0.5">{{ $draftCount }} Draft belum dikirim</p>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 sm:p-5 shadow-xs">
        <form method="GET" action="{{ route('purchasing.purchase-orders.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            
            <!-- Cari No. PO / Supplier -->
            <div class="sm:col-span-3">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Cari PO / Supplier</label>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search ?? '' }}" 
                    placeholder="No. PO, nama supplier, catatan..."
                    class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:border-primary"
                >
            </div>

            <!-- Lokasi Toko -->
            <div class="sm:col-span-3">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Cabang Penerima</label>
                <select name="store_id" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:border-primary">
                    <option value="all" {{ ($storeId ?? '') === 'all' ? 'selected' : '' }}>Semua Cabang Toko</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ (string)($storeId ?? '') === (string)$st->id ? 'selected' : '' }}>
                            {{ $st->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Status -->
            <div class="sm:col-span-3">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Status PO</label>
                <select name="status" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:border-primary">
                    <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="draft" {{ ($status ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent" {{ ($status ?? '') === 'sent' ? 'selected' : '' }}>Dikirim ke Supplier</option>
                    <option value="partial" {{ ($status ?? '') === 'partial' ? 'selected' : '' }}>Diterima Sebagian</option>
                    <option value="received" {{ ($status ?? '') === 'received' ? 'selected' : '' }}>Selesai (Diterima Penuh)</option>
                    <option value="cancelled" {{ ($status ?? '') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="sm:col-span-3 flex items-end gap-2 pt-2 sm:pt-0">
                <button type="submit" class="flex-1 py-2 px-4 bg-primary hover:bg-[#00553a] text-on-primary font-bold text-xs rounded-xl shadow-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-sm">filter_alt</span>
                    <span>Filter</span>
                </button>
                @if(!empty($search) || (!empty($status) && $status !== 'all') || (!empty($storeId) && $storeId !== 'all'))
                    <a href="{{ route('purchasing.purchase-orders.index') }}" class="p-2 rounded-xl border border-outline-variant bg-surface-container-low text-on-surface-variant hover:text-on-surface text-xs font-bold flex items-center justify-center" title="Reset Filter">
                        <span class="material-symbols-outlined text-sm">restart_alt</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table List Index --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant/60 text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                    <tr>
                        <th class="py-3 px-4 w-10 text-center">
                            <input type="checkbox" disabled class="rounded border-outline-variant text-primary focus:ring-primary/20">
                        </th>
                        <th class="py-3 px-4">Tanggal PO</th>
                        <th class="py-3 px-4">No. PO</th>
                        <th class="py-3 px-4">No. PR</th>
                        <th class="py-3 px-4">Supplier / Vendor</th>
                        <th class="py-3 px-4">Cabang Toko</th>
                        <th class="py-3 px-4 text-center">Total Item</th>
                        <th class="py-3 px-4 text-right">Total Nilai PO</th>
                        <th class="py-3 px-4">Syarat Bayar</th>
                        <th class="py-3 px-4">Estimasi Bayar</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @forelse($purchaseOrders as $po)
                    @php
                        $poItemsCount = $po->items->count();
                        $poOrderedQty = $po->items->sum('quantity_ordered');
                        $poReceivedQty = $po->items->sum('quantity_received');
                    @endphp
                    <tr class="hover:bg-surface-container-low/70 transition-colors">
                        <td class="py-3.5 px-4 text-center">
                            <input type="checkbox" class="rounded border-outline-variant text-primary focus:ring-primary/20">
                        </td>

                        <!-- Tanggal PO -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <a href="{{ route('purchasing.purchase-orders.show', $po->id) }}" class="group block">
                                <span class="font-bold text-on-surface group-hover:text-primary transition-colors block">{{ \Carbon\Carbon::parse($po->po_date)->isoFormat('D MMMM Y') }}</span>
                            </a>
                        </td>

                        <!-- No. PO -->
                        <td class="py-3.5 px-4">
                            <a href="{{ route('purchasing.purchase-orders.show', $po->id) }}" class="font-mono text-xs font-bold text-on-surface hover:text-primary transition-colors block">
                                {{ $po->po_number }}
                            </a>
                        </td>

                        <!-- No. PR -->
                        <td class="py-3.5 px-4">
                            @if($po->purchaseRequest)
                                <a href="{{ route('purchasing.orders.show', $po->purchaseRequest->id) }}" class="inline-flex items-center gap-1 text-[11px] text-primary hover:underline font-mono font-bold" title="Buka PR Terkait">
                                    <span>{{ $po->purchaseRequest->pr_number }}</span>
                                </a>
                            @else
                                <span class="text-on-surface-variant font-mono text-xs">—</span>
                            @endif
                        </td>

                        <!-- Supplier -->
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-on-surface text-xs block truncate">{{ $po->supplier_name }}</span>
                        </td>

                        <!-- Cabang Toko -->
                        <td class="py-3.5 px-4">
                            <span class="font-semibold text-on-surface text-xs block truncate">{{ $po->store?->name ?? 'Toko Utama' }}</span>
                        </td>

                        <!-- Total Item -->
                        <td class="py-3.5 px-4 text-center">
                            <span class="font-bold text-on-surface text-xs block">{{ $poItemsCount }} Item</span>
                        </td>

                        <!-- Total Nilai PO -->
                        <td class="py-3.5 px-4 text-right">
                            <span class="font-black text-on-surface block font-mono text-emerald-800">
                                Rp {{ number_format($po->total_amount, 0, ',', '.') }}
                            </span>
                        </td>

                        <!-- Syarat Bayar -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($po->payment_method === 'tempo' && $po->payment_term_days > 0)
                                <span class="font-bold text-xs text-on-surface">{{ $po->payment_term_days }} Hari</span>
                            @elseif($po->payment_method === 'tempo')
                                <span class="font-bold text-xs text-on-surface">Tempo</span>
                            @else
                                <span class="font-medium text-xs text-on-surface-variant">{{ ucfirst($po->payment_method) }}</span>
                            @endif
                        </td>

                        <!-- Estimasi Bayar -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($po->payment_method === 'tempo' && ($po->payment_term_days > 0 || $po->expected_date))
                                @php
                                    $estDate = $po->expected_date ?? ($po->po_date ? \Carbon\Carbon::parse($po->po_date)->addDays((int)$po->payment_term_days) : null);
                                @endphp
                                @if($estDate)
                                    <span class="font-bold font-mono text-xs text-on-surface">
                                        {{ \Carbon\Carbon::parse($estDate)->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-on-surface-variant text-xs">—</span>
                                @endif
                            @else
                                <span class="text-on-surface-variant text-xs">—</span>
                            @endif
                        </td>

                        <!-- Status -->
                        <td class="py-3.5 px-4 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $po->status_badge }}">
                                {{ $po->status_label }}
                            </span>
                        </td>

                        <!-- Aksi -->
                        <td class="py-3.5 px-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a
                                    href="{{ route('purchasing.purchase-orders.show', $po->id) }}"
                                    class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-colors cursor-pointer"
                                    title="Rincian PO"
                                >
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                </a>

                                <a
                                    href="{{ route('purchasing.purchase-orders.print', $po->id) }}"
                                    target="_blank"
                                    class="p-1.5 rounded-lg text-on-surface-variant hover:text-secondary hover:bg-surface-container-high transition-colors cursor-pointer"
                                    title="Cetak Surat PO"
                                >
                                    <span class="material-symbols-outlined text-base">print</span>
                                </a>

                                @if($po->status !== 'received' && $poReceivedQty == 0)
                                    <form action="{{ route('purchasing.purchase-orders.destroy', $po->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus PO #{{ $po->po_number }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-on-surface-variant hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Hapus PO">
                                            <span class="material-symbols-outlined text-base">delete</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-12 text-on-surface-variant text-xs">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-4xl text-outline-variant">shopping_bag</span>
                                <p class="font-bold">Belum ada Purchase Order (PO) yang tercatat.</p>
                                <a href="{{ route('purchasing.purchase-orders.create') }}" class="mt-1 text-xs text-primary font-bold hover:underline cursor-pointer">
                                    + Buat Purchase Order Baru Sekarang
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchaseOrders->hasPages())
        <div class="p-4 border-t border-outline-variant/60">
            {{ $purchaseOrders->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

