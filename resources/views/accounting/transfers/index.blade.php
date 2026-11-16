@extends('layouts.app')

@section('title', 'Z1 VapeOS | Transfer Kas & Bank')

@section('content')

{{-- Header & Action Button --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="font-headline-lg text-headline-lg text-on-surface">Transfer Kas & Bank</h1>
        <p class="text-on-surface-variant text-body-lg">Pencatatan mutasi perpindahan dana internal (setor kas ke bank, penarikan tunai, atau antar rekening).</p>
    </div>

    <div class="flex items-center gap-2 self-start sm:self-auto">
        <a href="{{ route('accounting.journals.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
            <span class="material-symbols-outlined text-base">receipt_long</span>
            Jurnal Transaksi
        </a>
        @can('Accounting Transfer Manage')
        <a href="{{ route('accounting.transfers.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer no-underline">
            <span class="material-symbols-outlined text-base">swap_horiz</span>
            Catat Transfer Baru
        </a>
        @endcan
    </div>
</div>

{{-- Metric Card --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-2xl">sync_alt</span>
        </div>
        <div>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Transaksi Transfer</p>
            <h3 class="text-xl font-headline-md text-on-surface font-extrabold mt-0.5">
                {{ number_format($transfers->total(), 0, ',', '.') }} Transaksi
            </h3>
        </div>
    </div>

    <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-2xl">payments</span>
        </div>
        <div>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Dana Dimutasikan (Periode)</p>
            <h3 class="text-xl font-headline-md text-emerald-800 font-extrabold mt-0.5">
                Rp {{ number_format($totalTransferred, 0, ',', '.') }}
            </h3>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="p-4 mb-6 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
    <form method="GET" action="{{ route('accounting.transfers.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <div class="flex items-center gap-2 flex-1">
            <div class="w-full sm:w-48">
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-3 py-2 text-xs bg-surface-container-low border border-outline-variant rounded-xl font-medium focus:outline-none focus:ring-1 focus:ring-primary">
            </div>
            <div class="w-full sm:w-48">
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-3 py-2 text-xs bg-surface-container-low border border-outline-variant rounded-xl font-medium focus:outline-none focus:ring-1 focus:ring-primary">
            </div>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="px-4 py-2 bg-secondary text-on-secondary rounded-xl font-bold text-xs hover:opacity-90 transition-all flex items-center gap-1.5 cursor-pointer shadow-xs">
                <span class="material-symbols-outlined text-sm">filter_list</span>
                Terapkan
            </button>
            <a href="{{ route('accounting.transfers.index') }}" class="px-4 py-2 bg-surface-container-low border border-outline-variant text-on-surface-variant rounded-xl font-semibold text-xs hover:bg-surface-container-high transition-all flex items-center gap-1 cursor-pointer no-underline">
                Reset
            </a>
        </div>
    </form>
</div>

{{-- Table of Fund Transfers --}}
<div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-bold uppercase tracking-wider">
                    <th class="px-4 py-3.5">No. Referensi</th>
                    <th class="px-4 py-3.5">Tanggal</th>
                    <th class="px-4 py-3.5">Dari Akun (Pengirim)</th>
                    <th class="px-4 py-3.5">Ke Akun (Penerima)</th>
                    <th class="px-4 py-3.5 text-right">Jumlah (Rp)</th>
                    <th class="px-4 py-3.5">Keterangan</th>
                    <th class="px-4 py-3.5">User</th>
                    <th class="px-4 py-3.5 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($transfers as $trf)
                <tr class="hover:bg-surface-container-low/50 transition-colors">
                    <td class="px-4 py-3 font-bold text-on-surface">
                        <span class="font-mono text-primary font-bold">{{ $trf->transfer_number }}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-on-surface">
                        {{ \Illuminate\Support\Carbon::parse($trf->transfer_date)->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5 text-rose-700 font-semibold">
                            <span class="material-symbols-outlined text-sm">arrow_upward</span>
                            <span>{{ $trf->fromAccount->code }} - {{ $trf->fromAccount->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5 text-emerald-700 font-semibold">
                            <span class="material-symbols-outlined text-sm">arrow_downward</span>
                            <span>{{ $trf->toAccount->code }} - {{ $trf->toAccount->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right font-extrabold text-on-surface font-mono">
                        Rp {{ number_format($trf->amount, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 max-w-xs truncate text-on-surface-variant">
                        {{ $trf->notes ?: '-' }}
                    </td>
                    <td class="px-4 py-3 text-on-surface-variant">
                        {{ $trf->user?->name ?? 'System' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @can('Accounting Transfer Manage')
                        <form method="POST" action="{{ route('accounting.transfers.destroy', $trf) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan transfer {{ $trf->transfer_number }}? Jurnal mutasi terkait juga akan dihapus.')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 text-error hover:bg-error-container/20 rounded-lg transition-colors cursor-pointer" title="Batalkan Transfer">
                                <span class="material-symbols-outlined text-base">delete</span>
                            </button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-on-surface-variant">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-3xl text-on-surface-variant/40">sync_alt</span>
                            <p class="font-medium">Belum ada riwayat mutasi/transfer kas dan bank pada periode ini.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transfers->hasPages())
    <div class="p-4 border-t border-outline-variant">
        {{ $transfers->links() }}
    </div>
    @endif
</div>

@endsection
