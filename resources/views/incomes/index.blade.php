@extends('layouts.app')

@section('title', 'InvenTrack | Pemasukan Toko')

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
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Pemasukan Toko</h1>
            <p class="text-on-surface-variant text-body-lg">Kelola dan amati seluruh catatan pemasukan kas/bank non-POS toko.</p>
        </div>

        @can('Income Create')
        <a href="{{ route('incomes.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer self-start sm:self-auto no-underline">
            <span class="material-symbols-outlined text-base">add_box</span>
            <span>Tambah Pemasukan</span>
        </a>
        @endcan
    </div>

    {{-- Standard KPI Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-secondary-container/40 text-on-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">savings</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Pemasukan Bulan Ini</p>
                <h3 class="text-2xl font-extrabold text-[#006c49] font-mono mt-0.5 truncate">
                    Rp {{ number_format($currentMonthIncomes, 0, ',', '.') }}
                </h3>
            </div>
        </div>

        <div class="p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-surface-container-high text-[#006c49] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Hasil Filter</p>
                <h3 class="text-2xl font-extrabold text-on-surface font-mono mt-0.5 truncate">
                    Rp {{ number_format($totalFilteredIncomes, 0, ',', '.') }}
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
                    {{ $incomes->total() }} Transaksi
                </h3>
            </div>
        </div>
    </div>

    {{-- Standard Filter & Search Form --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 sm:p-5 shadow-sm">
        <form method="GET" action="{{ route('incomes.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-medium text-on-surface focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] transition">
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-medium text-on-surface focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] transition">
            </div>

            <div class="income-filter-ts">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Kategori</label>
                <select name="category" id="income_category_filter" class="w-full">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Pencarian</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau catatan..." class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs sm:text-sm font-medium text-on-surface focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] transition">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-[#006c49] text-white hover:bg-[#00553a] font-bold text-xs sm:text-sm rounded-xl active:scale-95 transition-all shadow-sm cursor-pointer h-[42px]">
                    <span class="material-symbols-outlined text-sm">filter_alt</span>
                    <span>Filter</span>
                </button>
                @if(request()->hasAny(['start_date', 'end_date', 'category', 'search']))
                    <a href="{{ route('incomes.index') }}" class="inline-flex items-center justify-center gap-1 px-3.5 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-semibold text-xs sm:text-sm rounded-xl transition-all cursor-pointer h-[42px] no-underline" title="Reset Filter">
                        <span class="material-symbols-outlined text-sm">restart_alt</span>
                        <span>Reset</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Standard Incomes Table Card --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
                        <th class="px-5 py-4">Tanggal</th>
                        <th class="px-5 py-4">Kategori</th>
                        <th class="px-5 py-4">Nama / Keterangan</th>
                        <th class="px-5 py-4 text-right">Nominal</th>
                        <th class="px-5 py-4">Dicatat Oleh</th>
                        <th class="px-5 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60 text-sm">
                @forelse($incomes as $income)
                <tr class="hover:bg-surface-container-low/50 transition-colors">
                    <td class="px-5 py-4 font-mono text-xs text-on-surface whitespace-nowrap">
                        {{ $income->income_date->format('d M Y') }}
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex flex-col gap-1 items-start">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ in_array($income->account?->code, ['1104', '1105']) ? 'bg-amber-500/10 text-amber-700 border border-amber-500/20' : ($income->account?->code === '3101' ? 'bg-purple-500/10 text-purple-700 border border-purple-500/20' : 'bg-[#006c49]/10 text-[#006c49] border border-[#006c49]/20') }}">
                                @if($income->account)
                                    [{{ $income->account->code }}] {{ $income->account->name }}
                                @else
                                    {{ $income->category }}
                                @endif
                            </span>
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-on-surface-variant">
                                <span class="material-symbols-outlined text-xs">{{ ($income->payment_method ?? 'cash') === 'transfer' ? 'account_balance' : 'payments' }}</span>
                                {{ ($income->payment_method ?? 'cash') === 'transfer' ? 'Bank Toko' : 'Kas Tunai' }}
                            </span>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="font-bold text-on-surface">{{ $income->title }}</div>
                        @if($income->notes)
                            <div class="text-xs text-on-surface-variant mt-0.5">{{ $income->notes }}</div>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-right font-mono font-extrabold text-[#006c49] whitespace-nowrap">
                        Rp {{ number_format($income->amount, 0, ',', '.') }}
                    </td>
                    <td class="px-5 py-4 text-xs text-on-surface-variant whitespace-nowrap">
                        {{ $income->user?->name ?? 'System' }}
                    </td>
                    <td class="px-5 py-4 text-center whitespace-nowrap">
                        <div class="inline-flex items-center gap-1.5">
                            @can('Income Edit')
                            <a href="{{ route('incomes.edit', $income) }}" class="p-1.5 text-on-surface-variant hover:text-primary hover:bg-surface-container-high rounded-lg transition-colors" title="Edit">
                                <span class="material-symbols-outlined text-lg">edit</span>
                            </a>
                            @endcan

                            @can('Income Destroy')
                            <button type="button" onclick="confirmDelete({{ $income->id }}, '{{ addslashes($income->title) }}')" class="p-1.5 text-on-surface-variant hover:text-error hover:bg-error-container/40 rounded-lg transition-colors cursor-pointer border-0 bg-transparent" title="Hapus">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-on-surface-variant text-sm">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-4xl text-outline-variant">savings</span>
                            <p class="font-medium">Belum ada catatan pemasukan toko.</p>
                            @can('Income Create')
                            <a href="{{ route('incomes.create') }}" class="text-primary font-bold text-xs hover:underline mt-1">
                                + Tambah Pemasukan Pertama
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($incomes->hasPages())
    <div class="p-4 border-t border-outline-variant bg-surface-container-lowest">
        {{ $incomes->links() }}
    </div>
    @endif
</div>

</div> {{-- End of .flex flex-col gap-6 --}}

{{-- Delete Confirmation Modal --}}
<div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs hidden transition-opacity">
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 max-w-sm w-full mx-4 shadow-xl">
        <div class="flex items-center gap-3 text-error mb-3">
            <span class="material-symbols-outlined text-3xl">warning</span>
            <h3 class="font-headline-md text-lg text-on-surface">Konfirmasi Hapus</h3>
        </div>
        <p class="text-sm text-on-surface-variant mb-5">
            Apakah Anda yakin ingin menghapus catatan pemasukan <strong id="deleteIncomeTitle" class="text-on-surface"></strong>? Tindakan ini tidak dapat dibatalkan.
        </p>
        <div class="flex justify-end gap-2.5">
            <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-semibold text-xs rounded-xl transition-all cursor-pointer border-0">
                Batal
            </button>
            <form id="deleteForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-error text-on-error font-bold text-xs rounded-xl hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer border-0">
                    Hapus Pemasukan
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<style>
    .income-filter-ts .ts-wrapper.single .ts-control {
        height: 42px !important;
        min-height: 42px !important;
        font-size: 0.8125rem !important;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof TomSelect !== 'undefined') {
            const catEl = document.getElementById('income_category_filter');
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

    function confirmDelete(id, title) {
        document.getElementById('deleteIncomeTitle').textContent = title;
        document.getElementById('deleteForm').action = `/incomes/${id}`;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Close on backdrop click
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });
</script>
@endpush

@endsection
