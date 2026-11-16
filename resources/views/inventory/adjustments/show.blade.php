@extends('layouts.app')

@section('title', 'Detail Audit Penyesuaian Stok | InvenTrack')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-[#006c49] text-white flex items-center justify-center shadow-md shadow-emerald-900/10 shrink-0">
                <span class="material-symbols-outlined text-2xl">receipt_long</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-secondary">Inventory</span>
                    <span class="text-outline-variant font-bold">•</span>
                    <a href="{{ route('stock-adjustments.index') }}" class="text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant hover:text-primary transition no-underline">
                        Audit & Opname
                    </a>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-on-surface tracking-tight">Detail Penyesuaian #{{ $adjustment->id }}</h1>
                <p class="text-xs text-on-surface-variant">{{ $adjustment->created_at->translatedFormat('d F Y, H:i') }} WIB</p>
            </div>
        </div>

        <a href="{{ route('stock-adjustments.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-semibold text-xs rounded-xl transition-all shadow-xs no-underline self-start sm:self-auto cursor-pointer">
            <span class="material-symbols-outlined text-base">arrow_back</span>
            <span>Kembali ke Riwayat</span>
        </a>
    </div>

    {{-- Alerts --}}
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

    @if($adjustment->isReversed())
        <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl flex items-start gap-3 text-amber-800 text-xs">
            <span class="material-symbols-outlined text-amber-600 text-xl shrink-0 mt-0.5">warning</span>
            <div>
                <span class="font-bold">Penyesuaian ini telah dibatalkan (Reversed)</span>
                <p class="mt-0.5 text-amber-700">Dibatalkan pada {{ $adjustment->reversed_at?->translatedFormat('d M Y, H:i') }} WIB oleh {{ $adjustment->reversedBy?->name ?? 'Sistem' }}.</p>
            </div>
        </div>
    @endif

    {{-- Detail Card --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
        
        {{-- Stat Summary Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="p-4 bg-surface-container-low rounded-xl border border-outline-variant/60">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase">Stok Sebelum</span>
                <div class="text-xl font-black text-on-surface mt-1">{{ $adjustment->quantity_before }} unit</div>
            </div>
            <div class="p-4 bg-surface-container-low rounded-xl border border-outline-variant/60">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase">Perubahan</span>
                <div class="text-xl font-black mt-1 {{ $adjustment->quantity_change > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $adjustment->quantity_change > 0 ? '+' : '' }}{{ $adjustment->quantity_change }} unit
                </div>
            </div>
            <div class="p-4 bg-surface-container-low rounded-xl border border-outline-variant/60">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase">Stok Sesudah</span>
                <div class="text-xl font-black text-primary mt-1">{{ $adjustment->quantity_after }} unit</div>
            </div>
        </div>

        {{-- Product & Meta Information --}}
        <div class="divide-y divide-outline-variant/60 text-xs sm:text-sm">
            <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                <span class="font-bold text-on-surface-variant">Produk</span>
                <span class="font-bold text-on-surface">[{{ $adjustment->product->sku }}] {{ $adjustment->product->name }}</span>
            </div>
            <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                <span class="font-bold text-on-surface-variant">Alasan Penyesuaian</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-surface-container-high font-semibold text-on-surface text-xs">
                    {{ \App\Models\StockAdjustment::REASONS[$adjustment->reason] ?? $adjustment->reason }}
                </span>
            </div>
            <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                <span class="font-bold text-on-surface-variant">Petugas / Operator</span>
                <span class="font-semibold text-on-surface">{{ $adjustment->user?->name ?? 'Sistem' }}</span>
            </div>
            <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                <span class="font-bold text-on-surface-variant">Waktu Input</span>
                <span class="font-semibold text-on-surface">{{ $adjustment->created_at->format('d/m/Y H:i:s') }} WIB</span>
            </div>
            <div class="py-3.5 flex flex-col sm:flex-row sm:items-start justify-between gap-1">
                <span class="font-bold text-on-surface-variant">Catatan</span>
                <span class="text-on-surface font-medium sm:text-right max-w-md">{{ $adjustment->notes ?: '-' }}</span>
            </div>
        </div>

        {{-- Actions --}}
        <div class="pt-4 border-t border-outline-variant/60 flex flex-col sm:flex-row items-center justify-between gap-3">
            <a href="{{ route('stock-adjustments.index') }}" class="w-full sm:w-auto px-5 py-2.5 text-center text-xs font-bold text-on-surface-variant hover:text-on-surface bg-surface-container-high rounded-xl transition no-underline">
                Kembali ke Daftar
            </a>

            @if(!$adjustment->isReversed() && $adjustment->reason !== 'reversal')
                @can('Stock In Store')
                <form method="POST" action="{{ route('stock-adjustments.reverse', $adjustment) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan (reversal) penyesuaian stok ini? Stok produk akan dikembalikan ke kondisi sebelumnya.');" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition shadow-sm cursor-pointer">
                        <span class="material-symbols-outlined text-sm">undo</span>
                        <span>Batalkan Penyesuaian (Reversal)</span>
                    </button>
                </form>
                @endcan
            @endif
        </div>
    </div>
</div>
@endsection
