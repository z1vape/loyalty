@extends('layouts.app')

@section('title', 'Z1 VapeOS | Gudang & Stok Antar Cabang')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Header & Action Buttons --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-2">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Gudang & Stok Antar Cabang</h1>
            <p class="text-on-surface-variant text-body-lg">
                Kelola Surat Jalan mutasi stok (In-Transit), sinkronisasi HPP Moving Average, dan jurnal otomatis antar cabang.
            </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
            <a href="{{ route('stock-transfers.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>Buat Surat Jalan Mutasi</span>
            </a>
        </div>
    </div>

    {{-- Flash Alerts --}}
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
    @if(session('error'))
        <div class="p-4 bg-error-container/40 border border-error/30 rounded-2xl flex items-center justify-between gap-3 text-error">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-xl">error</span>
                <span class="text-sm font-bold">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="hover:opacity-75 cursor-pointer">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    {{-- Summary Metrics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Dokumen --}}
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-surface-container-high text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">description</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Dokumen</p>
                <h3 class="text-xl sm:text-2xl font-headline-md font-extrabold text-on-surface mt-0.5 font-mono truncate">
                    {{ number_format($totalTransferCount) }}
                </h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5">Draft: <strong class="font-mono">{{ $draftCount }}</strong> surat jalan</p>
            </div>
        </div>

        {{-- Card 2: Dalam Perjalanan --}}
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">local_shipping</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Dalam Perjalanan</p>
                <h3 class="text-xl sm:text-2xl font-headline-md font-extrabold text-blue-700 mt-0.5 font-mono truncate">
                    {{ number_format($inTransitCount) }}
                </h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5 truncate">Menunggu konfirmasi tujuan</p>
            </div>
        </div>

        {{-- Card 3: Diterima (Selesai) --}}
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">task_alt</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Diterima (Selesai)</p>
                <h3 class="text-xl sm:text-2xl font-headline-md font-extrabold text-emerald-700 mt-0.5 font-mono truncate">
                    {{ number_format($receivedCount) }}
                </h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5 truncate">Stok & HPP telah sinkron</p>
            </div>
        </div>

        {{-- Card 4: Nilai Persediaan Mutasi --}}
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-secondary-container text-on-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Nilai Persediaan Mutasi</p>
                <h3 class="text-xl sm:text-2xl font-headline-md font-extrabold text-on-surface mt-0.5 font-mono truncate">
                    Rp {{ number_format($totalTransferCost, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5 truncate">Akumulasi HPP In-Transit & Selesai</p>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 shadow-xs">
        <form method="GET" action="{{ route('stock-transfers.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Cabang</label>
                <select name="store_id" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary cursor-pointer">
                    <option value="all">Semua Cabang</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ (string) $storeId === (string) $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Arah Mutasi</label>
                <select name="direction" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary cursor-pointer">
                    <option value="all" {{ $direction === 'all' ? 'selected' : '' }}>Semua (Kirim & Terima)</option>
                    <option value="outgoing" {{ $direction === 'outgoing' ? 'selected' : '' }}>Kiriman Keluar</option>
                    <option value="incoming" {{ $direction === 'incoming' ? 'selected' : '' }}>Kiriman Masuk</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary cursor-pointer">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="in_transit" {{ $status === 'in_transit' ? 'selected' : '' }}>In-Transit (Dikirim)</option>
                    <option value="received" {{ $status === 'received' ? 'selected' : '' }}>Diterima (Received)</option>
                    <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary">
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary">
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1">Cari No. Surat Jalan</label>
                <div class="flex gap-1.5">
                    <input type="text" name="search" value="{{ $search }}" placeholder="TRF-2026..." class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary">
                    <button type="submit" class="px-3.5 py-2 bg-primary text-on-primary text-xs font-bold rounded-xl hover:opacity-90 active:scale-95 transition-all cursor-pointer flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-base">search</span>
                    </button>
                    @if($storeId !== 'all' || $direction !== 'all' || $status !== 'all' || $startDate || $endDate || $search)
                        <a href="{{ route('stock-transfers.index') }}" class="px-3 py-2 bg-surface-container-low border border-outline-variant hover:bg-surface-container-high text-on-surface font-semibold text-xs rounded-xl transition-all flex items-center justify-center shrink-0 no-underline" title="Reset Filter">
                            <span class="material-symbols-outlined text-base">restart_alt</span>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Table Card --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">No. Surat Jalan</th>
                        <th class="px-4 py-3.5">Tanggal</th>
                        <th class="px-4 py-3.5">Cabang Pengirim</th>
                        <th class="px-4 py-3.5">Cabang Tujuan</th>
                        <th class="px-4 py-3.5 text-center">Jumlah SKU / Unit</th>
                        <th class="px-4 py-3.5 text-right">Nilai HPP</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @forelse($transfers as $trf)
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-4 py-3.5">
                                <a href="{{ route('stock-transfers.show', $trf->id) }}" class="font-mono font-bold text-primary hover:underline">
                                    {{ $trf->transfer_number }}
                                </a>
                                @if($trf->notes)
                                    <p class="text-[11px] text-on-surface-variant truncate max-w-[240px] mt-0.5">{{ $trf->notes }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 font-mono text-xs text-on-surface">
                                {{ $trf->transfer_date?->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-semibold text-on-surface">{{ $trf->fromStore?->name ?? '-' }}</span>
                                <span class="block text-[11px] text-on-surface-variant">Oleh: {{ $trf->sender?->name ?? $trf->creator?->name ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-semibold text-on-surface">{{ $trf->toStore?->name ?? '-' }}</span>
                                @if($trf->receiver)
                                    <span class="block text-[11px] text-emerald-700 font-medium">Diterima: {{ $trf->receiver->name }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center font-mono text-xs">
                                <span class="font-bold text-on-surface">{{ $trf->items->count() }}</span> SKU
                                <span class="text-on-surface-variant">({{ number_format($trf->items->sum(fn($i) => $trf->status === 'draft' ? $i->quantity_requested : $i->quantity_sent)) }} pcs)</span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-bold text-on-surface">
                                Rp {{ number_format($trf->total_cost, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $trf->status_badge }}">
                                    {{ $trf->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('stock-transfers.show', $trf->id) }}"
                                       class="px-3 py-1.5 rounded-xl bg-surface-container-low border border-outline-variant hover:bg-surface-container-high text-on-surface font-semibold text-xs transition no-underline">
                                        Detail
                                    </a>
                                    <a href="{{ route('stock-transfers.print', $trf->id) }}" target="_blank"
                                       class="p-1.5 rounded-xl border border-outline-variant hover:bg-surface-container-high text-on-surface-variant transition" title="Cetak Surat Jalan">
                                        <span class="material-symbols-outlined text-base">print</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <span class="material-symbols-outlined text-4xl text-on-surface-variant/40">local_shipping</span>
                                <p class="text-sm font-bold text-on-surface mt-2">Belum ada dokumen Surat Jalan Mutasi Antar Cabang</p>
                                <p class="text-xs text-on-surface-variant mt-1">Klik tombol "Buat Surat Jalan Mutasi" untuk memindahkan stok dari satu cabang ke cabang lain.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transfers->hasPages())
            <div class="px-4 py-3 border-t border-outline-variant/60 bg-surface-container-low/40">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
