@extends('layouts.app')

@php
    $carbonDate = \Carbon\Carbon::parse($date);
    $dateLabel = $carbonDate->translatedFormat('d F Y');
    $dayName = $carbonDate->translatedFormat('l');
    $isToday = $carbonDate->isToday();
@endphp

@section('title', "Rincian Opname: {$dateLabel} | InvenTrack")

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

    {{-- Header & Action Buttons --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 border border-amber-500/30 flex items-center justify-center shadow-xs shrink-0">
                <span class="material-symbols-outlined text-2xl">folder_open</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('stock-adjustments.index') }}" class="text-[10px] font-extrabold uppercase tracking-wider text-secondary hover:text-primary transition no-underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">arrow_back</span>
                        <span>Daftar Folder Opname</span>
                    </a>
                    <span class="text-outline-variant font-bold">•</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">{{ $dayName }}</span>
                    @if($isToday)
                        <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-300">
                            Hari Ini
                        </span>
                    @endif
                </div>
                <h1 class="font-headline-lg text-headline-lg text-on-surface font-black mt-0.5">
                    Stock Opname: {{ $dateLabel }}
                </h1>
                <p class="text-on-surface-variant text-body-md">Daftar rincian seluruh produk yang disesuaikan pada tanggal ini.</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-auto flex-wrap">
            <a href="{{ route('stock-adjustments.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-semibold text-xs sm:text-sm rounded-xl transition-all shadow-xs no-underline cursor-pointer">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                <span>Kembali ke Folder</span>
            </a>

            @can('Stock In Store')
            <a href="{{ route('stock-adjustments.create', ['date' => $date]) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>+ Tambah Barang ke Tanggal Ini</span>
            </a>
            @endcan
        </div>
    </div>

    {{-- Sesi Creator & Notes Banner --}}
    @if(isset($session) && ($session->notes || $session->creator))
    <div class="p-3.5 bg-surface-container-lowest border border-outline-variant/60 rounded-2xl flex flex-wrap items-center justify-between gap-3 text-xs shadow-xs">
        <div class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[#006c49] text-base">info</span>
            <span class="text-on-surface-variant">Sesi Dibuat Oleh: <strong class="text-on-surface">{{ $session->creator?->name ?? 'Sistem' }}</strong></span>
            @if($session->notes)
                <span class="text-outline-variant">•</span>
                <span class="text-on-surface-variant">Keterangan: <em class="text-on-surface font-medium">"{{ $session->notes }}"</em></span>
            @endif
        </div>
        <span class="text-[11px] text-on-surface-variant/70 font-mono">{{ $carbonDate->format('d/m/Y') }}</span>
    </div>
    @endif

    {{-- Metric Summary Cards Tanggal Ini --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="p-4 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
            <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Produk Di-audit</span>
            <div class="text-xl font-headline-md font-extrabold text-on-surface mt-1 flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-primary">inventory_2</span>
                <span>{{ number_format($summary['total_items'] ?? 0) }} Produk</span>
            </div>
        </div>

        <div class="p-4 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
            <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Penambahan (+)</span>
            <div class="text-xl font-headline-md font-extrabold text-emerald-600 mt-1 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">arrow_upward</span>
                <span>+{{ number_format($summary['total_added'] ?? 0) }} unit</span>
            </div>
        </div>

        <div class="p-4 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
            <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Pengurangan (-)</span>
            <div class="text-xl font-headline-md font-extrabold text-rose-600 mt-1 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">arrow_downward</span>
                <span>{{ number_format($summary['total_reduced'] ?? 0) }} unit</span>
            </div>
        </div>

        <div class="p-4 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
            <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Netto Selisih Fisik</span>
            <div class="text-xl font-headline-md font-extrabold mt-1 flex items-center gap-1.5 {{ ($summary['net_change'] ?? 0) >= 0 ? 'text-[#006c49]' : 'text-rose-600' }}">
                <span class="material-symbols-outlined text-base">{{ ($summary['net_change'] ?? 0) >= 0 ? 'trending_up' : 'trending_down' }}</span>
                <span>{{ ($summary['net_change'] ?? 0) > 0 ? '+' : '' }}{{ number_format($summary['net_change'] ?? 0) }} unit</span>
            </div>
        </div>
    </div>

    {{-- Search & Filter in Date Folder --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 sm:p-5 shadow-sm">
        <form method="GET" action="{{ route('stock-adjustments.date', ['date' => $date]) }}" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Cari Produk / SKU</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant text-base">search</span>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Ketik nama produk atau SKU..." 
                        class="w-full pl-9 pr-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-medium text-on-surface focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] transition"
                    >
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Alasan / Tipe</label>
                <select name="reason" class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-medium text-on-surface focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] transition">
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
                @if(request()->hasAny(['search', 'reason', 'product_id']))
                <a href="{{ route('stock-adjustments.date', ['date' => $date]) }}" class="inline-flex items-center justify-center gap-1 px-3.5 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-semibold text-xs sm:text-sm rounded-xl transition-all cursor-pointer h-[42px] no-underline" title="Reset Filter">
                    <span class="material-symbols-outlined text-sm">restart_alt</span>
                    <span>Reset</span>
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Items Table Card --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm overflow-hidden flex flex-col">
        <div class="px-5 py-4 border-b border-outline-variant/60 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-lg">list_alt</span>
                <h2 class="text-sm font-black text-on-surface uppercase tracking-wider">Daftar Barang Sesi Opname ({{ $adjustments->total() }} Log)</h2>
            </div>
            @can('Stock In Store')
            <a href="{{ route('stock-adjustments.create', ['date' => $date]) }}" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                <span class="material-symbols-outlined text-sm">add</span>
                <span>Tambah Barang Baru</span>
            </a>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5 text-center w-12">No</th>
                        <th class="px-5 py-3.5">Waktu</th>
                        <th class="px-5 py-3.5">Produk & SKU</th>
                        <th class="px-5 py-3.5">Petugas</th>
                        <th class="px-5 py-3.5">Alasan</th>
                        <th class="px-5 py-3.5 text-center">Stok Awal</th>
                        <th class="px-5 py-3.5 text-center">Selisih (+ / -)</th>
                        <th class="px-5 py-3.5 text-center">Stok Akhir</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Harga Jual</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @forelse($adjustments as $index => $a)
                    @php
                        $iteration = ($adjustments->currentPage() - 1) * $adjustments->perPage() + $index + 1;
                    @endphp
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-5 py-3.5 text-center text-on-surface-variant font-mono">{{ $iteration }}</td>
                        <td class="px-5 py-3.5 font-mono text-on-surface-variant whitespace-nowrap">
                            {{ $a->created_at->format('H:i') }} WIB
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="font-bold text-on-surface text-sm">{{ $a->product->name }}</div>
                            <div class="text-[11px] font-mono text-on-surface-variant mt-0.5">SKU: {{ $a->product->sku }}</div>
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-on-surface-variant">
                            {{ $a->user?->name ?? 'Sistem' }}
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-surface-container-high text-on-surface-variant border border-outline-variant/50">
                                {{ $reasons[$a->reason] ?? $a->reason }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-center font-mono font-medium text-on-surface whitespace-nowrap">
                            {{ number_format($a->quantity_before) }}
                        </td>
                        <td class="px-5 py-3.5 text-center font-mono font-extrabold whitespace-nowrap {{ $a->quantity_change > 0 ? 'text-[#006c49]' : 'text-rose-600' }}">
                            {{ $a->quantity_change > 0 ? '+' : '' }}{{ number_format($a->quantity_change) }}
                        </td>
                        <td class="px-5 py-3.5 text-center font-mono font-bold text-on-surface whitespace-nowrap">
                            {{ number_format($a->quantity_after) }}
                        </td>
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            @if($a->reason === 'reversal')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">Reversal</span>
                            @elseif($a->isReversed())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200">Dibatalkan</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">Aktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right font-mono whitespace-nowrap">
                            @php
                                $isActive = !$a->isReversed() && $a->reason !== 'reversal';
                                $itemPrice = (float) ($a->product?->selling_price ?? 0);
                                $isMinus = $a->quantity_change < 0;
                                $rowMinusTotal = ($isActive && $isMinus) ? abs($a->quantity_change) * $itemPrice : 0;
                            @endphp
                            <div class="font-bold text-on-surface">
                                Rp {{ number_format($itemPrice, 0, ',', '.') }}
                            </div>
                            @if($isActive && $isMinus)
                                <div class="text-[11px] font-bold text-rose-600">
                                    -Rp {{ number_format($rowMinusTotal, 0, ',', '.') }}
                                </div>
                            @elseif($isActive && $a->quantity_change > 0)
                                <div class="text-[10px] text-on-surface-variant font-medium">
                                    Beban: Rp 0
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <a 
                                    href="{{ route('stock-adjustments.show', $a) }}" 
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-semibold text-xs rounded-lg transition no-underline"
                                    title="Lihat Detail Audit"
                                >
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                    <span>Detail</span>
                                </a>

                                @if(!$a->isReversed() && $a->reason !== 'reversal')
                                    @can('Stock In Store')
                                    <form method="POST" action="{{ route('stock-adjustments.reverse', $a) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan (reversal) penyesuaian stok produk ini?');" class="inline">
                                        @csrf
                                        <button 
                                            type="submit" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-xs rounded-lg border border-rose-200 transition cursor-pointer"
                                            title="Batalkan (Reversal)"
                                        >
                                            <span class="material-symbols-outlined text-sm">undo</span>
                                            <span>Batal</span>
                                        </button>
                                    </form>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-5 py-12 text-center text-on-surface-variant text-sm">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-4xl text-outline-variant">inventory_2</span>
                                <p class="font-bold text-on-surface">Tidak ada barang yang cocok dengan kriteria pencarian.</p>
                                @can('Stock In Store')
                                <a href="{{ route('stock-adjustments.create', ['date' => $date]) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-primary text-white text-xs font-bold rounded-lg hover:opacity-90 transition mt-1 no-underline">
                                    <span class="material-symbols-outlined text-sm">add_circle</span>
                                    <span>Tambah Produk Pertama ke Tanggal Ini</span>
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>

                @if($adjustments->isNotEmpty())
                @php
                    $pageActiveMinusAdjustments = $adjustments->filter(fn($a) => !$a->isReversed() && $a->reason !== 'reversal' && $a->quantity_change < 0);
                    $pageTotalSelisih = $adjustments->sum('quantity_change');
                    $pageTotalHargaJual = $pageActiveMinusAdjustments->sum(fn($a) => abs($a->quantity_change) * (float) ($a->product?->selling_price ?? 0));
                    $grandTotalSelisih = $summary['net_change'] ?? $pageTotalSelisih;
                    $grandTotalHargaJual = $summary['total_harga_jual'] ?? $pageTotalHargaJual;
                    $totalCount = $summary['total_items'] ?? $adjustments->total();
                    $activeCount = $summary['active_items_count'] ?? $adjustments->filter(fn($a) => !$a->isReversed() && $a->reason !== 'reversal')->count();
                @endphp
                <tfoot class="border-t-2 border-outline-variant bg-surface-container-low text-xs">
                    @if($adjustments->hasPages())
                    <tr class="text-on-surface-variant border-b border-outline-variant/60 font-semibold">
                        <td colspan="6" class="px-5 py-3 text-right uppercase tracking-wider">
                            Subtotal Halaman Ini ({{ $adjustments->count() }} log):
                        </td>
                        <td class="px-5 py-3 text-center font-mono font-bold whitespace-nowrap {{ $pageTotalSelisih >= 0 ? 'text-[#006c49]' : 'text-rose-600' }}">
                            {{ $pageTotalSelisih > 0 ? '+' : '' }}{{ number_format($pageTotalSelisih) }}
                        </td>
                        <td class="px-5 py-3 text-center text-on-surface-variant font-mono">-</td>
                        <td class="px-5 py-3 text-center text-on-surface-variant font-mono">-</td>
                        <td class="px-5 py-3 text-right font-mono font-extrabold text-rose-600 whitespace-nowrap">
                            {{ $pageTotalHargaJual > 0 ? '-Rp ' . number_format($pageTotalHargaJual, 0, ',', '.') : 'Rp 0' }}
                        </td>
                        <td class="px-5 py-3"></td>
                    </tr>
                    @endif
                    <tr class="bg-surface-container text-on-surface font-extrabold">
                        <td colspan="6" class="px-5 py-3.5 text-right uppercase tracking-wider text-xs font-black text-on-surface">
                            Total Keseluruhan ({{ number_format($totalCount) }} Log):
                        </td>
                        <td class="px-5 py-3.5 text-center font-mono font-black text-sm whitespace-nowrap {{ $grandTotalSelisih >= 0 ? 'text-[#006c49]' : 'text-rose-600' }}">
                            {{ $grandTotalSelisih > 0 ? '+' : '' }}{{ number_format($grandTotalSelisih) }}
                        </td>
                        <td class="px-5 py-3.5 text-center text-on-surface-variant font-mono">-</td>
                        <td class="px-5 py-3.5 text-center text-on-surface-variant font-mono">-</td>
                        <td class="px-5 py-3.5 text-right font-mono font-black text-sm text-rose-600 whitespace-nowrap">
                            {{ $grandTotalHargaJual > 0 ? '-Rp ' . number_format($grandTotalHargaJual, 0, ',', '.') : 'Rp 0' }}
                        </td>
                        <td class="px-5 py-3.5"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        @if($adjustments->hasPages())
        <div class="px-5 py-4 border-t border-outline-variant/60">
            {{ $adjustments->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

