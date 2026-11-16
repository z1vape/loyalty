@extends('layouts.app')

@section('title', 'Z1 VapeOS | Detail Order Barang (Purchase Request) - ' . $pr->pr_number)

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb / Back Link --}}
    <div>
        <a href="{{ route('purchasing.orders.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-on-surface-variant hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            <span>Kembali ke Daftar Order Barang</span>
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

            <!-- Subtitle info -->
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
                @if($pr->approvedByUser)
                    <span>•</span>
                    <span class="flex items-center gap-1 text-emerald-700 font-semibold">
                        <span class="material-symbols-outlined text-sm">verified</span>
                        <span>Disetujui: {{ $pr->approvedByUser->name }} ({{ $pr->approved_at ? $pr->approved_at->format('d/m/Y H:i') : '' }})</span>
                    </span>
                @endif
            </div>

            <!-- Metadata Badges -->
            <div class="flex items-center gap-2 mt-3 flex-wrap text-xs">
                <!-- Cabang Toko -->
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-semibold bg-surface-container-low text-on-surface border border-outline-variant shadow-xs">
                    <span class="material-symbols-outlined text-sm text-secondary">storefront</span>
                    <span>Cabang: <strong>{{ $pr->store?->name ?? 'Toko Utama' }}</strong></span>
                </span>

                <!-- Nomor PR / PO -->
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
                    <span class="truncate max-w-[280px]">Catatan: "{{ $pr->notes }}"</span>
                </span>
                @endif

                @if($pr->admin_notes)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg font-bold bg-amber-500/10 text-amber-800 border border-amber-500/20 shadow-xs" title="{{ $pr->admin_notes }}">
                    <span class="material-symbols-outlined text-sm text-amber-700">info</span>
                    <span class="truncate max-w-[280px]">Catatan Admin: "{{ $pr->admin_notes }}"</span>
                </span>
                @endif
            </div>
        </div>

        <!-- Action Buttons Bar -->
        <div class="flex items-center gap-2 flex-wrap w-full lg:w-auto">
            <!-- Tambah Barang Button (Hanya jika belum disetujui / completed) -->
            @if($pr->status !== 'completed' && $pr->status !== 'approved')
                <button
                    type="button"
                    onclick="document.getElementById('modal-add-item-po').showModal()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-[#00553a] text-on-primary font-bold rounded-xl text-xs hover:opacity-95 active:scale-95 transition-all shadow-xs cursor-pointer border-0"
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

            <!-- Hapus Order Button (Hanya jika belum approved & belum completed) -->
            @if(!in_array($pr->status, ['approved', 'completed']))
                @if(($pr->status === 'pending' && ($totalUnits == 0 || $totalItemsCount == 0)) || $isSuperAdmin || $pr->user_id === auth()->id() || auth()->user()->can('Stock In Create'))
                <form action="{{ route('purchasing.orders.destroy', $pr->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus order #{{ $pr->pr_number }} beserta seluruh itemnya?');">
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

    {{-- Metric Cards (3 Cards sama seperti Z1 App) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <!-- Card 1: Total Item -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-surface-container-high text-on-surface-variant flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl text-primary">receipt_long</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Item</p>
                <h3 class="text-xl sm:text-2xl font-black font-headline-md text-on-surface mt-0.5 truncate">{{ $totalItemsCount }} Item</h3>
                <span class="text-[10px] text-on-surface-variant block mt-0.5">Daftar item produk dalam pengajuan ini</span>
            </div>
        </div>

        <!-- Card 2: Total Estimasi Nilai -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Estimasi Nilai</p>
                <h3 class="text-xl sm:text-2xl font-black font-headline-md text-emerald-800 font-mono mt-0.5 truncate">
                    Rp {{ number_format($totalAmount, 0, ',', '.') }}
                </h3>
                <span class="text-[10px] text-on-surface-variant block mt-0.5">Akumulasi estimasi nilai seluruh barang</span>
            </div>
        </div>

        <!-- Card 3: Total Unit Dipesan -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-secondary-container/40 text-on-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">inventory_2</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Unit Dipesan</p>
                <h3 class="text-xl sm:text-2xl font-black font-headline-md text-on-surface mt-0.5 truncate">{{ number_format($totalUnits) }} Pcs</h3>
                <span class="text-[10px] text-on-surface-variant block mt-0.5">Kuantitas fisik unit yang diminta restock</span>
            </div>
        </div>
    </div>

    {{-- Table of Order Items --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-outline-variant/60 flex items-center justify-between gap-4">
            <div>
                <h3 class="font-black text-sm sm:text-base text-on-surface">Daftar Barang yang Dipesan</h3>
                <p class="text-xs text-on-surface-variant mt-0.5">Rincian produk, estimasi harga beli satuan, kuantitas restock, dan total biaya</p>
            </div>
            @if($pr->status !== 'completed' && $pr->status !== 'approved')
                <button
                    type="button"
                    onclick="document.getElementById('modal-add-item-po').showModal()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary/10 hover:bg-primary/20 text-primary rounded-xl text-xs font-bold transition-all cursor-pointer"
                >
                    <span class="material-symbols-outlined text-base">add</span>
                    <span>Tambah Item</span>
                </button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant/60 text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Nama Produk / Barang</th>
                        <th class="py-3 px-4">SKU / Kode</th>
                        <th class="py-3 px-4 text-center">Kuantitas</th>
                        <th class="py-3 px-4 text-right">Harga Beli Est.</th>
                        <th class="py-3 px-4 text-right">Subtotal</th>
                        <th class="py-3 px-4">Catatan Item</th>
                        @if($pr->status !== 'completed' && $pr->status !== 'approved')
                            <th class="py-3 px-4 text-center w-16">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @forelse($pr->items as $index => $item)
                    @php
                        $itemSubtotal = $item->item_price * $item->quantity_requested;
                        $isCancelled = (bool) $item->is_cancelled;
                    @endphp
                    <tr class="hover:bg-surface-container-low/70 transition-colors {{ $isCancelled ? 'bg-rose-50/50 text-rose-600' : '' }}">
                        <td class="py-3.5 px-4 text-center text-on-surface-variant font-bold">
                            {{ $index + 1 }}
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-bold block text-xs {{ $isCancelled ? 'line-through decoration-rose-500 text-rose-700' : 'text-on-surface' }}">
                                    {{ $item->item_name }}
                                </span>
                                @if($isCancelled)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-rose-500/15 text-rose-700 border border-rose-500/30">
                                        Dibatalkan di PO
                                    </span>
                                @endif
                                @if($item->added_from_po)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-emerald-500/15 text-emerald-800 border border-emerald-500/30">
                                        + Tambahan PO
                                    </span>
                                @endif
                            </div>
                            @if($item->product)
                                <span class="text-[10px] text-on-surface-variant">Stok toko: <strong class="text-primary">{{ $item->product->stock }} pcs</strong></span>
                            @else
                                <span class="text-[10px] text-amber-700 bg-amber-500/10 px-1.5 py-0.5 rounded font-semibold">Barang Baru / Manual</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 font-mono text-[11px] {{ $isCancelled ? 'line-through text-rose-400' : 'text-on-surface-variant' }}">
                            {{ $item->item_sku }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg font-black text-xs {{ $isCancelled ? 'bg-rose-100 text-rose-700 line-through' : 'bg-surface-container text-on-surface' }}">
                                {{ $item->quantity_requested }} pcs
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono text-xs {{ $isCancelled ? 'line-through text-rose-500' : '' }}">
                            Rp {{ number_format($item->item_price, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono font-bold text-xs {{ $isCancelled ? 'line-through text-rose-500' : 'text-emerald-800' }}">
                            Rp {{ number_format($itemSubtotal, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-[11px] italic {{ $isCancelled ? 'text-rose-500' : 'text-on-surface-variant' }}">
                            {{ $item->notes ?? '—' }}
                        </td>
                        @if($pr->status !== 'completed' && $pr->status !== 'approved')
                        <td class="py-3.5 px-4 text-center">
                            <form action="{{ route('purchasing.orders.destroy-item', $item->id) }}" method="POST" onsubmit="return confirm('Hapus item ini dari order?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 rounded-lg text-on-surface-variant hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Hapus Item">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $pr->status !== 'completed' && $pr->status !== 'approved' ? 8 : 7 }}" class="text-center py-10 text-on-surface-variant text-xs">
                            <div class="flex flex-col items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-3xl text-outline-variant">post_add</span>
                                <p class="font-bold">Belum ada item barang yang ditambahkan ke order ini.</p>
                                @if($pr->status !== 'completed' && $pr->status !== 'approved')
                                <button type="button" onclick="document.getElementById('modal-add-item-po').showModal()" class="text-xs text-primary font-bold hover:underline cursor-pointer">
                                    + Tambah Barang Sekarang
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($pr->items->isNotEmpty())
                <tfoot class="bg-surface-container-low/60 border-t-2 border-outline-variant/80 font-bold">
                    <tr>
                        <td colspan="3" class="py-3 px-4 text-right uppercase tracking-wider text-[11px] text-on-surface">Total:</td>
                        <td class="py-3 px-4 text-center font-black text-on-surface">{{ $totalUnits }} pcs</td>
                        <td></td>
                        <td class="py-3 px-4 text-right font-mono text-sm font-black text-emerald-800">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                        <td colspan="{{ $pr->status !== 'completed' ? 2 : 1 }}"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
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
            @if($canApprovePr ?? $isSuperAdmin)
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <!-- Reject Button (Modal Trigger) -->
                <button 
                    type="button" 
                    onclick="document.getElementById('modal-reject-po').showModal()"
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
                Persetujuan order ini memerlukan otorisasi peran (Role: {{ $workflowRolesText ?? 'Super Admin' }}).
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
                    <p class="text-[11px] text-on-surface-variant">Order ini telah disetujui dan siap diteruskan ke Purchase Order (PO Supplier).</p>
                </div>
            </div>
            @if($canApprovePr ?? $isSuperAdmin)
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

    {{-- MODAL 1: TAMBAH BARANG KE ORDER --}}
    <dialog id="modal-add-item-po" class="rounded-2xl p-0 bg-surface-container-lowest border border-outline-variant shadow-2xl backdrop:bg-black/40 backdrop:backdrop-blur-xs w-[94vw] max-w-lg m-auto">
        <div class="flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 sm:p-5 border-b border-outline-variant/60">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">add_box</span>
                    </span>
                    <div>
                        <h3 class="font-black text-sm sm:text-base text-on-surface">Tambah Barang ke Order</h3>
                        <p class="text-[11px] sm:text-xs text-on-surface-variant">Pilih produk katalog atau ketik nama barang manual</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modal-add-item-po').close()" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low cursor-pointer">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Form -->
            <form action="{{ route('purchasing.orders.add-item', $pr->id) }}" method="POST" onsubmit="return validateAddItemForm(event)" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
                @csrf

                <!-- Tab Pemilihan: Katalog vs Manual -->
                <div class="flex border-b border-outline-variant/60 gap-4 text-xs font-bold">
                    <button type="button" id="tab-btn-catalog" onclick="switchItemMode('catalog')" class="pb-2 border-b-2 border-primary text-primary cursor-pointer">
                        Dari Katalog Produk
                    </button>
                    <button type="button" id="tab-btn-manual" onclick="switchItemMode('manual')" class="pb-2 border-b-2 border-transparent text-on-surface-variant hover:text-on-surface cursor-pointer">
                        Input Manual / Non-Katalog
                    </button>
                </div>

                <!-- Mode 1: Produk Katalog -->
                <div id="section-catalog" class="space-y-3">
                    <label class="block text-xs font-bold text-on-surface">Pilih Produk dari Katalog:</label>
                    
                    <!-- Search Input & Combobox -->
                    <div class="relative" id="order-combobox-container">
                        <div class="relative">
                            <input
                                type="text"
                                id="order-product-search-input"
                                placeholder="Ketik nama produk atau SKU untuk mencari..."
                                autocomplete="off"
                                class="w-full pl-9 pr-8 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary"
                                oninput="filterOrderProducts(this.value)"
                                onfocus="openOrderProductDropdown()"
                            />
                            <span class="material-symbols-outlined text-base text-on-surface-variant absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none">search</span>
                            <button type="button" onclick="clearOrderProductSelection()" id="order-product-clear-btn" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface cursor-pointer p-0.5">
                                <span class="material-symbols-outlined text-base">close</span>
                            </button>
                        </div>

                        <!-- Hidden Product ID for Form Submit -->
                        <input type="hidden" name="product_id" id="select_product_id" value="">

                        <!-- Selected Product Preview Badge -->
                        <div id="order-selected-product-card" class="mt-2 hidden p-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-800 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-base">check_circle</span>
                                </div>
                                <div class="min-w-0">
                                    <span class="font-extrabold text-xs block truncate text-on-surface" id="order-selected-name"></span>
                                    <span class="text-[10px] text-on-surface-variant block" id="order-selected-meta"></span>
                                </div>
                            </div>
                            <button type="button" onclick="clearOrderProductSelection()" class="text-[11px] font-bold text-primary hover:underline ml-2 shrink-0 cursor-pointer">
                                Ganti
                            </button>
                        </div>

                        <!-- Dropdown List of Products -->
                        <div id="order-product-dropdown" class="absolute left-0 right-0 top-full mt-1.5 max-h-56 overflow-y-auto bg-surface-container-lowest border border-outline-variant rounded-xl shadow-2xl z-50 hidden divide-y divide-outline-variant/30">
                            @foreach($storeProducts as $prod)
                            <div
                                class="order-product-row p-2.5 hover:bg-surface-container-low cursor-pointer transition-colors flex items-center justify-between gap-2"
                                data-id="{{ $prod->id }}"
                                data-name="{{ strtolower($prod->name) }}"
                                data-sku="{{ strtolower($prod->sku ?? '') }}"
                                data-display-name="{{ $prod->name }}"
                                data-display-sku="{{ $prod->sku ?? '-' }}"
                                data-stock="{{ $prod->stock ?? 0 }}"
                                data-price="{{ $prod->purchase_price ?? $prod->price ?? 0 }}"
                                onclick="selectOrderProduct({{ $prod->id }}, '{{ addslashes($prod->name) }}', '{{ $prod->sku ?? '-' }}', {{ $prod->stock ?? 0 }}, {{ $prod->purchase_price ?? $prod->price ?? 0 }})"
                            >
                                <div class="min-w-0">
                                    <span class="font-bold text-xs text-on-surface block truncate">{{ $prod->name }}</span>
                                    <span class="text-[10px] text-on-surface-variant block">SKU: {{ $prod->sku ?? '-' }} &bull; Stok Toko: {{ $prod->stock ?? 0 }} pcs</span>
                                </div>
                                <span class="font-mono font-bold text-xs text-primary shrink-0">
                                    Rp {{ number_format($prod->purchase_price ?? $prod->price ?? 0, 0, ',', '.') }}
                                </span>
                            </div>
                            @endforeach
                            <div id="order-product-no-match" class="hidden p-3 text-center text-xs text-on-surface-variant font-medium">
                                Tidak ada produk katalog yang sesuai pencarian.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mode 2: Input Manual -->
                <div id="section-manual" class="space-y-4 hidden">
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Nama Barang Manual:</label>
                        <input type="text" name="custom_item_name" id="input_custom_item_name" placeholder="Misal: Coil Master DIY Kit V3..." class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:border-primary">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Kuantitas Dipesan: <span class="text-rose-500">*</span></label>
                        <input type="number" name="quantity_requested" required min="1" value="1" class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Estimasi Harga Satuan (Rp):</label>
                        <input type="number" name="estimated_price" id="input_estimated_price" min="0" placeholder="0" class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Catatan Tambahan (Opsional):</label>
                    <input type="text" name="notes" placeholder="Misal: Varian warna black, packaging kardus utuh..." class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                </div>

                <!-- Footer -->
                <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="document.getElementById('modal-add-item-po').close()" class="px-4 py-2.5 rounded-xl border border-outline-variant text-xs font-bold text-on-surface-variant hover:bg-surface-container cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary hover:bg-[#00553a] text-on-primary text-xs font-bold shadow-xs hover:opacity-95 transition-all cursor-pointer">
                        Simpan Barang
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- MODAL 2: TOLAK ORDER (REJECT) --}}
    <dialog id="modal-reject-po" class="rounded-2xl p-0 bg-surface-container-lowest border border-outline-variant shadow-2xl backdrop:bg-black/40 backdrop:backdrop-blur-xs w-[94vw] max-w-md m-auto">
        <div class="flex flex-col">
            <div class="p-4 sm:p-5 border-b border-outline-variant/60 flex items-center justify-between">
                <div class="flex items-center gap-2 text-rose-600 font-black text-sm">
                    <span class="material-symbols-outlined text-xl">warning</span>
                    <span>Tolak Pengajuan Order Barang</span>
                </div>
                <button type="button" onclick="document.getElementById('modal-reject-po').close()" class="text-on-surface-variant hover:text-on-surface cursor-pointer">
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
                    <button type="button" onclick="document.getElementById('modal-reject-po').close()" class="px-4 py-2 rounded-xl border border-outline-variant text-xs font-bold text-on-surface-variant hover:bg-surface-container cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs cursor-pointer">
                        Konfirmasi Tolak Order
                    </button>
                </div>
            </form>
        </div>
    </dialog>

</div>

<script>
    function switchItemMode(mode) {
        const secCatalog = document.getElementById('section-catalog');
        const secManual = document.getElementById('section-manual');
        const btnCatalog = document.getElementById('tab-btn-catalog');
        const btnManual = document.getElementById('tab-btn-manual');
        const inputCustom = document.getElementById('input_custom_item_name');

        if (mode === 'catalog') {
            secCatalog.classList.remove('hidden');
            secManual.classList.add('hidden');
            btnCatalog.className = 'pb-2 border-b-2 border-primary text-primary cursor-pointer';
            btnManual.className = 'pb-2 border-b-2 border-transparent text-on-surface-variant hover:text-on-surface cursor-pointer';
            inputCustom.value = '';
        } else {
            secCatalog.classList.add('hidden');
            secManual.classList.remove('hidden');
            btnCatalog.className = 'pb-2 border-b-2 border-transparent text-on-surface-variant hover:text-on-surface cursor-pointer';
            btnManual.className = 'pb-2 border-b-2 border-primary text-primary cursor-pointer';
            clearOrderProductSelection();
        }
    }

    function openOrderProductDropdown() {
        const dropdown = document.getElementById('order-product-dropdown');
        if (dropdown) dropdown.classList.remove('hidden');
    }

    function closeOrderProductDropdown() {
        const dropdown = document.getElementById('order-product-dropdown');
        if (dropdown) dropdown.classList.add('hidden');
    }

    function filterOrderProducts(val) {
        const query = (val || '').trim().toLowerCase();
        const clearBtn = document.getElementById('order-product-clear-btn');
        if (clearBtn) clearBtn.classList.toggle('hidden', !val);

        openOrderProductDropdown();

        const rows = document.querySelectorAll('.order-product-row');
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

        const noMatch = document.getElementById('order-product-no-match');
        if (noMatch) noMatch.classList.toggle('hidden', matchCount > 0);
    }

    function selectOrderProduct(id, name, sku, stock, price) {
        document.getElementById('select_product_id').value = id;
        document.getElementById('order-product-search-input').value = name;
        document.getElementById('input_estimated_price').value = price;

        document.getElementById('order-selected-name').textContent = name;
        document.getElementById('order-selected-meta').textContent = 'SKU: ' + sku + ' • Stok Toko: ' + stock + ' pcs';
        document.getElementById('order-selected-product-card').classList.remove('hidden');

        document.getElementById('order-product-clear-btn').classList.remove('hidden');
        closeOrderProductDropdown();
    }

    function clearOrderProductSelection() {
        document.getElementById('select_product_id').value = '';
        document.getElementById('order-product-search-input').value = '';
        document.getElementById('order-selected-product-card').classList.add('hidden');
        document.getElementById('order-product-clear-btn').classList.add('hidden');

        // Reset filter
        const rows = document.querySelectorAll('.order-product-row');
        rows.forEach(r => r.classList.remove('hidden'));
        const noMatch = document.getElementById('order-product-no-match');
        if (noMatch) noMatch.classList.add('hidden');
    }

    function validateAddItemForm(event) {
        const isCatalog = !document.getElementById('section-catalog').classList.contains('hidden');
        if (isCatalog) {
            const prodId = document.getElementById('select_product_id').value;
            if (!prodId) {
                alert('Silakan cari dan pilih produk dari katalog terlebih dahulu.');
                openOrderProductDropdown();
                event.preventDefault();
                return false;
            }
        } else {
            const customName = document.getElementById('input_custom_item_name').value.trim();
            if (!customName) {
                alert('Silakan masukkan nama barang manual.');
                event.preventDefault();
                return false;
            }
        }
        return true;
    }

    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        const container = document.getElementById('order-combobox-container');
        if (container && !container.contains(e.target)) {
            closeOrderProductDropdown();
        }
    });
</script>
@endsection
