@extends('layouts.app')

@section('title', 'InvenTrack | Pengeluaran Toko')

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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Pengeluaran Toko</h1>
            <p class="text-on-surface-variant text-body-lg">Kelola dan amati seluruh catatan pengeluaran operasional toko.</p>
        </div>

        @can('Expense Create')
        <a href="{{ route('expenses.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer self-start sm:self-auto no-underline">
            <span class="material-symbols-outlined text-base">add_box</span>
            <span>Tambah Pengeluaran</span>
        </a>
        @endcan
    </div>

    {{-- Standard KPI Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-error-container/40 text-error flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Pengeluaran Bulan Ini</p>
                <h3 class="text-2xl font-extrabold text-error font-mono mt-0.5 truncate">
                    Rp {{ number_format($currentMonthExpenses, 0, ',', '.') }}
                </h3>
            </div>
        </div>

        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-secondary-container/40 text-on-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Hasil Filter</p>
                <h3 class="text-2xl font-extrabold text-on-surface font-mono mt-0.5 truncate">
                    Rp {{ number_format($totalFilteredExpenses, 0, ',', '.') }}
                </h3>
            </div>
        </div>

        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-surface-container text-on-surface flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">receipt_long</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Jumlah Catatan</p>
                <h3 class="text-2xl font-extrabold text-on-surface mt-0.5 truncate">
                    {{ $expenses->total() }} Transaksi
                </h3>
            </div>
        </div>
    </div>

    {{-- Standard Filter & Search Form --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 sm:p-5 shadow-sm">
        <form method="GET" action="{{ route('expenses.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-medium text-on-surface focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] transition">
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-medium text-on-surface focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] transition">
            </div>

            <div class="expense-filter-ts">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Kategori</label>
                <select name="category" id="expense_category_filter" class="w-full">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Cari Pengeluaran</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / catatan..." class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-medium text-on-surface focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] transition">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-[#006c49] text-white hover:bg-[#00553a] font-bold text-xs sm:text-sm rounded-xl active:scale-95 transition-all shadow-sm cursor-pointer h-[42px]">
                    <span class="material-symbols-outlined text-sm">filter_alt</span>
                    <span>Filter</span>
                </button>
                @if(request()->hasAny(['start_date', 'end_date', 'category', 'search']))
                <a href="{{ route('expenses.index') }}" class="inline-flex items-center justify-center gap-1 px-3.5 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-semibold text-xs sm:text-sm rounded-xl transition-all cursor-pointer h-[42px] no-underline" title="Reset Filter">
                    <span class="material-symbols-outlined text-sm">restart_alt</span>
                    <span>Reset</span>
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Standard Expense Table Card --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm flex flex-col">
        @if($expenses->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low text-on-surface-variant text-[11px] font-bold uppercase tracking-wider border-b border-outline-variant">
                    <tr>
                        <th class="px-5 py-4">Tanggal</th>
                        <th class="px-5 py-4">Nama Pengeluaran</th>
                        <th class="px-5 py-4">Kategori</th>
                        <th class="px-5 py-4 text-right">Jumlah (Nominal)</th>
                        <th class="px-5 py-4">Catatan</th>
                        <th class="px-5 py-4">Dicatat Oleh</th>
                        <th class="px-5 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60 text-sm">
                    @foreach($expenses as $expense)
                    <tr class="hover:bg-surface-container-low/40 transition-colors">
                        <td class="px-5 py-4 font-mono text-xs text-on-surface whitespace-nowrap">
                            {{ $expense->expense_date->format('d M Y') }}
                        </td>
                        <td class="px-5 py-4 font-bold text-on-surface">
                            {{ $expense->title }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex flex-col gap-1 items-start">
                                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-bold rounded-full {{ in_array($expense->account?->code, ['1104', '1105']) ? 'bg-amber-500/10 text-amber-700 border border-amber-500/20' : (in_array($expense->account?->code, ['2102', '2103']) ? 'bg-purple-500/10 text-purple-700 border border-purple-500/20' : 'bg-surface-container-high text-on-surface-variant border border-outline-variant/50') }}">
                                    @if($expense->account)
                                        [{{ $expense->account->code }}] {{ $expense->account->name }}
                                    @else
                                        {{ $expense->category }}
                                    @endif
                                </span>
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-on-surface-variant">
                                    <span class="material-symbols-outlined text-xs">{{ ($expense->payment_method ?? 'cash') === 'transfer' ? 'account_balance' : 'payments' }}</span>
                                    {{ ($expense->payment_method ?? 'cash') === 'transfer' ? 'Bank Toko' : 'Kas Tunai' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right font-mono font-extrabold text-error whitespace-nowrap">
                            Rp {{ number_format($expense->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-4 text-on-surface-variant text-xs max-w-xs truncate">
                            {{ $expense->notes ?? '-' }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-on-surface-variant">
                                <span class="w-6 h-6 rounded-full bg-primary-container text-on-primary-container text-[10px] font-extrabold flex items-center justify-center uppercase shrink-0">
                                    {{ substr($expense->user?->name ?? 'S', 0, 1) }}
                                </span>
                                {{ $expense->user?->name ?? 'System' }}
                            </div>
                        </td>
                        <td class="px-5 py-4 text-center whitespace-nowrap">
                            <div class="inline-flex items-center justify-center gap-1.5">
                                @can('Expense Edit')
                                <a href="{{ route('expenses.edit', $expense->id) }}" class="p-1.5 text-on-surface-variant hover:text-[#006c49] hover:bg-surface-container-high rounded-lg transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </a>
                                @endcan

                                @can('Expense Destroy')
                                <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan pengeluaran ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-on-surface-variant hover:text-error hover:bg-error-container/40 rounded-lg transition-colors cursor-pointer border-0 bg-transparent" title="Hapus">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
        <div class="p-4 border-t border-outline-variant bg-surface-container-lowest">
            {{ $expenses->links() }}
        </div>
        @endif

        @else
        <div class="py-16 text-center">
            <span class="material-symbols-outlined text-5xl text-on-surface-variant/40 mb-3 block">payments</span>
            <p class="font-bold text-on-surface-variant text-sm">Belum ada catatan pengeluaran toko.</p>
            <p class="text-on-surface-variant text-xs mt-1">Klik "+ Tambah Pengeluaran" di atas untuk membuat catatan baru.</p>
        </div>
        @endif
    </div>

</div> {{-- End of .flex flex-col gap-6 --}}

@push('scripts')
<style>
    .expense-filter-ts .ts-wrapper.single .ts-control {
        height: 42px !important;
        min-height: 42px !important;
        font-size: 0.8125rem !important;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof TomSelect !== 'undefined') {
            const catEl = document.getElementById('expense_category_filter');
            if (catEl) {
                new TomSelect(catEl, {
                    create: false,
                    allowEmptyOption: true,
                    placeholder: 'Semua Kategori',
                    maxOptions: 100
                });
            }
        }
    });
</script>
@endpush

@endsection
