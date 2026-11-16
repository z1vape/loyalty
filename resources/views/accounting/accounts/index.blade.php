@extends('layouts.app')

@section('title', 'Z1 VapeOS | Bagan Akun (Chart of Accounts)')

@section('content')

{{-- Header & Action Button --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="font-headline-lg text-headline-lg text-on-surface">Bagan Akun (Chart of Accounts)</h1>
        <p class="text-on-surface-variant text-body-lg">Daftar kode akun standar dan klasifikasi pembukuan toko aktif.</p>
    </div>

    <div class="flex items-center gap-2 self-start sm:self-auto">
        @can('Accounting CoA Create')
        <a href="{{ route('accounting.accounts.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-sm cursor-pointer no-underline">
            <span class="material-symbols-outlined text-base">add</span>
            Tambah Akun Baru
        </a>
        @endcan
    </div>
</div>

{{-- Metric Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
    <div class="p-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
        <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Akun</p>
        <p class="text-lg font-headline-md font-extrabold text-on-surface mt-0.5">{{ $totalAccounts }}</p>
    </div>
    <div class="p-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
        <p class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">1. Aset</p>
        <p class="text-lg font-headline-md font-extrabold text-emerald-800 mt-0.5">{{ $totalAssets }}</p>
    </div>
    <div class="p-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
        <p class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">2. Kewajiban</p>
        <p class="text-lg font-headline-md font-extrabold text-amber-800 mt-0.5">{{ $totalLiabilities }}</p>
    </div>
    <div class="p-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
        <p class="text-[10px] font-bold text-purple-700 uppercase tracking-wider">3. Modal</p>
        <p class="text-lg font-headline-md font-extrabold text-purple-800 mt-0.5">{{ $totalEquity }}</p>
    </div>
    <div class="p-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
        <p class="text-[10px] font-bold text-sky-700 uppercase tracking-wider">4. Pendapatan</p>
        <p class="text-lg font-headline-md font-extrabold text-sky-800 mt-0.5">{{ $totalRevenue }}</p>
    </div>
    <div class="p-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs">
        <p class="text-[10px] font-bold text-rose-700 uppercase tracking-wider">5 & 6. Beban/HPP</p>
        <p class="text-lg font-headline-md font-extrabold text-rose-800 mt-0.5">{{ $totalExpenses }}</p>
    </div>
</div>

{{-- Filter & Search Form --}}
<div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 shadow-xs">
    <form method="GET" action="{{ route('accounting.accounts.index') }}" class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode akun, nama, atau kategori..." class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
        </div>
        <div class="w-full sm:w-56">
            <select name="type" class="w-full px-3.5 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
                <option value="">Semua Klasifikasi</option>
                <option value="asset" {{ request('type') === 'asset' ? 'selected' : '' }}>1. Aset (Aktiva)</option>
                <option value="liability" {{ request('type') === 'liability' ? 'selected' : '' }}>2. Kewajiban (Hutang)</option>
                <option value="equity" {{ request('type') === 'equity' ? 'selected' : '' }}>3. Ekuitas (Modal)</option>
                <option value="revenue" {{ request('type') === 'revenue' ? 'selected' : '' }}>4. Pendapatan</option>
                <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>5 & 6. Beban / HPP</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-secondary text-on-secondary text-xs font-bold rounded-xl hover:opacity-90 transition-all cursor-pointer">
                Filter
            </button>
            <a href="{{ route('accounting.accounts.index') }}" class="px-4 py-2 bg-surface-container-low border border-outline-variant text-on-surface text-xs font-semibold rounded-xl hover:bg-surface-container-high transition-all text-center no-underline">
                Reset
            </a>
        </div>
    </form>
</div>

{{-- Accounts Table --}}
<div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-xs">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant">
                <tr>
                    <th class="px-4 py-3.5">Kode</th>
                    <th class="px-4 py-3.5">Nama Akun</th>
                    <th class="px-4 py-3.5">Klasifikasi</th>
                    <th class="px-4 py-3.5">Kategori</th>
                    <th class="px-4 py-3.5 text-center">Saldo Normal</th>
                    <th class="px-4 py-3.5 text-center">Status</th>
                    <th class="px-4 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/60">
                @forelse($accounts as $acc)
                <tr class="hover:bg-surface-container-low/50 transition-colors">
                    <td class="px-4 py-3 font-mono font-bold text-on-surface">
                        {{ $acc->code }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-bold text-on-surface text-xs">{{ $acc->name }}</div>
                        @if($acc->description)
                            <div class="text-[11px] text-on-surface-variant mt-0.5 line-clamp-1">{{ $acc->description }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md border text-[10px] font-bold {{ $acc->type_badge_class }}">
                            {{ $acc->type_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-medium text-on-surface-variant">
                        {{ $acc->category }}
                    </td>
                    <td class="px-4 py-3 text-center uppercase font-mono font-semibold text-[11px] text-on-surface-variant">
                        {{ $acc->normal_balance }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($acc->is_system)
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-surface-container text-on-surface text-[10px] font-bold rounded" title="Akun sistem tidak dapat dihapus">
                                <span class="material-symbols-outlined text-[13px] text-primary">lock</span>
                                Sistem
                            </span>
                        @elseif($acc->is_active)
                            <span class="inline-block px-1.5 py-0.5 bg-emerald-500/10 text-emerald-700 text-[10px] font-bold rounded">
                                Aktif
                            </span>
                        @else
                            <span class="inline-block px-1.5 py-0.5 bg-gray-100 text-gray-500 text-[10px] font-bold rounded">
                                Nonaktif
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <a href="{{ route('accounting.journals.ledger', ['account_id' => $acc->id]) }}" class="p-1.5 text-on-surface-variant hover:text-primary hover:bg-surface-container-high rounded-lg transition-all" title="Buku Besar">
                                <span class="material-symbols-outlined text-base">menu_book</span>
                            </a>
                            @can('Accounting CoA Edit')
                            <a href="{{ route('accounting.accounts.edit', $acc->id) }}" class="p-1.5 text-on-surface-variant hover:text-secondary hover:bg-surface-container-high rounded-lg transition-all" title="Edit Akun">
                                <span class="material-symbols-outlined text-base">edit</span>
                            </a>
                            @endcan
                            @can('Accounting CoA Delete')
                            @if(!$acc->is_system)
                            <form action="{{ route('accounting.accounts.destroy', $acc->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $acc->code }} - {{ $acc->name }}?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-error hover:bg-error-container/20 rounded-lg transition-all cursor-pointer" title="Hapus Akun">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </form>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-3xl mb-1 text-outline">account_balance</span>
                        <p class="font-medium">Tidak ada akun yang sesuai dengan filter.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
