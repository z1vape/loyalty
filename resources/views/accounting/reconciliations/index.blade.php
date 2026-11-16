@extends('layouts.app')

@section('title', 'Z1 VapeOS | Rekonsiliasi Bank & Kas')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">

    {{-- Header & Action Button --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container">
                    <span class="material-symbols-outlined text-sm">account_balance</span>
                    Accounting & Cash Management
                </span>
                <span class="text-xs text-on-surface-variant font-medium">/</span>
                <span class="text-xs text-on-surface-variant font-bold">Rekonsiliasi Bank</span>
            </div>
            <h1 class="font-headline-lg text-2xl sm:text-3xl font-extrabold text-on-surface">Rekonsiliasi Bank & Kas</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-1">
                Cocokkan dan samakan saldo rekening di sistem dengan saldo nyata pada mutasi rekening bank / ATM Anda secara instan.
            </p>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <a href="{{ route('accounting.journals.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-surface-container-low border border-outline-variant/60 text-on-surface font-bold rounded-xl text-xs hover:bg-surface-container-high transition-all no-underline shadow-2xs">
                <span class="material-symbols-outlined text-base">receipt_long</span>
                <span>Jurnal Akuntansi</span>
            </a>
            <a href="{{ route('accounting.reconciliations.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#006c49] hover:bg-[#005a3c] text-white font-extrabold rounded-xl text-xs shadow-sm hover:shadow transition-all no-underline">
                <span class="material-symbols-outlined text-base">sync_alt</span>
                <span>+ Rekonsiliasi Baru (Samakan Saldo)</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <span class="text-xs sm:text-sm font-semibold">{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">
            <span class="material-symbols-outlined text-base">close</span>
        </button>
    </div>
    @endif

    {{-- Liquidity Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Rekening Bank Toko --}}
        @php
            $bankAcc = $accounts->firstWhere('code', '1102');
            $cashAcc = $accounts->firstWhere('code', '1101');
            $qrisAcc = $accounts->firstWhere('code', '1103');
        @endphp
        <div class="p-5 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Rekening Bank Toko</span>
                <span class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">account_balance</span>
                </span>
            </div>
            <div class="mt-2 text-2xl font-black text-on-surface">
                Rp {{ number_format($bankAcc?->current_balance ?? 0, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-slate-500 mt-1 truncate">
                {{ $store && $store->bank_name ? "{$store->bank_name} - {$store->bank_account_number}" : 'Akun: 1102 - Bank Toko' }}
            </p>
        </div>

        {{-- Card 2: Kas Tunai Toko --}}
        <div class="p-5 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Kas Tunai (Laci)</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-[#006c49] flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">payments</span>
                </span>
            </div>
            <div class="mt-2 text-2xl font-black text-on-surface">
                Rp {{ number_format($cashAcc?->current_balance ?? 0, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Akun: 1101 - Kas Fisik Laci Toko</p>
        </div>

        {{-- Card 3: Piutang QRIS & EDC --}}
        <div class="p-5 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">QRIS / EDC Pending</span>
                <span class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">qr_code_scanner</span>
                </span>
            </div>
            <div class="mt-2 text-2xl font-black text-on-surface">
                Rp {{ number_format($qrisAcc?->current_balance ?? 0, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Akun: 1103 - Menunggu Settlement</p>
        </div>

        {{-- Card 4: Terakhir Direkonsiliasi --}}
        <div class="p-5 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Terakhir Rekonsiliasi</span>
                <span class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">history</span>
                </span>
            </div>
            <div class="mt-2 text-sm font-extrabold text-on-surface">
                {{ $lastRecon ? $lastRecon->reconciliation_date->isoFormat('D MMMM Y') : 'Belum pernah' }}
            </div>
            <p class="text-[11px] text-slate-500 mt-1 truncate">
                {{ $lastRecon ? ($lastRecon->status === 'balanced' ? '✓ Cocok seimbang' : 'Disesuaikan (' . ($lastRecon->difference > 0 ? '+' : '') . 'Rp ' . number_format($lastRecon->difference, 0, ',', '.') . ')') : 'Siap direkonsiliasi' }}
            </p>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="p-4 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs">
        <form method="GET" action="{{ route('accounting.reconciliations.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Mulai Tanggal</label>
                    <input type="date" name="start_date" value="{{ !empty($startDate) ? \Illuminate\Support\Carbon::parse($startDate)->format('Y-m-d') : '' }}" class="px-3 py-2 text-xs bg-surface-container-low border border-outline-variant rounded-xl font-medium focus:ring-1 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ !empty($endDate) ? \Illuminate\Support\Carbon::parse($endDate)->format('Y-m-d') : '' }}" class="px-3 py-2 text-xs bg-surface-container-low border border-outline-variant rounded-xl font-medium focus:ring-1 focus:ring-primary">
                </div>
                <div class="flex items-end gap-2 pt-4 sm:pt-0">
                    <button type="submit" class="px-4 py-2 bg-secondary text-on-secondary rounded-xl font-bold text-xs hover:opacity-90 transition-all flex items-center gap-1.5 cursor-pointer shadow-xs">
                        <span class="material-symbols-outlined text-sm">filter_list</span>
                        Terapkan
                    </button>
                    <a href="{{ route('accounting.reconciliations.index') }}" class="px-4 py-2 bg-surface-container-low border border-outline-variant text-on-surface-variant rounded-xl font-semibold text-xs hover:bg-surface-container-high transition-all flex items-center gap-1 no-underline">
                        Reset
                    </a>
                </div>
            </div>

            <div class="text-xs text-on-surface-variant text-right">
                Toko Aktif: <strong class="text-on-surface">{{ $store->name ?? 'Semua Toko' }}</strong>
            </div>
        </form>
    </div>

    {{-- Reconciliations History Table --}}
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl overflow-hidden shadow-xs">
        <div class="p-4 sm:p-5 border-b border-outline-variant/60 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-[#006c49] flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">receipt_long</span>
                </span>
                <h3 class="text-sm sm:text-base font-extrabold text-on-surface">Riwayat Rekonsiliasi & Penyesuaian Saldo</h3>
            </div>
            <span class="text-xs text-on-surface-variant">{{ $reconciliations->total() }} catatan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-surface-container-low/60 border-b border-outline-variant/60 text-on-surface-variant text-[11px] uppercase tracking-wider font-bold">
                        <th class="py-3 px-4">No. Rekon / Tanggal</th>
                        <th class="py-3 px-4">Akun Rekening</th>
                        <th class="py-3 px-4 text-right">Saldo Sistem (Sebelum)</th>
                        <th class="py-3 px-4 text-right">Saldo Riil (ATM)</th>
                        <th class="py-3 px-4 text-right">Selisih</th>
                        <th class="py-3 px-4">Akun Penyeimbang</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($reconciliations as $recon)
                    @php
                        $journal = $recon->journalEntry;
                    @endphp
                    <tr class="hover:bg-surface-container-low/40 transition-colors">
                        <td class="py-3.5 px-4">
                            <span class="font-extrabold text-on-surface block">{{ $recon->reconciliation_number }}</span>
                            <span class="text-[11px] text-on-surface-variant">{{ $recon->reconciliation_date->isoFormat('D MMM Y') }}</span>
                            @if($recon->user)
                                <span class="text-[10px] text-slate-400 block mt-0.5">Oleh: {{ $recon->user->name }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-on-surface">{{ $recon->account->name ?? '-' }}</span>
                            <span class="text-[11px] text-on-surface-variant block font-mono">({{ $recon->account->code ?? '' }})</span>
                        </td>
                        <td class="py-3.5 px-4 text-right font-medium text-on-surface-variant font-mono">
                            Rp {{ number_format($recon->system_balance, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-bold text-on-surface font-mono">
                            Rp {{ number_format($recon->actual_balance, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-black font-mono">
                            @if(abs($recon->difference) < 0.01)
                                <span class="text-slate-400">Rp 0</span>
                            @elseif($recon->difference > 0)
                                <span class="text-emerald-700">+Rp {{ number_format($recon->difference, 0, ',', '.') }}</span>
                            @else
                                <span class="text-rose-700">-Rp {{ number_format(abs($recon->difference), 0, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            @if($recon->adjustmentAccount)
                                <span class="text-on-surface font-semibold block">{{ $recon->adjustmentAccount->name }}</span>
                                <span class="text-[10px] text-on-surface-variant font-mono">{{ $recon->adjustmentAccount->code }}</span>
                            @else
                                <span class="text-slate-400 italic">- (Tidak perlu)</span>
                            @endif
                            @if($recon->notes)
                                <span class="text-[10px] text-slate-500 italic block mt-0.5 line-clamp-1" title="{{ $recon->notes }}">{{ $recon->notes }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($recon->status === 'balanced')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    <span class="material-symbols-outlined text-xs">done</span>
                                    Cocok
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-800 border border-blue-200">
                                    <span class="material-symbols-outlined text-xs">tune</span>
                                    Disesuaikan
                                </span>
                            @endif
                            @if($journal)
                                <a href="{{ route('accounting.journals.show', $journal->id) }}" class="block text-[10px] text-primary hover:underline font-mono mt-1" title="Lihat Jurnal Akuntansi">
                                    {{ $journal->entry_number }} &rarr;
                                </a>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <form action="{{ route('accounting.reconciliations.destroy', $recon->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan rekonsiliasi #{{ $recon->reconciliation_number }} ini? Jurnal penyesuaian terkait akan ikut dihapus.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors" title="Batalkan Rekonsiliasi">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-on-surface-variant">
                            <div class="w-12 h-12 rounded-full bg-surface-container-low flex items-center justify-center mx-auto mb-2 text-slate-400">
                                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
                            </div>
                            <p class="font-bold text-sm">Belum ada riwayat rekonsiliasi</p>
                            <p class="text-xs text-slate-500 mt-1">Gunakan tombol di atas untuk mencocokkan saldo rekening pertama Anda.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reconciliations->hasPages())
        <div class="p-4 border-t border-outline-variant/60">
            {{ $reconciliations->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

