@extends('layouts.app')

@section('title', 'Z1 VapeOS | Jurnal Transaksi')

@section('content')

{{-- Header & Action Button --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="font-headline-lg text-headline-lg text-on-surface">Jurnal Transaksi (Journal Entries)</h1>
        <p class="text-on-surface-variant text-body-lg">Catatan historis pembukuan berpasangan (debit & kredit) otomatis dan manual.</p>
    </div>

    <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
        <a href="{{ route('accounting.journals.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white font-bold rounded-xl text-xs sm:text-sm hover:bg-emerald-700 active:scale-95 transition-all shadow-xs cursor-pointer no-underline" title="Export Jurnal Transaksi ke Excel (.xlsx)">
            <span class="material-symbols-outlined text-base">download</span>
            Export Excel
        </a>
        <a href="{{ route('accounting.journals.ledger') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
            <span class="material-symbols-outlined text-base">menu_book</span>
            Buku Besar
        </a>
        @can('Accounting Journal Create')
        <a href="{{ route('accounting.journals.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer no-underline">
            <span class="material-symbols-outlined text-base">add</span>
            Input Jurnal Manual
        </a>
        @endcan
    </div>
</div>

{{-- Metric Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-secondary-container text-on-secondary-container flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-2xl">receipt_long</span>
        </div>
        <div>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Entri Jurnal</p>
            <h3 class="text-xl font-headline-md text-on-surface font-extrabold mt-0.5">
                {{ number_format($totalEntries, 0, ',', '.') }} Entri
            </h3>
            <p class="text-[11px] text-on-surface-variant mt-0.5 font-medium">Histori transaksi pembukuan</p>
        </div>
    </div>

    <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-2xl">arrow_upward</span>
        </div>
        <div>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Debit (Hasil Filter)</p>
            <h3 class="text-xl font-headline-md text-emerald-800 font-extrabold mt-0.5">
                Rp {{ number_format($totalDebits, 0, ',', '.') }}
            </h3>
            <p class="text-[11px] text-on-surface-variant mt-0.5 font-medium">Akumulasi mutasi debit (bukan omset)</p>
        </div>
    </div>

    <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-sky-500/10 text-sky-700 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-2xl">arrow_downward</span>
        </div>
        <div>
            <div class="flex items-center gap-1.5 flex-wrap">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Kredit (Hasil Filter)</p>
                @if(abs($totalDebits - $totalCredits) < 0.01)
                    <span class="px-1.5 py-0.5 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md">Balance</span>
                @endif
            </div>
            <h3 class="text-xl font-headline-md text-sky-800 font-extrabold mt-0.5">
                Rp {{ number_format($totalCredits, 0, ',', '.') }}
            </h3>
            <p class="text-[11px] text-on-surface-variant mt-0.5 font-medium">Akumulasi mutasi kredit (bukan omset)</p>
        </div>
    </div>
</div>

{{-- Explanatory Note Banner --}}
<div class="mb-6 p-4 bg-surface-container-low border border-outline-variant rounded-2xl flex items-start gap-3 text-xs text-on-surface-variant">
    <span class="material-symbols-outlined text-primary text-lg shrink-0 mt-0.5">info</span>
    <div class="leading-relaxed">
        <strong>Catatan Pembukuan:</strong> Total Debit & Kredit di atas adalah <strong>akumulasi perputaran seluruh akun akuntansi</strong> (termasuk persediaan barang masuk/Stock In, pencatatan beban HPP, mutasi kas/bank, dan operasional). Angka ini <em>bukan</em> omset penjualan. Untuk melihat <strong>Omset (Pendapatan Penjualan)</strong> sebenarnya, silakan buka <a href="{{ route('accounting.reports.profit-and-loss') }}" class="font-bold text-primary hover:underline">Laporan Laba Rugi</a> atau periksa <a href="{{ route('accounting.journals.ledger') }}?account_id={{ \App\Models\Account::where('code', '4101')->value('id') }}" class="font-bold text-primary hover:underline">Buku Besar Akun 4101 (Pendapatan Penjualan)</a>.
    </div>
</div>

{{-- Filter & Search Form --}}
<div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 shadow-xs">
    <form method="GET" action="{{ route('accounting.journals.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div>
            <label class="block text-xs font-semibold text-on-surface-variant mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
        </div>
        <div>
            <label class="block text-xs font-semibold text-on-surface-variant mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
        </div>
        <div>
            <label class="block text-xs font-semibold text-on-surface-variant mb-1">Sumber Transaksi</label>
            <select name="source_type" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
                <option value="">Semua Sumber</option>
                @foreach($sourceTypes as $val => $lbl)
                    <option value="{{ $val }}" {{ request('source_type') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-on-surface-variant mb-1">Pencarian</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. Jurnal / Keterangan..." class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
        </div>
        <div class="flex items-end gap-2 flex-wrap sm:flex-nowrap">
            <button type="submit" class="flex-1 px-4 py-2 bg-secondary text-on-secondary text-xs font-bold rounded-xl hover:opacity-90 transition-all cursor-pointer">
                Filter
            </button>
            <a href="{{ route('accounting.journals.export', request()->query()) }}" class="px-3.5 py-2 bg-emerald-50 text-emerald-800 border border-emerald-300 hover:bg-emerald-100 text-xs font-bold rounded-xl transition-all text-center no-underline inline-flex items-center gap-1 shrink-0" title="Download Excel sesuai filter">
                <span class="material-symbols-outlined text-base">download</span>
                <span>Export</span>
            </a>
            <a href="{{ route('accounting.journals.index') }}" class="px-4 py-2 bg-surface-container-low border border-outline-variant text-on-surface text-xs font-semibold rounded-xl hover:bg-surface-container-high transition-all text-center no-underline">
                Reset
            </a>
        </div>
    </form>
</div>

{{-- Journal Entries List --}}
<div class="space-y-4">
    @forelse($journalEntries as $entry)
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-xs">
        {{-- Entry Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 px-5 py-3.5 bg-surface-container-low border-b border-outline-variant/60">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md border text-[10px] font-bold {{ $entry->source_badge_class }}">
                    {{ $entry->source_label }}
                </span>
                <span class="font-mono font-bold text-xs text-on-surface">{{ $entry->entry_number }}</span>
                @if($entry->reference_number)
                    <span class="text-[11px] text-on-surface-variant font-mono">Ref: {{ $entry->reference_number }}</span>
                @endif
            </div>

            <div class="flex items-center gap-3 text-xs text-on-surface-variant">
                <span class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">calendar_today</span>
                    {{ $entry->entry_date ? \Carbon\Carbon::parse($entry->entry_date)->format('d/m/Y') : '-' }}
                </span>
                @if($entry->user)
                    <span class="hidden sm:inline text-outline-variant">•</span>
                    <span class="hidden sm:inline font-medium text-[11px]">Oleh: {{ $entry->user->name }}</span>
                @endif
                @if($entry->is_balanced)
                    <span class="inline-flex items-center gap-0.5 text-emerald-700 font-bold text-[10px] bg-emerald-500/10 px-1.5 py-0.5 rounded">
                        <span class="material-symbols-outlined text-[13px]">check_circle</span> Seimbang
                    </span>
                @else
                    <span class="inline-flex items-center gap-0.5 text-error font-bold text-[10px] bg-error-container px-1.5 py-0.5 rounded">
                        <span class="material-symbols-outlined text-[13px]">warning</span> Tidak Seimbang
                    </span>
                @endif
            </div>
        </div>

        {{-- Entry Description --}}
        <div class="px-5 pt-3 pb-2 text-xs font-semibold text-on-surface">
            {{ $entry->description }}
        </div>

        {{-- Items Table --}}
        <div class="overflow-x-auto px-5 pb-4">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-on-surface-variant text-[11px] border-b border-outline-variant/60">
                        <th class="py-2 text-left font-semibold">Akun</th>
                        <th class="py-2 text-left font-semibold">Memo / Catatan</th>
                        <th class="py-2 text-right font-semibold w-36">Debit</th>
                        <th class="py-2 text-right font-semibold w-36">Kredit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @foreach($entry->items as $item)
                    <tr class="hover:bg-surface-container-low/40">
                        <td class="py-2 pr-3">
                            <a href="{{ route('accounting.journals.ledger', ['account_id' => $item->account_id]) }}" class="inline-flex items-center gap-1.5 font-bold text-on-surface hover:text-primary no-underline">
                                <span class="font-mono text-[11px] text-on-surface-variant">{{ $item->account?->code ?? '-' }}</span>
                                <span>{{ $item->account?->name ?? 'Akun Terhapus' }}</span>
                            </a>
                        </td>
                        <td class="py-2 text-on-surface-variant text-[11px]">
                            {{ $item->memo ?: '-' }}
                        </td>
                        <td class="py-2 text-right font-mono font-medium text-on-surface">
                            {{ $item->debit > 0 ? 'Rp ' . number_format($item->debit, 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-2 text-right font-mono font-medium text-on-surface">
                            {{ $item->credit > 0 ? 'Rp ' . number_format($item->credit, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                    @endforeach
                    <tr class="bg-surface-container-low/60 font-bold border-t border-outline-variant">
                        <td colspan="2" class="py-2 text-right text-on-surface-variant text-[11px] uppercase tracking-wider">Total</td>
                        <td class="py-2 text-right font-mono font-bold text-emerald-800">
                            Rp {{ number_format($entry->total_debit, 0, ',', '.') }}
                        </td>
                        <td class="py-2 text-right font-mono font-bold text-sky-800">
                            Rp {{ number_format($entry->total_credit, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-12 text-center text-on-surface-variant shadow-xs">
        <span class="material-symbols-outlined text-4xl mb-2 text-outline">menu_book</span>
        <h3 class="font-bold text-base text-on-surface">Belum Ada Transaksi Jurnal</h3>
        <p class="text-xs text-on-surface-variant mt-1">Jurnal akan terisi secara otomatis ketika ada transaksi penjualan, stok masuk, pemasukan, atau pengeluaran toko.</p>
    </div>
    @endforelse
</div>

{{-- Pagination --}}
@if($journalEntries->hasPages())
<div class="mt-6">
    {{ $journalEntries->links() }}
</div>
@endif

@endsection
