@extends('layouts.app')

@section('title', 'Z1 VapeOS | Purchase Order - ' . $po->po_number)

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb / Back --}}
    <div>
        <a href="{{ route('purchasing.purchase-orders.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-on-surface-variant hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            <span>Kembali ke Daftar Purchase Order</span>
        </a>
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

    {{-- Header Section --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <!-- Title & Status Badges -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-2xl text-primary">shopping_bag</span>
                    <h1 class="font-headline-lg text-xl sm:text-2xl font-black text-on-surface">
                        Purchase Order #{{ $po->po_number }}
                    </h1>
                </div>
                <span class="px-2.5 py-1 rounded-md text-xs font-bold border {{ $po->status_badge }} uppercase">
                    {{ $po->status_label }}
                </span>
            </div>

            <!-- Subtitle info -->
            <div class="flex items-center gap-3 text-xs text-on-surface-variant mt-2 flex-wrap font-medium">
                <span class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">local_shipping</span>
                    <span>Supplier: <strong>{{ $po->supplier_name }}</strong></span>
                </span>
                <span>•</span>
                <span class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">schedule</span>
                    <span>Tanggal PO: <strong>{{ \Carbon\Carbon::parse($po->po_date)->isoFormat('D MMMM Y') }}</strong></span>
                </span>
                <span>•</span>
                <span class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">person</span>
                    <span>Dibuat oleh: <strong>{{ $po->creator?->name ?? 'Staff Purchasing' }}</strong></span>
                </span>
            </div>

            <!-- Metadata Badges -->
            <div class="flex items-center gap-2 mt-3 flex-wrap text-xs">
                <!-- Cabang Toko -->
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-semibold bg-surface-container-low text-on-surface border border-outline-variant shadow-xs">
                    <span class="material-symbols-outlined text-sm text-secondary">storefront</span>
                    <span>Cabang Penerima: <strong>{{ $po->store?->name ?? 'Toko Utama' }}</strong></span>
                </span>

                <!-- Syarat Pembayaran -->
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-semibold bg-surface-container-low text-on-surface border border-outline-variant shadow-xs">
                    <span class="material-symbols-outlined text-sm text-primary">payments</span>
                    <span>Metode: <strong class="uppercase">{{ $po->payment_method }}</strong> @if($po->payment_term_days > 0) ({{ $po->payment_term_days }} Hari) @endif</span>
                </span>

                <!-- Estimasi Tiba -->
                @if($po->expected_date)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-semibold bg-surface-container-low text-on-surface border border-outline-variant shadow-xs">
                    <span class="material-symbols-outlined text-sm text-secondary">event</span>
                    <span>Estimasi Tiba: <strong>{{ $po->expected_date->format('d/m/Y') }}</strong></span>
                </span>
                @endif

                @if($po->notes)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-semibold bg-surface-container-low text-on-surface border border-outline-variant shadow-xs italic" title="{{ $po->notes }}">
                    <span class="material-symbols-outlined text-sm text-secondary">edit_note</span>
                    <span class="truncate max-w-[280px]">Catatan: "{{ $po->notes }}"</span>
                </span>
                @endif
            </div>
        </div>

        <!-- Action Buttons Bar -->
        <div class="flex items-center gap-2 flex-wrap w-full lg:w-auto">
            
            <!-- Cetak Surat PO -->
            <a
                href="{{ route('purchasing.purchase-orders.print', $po->id) }}"
                target="_blank"
                class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest hover:bg-surface-container text-on-surface text-xs font-bold transition-all shadow-xs cursor-pointer"
            >
                <span class="material-symbols-outlined text-base">print</span>
                <span>Cetak Surat PO</span>
            </a>

            <!-- Kirim ke Supplier (jika masih draft) -->
            @if($po->status === 'draft' && ($canApprovePo ?? true))
                <form action="{{ route('purchasing.purchase-orders.update-status', $po->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="status" value="sent">
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-all shadow-xs cursor-pointer">
                        <span class="material-symbols-outlined text-base">send</span>
                        <span>Kirim ke Supplier</span>
                    </button>
                </form>
            @endif

            <!-- Terima Barang Fisik (Stock In) -->
            @if(in_array($po->status, ['sent', 'partial', 'draft']))
                <button
                    type="button"
                    onclick="document.getElementById('modal-receive-goods').showModal()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs hover:opacity-95 active:scale-95 transition-all shadow-xs cursor-pointer border-0"
                >
                    <span class="material-symbols-outlined text-base">inventory</span>
                    <span>Terima Barang (Stock In)</span>
                </button>
            @endif

            <!-- Hapus PO (jika belum ada barang diterima) -->
            @if($po->status !== 'received' && $totalUnitsReceived == 0)
                <form action="{{ route('purchasing.purchase-orders.destroy', $po->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan & menghapus PO #{{ $po->po_number }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-2.5 rounded-xl text-rose-600 hover:bg-rose-50 border border-rose-200 transition-colors cursor-pointer" title="Hapus PO">
                        <span class="material-symbols-outlined text-base">delete</span>
                    </button>
                </form>
            @endif

        </div>
    </div>

    {{-- Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <!-- Card 1: Total Item -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-surface-container-high text-on-surface-variant flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl text-primary">receipt_long</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Item Dipesan</p>
                <h3 class="text-xl sm:text-2xl font-black font-headline-md text-on-surface mt-0.5 truncate">{{ $po->items->count() }} Jenis Item</h3>
                <span class="text-[10px] text-on-surface-variant block mt-0.5">Seluruh variasi barang ke supplier</span>
            </div>
        </div>

        <!-- Card 2: Total Nilai PO -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Nilai Pembelian</p>
                <h3 class="text-xl sm:text-2xl font-black font-headline-md text-emerald-800 font-mono mt-0.5 truncate">
                    Rp {{ number_format($po->total_amount, 0, ',', '.') }}
                </h3>
                <span class="text-[10px] text-on-surface-variant block mt-0.5">Nilai faktur pengadaan supplier</span>
            </div>
        </div>

        <!-- Card 3: Unit Fisik -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-secondary-container/40 text-on-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">inventory_2</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Progres Fisik Barang</p>
                <h3 class="text-xl sm:text-2xl font-black font-headline-md text-on-surface mt-0.5 truncate">
                    {{ $totalUnitsReceived }} / {{ $totalUnitsOrdered }} Pcs
                </h3>
                <span class="text-[10px] text-on-surface-variant block mt-0.5">
                    @if($totalUnitsOrdered > 0)
                        {{ round(($totalUnitsReceived / $totalUnitsOrdered) * 100) }}% fisik telah masuk stok
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-outline-variant/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="font-black text-sm sm:text-base text-on-surface">Daftar Barang dalam Surat Pesanan (PO)</h3>
                <p class="text-xs text-on-surface-variant mt-0.5">Kuantitas dipesan, jumlah barang diterima, harga satuan beli & jual, dan subtotal</p>
            </div>
            @if($po->status !== 'received' && $po->status !== 'cancelled')
            <button 
                type="button" 
                onclick="document.getElementById('modal-add-item-po').showModal()"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-xs cursor-pointer"
            >
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>Tambah Barang</span>
            </button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant/60 text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                    <tr>
                        <th class="py-3 px-4 w-10 text-center">No</th>
                        <th class="py-3 px-4">Nama Produk / Barang</th>
                        <th class="py-3 px-4">SKU</th>
                        <th class="py-3 px-4 text-center">Dipesan</th>
                        <th class="py-3 px-4 text-center">Diterima</th>
                        <th class="py-3 px-4 text-right">Harga Beli (Rp)</th>
                        <th class="py-3 px-4 text-right">Harga Jual (Rp)</th>
                        <th class="py-3 px-4 text-right">Subtotal (Rp)</th>
                        <th class="py-3 px-4 text-center">Status Barang</th>
                        <th class="py-3 px-4 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @forelse($po->items as $idx => $item)
                    @php
                        $isCancelled = (bool) $item->is_cancelled;
                        $isFulfilled = $item->quantity_received >= $item->quantity_ordered;
                        $isPartial = $item->quantity_received > 0 && !$isFulfilled;
                        $sellingPrice = (float) ($item->selling_price ?? $item->product?->selling_price ?? $item->product?->price ?? 0);
                    @endphp
                    <tr class="hover:bg-surface-container-low/70 transition-colors {{ $isCancelled ? 'bg-rose-50/50 text-rose-600' : '' }}">
                        <td class="py-3.5 px-4 text-center text-on-surface-variant font-bold">{{ $idx + 1 }}</td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-bold block {{ $isCancelled ? 'line-through decoration-rose-500 text-rose-700' : 'text-on-surface' }}">
                                    {{ $item->item_name }}
                                </span>
                                @if($isCancelled)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-rose-500/15 text-rose-700 border border-rose-500/30">
                                        Dibatalkan / Kosong
                                    </span>
                                @endif
                                @if($item->purchaseRequestItem && $item->purchaseRequestItem->added_from_po)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-emerald-500/15 text-emerald-800 border border-emerald-500/30">
                                        + Baru dari PO
                                    </span>
                                @endif
                            </div>
                            @if($item->notes)
                                <span class="text-[10px] italic block mt-0.5 {{ $isCancelled ? 'text-rose-400' : 'text-on-surface-variant' }}">{{ $item->notes }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 font-mono text-[11px] {{ $isCancelled ? 'line-through text-rose-400' : 'text-on-surface-variant' }}">{{ $item->item_sku }}</td>
                        <td class="py-3.5 px-4 text-center font-bold {{ $isCancelled ? 'line-through text-rose-500' : 'text-slate-800' }}">{{ $item->quantity_ordered }} pcs</td>
                        <td class="py-3.5 px-4 text-center font-bold {{ $item->quantity_received > 0 ? 'text-emerald-700' : 'text-slate-400' }}">
                            {{ $item->quantity_received }} pcs
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono {{ $isCancelled ? 'line-through text-rose-500' : '' }}">
                            Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono {{ $isCancelled ? 'line-through text-rose-500' : 'text-blue-800 font-semibold' }}">
                            {{ $sellingPrice > 0 ? 'Rp ' . number_format($sellingPrice, 0, ',', '.') : '—' }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono font-bold {{ $isCancelled ? 'line-through text-rose-500' : 'text-emerald-800' }}">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($isCancelled)
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-rose-500/10 text-rose-700 border border-rose-500/20">
                                    Dibatalkan
                                </span>
                            @elseif($isFulfilled)
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-500/10 text-emerald-700 border border-emerald-500/20">
                                    Lengkap
                                </span>
                            @elseif($isPartial)
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-500/10 text-amber-700 border border-amber-500/20">
                                    Kurang {{ $item->quantity_ordered - $item->quantity_received }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-slate-500/10 text-slate-600 border border-slate-500/20">
                                    Belum Datang
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($po->status !== 'received')
                            <div class="flex items-center justify-center gap-1">
                                <!-- Edit Item -->
                                @if(!$isCancelled)
                                <button 
                                    type="button" 
                                    onclick="openEditItemModal({{ $item->id }}, '{{ addslashes($item->item_name) }}', {{ $item->quantity_ordered }}, {{ $item->unit_price }}, {{ $sellingPrice }}, '{{ addslashes($item->notes ?? '') }}', {{ $item->quantity_received }})" 
                                    class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors cursor-pointer" 
                                    title="Edit Item"
                                >
                                    <span class="material-symbols-outlined text-base">edit</span>
                                </button>
                                @endif

                                <!-- Toggle Batal / Pulihkan -->
                                @if($item->quantity_received == 0)
                                <form action="{{ route('purchasing.purchase-orders.items.toggle-cancel', [$po->id, $item->id]) }}" method="POST" class="inline" onsubmit="return confirm('{{ $isCancelled ? 'Pulihkan barang ini kembali ke pesanan?' : 'Batalkan barang ini? Item akan digaris merah dan disinkronkan ke PR.' }}');">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        class="p-1.5 rounded-lg transition-colors cursor-pointer {{ $isCancelled ? 'text-emerald-700 hover:bg-emerald-50' : 'text-rose-600 hover:bg-rose-50' }}" 
                                        title="{{ $isCancelled ? 'Pulihkan Barang' : 'Batalkan Barang' }}"
                                    >
                                        <span class="material-symbols-outlined text-base">{{ $isCancelled ? 'undo' : 'block' }}</span>
                                    </button>
                                </form>
                                @endif

                                <!-- Hapus Item (hanya jika belum ada qty diterima) -->
                                @if($item->quantity_received == 0)
                                <form action="{{ route('purchasing.purchase-orders.items.delete', [$po->id, $item->id]) }}" method="POST" class="inline" onsubmit="return confirm('Hapus permanen item {{ addslashes($item->item_name) }} dari PO?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Hapus Permanen">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </form>
                                @endif
                            </div>
                            @else
                                <span class="text-on-surface-variant text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-8 text-center text-on-surface-variant text-xs">Belum ada barang di dalam PO ini.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t-2 border-outline-variant/80 font-bold bg-surface-container-low/60">
                    <tr>
                        <td colspan="3" class="py-3 px-4 text-right uppercase tracking-wider text-[11px] text-on-surface">Total Aktif:</td>
                        <td class="py-3 px-4 text-center font-black text-on-surface">{{ $totalUnitsOrdered }} pcs</td>
                        <td class="py-3 px-4 text-center font-black text-emerald-700">{{ $totalUnitsReceived }} pcs</td>
                        <td></td>
                        <td></td>
                        <td class="py-3 px-4 text-right font-mono text-sm font-black text-emerald-800">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Connected Stock In Records History --}}
    @if($po->stockMovements->isNotEmpty())
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-outline-variant/60 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600 text-xl">inventory</span>
                <div>
                    <h3 class="font-bold text-sm text-on-surface">Riwayat Penerimaan Fisik (Stock In)</h3>
                    <p class="text-[11px] text-on-surface-variant">Daftar mutasi stok masuk yang telah tercatat dari PO ini</p>
                </div>
            </div>
            <a href="{{ route('movements.index', ['type' => 'in']) }}" class="text-xs text-primary font-bold hover:underline">
                Buka Modul Stock In &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-surface-container-low text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                    <tr>
                        <th class="py-2.5 px-4">Tanggal Masuk</th>
                        <th class="py-2.5 px-4">Nama Produk</th>
                        <th class="py-2.5 px-4 text-center">Jumlah Masuk</th>
                        <th class="py-2.5 px-4 text-right">Harga Beli</th>
                        <th class="py-2.5 px-4">Status Pembayaran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @foreach($po->stockMovements as $mv)
                    <tr>
                        <td class="py-3 px-4 font-mono">{{ $mv->movement_date ?? $mv->created_at->format('Y-m-d') }}</td>
                        <td class="py-3 px-4 font-bold text-on-surface">{{ $mv->product?->name }}</td>
                        <td class="py-3 px-4 text-center font-bold text-emerald-700">+{{ $mv->quantity }} pcs</td>
                        <td class="py-3 px-4 text-right font-mono">Rp {{ number_format($mv->purchase_price, 0, ',', '.') }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $mv->payment_status === 'paid' ? 'bg-emerald-500/10 text-emerald-700' : 'bg-amber-500/10 text-amber-700' }}">
                                {{ $mv->payment_status }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- MODAL: TAMBAH BARANG / PRODUK KE PO --}}
    <dialog id="modal-add-item-po" class="rounded-2xl p-0 bg-surface-container-lowest border border-outline-variant shadow-2xl backdrop:bg-black/40 backdrop:backdrop-blur-xs w-[94vw] max-w-xl m-auto">
        <div class="flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between p-4 sm:p-5 border-b border-outline-variant/60">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">add_box</span>
                    </span>
                    <div>
                        <h3 class="font-black text-sm sm:text-base text-on-surface">Tambah Barang ke PO</h3>
                        <p class="text-[11px] sm:text-xs text-on-surface-variant">Tambahkan item dari inventori atau buat produk baru</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modal-add-item-po').close()" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low cursor-pointer">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Tab Switcher -->
            <div class="flex border-b border-outline-variant px-4 sm:px-5 bg-surface-container-low/50">
                <button type="button" id="tab-btn-existing" onclick="switchAddItemTab('existing')" class="py-2.5 px-4 text-xs font-bold border-b-2 border-primary text-primary transition-all">
                    Pilih dari Inventory Toko
                </button>
                <button type="button" id="tab-btn-custom" onclick="switchAddItemTab('custom')" class="py-2.5 px-4 text-xs font-bold border-b-2 border-transparent text-on-surface-variant hover:text-on-surface transition-all">
                    + Produk Baru (Manual)
                </button>
            </div>

            <form action="{{ route('purchasing.purchase-orders.items.add', $po->id) }}" method="POST" onsubmit="return validateAddPoItemForm(event)" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4 min-h-[380px]">
                @csrf
                <input type="hidden" name="item_type" id="add-item-type" value="existing">

                <!-- Tab 1: Existing Product -->
                <div id="tab-pane-existing" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Pilih Produk dari Inventori Toko: <span class="text-rose-500">*</span></label>
                        
                        <!-- Searchable Combobox -->
                        <div class="relative" id="po-product-combobox-container">
                            <div class="relative">
                                <input
                                    type="text"
                                    id="po-product-search-input"
                                    placeholder="Ketik nama produk atau SKU untuk mencari..."
                                    autocomplete="off"
                                    class="w-full pl-9 pr-8 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary"
                                    oninput="filterPoProducts(this.value)"
                                    onfocus="openPoProductDropdown()"
                                />
                                <span class="material-symbols-outlined text-base text-on-surface-variant absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none">search</span>
                                <button type="button" onclick="clearPoProductSelection()" id="po-product-clear-btn" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface cursor-pointer p-0.5">
                                    <span class="material-symbols-outlined text-base">close</span>
                                </button>
                            </div>

                            <!-- Hidden Product ID for Form Submit -->
                            <input type="hidden" name="product_id" id="add-existing-product-id" value="">

                            <!-- Selected Product Preview Card -->
                            <div id="po-selected-product-card" class="mt-2 hidden p-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-800 flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-base">check_circle</span>
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-extrabold text-xs block truncate text-on-surface" id="po-selected-name"></span>
                                        <span class="text-[10px] text-on-surface-variant block" id="po-selected-meta"></span>
                                    </div>
                                </div>
                                <button type="button" onclick="clearPoProductSelection()" class="text-[11px] font-bold text-primary hover:underline ml-2 shrink-0 cursor-pointer">
                                    Ganti
                                </button>
                            </div>

                            <!-- Dropdown List of Products -->
                            <div id="po-product-dropdown" class="absolute left-0 right-0 top-full mt-1.5 max-h-56 overflow-y-auto bg-surface-container-lowest border border-outline-variant rounded-xl shadow-2xl z-50 hidden divide-y divide-outline-variant/30 custom-slim-scroll">
                                @foreach($storeProducts as $prod)
                                @php
                                    $pPrice = round($prod->purchase_price ?? $prod->price ?? 0);
                                    $sPrice = round($prod->selling_price ?? $prod->price ?? 0);
                                @endphp
                                <div
                                    class="po-product-row p-2.5 hover:bg-surface-container-low cursor-pointer transition-colors flex items-center justify-between gap-2"
                                    data-id="{{ $prod->id }}"
                                    data-name="{{ strtolower($prod->name) }}"
                                    data-sku="{{ strtolower($prod->sku ?? '') }}"
                                    data-display-name="{{ $prod->name }}"
                                    data-display-sku="{{ $prod->sku ?? '-' }}"
                                    data-stock="{{ $prod->stock ?? 0 }}"
                                    data-purchase-price="{{ $pPrice }}"
                                    data-selling-price="{{ $sPrice }}"
                                    onclick="selectPoProduct({{ $prod->id }}, '{{ addslashes($prod->name) }}', '{{ $prod->sku ?? '-' }}', {{ $prod->stock ?? 0 }}, {{ $pPrice }}, {{ $sPrice }})"
                                >
                                    <div class="min-w-0">
                                        <span class="font-bold text-xs text-on-surface block truncate">{{ $prod->name }}</span>
                                        <span class="text-[10px] text-on-surface-variant block">SKU: {{ $prod->sku ?? '-' }} &bull; Stok Toko: {{ $prod->stock ?? 0 }} pcs</span>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="font-mono font-bold text-xs text-emerald-700 block">
                                            Rp {{ number_format($pPrice, 0, ',', '.') }}
                                        </span>
                                        <span class="text-[9px] text-on-surface-variant block">Modal HPP</span>
                                    </div>
                                </div>
                                @endforeach
                                <div id="po-product-no-match" class="hidden p-3 text-center text-xs text-on-surface-variant font-medium">
                                    Tidak ada produk yang sesuai pencarian.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Custom / New Product -->
                <div id="tab-pane-custom" class="space-y-4 hidden">
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Nama Produk Baru: <span class="text-rose-500">*</span></label>
                        <input type="text" name="custom_item_name" id="add-custom-name" placeholder="Contoh: FOOM Pod X Saltnic 30ml..." class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-on-surface mb-1">SKU Produk:</label>
                            <div class="flex items-center gap-1.5">
                                <input type="text" name="custom_sku" id="add-custom-sku" placeholder="Contoh: LIQ-VOL2-999" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono text-on-surface focus:outline-none focus:border-primary">
                                <button type="button" onclick="autoGenerateSku()" class="px-2.5 py-2 rounded-xl bg-surface-container text-[11px] font-bold text-on-surface hover:bg-surface-container-high transition-colors whitespace-nowrap" title="Generate SKU Otomatis">
                                    Auto
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface mb-1">Kategori:</label>
                            <select name="category" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                                <option value="">-- Kategori (Opsional) --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Shared Fields: Qty, Prices, Notes -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Qty Dipesan: <span class="text-rose-500">*</span></label>
                        <input type="number" name="quantity_ordered" min="1" value="1" required class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-bold text-center text-on-surface focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Harga Beli Satuan (Rp): <span class="text-rose-500">*</span></label>
                        <input type="number" name="unit_price" id="add-unit-price" min="0" step="1" required placeholder="0" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono font-bold text-right text-on-surface focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Harga Jual Toko (Rp):</label>
                        <input type="number" name="selling_price" id="add-selling-price" min="0" step="1" placeholder="0" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono font-bold text-right text-blue-800 focus:outline-none focus:border-blue-600">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Catatan Tambahan:</label>
                    <input type="text" name="notes" placeholder="Catatan variasi, rasa, atau konfirmasi supplier..." class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                </div>

                <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="document.getElementById('modal-add-item-po').close()" class="px-4 py-2 rounded-xl border border-outline-variant text-xs font-bold text-on-surface-variant hover:bg-surface-container cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs hover:opacity-95 transition-all cursor-pointer">
                        Simpan Barang ke PO
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- MODAL: EDIT ITEM PO --}}
    <dialog id="modal-edit-item-po" class="rounded-2xl p-0 bg-surface-container-lowest border border-outline-variant shadow-2xl backdrop:bg-black/40 backdrop:backdrop-blur-xs w-[94vw] max-w-md m-auto">
        <div class="flex flex-col">
            <div class="flex items-center justify-between p-4 sm:p-5 border-b border-outline-variant/60">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">edit</span>
                    </span>
                    <div>
                        <h3 class="font-black text-sm sm:text-base text-on-surface">Edit Item PO</h3>
                        <p id="edit-item-title" class="text-[11px] sm:text-xs text-on-surface-variant truncate max-w-xs font-medium"></p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modal-edit-item-po').close()" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low cursor-pointer">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form id="form-edit-item-po" action="" method="POST" class="p-4 sm:p-5 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Qty Dipesan: <span class="text-rose-500">*</span></label>
                    <input type="number" name="quantity_ordered" id="edit-qty-ordered" min="1" required class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-bold text-center text-on-surface focus:outline-none focus:border-primary">
                    <p id="edit-qty-received-notice" class="text-[10px] text-on-surface-variant mt-1"></p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Harga Beli Satuan (Rp): <span class="text-rose-500">*</span></label>
                        <input type="number" name="unit_price" id="edit-unit-price" min="0" step="1" required class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono font-bold text-right text-on-surface focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Harga Jual Toko (Rp):</label>
                        <input type="number" name="selling_price" id="edit-selling-price" min="0" step="1" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono font-bold text-right text-blue-800 focus:outline-none focus:border-blue-600">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Catatan:</label>
                    <input type="text" name="notes" id="edit-notes" placeholder="Catatan perubahan..." class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                </div>

                <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="document.getElementById('modal-edit-item-po').close()" class="px-4 py-2 rounded-xl border border-outline-variant text-xs font-bold text-on-surface-variant hover:bg-surface-container cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-primary hover:bg-[#00553a] text-white text-xs font-bold shadow-xs transition-all cursor-pointer">
                        Perbarui Item
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- MODAL: TERIMA BARANG FISIK (STOCK IN) --}}
    <dialog id="modal-receive-goods" class="rounded-2xl p-0 bg-surface-container-lowest border border-outline-variant shadow-2xl backdrop:bg-black/40 backdrop:backdrop-blur-xs w-[94vw] max-w-3xl m-auto">
        <div class="flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between p-4 sm:p-5 border-b border-outline-variant/60">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">inventory</span>
                    </span>
                    <div>
                        <h3 class="font-black text-sm sm:text-base text-on-surface">Penerimaan Barang Fisik (Stock In)</h3>
                        <p class="text-[11px] sm:text-xs text-on-surface-variant">Input kuantitas barang yang diterima dari supplier ke stok toko serta konfirmasi harga faktur</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modal-receive-goods').close()" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low cursor-pointer">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('purchasing.purchase-orders.receive', $po->id) }}" method="POST" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Tanggal Penerimaan Fisik: <span class="text-rose-500">*</span></label>
                        <input type="date" name="received_date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Catatan Penerima / Nomor Surat Jalan:</label>
                        <input type="text" name="notes" placeholder="Contoh: Surat Jalan No. SJ-8819 via kurir J&T Cargo..." class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-2">Kuantitas Barang yang Tiba Saat Ini & Penyesuaian Harga:</label>
                    <div class="border border-outline-variant rounded-xl overflow-x-auto">
                        <table class="w-full text-xs text-left min-w-[600px]">
                            <thead class="bg-surface-container-low text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                                <tr>
                                    <th class="p-3">Nama Produk & SKU</th>
                                    <th class="p-3 text-center">Order</th>
                                    <th class="p-3 text-center w-28">Diterima Hari Ini</th>
                                    <th class="p-3 text-right w-36">Harga Beli Faktur (Rp)</th>
                                    <th class="p-3 text-right w-36">Harga Jual Toko (Rp)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/60">
                                @foreach($po->items as $it)
                                @if($it->is_cancelled)
                                    @continue
                                @endif
                                @php
                                    $remaining = max(0, $it->quantity_ordered - $it->quantity_received);
                                    $sellingPrice = (float) ($it->selling_price ?? $it->product?->selling_price ?? $it->product?->price ?? 0);
                                @endphp
                                <tr>
                                    <td class="p-3">
                                        <span class="font-bold text-on-surface block">{{ $it->item_name }}</span>
                                        <span class="text-[10px] font-mono text-on-surface-variant">{{ $it->item_sku }}</span>
                                    </td>
                                    <td class="p-3 text-center font-bold">
                                        {{ $it->quantity_ordered }} pcs
                                        <span class="text-[10px] text-on-surface-variant block font-normal">(Sisa: {{ $remaining }})</span>
                                    </td>
                                    <td class="p-3">
                                        <input 
                                            type="number" 
                                            name="items[{{ $it->id }}][received_qty]" 
                                            min="0" 
                                            max="{{ $remaining }}" 
                                            value="{{ $remaining }}" 
                                            class="w-full text-center px-2 py-1.5 bg-surface-container-low border border-outline-variant rounded-lg text-xs font-bold focus:border-emerald-600 focus:outline-none"
                                        >
                                    </td>
                                    <td class="p-3">
                                        <input 
                                            type="number" 
                                            name="items[{{ $it->id }}][unit_price]" 
                                            min="0" 
                                            step="1"
                                            value="{{ round($it->unit_price) }}" 
                                            placeholder="Harga Faktur"
                                            title="Harga Beli Riil dari Faktur Supplier"
                                            class="w-full text-right px-2 py-1.5 bg-surface-container-low border border-outline-variant rounded-lg text-xs font-mono font-bold focus:border-emerald-600 focus:outline-none"
                                        >
                                    </td>
                                    <td class="p-3">
                                        <input 
                                            type="number" 
                                            name="items[{{ $it->id }}][selling_price]" 
                                            min="0" 
                                            step="1"
                                            value="{{ round($sellingPrice) }}" 
                                            placeholder="Harga Jual"
                                            title="Harga Jual POS Kasir Cabang"
                                            class="w-full text-right px-2 py-1.5 bg-surface-container-low border border-outline-variant rounded-lg text-xs font-mono font-bold text-blue-800 focus:border-blue-600 focus:outline-none"
                                        >
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1.5 italic">
                        * Jika harga faktur atau harga jual diubah di sini, sistem akan otomatis memperbarui data master produk & mencatat mutasi stok fisik dengan harga terbaru.
                    </p>
                </div>

                <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="document.getElementById('modal-receive-goods').close()" class="px-4 py-2.5 rounded-xl border border-outline-variant text-xs font-bold text-on-surface-variant hover:bg-surface-container cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs hover:opacity-95 transition-all cursor-pointer">
                        Konfirmasi Masuk Stok Toko &rarr;
                    </button>
                </div>
            </form>
        </div>
    </dialog>

</div>

<script>
function switchAddItemTab(tab) {
    const btnExisting = document.getElementById('tab-btn-existing');
    const btnCustom = document.getElementById('tab-btn-custom');
    const paneExisting = document.getElementById('tab-pane-existing');
    const paneCustom = document.getElementById('tab-pane-custom');
    const itemTypeInput = document.getElementById('add-item-type');
    const customNameInput = document.getElementById('add-custom-name');

    if (tab === 'existing') {
        btnExisting.className = 'py-2.5 px-4 text-xs font-bold border-b-2 border-primary text-primary transition-all';
        btnCustom.className = 'py-2.5 px-4 text-xs font-bold border-b-2 border-transparent text-on-surface-variant hover:text-on-surface transition-all';
        paneExisting.classList.remove('hidden');
        paneCustom.classList.add('hidden');
        itemTypeInput.value = 'existing';
        customNameInput.required = false;
    } else {
        btnCustom.className = 'py-2.5 px-4 text-xs font-bold border-b-2 border-primary text-primary transition-all';
        btnExisting.className = 'py-2.5 px-4 text-xs font-bold border-b-2 border-transparent text-on-surface-variant hover:text-on-surface transition-all';
        paneCustom.classList.remove('hidden');
        paneExisting.classList.add('hidden');
        itemTypeInput.value = 'custom';
        customNameInput.required = true;
    }
}

function openPoProductDropdown() {
    const dropdown = document.getElementById('po-product-dropdown');
    if (dropdown) dropdown.classList.remove('hidden');
}

function closePoProductDropdown() {
    const dropdown = document.getElementById('po-product-dropdown');
    if (dropdown) dropdown.classList.add('hidden');
}

function filterPoProducts(val) {
    const query = (val || '').trim().toLowerCase();
    const clearBtn = document.getElementById('po-product-clear-btn');
    if (clearBtn) clearBtn.classList.toggle('hidden', !val);

    openPoProductDropdown();

    const rows = document.querySelectorAll('.po-product-row');
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

    const noMatch = document.getElementById('po-product-no-match');
    if (noMatch) noMatch.classList.toggle('hidden', matchCount > 0);
}

function selectPoProduct(id, name, sku, stock, purchasePrice, sellingPrice) {
    document.getElementById('add-existing-product-id').value = id;
    document.getElementById('po-product-search-input').value = name;
    document.getElementById('add-unit-price').value = Math.round(purchasePrice);
    document.getElementById('add-selling-price').value = Math.round(sellingPrice);

    document.getElementById('po-selected-name').textContent = name;
    document.getElementById('po-selected-meta').textContent = 'SKU: ' + sku + ' • Stok Toko: ' + stock + ' pcs';
    document.getElementById('po-selected-product-card').classList.remove('hidden');

    document.getElementById('po-product-clear-btn').classList.remove('hidden');
    closePoProductDropdown();
}

function clearPoProductSelection() {
    document.getElementById('add-existing-product-id').value = '';
    document.getElementById('po-product-search-input').value = '';
    document.getElementById('po-selected-product-card').classList.add('hidden');
    document.getElementById('po-product-clear-btn').classList.add('hidden');

    // Reset filter
    const rows = document.querySelectorAll('.po-product-row');
    rows.forEach(r => r.classList.remove('hidden'));
    const noMatch = document.getElementById('po-product-no-match');
    if (noMatch) noMatch.classList.add('hidden');
}

function validateAddPoItemForm(event) {
    const itemType = document.getElementById('add-item-type').value;
    if (itemType === 'existing') {
        const prodId = document.getElementById('add-existing-product-id').value;
        if (!prodId) {
            alert('Silakan cari dan pilih produk dari inventori toko terlebih dahulu.');
            openPoProductDropdown();
            event.preventDefault();
            return false;
        }
    } else {
        const customName = document.getElementById('add-custom-name').value.trim();
        if (!customName) {
            alert('Silakan masukkan nama produk baru.');
            event.preventDefault();
            return false;
        }
    }
    return true;
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
    const container = document.getElementById('po-product-combobox-container');
    if (container && !container.contains(e.target)) {
        closePoProductDropdown();
    }
});

function autoGenerateSku() {
    const rand = Math.random().toString(36).substring(2, 8).toUpperCase();
    document.getElementById('add-custom-sku').value = 'NEW-' + rand;
}

function openEditItemModal(itemId, itemName, qty, unitPrice, sellingPrice, notes, qtyReceived) {
    const form = document.getElementById('form-edit-item-po');
    form.action = "{{ url('/purchasing/purchase-orders/' . $po->id . '/items') }}/" + itemId;
    document.getElementById('edit-item-title').textContent = itemName;
    document.getElementById('edit-qty-ordered').value = qty;
    document.getElementById('edit-qty-ordered').min = Math.max(1, qtyReceived);
    document.getElementById('edit-unit-price').value = Math.round(unitPrice);
    document.getElementById('edit-selling-price').value = Math.round(sellingPrice);
    document.getElementById('edit-notes').value = notes;

    const noticeEl = document.getElementById('edit-qty-received-notice');
    if (qtyReceived > 0) {
        noticeEl.textContent = '* Sudah diterima ' + qtyReceived + ' pcs. Minimal qty adalah ' + qtyReceived + ' pcs.';
    } else {
        noticeEl.textContent = '';
    }

    document.getElementById('modal-edit-item-po').showModal();
}
</script>
@endsection

