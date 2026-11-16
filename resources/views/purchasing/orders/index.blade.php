@extends('layouts.app')

@section('title', 'Z1 VapeOS | Order Barang (Purchase Request)')

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
                <span class="text-xs text-on-surface-variant font-medium">Pengadaan & Restock Cabang</span>
            </div>
            <h1 class="font-headline-lg text-2xl sm:text-3xl font-black text-on-surface">Order Barang (Purchase Request)</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">Kelola daftar pengajuan restock cabang, rincian barang, review persetujuan, dan surat purchase request.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <button
                type="button"
                onclick="document.getElementById('modal-create-po').showModal()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-[#00553a] text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-95 active:scale-95 transition-all shadow-xs cursor-pointer border-0"
            >
                <span class="material-symbols-outlined text-base">add_shopping_cart</span>
                <span>Buat Order Barang</span>
            </button>
        </div>
    </div>

    {{-- Flash Messages --}}
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

    {{-- Metric Cards (3 Cards sama seperti di Z1 App) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <!-- Card 1: Total Logs -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-surface-container-high text-on-surface-variant flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl text-primary">receipt_long</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Log Transaksi</p>
                <h3 class="text-xl sm:text-2xl font-black font-headline-md text-on-surface mt-0.5 truncate">{{ number_format($totalPrOrders ?? 0) }} Log</h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5">
                    <span class="font-bold text-amber-700">{{ $pendingCount ?? 0 }}</span> Pending &bull; <span class="font-bold text-emerald-700">{{ $approvedCount ?? 0 }}</span> Disetujui
                </p>
            </div>
        </div>

        <!-- Card 2: Total Units -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-secondary-container/40 text-on-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">inventory_2</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Unit Dipesan</p>
                <h3 class="text-xl sm:text-2xl font-black font-headline-md text-on-surface mt-0.5 truncate">{{ number_format($totalPrUnits ?? 0) }} Pcs</h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5">Akumulasi kuantitas fisik dipesan</p>
            </div>
        </div>

        <!-- Card 3: Total Nilai Estimasi -->
        <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Nilai Estimasi</p>
                <h3 class="text-xl sm:text-2xl font-black font-headline-md text-emerald-800 font-mono mt-0.5 truncate">
                    Rp {{ number_format($totalPrAmount ?? 0, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5">Total estimasi biaya pengadaan restock</p>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 sm:p-5 shadow-xs">
        <form id="purchasingOrderFilterForm" method="GET" action="{{ route('purchasing.orders.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            
            <!-- Tanggal Sesi -->
            <div class="sm:col-span-3">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Tanggal Sesi</label>
                <input 
                    type="date" 
                    name="pr_date" 
                    value="{{ $prDate ?? '' }}" 
                    class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:border-primary"
                >
            </div>

            <!-- Filter Toko (Cabang) -->
            <div class="sm:col-span-3">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Lokasi Toko</label>
                <select name="store_id" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:border-primary">
                    <option value="all" {{ (string)($storeId ?? '') === 'all' ? 'selected' : '' }}>Semua Cabang Toko</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ (string)($storeId ?? '') === (string)$st->id ? 'selected' : '' }}>
                            {{ $st->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Status (Custom Styled Dropdown) -->
            <div class="sm:col-span-3 relative" id="pr-status-dropdown-container">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Filter Status</label>
                <input type="hidden" name="pr_status" id="pr_status_input" value="{{ $prStatus ?? 'all' }}">

                <!-- Trigger Button -->
                <button 
                    type="button" 
                    id="pr-status-btn"
                    onclick="togglePrStatusDropdown(event)"
                    class="w-full px-3 py-2 bg-surface-container-low hover:bg-surface-container border border-outline-variant hover:border-primary/60 focus:border-primary rounded-xl text-xs font-medium text-on-surface flex items-center justify-between gap-2 transition-all shadow-2xs cursor-pointer select-none"
                    aria-haspopup="listbox"
                    aria-expanded="false"
                >
                    <span class="flex items-center gap-2 truncate">
                        <span id="pr-status-dot" class="w-2 h-2 rounded-full shrink-0 {{ ($prStatus ?? 'all') === 'pending' ? 'bg-amber-500' : (($prStatus ?? 'all') === 'approved' ? 'bg-emerald-500' : (($prStatus ?? 'all') === 'completed' ? 'bg-blue-500' : (($prStatus ?? 'all') === 'rejected' ? 'bg-rose-500' : 'bg-slate-400'))) }}"></span>
                        <span id="pr-status-label" class="truncate font-semibold">
                            @switch($prStatus ?? 'all')
                                @case('pending') Pending (Menunggu Review) @break
                                @case('approved') Approved (Disetujui Gudang) @break
                                @case('completed') Completed (Barang Diterima) @break
                                @case('rejected') Rejected (Ditolak) @break
                                @default Semua Status
                            @endswitch
                        </span>
                    </span>
                    <span class="material-symbols-outlined text-[18px] text-on-surface-variant transition-transform duration-200" id="pr-status-chevron">expand_more</span>
                </button>

                <!-- Dropdown Options Menu -->
                <div 
                    id="pr-status-menu" 
                    class="hidden absolute left-0 right-0 top-full mt-1.5 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xl p-1.5 z-50 space-y-0.5"
                    role="listbox"
                >
                    <button 
                        type="button" 
                        onclick="setPrStatus('all', 'Semua Status', 'bg-slate-400')" 
                        class="w-full px-3 py-2 rounded-xl text-xs font-medium flex items-center justify-between gap-2 transition-colors hover:bg-surface-container-low cursor-pointer {{ ($prStatus ?? 'all') === 'all' ? 'bg-primary/10 text-primary font-bold' : 'text-on-surface' }}"
                    >
                        <span class="flex items-center gap-2 truncate">
                            <span class="w-2 h-2 rounded-full bg-slate-400 shrink-0"></span>
                            <span>Semua Status</span>
                        </span>
                        @if(($prStatus ?? 'all') === 'all')
                            <span class="material-symbols-outlined text-base text-primary">check</span>
                        @endif
                    </button>

                    <button 
                        type="button" 
                        onclick="setPrStatus('pending', 'Pending (Menunggu Review)', 'bg-amber-500')" 
                        class="w-full px-3 py-2 rounded-xl text-xs font-medium flex items-center justify-between gap-2 transition-colors hover:bg-amber-500/10 cursor-pointer {{ ($prStatus ?? '') === 'pending' ? 'bg-amber-500/15 text-amber-800 font-bold' : 'text-on-surface' }}"
                    >
                        <span class="flex items-center gap-2 truncate">
                            <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                            <span>Pending (Menunggu Review)</span>
                        </span>
                        @if(($prStatus ?? '') === 'pending')
                            <span class="material-symbols-outlined text-base text-amber-700">check</span>
                        @endif
                    </button>

                    <button 
                        type="button" 
                        onclick="setPrStatus('approved', 'Approved (Disetujui Gudang)', 'bg-emerald-500')" 
                        class="w-full px-3 py-2 rounded-xl text-xs font-medium flex items-center justify-between gap-2 transition-colors hover:bg-emerald-500/10 cursor-pointer {{ ($prStatus ?? '') === 'approved' ? 'bg-emerald-500/15 text-emerald-800 font-bold' : 'text-on-surface' }}"
                    >
                        <span class="flex items-center gap-2 truncate">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                            <span>Approved (Disetujui Gudang)</span>
                        </span>
                        @if(($prStatus ?? '') === 'approved')
                            <span class="material-symbols-outlined text-base text-emerald-700">check</span>
                        @endif
                    </button>

                    <button 
                        type="button" 
                        onclick="setPrStatus('completed', 'Completed (Barang Diterima)', 'bg-blue-500')" 
                        class="w-full px-3 py-2 rounded-xl text-xs font-medium flex items-center justify-between gap-2 transition-colors hover:bg-blue-500/10 cursor-pointer {{ ($prStatus ?? '') === 'completed' ? 'bg-blue-500/15 text-blue-800 font-bold' : 'text-on-surface' }}"
                    >
                        <span class="flex items-center gap-2 truncate">
                            <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                            <span>Completed (Barang Diterima)</span>
                        </span>
                        @if(($prStatus ?? '') === 'completed')
                            <span class="material-symbols-outlined text-base text-blue-700">check</span>
                        @endif
                    </button>

                    <button 
                        type="button" 
                        onclick="setPrStatus('rejected', 'Rejected (Ditolak)', 'bg-rose-500')" 
                        class="w-full px-3 py-2 rounded-xl text-xs font-medium flex items-center justify-between gap-2 transition-colors hover:bg-rose-500/10 cursor-pointer {{ ($prStatus ?? '') === 'rejected' ? 'bg-rose-500/15 text-rose-800 font-bold' : 'text-on-surface' }}"
                    >
                        <span class="flex items-center gap-2 truncate">
                            <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span>
                            <span>Rejected (Ditolak)</span>
                        </span>
                        @if(($prStatus ?? '') === 'rejected')
                            <span class="material-symbols-outlined text-base text-rose-700">check</span>
                        @endif
                    </button>
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="sm:col-span-3 flex items-end gap-2 pt-2 sm:pt-0">
                <button type="submit" class="flex-1 py-2 px-4 bg-primary hover:bg-[#00553a] text-on-primary font-bold text-xs rounded-xl shadow-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-sm">filter_alt</span>
                    <span>Filter</span>
                </button>
                @if(!empty($prDate) || (!empty($prStatus) && $prStatus !== 'all') || (!empty($storeId) && $storeId !== 'all') || !empty($search))
                    <a href="{{ route('purchasing.orders.index') }}" class="p-2 rounded-xl border border-outline-variant bg-surface-container-low text-on-surface-variant hover:text-on-surface text-xs font-bold flex items-center justify-center" title="Reset Filter">
                        <span class="material-symbols-outlined text-sm">restart_alt</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table List Index --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs overflow-hidden">
        
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant/60 text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                    <tr>
                        <th class="py-3 px-4 w-10 text-center">
                            <input type="checkbox" disabled class="rounded border-outline-variant text-primary focus:ring-primary/20">
                        </th>
                        <th class="py-3 px-4">Tanggal Sesi</th>
                        <th class="py-3 px-4">No. PR</th>
                        <th class="py-3 px-4">No. PO</th>
                        <th class="py-3 px-4">Cabang Toko</th>
                        <th class="py-3 px-4 text-center">Total Item</th>
                        <th class="py-3 px-4 text-right">Total Nilai</th>
                        <th class="py-3 px-4">Rincian / Catatan</th>
                        <th class="py-3 px-4">Dicatat Oleh</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @forelse($purchaseRequests as $pr)
                    @php
                        $prItemsCount = $pr->items->count();
                        $prTotalUnits = $pr->items->sum('quantity_requested');
                        $prEstTotal = $pr->items->sum(fn($i) => ($i->product?->purchase_price ?? $i->product?->price ?? $i->estimated_price ?? 0) * $i->quantity_requested);
                    @endphp
                    <tr class="hover:bg-surface-container-low/70 transition-colors">
                        <td class="py-3.5 px-4 text-center">
                            <input type="checkbox" class="rounded border-outline-variant text-primary focus:ring-primary/20">
                        </td>

                        <!-- Tanggal Sesi -->
                        <td class="py-3.5 px-4">
                            <a href="{{ route('purchasing.orders.show', $pr->id) }}" class="flex items-center gap-2.5 group">
                                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <span class="material-symbols-outlined text-base">calendar_today</span>
                                </div>
                                <div>
                                    <span class="font-bold text-on-surface group-hover:text-primary transition-colors block">{{ \Carbon\Carbon::parse($pr->request_date)->isoFormat('D MMMM Y') }}</span>
                                    <span class="text-[10px] font-mono text-on-surface-variant">{{ $pr->request_date->format('Y-m-d') }}</span>
                                </div>
                            </a>
                        </td>

                        <!-- No. PR -->
                        <td class="py-3.5 px-4">
                            <a href="{{ route('purchasing.orders.show', $pr->id) }}" class="font-mono text-xs font-bold text-on-surface hover:text-primary transition-colors block">
                                {{ $pr->pr_number }}
                            </a>
                        </td>

                        <!-- No. PO -->
                        <td class="py-3.5 px-4">
                            @if($pr->purchaseOrders && $pr->purchaseOrders->isNotEmpty())
                                @php $linkedPo = $pr->purchaseOrders->first(); @endphp
                                <a href="{{ route('purchasing.purchase-orders.show', $linkedPo->id) }}" class="inline-flex items-center gap-1 text-[11px] text-blue-700 hover:underline font-mono font-bold" title="Buka PO Terkait">
                                    <span class="material-symbols-outlined text-[14px]">shopping_bag</span>
                                    <span>{{ $linkedPo->po_number }}</span>
                                </a>
                            @else
                                <span class="text-on-surface-variant font-mono text-xs">—</span>
                            @endif
                        </td>

                        <!-- Cabang Toko -->
                        <td class="py-3.5 px-4">
                            <span class="font-semibold text-on-surface text-xs block truncate">{{ $pr->store?->name ?? 'Toko Utama' }}</span>
                        </td>

                        <!-- Total Item (Hanya Total Unit Pcs) -->
                        <td class="py-3.5 px-4 text-center">
                            <span class="font-bold text-on-surface text-xs block">{{ $prTotalUnits }} pcs</span>
                        </td>

                        <!-- Total Nilai -->
                        <td class="py-3.5 px-4 text-right">
                            <span class="font-black text-on-surface block font-mono text-emerald-800">
                                Rp {{ number_format($prEstTotal, 0, ',', '.') }}
                            </span>
                        </td>

                        <!-- Rincian / Catatan -->
                        <td class="py-3.5 px-4 max-w-[200px]">
                            <span class="text-on-surface-variant text-[11px] truncate block" title="{{ $pr->notes }}">
                                {{ $pr->notes ? '"' . $pr->notes . '"' : '—' }}
                            </span>
                        </td>

                        <!-- Dicatat Oleh -->
                        <td class="py-3.5 px-4">
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

                        <!-- Status Column -->
                        <td class="py-3.5 px-4 text-center">
                            <div class="inline-flex flex-col items-center gap-1">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $pr->status_badge }}">
                                    {{ ucfirst($pr->status) }}
                                </span>
                                @if($pr->priority === 'urgent')
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-rose-500/10 text-rose-700 border border-rose-500/20 uppercase">
                                        Mendesak
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- Aksi -->
                        <td class="py-3.5 px-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a
                                    href="{{ route('purchasing.orders.show', $pr->id) }}"
                                    class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-colors cursor-pointer"
                                    title="Buka Rincian Order"
                                >
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                </a>

                                @if(!in_array($pr->status, ['approved', 'completed']))
                                    @if(($pr->status === 'pending' && ($prTotalUnits == 0 || $prItemsCount == 0)) || $isSuperAdmin || $pr->user_id === auth()->id() || auth()->user()->can('Stock In Create'))
                                    <form action="{{ route('purchasing.orders.destroy', $pr->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus order #{{ $pr->pr_number }}?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-on-surface-variant hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Hapus Order">
                                            <span class="material-symbols-outlined text-base">delete</span>
                                        </button>
                                    </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-12 text-on-surface-variant text-xs">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-4xl text-outline-variant">inventory_2</span>
                                <p class="font-bold">Belum ada data pengajuan order barang yang tercatat.</p>
                                <button type="button" onclick="document.getElementById('modal-create-po').showModal()" class="mt-1 text-xs text-primary font-bold hover:underline cursor-pointer">
                                    + Buat Order Barang Sekarang
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards View -->
        <div class="block md:hidden p-3 space-y-3">
            @forelse($purchaseRequests as $pr)
            @php
                $prItemsCount = $pr->items->count();
                $prTotalUnits = $pr->items->sum('quantity_requested');
                $prEstTotal = $pr->items->sum(fn($i) => ($i->product?->purchase_price ?? $i->product?->price ?? $i->estimated_price ?? 0) * $i->quantity_requested);
            @endphp
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 shadow-xs space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-lg">calendar_today</span>
                        </div>
                        <div>
                            <h4 class="font-extrabold text-sm text-on-surface leading-tight">
                                {{ \Carbon\Carbon::parse($pr->request_date)->isoFormat('D MMMM Y') }}
                            </h4>
                            <span class="text-[11px] font-mono text-on-surface-variant font-semibold">
                                {{ $pr->pr_number }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-1">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $pr->status_badge }}">
                            {{ ucfirst($pr->status) }}
                        </span>
                        @if($pr->priority === 'urgent')
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-rose-500/10 text-rose-700 border border-rose-500/20 uppercase">
                                Mendesak
                            </span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 p-2.5 rounded-xl bg-surface-container-low border border-outline-variant/60 text-xs">
                    <div>
                        <span class="text-[10px] text-on-surface-variant font-medium block">Total Barang</span>
                        <span class="font-black text-on-surface">{{ $prItemsCount }} Item ({{ $prTotalUnits }} Pcs)</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-on-surface-variant font-medium block">Total Nilai</span>
                        <span class="font-black font-mono text-emerald-800">Rp {{ number_format($prEstTotal, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-on-surface-variant">
                    <span class="flex items-center gap-1 font-semibold">
                        <span class="material-symbols-outlined text-sm text-secondary">storefront</span>
                        <span>{{ $pr->store?->name ?? 'Toko Utama' }}</span>
                    </span>
                    <span>Dibuat: {{ $pr->user?->name ?? 'Staff' }}</span>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-outline-variant/50">
                    @if(($pr->status === 'pending' && ($prTotalUnits == 0 || $prItemsCount == 0)) || $isSuperAdmin || $pr->user_id === auth()->id() || auth()->user()->can('Stock In Create'))
                        @if($pr->status !== 'completed' || $isSuperAdmin)
                        <form action="{{ route('purchasing.orders.destroy', $pr->id) }}" method="POST" onsubmit="return confirm('Hapus order #{{ $pr->pr_number }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-bold transition-all cursor-pointer" title="Hapus Order">
                                <span class="material-symbols-outlined text-base">delete</span>
                            </button>
                        </form>
                        @endif
                    @endif
                    <a href="{{ route('purchasing.orders.show', $pr->id) }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-primary text-on-primary rounded-xl font-bold text-xs shadow-xs hover:bg-[#00553a] transition-all">
                        <span>Rincian Order</span>
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </div>
            @empty
            <div class="text-center py-10 text-on-surface-variant text-xs space-y-2">
                <span class="material-symbols-outlined text-3xl text-outline-variant block">inventory_2</span>
                <p class="font-bold">Belum ada data pengajuan order barang yang tercatat.</p>
                <button type="button" onclick="document.getElementById('modal-create-po').showModal()" class="text-xs text-primary font-bold hover:underline cursor-pointer">
                    + Buat Order Barang Sekarang
                </button>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($purchaseRequests->hasPages())
        <div class="p-4 border-t border-outline-variant/60">
            {{ $purchaseRequests->links() }}
        </div>
        @endif
    </div>

    {{-- MODAL: BUAT ORDER BARANG (PURCHASE REQUEST) --}}
    <dialog id="modal-create-po" class="rounded-2xl p-0 bg-surface-container-lowest border border-outline-variant shadow-2xl backdrop:bg-black/40 backdrop:backdrop-blur-xs w-[94vw] max-w-lg m-auto">
        <div class="flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 sm:p-5 border-b border-outline-variant/60">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">add_shopping_cart</span>
                    </span>
                    <div>
                        <h3 class="font-black text-sm sm:text-base text-on-surface">Buat Order Barang (Purchase Request)</h3>
                        <p class="text-[11px] sm:text-xs text-on-surface-variant">Tentukan cabang tujuan restock dan jadwal order</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modal-create-po').close()" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low cursor-pointer">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Form -->
            <form action="{{ route('purchasing.orders.store') }}" method="POST" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
                @csrf
                
                <!-- Cabang Toko -->
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Cabang Toko / Gudang Restock: <span class="text-rose-500">*</span></label>
                    <select name="store_id" required class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary">
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ (string)($activeStoreId ?? '') === (string)$st->id ? 'selected' : '' }}>
                                {{ $st->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Tanggal Order / Request: <span class="text-rose-500">*</span></label>
                        <input type="date" name="request_date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Tingkat Urgensi: <span class="text-rose-500">*</span></label>
                        <select name="priority" required class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary">
                            <option value="normal">Normal (Restock Berkala)</option>
                            <option value="urgent">Mendesak (Stok Toko Menipis)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Catatan / Catatan Pengadaan (Opsional):</label>
                    <textarea name="notes" rows="3" placeholder="Contoh: Order restock mingguan untuk etalase utama..." class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary resize-none"></textarea>
                </div>

                <!-- Footer / Submit -->
                <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="document.getElementById('modal-create-po').close()" class="px-4 py-2.5 rounded-xl border border-outline-variant text-xs font-bold text-on-surface-variant hover:bg-surface-container cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary hover:bg-[#00553a] text-on-primary text-xs font-bold shadow-xs hover:opacity-95 transition-all cursor-pointer">
                        Lanjut Tambah Barang &rarr;
                    </button>
                </div>
            </form>
        </div>
    </dialog>

</div>

<script>
    // Custom Status Dropdown Handler
    function togglePrStatusDropdown(event) {
        event.stopPropagation();
        const menu = document.getElementById('pr-status-menu');
        const chevron = document.getElementById('pr-status-chevron');
        const isHidden = menu.classList.contains('hidden');

        if (isHidden) {
            menu.classList.remove('hidden');
            chevron.classList.add('rotate-180');
        } else {
            menu.classList.add('hidden');
            chevron.classList.remove('rotate-180');
        }
    }

    function setPrStatus(value, label, dotColorClass) {
        document.getElementById('pr_status_input').value = value;
        document.getElementById('pr-status-label').textContent = label;

        const dot = document.getElementById('pr-status-dot');
        dot.className = 'w-2 h-2 rounded-full shrink-0 ' + dotColorClass;

        document.getElementById('pr-status-menu').classList.add('hidden');
        document.getElementById('pr-status-chevron').classList.remove('rotate-180');

        // Submit form
        document.getElementById('purchasingOrderFilterForm').submit();
    }

    // Close dropdown when clicked outside
    document.addEventListener('click', function(event) {
        const container = document.getElementById('pr-status-dropdown-container');
        if (container && !container.contains(event.target)) {
            const menu = document.getElementById('pr-status-menu');
            const chevron = document.getElementById('pr-status-chevron');
            if (menu && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
                if (chevron) chevron.classList.remove('rotate-180');
            }
        }
    });
</script>
@endsection
