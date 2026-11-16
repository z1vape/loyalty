@extends('layouts.app')

@section('title', 'Z1 VapeOS | Detail Surat Jalan ' . $transfer->transfer_number)

@section('content')
<div class="flex flex-col gap-6">

    {{-- Top Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-2">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-headline-lg text-headline-lg font-mono text-on-surface">
                    {{ $transfer->transfer_number }}
                </h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $transfer->status_badge }}">
                    {{ $transfer->status_label }}
                </span>
            </div>
            <p class="text-on-surface-variant text-body-lg mt-1">
                Tanggal Dokumen: <strong class="font-mono text-on-surface">{{ $transfer->transfer_date?->format('d F Y') }}</strong>
                &bull; Dibuat oleh: <strong class="text-on-surface">{{ $transfer->creator?->name ?? '-' }}</strong>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
            <a href="{{ route('stock-transfers.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                <span>Kembali</span>
            </a>

            <a href="{{ route('stock-transfers.print', $transfer->id) }}" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base">print</span>
                <span>Cetak Surat Jalan</span>
            </a>

            @if($transfer->status === 'draft')
                <form method="POST" action="{{ route('stock-transfers.send', $transfer->id) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-on-primary text-xs sm:text-sm font-bold shadow-xs hover:opacity-95 transition cursor-pointer">
                        <span class="material-symbols-outlined text-base">local_shipping</span>
                        <span>Kirim Barang (In-Transit)</span>
                    </button>
                </form>

                <form method="POST" action="{{ route('stock-transfers.cancel', $transfer->id) }}" class="inline"
                      onsubmit="return confirm('Batalkan draft Surat Jalan ini?')">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-rose-500/30 bg-rose-500/10 text-rose-700 text-xs sm:text-sm font-bold hover:bg-rose-500/20 transition cursor-pointer">
                        <span class="material-symbols-outlined text-base">cancel</span>
                        <span>Batalkan</span>
                    </button>
                </form>
            @endif
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

    {{-- Store Route Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Cabang Pengirim (Asal)</span>
            <p class="text-lg font-extrabold text-on-surface mt-1.5">{{ $transfer->fromStore?->name ?? '-' }}</p>
            <p class="text-xs text-on-surface-variant mt-1">
                @if($transfer->sent_at)
                    Dikirim: <span class="font-mono font-semibold">{{ $transfer->sent_at->format('d M Y H:i') }}</span>
                    oleh <strong>{{ $transfer->sender?->name ?? '-' }}</strong>
                @else
                    Belum dikirim (Masih Draft)
                @endif
            </p>
        </div>

        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Cabang Tujuan (Penerima)</span>
            <p class="text-lg font-extrabold text-on-surface mt-1.5">{{ $transfer->toStore?->name ?? '-' }}</p>
            <p class="text-xs text-on-surface-variant mt-1">
                @if($transfer->received_at)
                    Diterima: <span class="font-mono font-semibold text-emerald-700">{{ $transfer->received_at->format('d M Y H:i') }}</span>
                    oleh <strong>{{ $transfer->receiver?->name ?? '-' }}</strong>
                @elseif($transfer->status === 'in_transit')
                    <span class="text-blue-700 font-semibold">Sedang dalam perjalanan (In-Transit)</span>
                @else
                    Menunggu pengiriman
                @endif
            </p>
        </div>

        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Total Nilai HPP Mutasi</span>
            <p class="text-2xl font-black font-mono text-primary mt-1.5">Rp {{ number_format($transfer->total_cost, 0, ',', '.') }}</p>
            <p class="text-xs text-on-surface-variant mt-1">
                Total: <strong class="font-mono">{{ $transfer->items->count() }}</strong> SKU
                ({{ number_format($transfer->items->sum(fn($i) => $transfer->status === 'draft' ? $i->quantity_requested : $i->quantity_sent)) }} pcs)
            </p>
        </div>
    </div>

    {{-- Items & Receiving Form --}}
    @if($transfer->status === 'in_transit')
        <form method="POST" action="{{ route('stock-transfers.receive', $transfer->id) }}" class="bg-surface-container-lowest border-2 border-primary/30 rounded-2xl p-5 shadow-xs space-y-4">
            @csrf
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-outline-variant/60">
                <div>
                    <h2 class="text-base font-black text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">inventory</span>
                        <span>Konfirmasi Penerimaan Barang di {{ $transfer->toStore?->name }}</span>
                    </h2>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        Periksa kuantitas fisik yang tiba. Saat dikonfirmasi, stok & HPP (Moving Average) cabang tujuan serta jurnal akuntansi otomatis diperbarui.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-on-surface-variant">Tgl Terima:</label>
                    <input type="date" name="received_date" value="{{ date('Y-m-d') }}" required
                           class="rounded-xl border border-outline-variant bg-surface-container-low text-xs font-mono px-3 py-1.5 text-on-surface">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-outline-variant/60 text-[11px] font-extrabold text-on-surface-variant uppercase bg-surface-container-low">
                            <th class="py-2.5 px-3">SKU & Nama Produk</th>
                            <th class="py-2.5 px-3 text-center">Qty Dikirim</th>
                            <th class="py-2.5 px-3 text-center w-40">Qty Diterima</th>
                            <th class="py-2.5 px-3 text-right">HPP / Unit</th>
                            <th class="py-2.5 px-3 text-right">Subtotal HPP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        @foreach($transfer->items as $item)
                            <tr>
                                <td class="py-3 px-3">
                                    <span class="font-mono text-xs font-bold text-primary">{{ $item->sourceProduct?->sku ?: 'NO-SKU' }}</span>
                                    <p class="font-bold text-on-surface">{{ $item->sourceProduct?->name }}</p>
                                    @if($item->notes)
                                        <p class="text-[11px] text-on-surface-variant">{{ $item->notes }}</p>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center font-mono font-bold">
                                    {{ number_format($item->quantity_sent) }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <input type="number"
                                           name="items[{{ $item->id }}][quantity_received]"
                                           value="{{ $item->quantity_sent }}"
                                           min="0"
                                           max="{{ $item->quantity_sent }}"
                                           required
                                           class="w-28 rounded-xl border border-outline-variant bg-surface-container-low text-xs sm:text-sm font-mono font-bold px-3 py-1.5 text-center text-on-surface">
                                </td>
                                <td class="py-3 px-3 text-right font-mono">
                                    Rp {{ number_format($item->unit_cost, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-bold">
                                    Rp {{ number_format($item->subtotal_cost, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-outline-variant/60">
                <input type="text" name="notes" placeholder="Catatan penerimaan (opsional, misal: kondisi barang lengkap)"
                       class="w-full sm:w-96 rounded-xl border border-outline-variant bg-surface-container-low text-xs px-3 py-2 text-on-surface">

                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-on-primary text-xs sm:text-sm font-bold shadow-sm hover:opacity-95 transition cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">task_alt</span>
                    <span>Konfirmasi Terima Barang & Sinkronkan HPP</span>
                </button>
            </div>
        </form>
    @else
        {{-- Read-only Items Table --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-xs">
            <div class="px-5 py-3.5 border-b border-outline-variant bg-surface-container-low">
                <h2 class="text-xs sm:text-sm font-extrabold text-on-surface uppercase tracking-wider">Rincian Barang Mutasi</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-outline-variant text-[11px] font-extrabold text-on-surface-variant uppercase bg-surface-container-low">
                            <th class="px-5 py-3">SKU & Produk</th>
                            <th class="px-4 py-3 text-center">Diminta</th>
                            <th class="px-4 py-3 text-center">Dikirim</th>
                            <th class="px-4 py-3 text-center">Diterima</th>
                            <th class="px-4 py-3 text-right">HPP / Unit</th>
                            <th class="px-5 py-3 text-right">Subtotal HPP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/60">
                        @foreach($transfer->items as $item)
                            <tr class="hover:bg-surface-container-low/40 transition-colors">
                                <td class="px-5 py-3.5">
                                    <span class="font-mono text-xs font-bold text-primary">{{ $item->sourceProduct?->sku ?: 'NO-SKU' }}</span>
                                    <p class="font-bold text-on-surface">{{ $item->sourceProduct?->name }}</p>
                                    @if($item->notes)
                                        <p class="text-[11px] text-on-surface-variant">{{ $item->notes }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-center font-mono">{{ number_format($item->quantity_requested) }}</td>
                                <td class="px-4 py-3.5 text-center font-mono font-bold text-blue-700">{{ number_format($item->quantity_sent) }}</td>
                                <td class="px-4 py-3.5 text-center font-mono font-bold text-emerald-700">{{ number_format($item->quantity_received) }}</td>
                                <td class="px-4 py-3.5 text-right font-mono">Rp {{ number_format($item->unit_cost, 0, ',', '.') }}</td>
                                <td class="px-5 py-3.5 text-right font-mono font-bold text-on-surface">Rp {{ number_format($item->subtotal_cost, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Automatic Journals Section --}}
    @if($journals->isNotEmpty())
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-xs">
            <div class="px-5 py-3.5 border-b border-outline-variant bg-surface-container-low flex items-center justify-between">
                <h2 class="text-xs sm:text-sm font-extrabold text-on-surface uppercase tracking-wider flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-primary">menu_book</span>
                    <span>Jurnal Akuntansi Otomatis Antar Cabang</span>
                </h2>
                <span class="text-[11px] font-semibold text-emerald-700">Double-Entry Balanced</span>
            </div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($journals as $journal)
                    <div class="border border-outline-variant rounded-xl p-4 space-y-2.5 bg-surface-container-low/30">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-mono text-xs font-bold text-primary">{{ $journal->entry_number }}</span>
                                <span class="text-xs font-bold text-on-surface ml-1">({{ $journal->store?->name }})</span>
                            </div>
                            <span class="font-mono text-[11px] text-on-surface-variant">{{ $journal->entry_date?->format('d M Y') }}</span>
                        </div>
                        <p class="text-xs text-on-surface-variant">{{ $journal->description }}</p>
                        <div class="border-t border-outline-variant/40 pt-2 space-y-1 text-xs">
                            @foreach($journal->items as $ji)
                                <div class="flex items-center justify-between font-mono">
                                    <span>{{ $ji->account?->code }} - {{ $ji->account?->name }}</span>
                                    <span>
                                        @if($ji->debit > 0)
                                            D: Rp {{ number_format($ji->debit, 0, ',', '.') }}
                                        @else
                                            K: Rp {{ number_format($ji->credit, 0, ',', '.') }}
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
