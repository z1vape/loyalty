@extends('layouts.app')

@section('title', 'Z1 VapeOS | Tutup Buku & Periode Akuntansi')

@section('content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="font-headline-lg text-headline-lg text-on-surface">Tutup Buku & Periode Akuntansi</h1>
        <p class="text-on-surface-variant text-body-lg">Kunci periode akuntansi untuk mencegah perubahan data transaksi setelah pelaporan keuangan selesai.</p>
    </div>

    <div class="flex items-center gap-2 self-start sm:self-auto">
        <a href="{{ route('accounting.reports.balance-sheet') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
            <span class="material-symbols-outlined text-base">account_balance</span>
            Neraca
        </a>
        <a href="{{ route('accounting.reports.profit-loss') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer no-underline">
            <span class="material-symbols-outlined text-base">analytics</span>
            Laba Rugi
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left 2 Cols: Form Tutup Buku Baru --}}
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs p-6">
            <h3 class="text-base font-extrabold text-on-surface mb-1 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">lock</span>
                Tutup Buku Periode Baru
            </h3>
            <p class="text-xs text-on-surface-variant mb-6">Pilih rentang tanggal yang ingin dikunci.</p>

            <form method="POST" action="{{ route('accounting.periods.store') }}">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-xs font-bold text-on-surface mb-1.5">
                            Nama Periode <span class="text-error">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name', $suggestedName) }}" required placeholder="Contoh: Tutup Buku Januari 2026"
                            class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary">
                        @error('name')
                            <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="start_date" class="block text-xs font-bold text-on-surface mb-1.5">
                                Dari Tanggal <span class="text-error">*</span>
                            </label>
                            <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $suggestedStart) }}" required
                                class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-primary">
                            @error('start_date')
                                <p class="text-xs text-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="end_date" class="block text-xs font-bold text-on-surface mb-1.5">
                                Sampai Tanggal <span class="text-error">*</span>
                            </label>
                            <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $suggestedEnd) }}" required
                                class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-primary">
                            @error('end_date')
                                <p class="text-xs text-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="closing_notes" class="block text-xs font-bold text-on-surface mb-1.5">
                            Catatan Tutup Buku
                        </label>
                        <textarea id="closing_notes" name="closing_notes" rows="3" placeholder="Contoh: Telah diaudit dan dicocokkan dengan rekening koran bank."
                            class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-primary">{{ old('closing_notes') }}</textarea>
                        @error('closing_notes')
                            <p class="text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-start gap-2.5">
                        <span class="material-symbols-outlined text-amber-700 text-lg mt-0.5">warning</span>
                        <p class="text-[11px] text-amber-900 leading-relaxed font-medium">
                            Setelah dikunci, kasir atau admin tidak dapat menambah, mengedit, atau menghapus penjualan, stok masuk, atau biaya pada rentang tanggal tersebut.
                        </p>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base">lock</span>
                        Kunci & Tutup Buku
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Right 2 Cols: Periods Table --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-outline-variant flex items-center justify-between">
                <div>
                    <h3 class="text-base font-extrabold text-on-surface">Daftar Periode Buku</h3>
                    <p class="text-xs text-on-surface-variant">Status periode akuntansi toko saat ini</p>
                </div>
                <span class="px-2.5 py-1 bg-surface-container-high text-on-surface-variant rounded-full text-xs font-bold">
                    {{ $activeClosedCount }} Periode Terkunci
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-bold uppercase tracking-wider">
                            <th class="px-4 py-3.5">Nama Periode</th>
                            <th class="px-4 py-3.5">Rentang Tanggal</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                            <th class="px-4 py-3.5">Ditutup Oleh</th>
                            <th class="px-4 py-3.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse($periods as $period)
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-bold text-on-surface text-sm">{{ $period->name }}</span>
                                @if($period->closing_notes)
                                    <p class="text-[11px] text-on-surface-variant line-clamp-1 mt-0.5">{{ $period->closing_notes }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-on-surface font-mono">
                                {{ $period->start_date->format('d/m/Y') }} &mdash; {{ $period->end_date->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($period->isClosed())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-500/10 text-rose-700 border border-rose-500/20">
                                        <span class="material-symbols-outlined text-xs">lock</span>
                                        Terkunci
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-500/10 text-emerald-700 border border-emerald-500/20">
                                        <span class="material-symbols-outlined text-xs">lock_open</span>
                                        Terbuka
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-on-surface-variant">
                                @if($period->isClosed())
                                    <p class="font-semibold text-on-surface">{{ $period->closedByUser?->name ?? 'Admin' }}</p>
                                    <p class="text-[10px]">{{ $period->closed_at ? $period->closed_at->format('d/m/Y H:i') : '-' }}</p>
                                @else
                                    <span class="text-on-surface-variant">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    @if($period->isClosed())
                                        <form method="POST" action="{{ route('accounting.periods.reopen', $period) }}" onsubmit="return confirm('Buka kembali periode {{ $period->name }}? Transaksi pada tanggal tersebut akan dapat diedit kembali.')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-2.5 py-1 bg-amber-500/10 border border-amber-500/20 text-amber-800 rounded-lg text-xs font-bold hover:bg-amber-500/20 transition-all cursor-pointer flex items-center gap-1" title="Buka Kunci">
                                                <span class="material-symbols-outlined text-xs">lock_open</span>
                                                Buka
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('accounting.periods.close', $period) }}" onsubmit="return confirm('Kunci kembali periode {{ $period->name }}?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-2.5 py-1 bg-rose-500/10 border border-rose-500/20 text-rose-800 rounded-lg text-xs font-bold hover:bg-rose-500/20 transition-all cursor-pointer flex items-center gap-1" title="Kunci">
                                                <span class="material-symbols-outlined text-xs">lock</span>
                                                Kunci
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('accounting.periods.destroy', $period) }}" onsubmit="return confirm('Hapus pencatatan periode {{ $period->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-error hover:bg-error-container/20 rounded-lg transition-colors cursor-pointer" title="Hapus Periode">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-on-surface-variant">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-3xl text-on-surface-variant/40">lock_clock</span>
                                    <p class="font-medium">Belum ada periode akuntansi yang ditutup.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($periods->hasPages())
            <div class="p-4 border-t border-outline-variant">
                {{ $periods->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@endsection
