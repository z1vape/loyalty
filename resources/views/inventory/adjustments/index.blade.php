@extends('layouts.app')

@section('title', 'Penyesuaian Stok (Stock Opname) | InvenTrack')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Flash Alert Messages --}}
    @if(session('success'))
        <div class="p-4 bg-[#e8f5e9] border border-[#006c49]/30 rounded-2xl flex items-center justify-between gap-3 text-[#006c49]">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-xl">check_circle</span>
                <span class="text-sm font-bold">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="hover:opacity-75 cursor-pointer">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-error-container/40 border border-error/30 rounded-2xl flex items-center justify-between gap-3 text-error">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-xl">error</span>
                <span class="text-sm font-bold">{{ $errors->first() }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-error hover:opacity-75 cursor-pointer">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    {{-- Header & Action Button --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-[#006c49] text-white flex items-center justify-center shadow-md shadow-emerald-900/10 shrink-0">
                <span class="material-symbols-outlined text-2xl">folder_managed</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-secondary">Inventory</span>
                    <span class="text-outline-variant font-bold">•</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">Stock Opname</span>
                </div>
                <h1 class="font-headline-lg text-headline-lg text-on-surface font-black">Audit & Penyesuaian Stok</h1>
                <p class="text-on-surface-variant text-body-md">Folder sesi opname fisik toko yang dikelompokkan berdasarkan tanggal audit.</p>
            </div>
        </div>

        @can('Stock In Store')
        <button
            type="button"
            onclick="document.getElementById('modal-date-opname').showModal()"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer border-0 self-start sm:self-auto"
        >
            <span class="material-symbols-outlined text-base">calendar_add_on</span>
            <span>Buat Tanggal Sesi</span>
        </button>
        @endcan
    </div>

    {{-- Metric Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">folder</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Tanggal Sesi Opname</p>
                <h3 class="text-xl font-headline-md font-extrabold text-on-surface mt-0.5 truncate">{{ number_format($summaryTotalFolders ?? 0) }} Sesi</h3>
            </div>
        </div>

        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-sky-500/10 text-sky-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">inventory_2</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Log Produk Diselaraskan</p>
                <h3 class="text-xl font-headline-md font-extrabold text-on-surface mt-0.5 truncate">{{ number_format($summaryTotalItems ?? 0) }} Item</h3>
            </div>
        </div>

        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl {{ ($summaryNetUnits ?? 0) >= 0 ? 'bg-emerald-500/10 text-emerald-600' : 'bg-rose-500/10 text-rose-600' }} flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">{{ ($summaryNetUnits ?? 0) >= 0 ? 'trending_up' : 'trending_down' }}</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Selisih Netto Fisik</p>
                <h3 class="text-xl font-headline-md font-extrabold mt-0.5 truncate {{ ($summaryNetUnits ?? 0) >= 0 ? 'text-[#006c49]' : 'text-rose-600' }}">
                    {{ ($summaryNetUnits ?? 0) > 0 ? '+' : '' }}{{ number_format($summaryNetUnits ?? 0) }} unit
                </h3>
            </div>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 sm:p-5 shadow-sm">
        <form method="GET" action="{{ route('stock-adjustments.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-medium text-on-surface focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] transition">
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-medium text-on-surface focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] transition">
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Produk</label>
                <select name="product_id" id="adjustment_product_id" class="w-full">
                    <option value="">Semua Produk</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->sku }} - {{ $product->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Alasan / Tipe</label>
                <select name="reason" id="adjustment_reason" class="w-full">
                    <option value="">Semua Tipe</option>
                    @foreach($reasons as $key => $label)
                        <option value="{{ $key }}" @selected(request('reason') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-[#006c49] text-white hover:bg-[#00553a] font-bold text-xs sm:text-sm rounded-xl active:scale-95 transition-all shadow-sm cursor-pointer h-[42px]">
                    <span class="material-symbols-outlined text-sm">filter_alt</span>
                    <span>Filter</span>
                </button>
                @if(request()->hasAny(['date_from', 'date_to', 'product_id', 'reason', 'user_id']))
                <a href="{{ route('stock-adjustments.index') }}" class="inline-flex items-center justify-center gap-1 px-3.5 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-semibold text-xs sm:text-sm rounded-xl transition-all cursor-pointer h-[42px] no-underline" title="Reset Filter">
                    <span class="material-symbols-outlined text-sm">restart_alt</span>
                    <span>Reset</span>
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Date Folders Table Card --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm overflow-hidden flex flex-col">
        <div class="px-5 py-4 border-b border-outline-variant/60 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-500 text-lg">folder_open</span>
                <h2 class="text-sm font-black text-on-surface uppercase tracking-wider">Folder Tanggal Sesi Stock Opname</h2>
            </div>
            <span class="text-xs text-on-surface-variant font-medium">Klik folder tanggal untuk melihat dan menambah rincian barang</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5">Folder Tanggal Opname</th>
                        <th class="px-5 py-3.5 text-center">Jumlah Barang</th>
                        <th class="px-5 py-3.5">Rincian Selisih Fisik</th>
                        <th class="px-5 py-3.5">Petugas / Operator</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @forelse($dateFolders as $d)
                    @php
                        $details = $folderDetails[$d] ?? null;
                        $carbonDate = \Carbon\Carbon::parse($d);
                        $dateLabel = $carbonDate->translatedFormat('d F Y');
                        $dayName = $carbonDate->translatedFormat('l');
                        $isToday = $carbonDate->isToday();
                        $totalItems = $details['total_items'] ?? 0;
                        $totalAdded = $details['total_added'] ?? 0;
                        $totalReduced = $details['total_reduced'] ?? 0;
                        $netChange = $details['net_change'] ?? 0;
                        $totalMinusQty = $details['total_minus_qty'] ?? 0;
                        $totalMinusNominal = $details['total_minus_nominal'] ?? 0;
                        $users = $details['users'] ?? collect();
                        $hasReversals = $details['has_reversals'] ?? false;
                        $sampleProducts = $details['sample_products'] ?? collect();
                        $notes = $details['notes'] ?? null;
                    @endphp
                    <tr class="hover:bg-surface-container-low/50 transition-colors group cursor-pointer" onclick="if(!event.target.closest('a, button')) { window.location.href = '{{ route('stock-adjustments.date', ['date' => $d]) }}'; }">
                        {{-- Folder Tanggal --}}
                        <td class="px-5 py-4">
                            <a href="{{ route('stock-adjustments.date', ['date' => $d]) }}" class="flex items-center gap-3.5 no-underline group-hover:text-primary transition">
                                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-600 border border-amber-500/20 flex items-center justify-center shrink-0 shadow-xs group-hover:scale-105 group-hover:bg-[#006c49]/10 group-hover:text-[#006c49] group-hover:border-[#006c49]/30 transition-all">
                                    <span class="material-symbols-outlined text-xl">folder</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-extrabold text-on-surface group-hover:text-primary transition">{{ $dateLabel }}</span>
                                        @if($isToday)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-300">
                                                Hari Ini
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[11px] text-on-surface-variant font-medium">{{ $dayName }}</span>
                                        @if($notes)
                                            <span class="text-[11px] text-outline-variant">•</span>
                                            <span class="text-[11px] text-on-surface-variant truncate max-w-xs italic" title="{{ $notes }}">
                                                "{{ $notes }}"
                                            </span>
                                        @elseif($sampleProducts->isNotEmpty())
                                            <span class="text-[11px] text-outline-variant">•</span>
                                            <span class="text-[11px] text-on-surface-variant truncate max-w-xs" title="{{ $sampleProducts->implode(', ') }}">
                                                {{ $sampleProducts->implode(', ') }}{{ $totalItems > 3 ? '...' : '' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        </td>

                        {{-- Total Barang / Item --}}
                        <td class="px-5 py-4 text-center whitespace-nowrap">
                            @if($totalItems > 0)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-extrabold bg-surface-container-high text-on-surface border border-outline-variant/60">
                                    <span class="material-symbols-outlined text-sm text-primary">inventory_2</span>
                                    <span>{{ $totalItems }} Produk</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    <span class="material-symbols-outlined text-xs">pending</span>
                                    <span>Belum ada barang</span>
                                </span>
                            @endif
                        </td>

                        {{-- Rincian Selisih Fisik --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($totalItems > 0)
                                <div class="flex items-center gap-2.5">
                                    @if($totalAdded > 0)
                                        <span class="inline-flex items-center gap-0.5 text-xs font-bold text-emerald-600 bg-emerald-500/10 px-2 py-0.5 rounded-lg border border-emerald-500/20" title="Total Penambahan">
                                            <span class="material-symbols-outlined text-xs">arrow_upward</span>
                                            +{{ number_format($totalAdded) }}
                                        </span>
                                    @endif
                                    @if($totalReduced < 0)
                                        <span class="inline-flex items-center gap-0.5 text-xs font-bold text-rose-600 bg-rose-500/10 px-2 py-0.5 rounded-lg border border-rose-500/20" title="Total Pengurangan">
                                            <span class="material-symbols-outlined text-xs">arrow_downward</span>
                                            {{ number_format($totalReduced) }}
                                        </span>
                                    @endif
                                    <span class="text-xs font-extrabold font-mono {{ $netChange >= 0 ? 'text-[#006c49]' : 'text-rose-600' }}" title="Netto Selisih">
                                        (Net: {{ $netChange > 0 ? '+' : '' }}{{ number_format($netChange) }} unit)
                                    </span>
                                </div>
                            @else
                                <span class="text-on-surface-variant/60 text-xs">-</span>
                            @endif
                        </td>

                        {{-- Petugas / Operator --}}
                        <td class="px-5 py-4 text-xs text-on-surface-variant whitespace-nowrap">
                            @if($users->isNotEmpty())
                                <span class="font-medium text-on-surface">{{ $users->implode(', ') }}</span>
                            @else
                                <span class="text-on-surface-variant">Sistem</span>
                            @endif
                        </td>

                        {{-- Aksi: Buka Folder & Bebankan ke Payroll --}}
                        <td class="px-5 py-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button 
                                    type="button" 
                                    onclick="openPayrollDeductionModal('{{ $d }}', '{{ $dateLabel }}', {{ $totalMinusQty }}, {{ $totalMinusNominal }}, {{ json_encode($details['existing_allocations'] ?? (object)[]) }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-900 border border-amber-500/30 text-xs font-bold rounded-xl transition-all shadow-xs active:scale-95 cursor-pointer"
                                    title="Bebankan minus SO ke Payroll Karyawan"
                                >
                                    <span class="material-symbols-outlined text-sm text-amber-700">payments</span>
                                    <span>Bebankan ke Payroll</span>
                                </button>

                                <a 
                                    href="{{ route('stock-adjustments.date', ['date' => $d]) }}" 
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#006c49] hover:bg-[#00553a] text-white text-xs font-bold rounded-xl transition-all shadow-xs active:scale-95 no-underline cursor-pointer"
                                    title="Buka dan lihat rincian barang pada tanggal ini"
                                >
                                    <span class="material-symbols-outlined text-sm">folder_open</span>
                                    <span>Buka Folder</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-14 text-center text-on-surface-variant text-sm">
                            <div class="flex flex-col items-center justify-center gap-3">
                                <div class="w-16 h-16 rounded-3xl bg-surface-container-high text-on-surface-variant flex items-center justify-center">
                                    <span class="material-symbols-outlined text-3xl text-outline-variant">folder_off</span>
                                </div>
                                <div class="max-w-sm">
                                    <p class="font-bold text-on-surface text-base">Belum ada folder sesi stock opname</p>
                                    <p class="text-xs text-on-surface-variant mt-1">Buat tanggal sesi opname baru untuk memulai audit fisik inventaris toko.</p>
                                </div>
                                @can('Stock In Store')
                                <button
                                    type="button"
                                    onclick="document.getElementById('modal-date-opname').showModal()"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-xs font-bold rounded-xl hover:opacity-90 transition mt-2 cursor-pointer border-0"
                                >
                                    <span class="material-symbols-outlined text-sm">calendar_add_on</span>
                                    <span>Buat Tanggal Sesi Sekarang</span>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($dateFolders->hasPages())
        <div class="px-5 py-4 border-t border-outline-variant/60">
            {{ $dateFolders->links() }}
        </div>
        @endif
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     MODAL: Buat Tanggal Sesi Stock Opname
════════════════════════════════════════════════════════════════════════════ --}}
<dialog id="modal-date-opname"
    class="backdrop:bg-black/50 p-0 rounded-2xl border border-outline-variant shadow-2xl w-full max-w-md bg-white overflow-visible">
    <div class="px-6 py-4 border-b border-outline-variant bg-surface-container-low flex items-center justify-between">
        <h3 class="font-bold text-on-surface text-base flex items-center gap-2">
            <span class="material-symbols-outlined text-[#006c49]">calendar_add_on</span>
            <span>Buat Tanggal Sesi</span>
            <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-extrabold">Stock Opname</span>
        </h3>
        <button onclick="document.getElementById('modal-date-opname').close()"
            class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant border-0 bg-transparent cursor-pointer transition">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <form action="{{ route('stock-adjustments.store-date') }}" method="POST" class="px-6 py-5 space-y-4">
        @csrf

        {{-- Date picker --}}
        <div>
            <label for="opname_date" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                Tanggal Opname <span class="text-error">*</span>
            </label>
            <input type="date" name="date" id="opname_date"
                value="{{ date('Y-m-d') }}"
                required
                class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#006c49]/40 focus:border-[#006c49] transition" />
        </div>

        {{-- Created by (read-only) --}}
        <div>
            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">Created By</label>
            <div class="w-full px-4 py-2.5 bg-surface-container border border-outline-variant rounded-xl text-sm text-on-surface-variant flex items-center gap-2 select-none">
                <span class="w-7 h-7 rounded-full bg-primary-container text-on-primary-container text-xs font-extrabold flex items-center justify-center uppercase shrink-0">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </span>
                <span class="font-semibold">{{ auth()->user()->name }}</span>
                <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-primary-container text-on-primary-container font-bold">
                    {{ auth()->user()->roles->first()?->name ?? 'No Role' }}
                </span>
            </div>
            <p class="text-xs text-on-surface-variant mt-1 opacity-60">Otomatis tercatat sebagai user yang sedang login.</p>
        </div>

        {{-- Notes --}}
        <div>
            <label for="opname_notes" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                Notes <span class="text-on-surface-variant font-normal">(opsional)</span>
            </label>
            <textarea name="notes" id="opname_notes" rows="2"
                placeholder="Keterangan opsional..."
                class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#006c49]/40 focus:border-[#006c49] transition resize-none"></textarea>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3 pt-1">
            <button type="button"
                onclick="document.getElementById('modal-date-opname').close()"
                class="flex-1 py-2.5 border border-outline-variant border-solid rounded-xl text-sm font-bold text-on-surface hover:bg-surface-container-low transition bg-transparent cursor-pointer">
                Batal
            </button>
            <button type="submit"
                class="flex-1 py-2.5 bg-[#006c49] text-white hover:bg-[#00553a] rounded-xl text-sm font-bold active:scale-95 transition shadow-sm border-0 cursor-pointer">
                Buat Tanggal
            </button>
        </div>
    </form>
</dialog>

{{-- ═══════════════════════════════════════════════════════════════════════
     MODAL: Bebankan Selisih SO ke Payroll Karyawan
════════════════════════════════════════════════════════════════════════════ --}}
<dialog id="modal-payroll-deduction"
    class="backdrop:bg-black/50 p-0 rounded-2xl border border-outline-variant shadow-2xl w-full max-w-lg bg-white overflow-hidden m-auto">
    <div class="px-6 py-4 border-b border-outline-variant bg-surface-container-low flex items-center justify-between">
        <h3 class="font-bold text-on-surface text-base flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-700">payments</span>
            <span>Bebankan Selisih SO ke Payroll</span>
        </h3>
        <button onclick="document.getElementById('modal-payroll-deduction').close()"
            class="p-1 hover:bg-surface-container-high rounded-full text-on-surface-variant border-0 bg-transparent cursor-pointer transition">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <form action="{{ route('stock-adjustments.assign-payroll') }}" method="POST" class="px-6 py-5 space-y-4" id="form-payroll-deduction">
        @csrf
        <input type="hidden" name="date" id="payroll_so_date" value="">

        {{-- Info Banner Ringkasan SO --}}
        <div class="p-3.5 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center justify-between gap-3 text-xs">
            <div>
                <span class="text-on-surface-variant block text-[10px] uppercase font-bold tracking-wider">Tanggal Sesi Opname</span>
                <span id="payroll_so_date_label" class="font-extrabold text-on-surface text-sm">-</span>
            </div>
            <div class="text-right">
                <span class="text-on-surface-variant block text-[10px] uppercase font-bold tracking-wider">Total Minus / Hilang</span>
                <span id="payroll_so_minus_info" class="font-extrabold text-rose-700 font-mono text-sm">0 unit</span>
            </div>
        </div>

        {{-- Filter Toko & Periode Payroll --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="payroll_store_id" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                    Pilih Toko
                </label>
                <select name="store_id" id="payroll_store_id" onchange="filterEmployeesByStore(this.value)" class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-[#006c49]/40 focus:border-[#006c49] transition">
                    <option value="">-- Semua Toko / Pilih Toko --</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                    Periode Gaji <span class="text-error">*</span>
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <select name="period_month" id="payroll_period_month" class="w-full px-2.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-[#006c49]/40 focus:border-[#006c49] transition">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($m == date('n'))>{{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}</option>
                        @endfor
                    </select>
                    <select name="period_year" id="payroll_period_year" class="w-full px-2.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-[#006c49]/40 focus:border-[#006c49] transition">
                        @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                            <option value="{{ $y }}" @selected($y == date('Y'))>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>

        {{-- Nominal Total Beban --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="payroll_total_nominal" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide">
                    Total Nominal Potongan (Rp) <span class="text-error">*</span>
                </label>
                <button type="button" onclick="distributeEvenly()" class="text-[11px] font-bold text-[#006c49] hover:underline bg-transparent border-0 cursor-pointer p-0">
                    Bagi Rata ke Terpilih
                </button>
            </div>
            <input type="number" id="payroll_total_nominal" min="0" step="any" placeholder="Contoh: 150000" oninput="distributeEvenly()"
                class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-bold font-mono text-on-surface focus:outline-none focus:ring-2 focus:ring-[#006c49]/40 focus:border-[#006c49] transition" />
            <p class="text-[11px] text-on-surface-variant mt-1">Otomatis terisi nilai harga jual minus SO, dapat Anda ubah bila hanya membebankan sebagian.</p>
        </div>

        {{-- Daftar Karyawan yang Kena Beban --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide">
                    Pilih Karyawan yang Menanggung <span class="text-error">*</span>
                </label>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="toggleSelectAllEmployees(true)" class="text-[11px] text-[#006c49] hover:underline bg-transparent border-0 cursor-pointer p-0 font-semibold">Pilih Semua</button>
                    <span class="text-outline-variant">•</span>
                    <button type="button" onclick="toggleSelectAllEmployees(false)" class="text-[11px] text-on-surface-variant hover:underline bg-transparent border-0 cursor-pointer p-0 font-semibold">Batal Semua</button>
                </div>
            </div>

            <div id="payroll_employees_container" class="max-h-56 overflow-y-auto border border-outline-variant rounded-xl divide-y divide-outline-variant/60 bg-surface-container-lowest">
                {{-- Di-render dinamis via JS --}}
            </div>
            <p id="payroll_employee_count_label" class="text-[11px] font-medium text-on-surface-variant mt-1">0 karyawan terpilih</p>
        </div>

        {{-- Catatan Opsional --}}
        <div>
            <label for="payroll_note" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                Catatan / Keterangan Slip Gaji
            </label>
            <input type="text" name="note" id="payroll_note" placeholder="Contoh: Beban minus stok opname..."
                class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#006c49]/40 focus:border-[#006c49] transition" />
        </div>

        {{-- Actions --}}
        <div class="flex gap-3 pt-2">
            <button type="button"
                onclick="document.getElementById('modal-payroll-deduction').close()"
                class="flex-1 py-2.5 border border-outline-variant border-solid rounded-xl text-sm font-bold text-on-surface hover:bg-surface-container-low transition bg-transparent cursor-pointer">
                Batal
            </button>
            <button type="submit"
                class="flex-1 py-2.5 bg-[#006c49] text-white hover:bg-[#00553a] rounded-xl text-sm font-bold active:scale-95 transition shadow-sm border-0 cursor-pointer flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Terapkan ke Payroll</span>
            </button>
        </div>
    </form>
</dialog>

@endsection

@push('scripts')
<script>
    const rawEmployees = @json($employees ?? []);

    let currentSoDate = '';
    let currentSoNominal = 0;
    let currentExistingAllocations = {};

    function openPayrollDeductionModal(dateStr, dateLabel, minusQty, minusNominal, existingAllocations = {}) {
        currentSoDate = dateStr;
        currentSoNominal = minusNominal || 0;
        currentExistingAllocations = existingAllocations || {};

        document.getElementById('payroll_so_date').value = dateStr;
        document.getElementById('payroll_so_date_label').textContent = dateLabel;
        document.getElementById('payroll_so_minus_info').textContent = minusQty + ' unit (Rp ' + Number(minusNominal || 0).toLocaleString('id-ID') + ')';

        // Pre-fill total nominal from existing allocations if present, else SO minus
        const existingKeys = Object.keys(currentExistingAllocations);
        if (existingKeys.length > 0) {
            let sumExisting = 0;
            existingKeys.forEach(k => sumExisting += Number(currentExistingAllocations[k]) || 0);
            document.getElementById('payroll_total_nominal').value = sumExisting > 0 ? sumExisting : (minusNominal > 0 ? minusNominal : '');
        } else {
            document.getElementById('payroll_total_nominal').value = minusNominal > 0 ? minusNominal : '';
        }

        document.getElementById('payroll_note').value = 'Beban selisih stok opname ' + dateLabel;

        // Set month and year to SO date
        if (dateStr) {
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                document.getElementById('payroll_period_year').value = parseInt(parts[0], 10);
                document.getElementById('payroll_period_month').value = parseInt(parts[1], 10);
            }
        }

        renderEmployeeList();
        document.getElementById('modal-payroll-deduction').showModal();
    }

    function filterEmployeesByStore(storeId) {
        renderEmployeeList(storeId);
    }

    function renderEmployeeList(storeIdFilter = null) {
        const storeId = storeIdFilter !== null ? storeIdFilter : document.getElementById('payroll_store_id').value;
        const container = document.getElementById('payroll_employees_container');
        container.innerHTML = '';

        const filtered = rawEmployees.filter(emp => {
            if (!storeId) return true;
            return emp.store_ids && emp.store_ids.map(String).includes(String(storeId));
        });

        if (filtered.length === 0) {
            container.innerHTML = `
                <div class="p-6 text-center text-xs text-on-surface-variant">
                    Tidak ada karyawan yang terhubung ke toko ini.
                </div>
            `;
            updateEmployeeCount();
            return;
        }

        filtered.forEach(emp => {
            const row = document.createElement('div');
            row.className = 'p-3 flex items-center justify-between gap-3 hover:bg-surface-container-low/60 transition';
            const prevAmount = currentExistingAllocations[emp.id] !== undefined ? currentExistingAllocations[emp.id] : null;
            const isChecked = prevAmount !== null && prevAmount > 0;

            row.innerHTML = `
                <div class="flex items-center gap-2.5 min-w-0">
                    <input type="checkbox" id="emp_check_${emp.id}" class="emp-checkbox rounded border-outline-variant text-[#006c49] focus:ring-[#006c49] cursor-pointer" onchange="onEmployeeCheckChanged()" data-id="${emp.id}" ${isChecked ? 'checked' : ''} />
                    <label for="emp_check_${emp.id}" class="cursor-pointer min-w-0 select-none">
                        <span class="font-bold text-xs text-on-surface block truncate">${escapeHtml(emp.name)}</span>
                        <span class="text-[10px] text-on-surface-variant block truncate">${escapeHtml(emp.position)} ${emp.store_names ? '• ' + escapeHtml(emp.store_names) : ''}</span>
                    </label>
                </div>
                <div class="flex items-center gap-1.5 shrink-0 w-32">
                    <span class="text-[11px] font-mono text-on-surface-variant">Rp</span>
                    <input type="number" min="0" step="any" name="deductions[${emp.id}]" id="emp_deduction_${emp.id}" value="${isChecked ? prevAmount : ''}" placeholder="0" class="emp-deduction-input w-full px-2 py-1.5 bg-surface-container-low border border-outline-variant rounded-lg text-xs font-mono font-bold text-right text-on-surface focus:outline-none focus:ring-1 focus:ring-[#006c49]" oninput="updateEmployeeCount()" ${isChecked ? '' : 'disabled'} />
                </div>
            `;
            container.appendChild(row);
        });

        updateEmployeeCount();
    }

    function onEmployeeCheckChanged() {
        distributeEvenly();
        updateEmployeeCount();
    }

    function toggleSelectAllEmployees(checkAll) {
        const checkboxes = document.querySelectorAll('.emp-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = checkAll;
        });
        distributeEvenly();
        updateEmployeeCount();
    }

    function distributeEvenly() {
        const totalVal = parseFloat(document.getElementById('payroll_total_nominal').value) || 0;
        const checkedBoxes = Array.from(document.querySelectorAll('.emp-checkbox:checked'));
        const allBoxes = document.querySelectorAll('.emp-checkbox');

        // First enable/disable inputs based on check state
        allBoxes.forEach(cb => {
            const input = document.getElementById('emp_deduction_' + cb.dataset.id);
            if (input) {
                input.disabled = !cb.checked;
                if (!cb.checked) {
                    input.value = '';
                }
            }
        });

        if (checkedBoxes.length > 0 && totalVal > 0) {
            const perPerson = Math.round(totalVal / checkedBoxes.length);
            checkedBoxes.forEach(cb => {
                const input = document.getElementById('emp_deduction_' + cb.dataset.id);
                if (input) {
                    input.value = perPerson;
                }
            });
        }

        updateEmployeeCount();
    }

    function updateEmployeeCount() {
        const checked = document.querySelectorAll('.emp-checkbox:checked').length;
        const countLabel = document.getElementById('payroll_employee_count_label');
        if (countLabel) {
            countLabel.textContent = checked + ' karyawan terpilih untuk menanggung beban';
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('adjustment_product_id')) {
            new TomSelect('#adjustment_product_id', {
                create: false,
                allowEmptyOption: true,
                maxOptions: 1000,
                placeholder: 'Semua Produk',
            });
        }
        if (document.getElementById('adjustment_reason')) {
            new TomSelect('#adjustment_reason', {
                create: false,
                allowEmptyOption: true,
                maxOptions: 100,
                placeholder: 'Semua Tipe',
            });
        }
    });
</script>
@endpush
