@extends('layouts.app')

@section('title', 'Manajemen Toko | InvenTrack')

@section('content')
<div class="flex flex-col gap-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Data Cabang Toko</h1>
            <p class="text-on-surface-variant text-body-lg">Kelola daftar lokasi cabang toko, penugasan staf, dan ketersediaan stok fisik.</p>
        </div>

        @can('Store Create')
        <a href="{{ route('stores.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer no-underline shrink-0 self-start sm:self-auto">
            <span class="material-symbols-outlined text-base">add_business</span>
            <span>Tambah Toko Baru</span>
        </a>
        @endcan
    </div>

    <!-- Error Alert Banner -->
    @if($errors->has('error'))
        <div class="p-4 bg-error-container/40 border border-error/30 rounded-2xl flex items-center justify-between gap-3 text-error">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-xl">error</span>
                <span class="text-sm font-bold">{{ $errors->first('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-error hover:opacity-75">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Stores -->
        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-surface-container-high text-on-surface flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl text-[#006c49]">storefront</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Toko</p>
                <h3 class="text-2xl font-extrabold text-on-surface mt-0.5">{{ $stats['total_stores'] ?? 0 }}</h3>
            </div>
        </div>

        <!-- Total Staff Assigned -->
        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-[#eff4ff] text-[#006c49] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">group</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Staf Terdaftar</p>
                <h3 class="text-2xl font-extrabold text-on-surface mt-0.5">{{ $stats['total_staff'] ?? 0 }}</h3>
            </div>
        </div>

        <!-- Total Products in Stores -->
        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-secondary-container/30 text-on-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl text-[#006c49]">inventory_2</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Katalog Produk</p>
                <h3 class="text-2xl font-extrabold text-on-surface mt-0.5">{{ $stats['total_products'] ?? 0 }}</h3>
            </div>
        </div>

        <!-- Total Sales/Transactions -->
        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-surface-container text-on-surface-variant flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">receipt_long</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Penjualan</p>
                <h3 class="text-2xl font-extrabold text-on-surface mt-0.5">{{ $stats['total_sales'] ?? 0 }}</h3>
            </div>
        </div>
    </div>

    <!-- Stores Table Card -->
    <div class="bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden flex flex-col">

        <!-- Search Controls -->
        <div class="p-4 sm:p-5 border-b border-outline-variant flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <form action="{{ route('stores.index') }}" method="GET" class="w-full sm:w-96 flex items-center relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">search</span>
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Cari nama toko, alamat, telepon..." 
                    value="{{ request('search') }}"
                    class="w-full pl-9 pr-8 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-xs font-medium text-on-surface transition"
                />
                @if(request('search'))
                    <a href="{{ route('stores.index') }}" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-black">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </a>
                @endif
            </form>

            <div class="text-xs font-bold text-on-surface-variant text-right shrink-0">
                Menampilkan {{ $stores->firstItem() ?? 0 }} - {{ $stores->lastItem() ?? 0 }} dari {{ $stores->total() }} toko
            </div>
        </div>

        <!-- Desktop Table (hidden on mobile) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant border-b border-outline-variant font-bold text-[11px] uppercase tracking-wider">
                        <th class="px-6 py-3.5">Nama Toko</th>
                        <th class="px-6 py-3.5">Rekening Bank</th>
                        <th class="px-6 py-3.5 text-right">Saldo Toko Berjalan</th>
                        <th class="px-6 py-3.5 text-center">Staf Ditugaskan</th>
                        <th class="px-6 py-3.5 text-center">Produk Terkait</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60 text-sm font-medium">
                    @forelse($stores as $store)
                        <tr class="hover:bg-neutral-50/80 transition-colors">
                            <!-- Store Name -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-[#eff4ff] text-[#006c49] border border-[#006c49]/20 font-black flex items-center justify-center text-sm shrink-0 shadow-sm">
                                        <span class="material-symbols-outlined text-xl">store</span>
                                    </div>
                                    <div>
                                        <a href="{{ route('stores.show', $store) }}" class="font-bold text-on-surface hover:text-secondary transition-colors block">
                                            {{ $store->name }}
                                        </a>
                                        <div class="text-[11px] text-on-surface-variant mt-0.5">
                                            {{ $store->address ?: 'Didaftarkan ' . $store->created_at->format('d M Y') }}
                                        </div>
                                        @if($store->hasGeofence())
                                            <div class="mt-1">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <span class="material-symbols-outlined text-xs">radar</span>
                                                    Geofence Aktif ({{ $store->radius_meters }}m)
                                                </span>
                                            </div>
                                        @else
                                            <div class="mt-1">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                                    <span class="material-symbols-outlined text-xs">location_off</span>
                                                    GPS Belum Disetel
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Bank Account -->
                            <td class="px-6 py-4">
                                @if($store->bank_name || $store->bank_account_number)
                                    <div class="text-xs">
                                        <span class="font-bold text-on-surface">{{ $store->bank_name ?? 'Bank' }}</span>
                                        <div class="font-mono text-on-surface-variant text-[11px]">{{ $store->bank_account_number ?? '-' }}</div>
                                        @if($store->bank_account_name)
                                            <div class="text-[10px] text-on-surface-variant truncate">a/n {{ $store->bank_account_name }}</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-on-surface-variant italic">Belum diatur</span>
                                @endif
                            </td>

                            <!-- Ending Balance -->
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-extrabold font-mono text-[#006c49]">
                                    Rp {{ number_format($store->ending_balance, 0, ',', '.') }}
                                </div>
                                <div class="text-[10px] text-on-surface-variant">
                                    Awal: Rp {{ number_format($store->initial_balance ?? 0, 0, ',', '.') }}
                                </div>
                            </td>

                            <!-- Staff Count -->
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-[#eff4ff] text-[#006c49] border border-outline-variant/60">
                                    <span class="material-symbols-outlined text-sm">badge</span>
                                    <span>{{ $store->users_count }} Staf</span>
                                </span>
                            </td>

                            <!-- Product Count -->
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-surface-container text-on-surface border border-outline-variant/60">
                                    <span class="material-symbols-outlined text-sm">inventory_2</span>
                                    <span>{{ $store->products_count }} Produk</span>
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- View Detail -->
                                    <a href="{{ route('stores.show', $store) }}" class="p-1.5 hover:bg-surface-container-high rounded-lg text-on-surface-variant hover:text-black transition" title="Lihat Detail Cabang">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                    </a>

                                    <!-- Edit -->
                                    @can('Store Edit')
                                    <a href="{{ route('stores.edit', $store) }}" class="p-1.5 hover:bg-surface-container-high rounded-lg text-on-surface-variant hover:text-black transition" title="Ubah Detail Toko">
                                        <span class="material-symbols-outlined text-lg">edit</span>
                                    </a>
                                    @endcan

                                    <!-- Delete -->
                                    @can('Store Destroy')
                                    <button 
                                        type="button" 
                                        onclick="confirmDeleteStore('{{ $store->id }}', '{{ addslashes($store->name) }}', {{ $store->products_count }}, {{ $store->users_count }})"
                                        class="p-1.5 hover:bg-error-container/40 rounded-lg text-error transition cursor-pointer" 
                                        title="Hapus Toko"
                                    >
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl block mb-2 opacity-30">storefront</span>
                                <p class="text-sm font-semibold">Tidak ada cabang toko yang terdaftar atau sesuai pencarian.</p>
                                @if(request('search'))
                                    <a href="{{ route('stores.index') }}" class="text-xs text-[#006c49] font-bold hover:underline mt-1 inline-block">
                                        Reset Pencarian
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View (hidden on desktop) -->
        <div class="block md:hidden divide-y divide-outline-variant/60">
            @forelse($stores as $store)
                <div class="p-4 flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl bg-[#eff4ff] text-[#006c49] border border-[#006c49]/20 font-black flex items-center justify-center text-sm shrink-0">
                                <span class="material-symbols-outlined text-xl">store</span>
                            </div>
                            <div>
                                <a href="{{ route('stores.show', $store) }}" class="font-bold text-sm text-on-surface hover:text-secondary block">
                                    {{ $store->name }}
                                </a>
                                @if($store->phone)
                                    <div class="text-xs text-on-surface-variant font-mono">{{ $store->phone }}</div>
                                @endif
                            </div>
                        </div>

                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#eff4ff] text-[#006c49] border border-outline-variant/60 shrink-0">
                            {{ $store->users_count }} Staf
                        </span>
                    </div>

                    @if($store->address)
                        <div class="text-xs text-on-surface-variant flex items-start gap-1">
                            <span class="material-symbols-outlined text-xs shrink-0 mt-0.5">location_on</span>
                            <span>{{ $store->address }}</span>
                        </div>
                    @endif

                    <!-- Mobile Action Buttons -->
                    <div class="flex items-center gap-2 pt-2 border-t border-outline-variant/40">
                        <a href="{{ route('stores.show', $store) }}" class="flex-1 py-1.5 px-3 rounded-xl border border-outline-variant bg-surface-container text-on-surface text-xs font-bold text-center">
                            Detail
                        </a>
                        @can('Store Edit')
                        <a href="{{ route('stores.edit', $store) }}" class="flex-1 py-1.5 px-3 rounded-xl bg-[#006c49] text-white hover:bg-[#00553a] text-xs font-bold text-center transition shadow-2xs">
                            Ubah
                        </a>
                        @endcan
                        @can('Store Destroy')
                        <button 
                            type="button" 
                            onclick="confirmDeleteStore('{{ $store->id }}', '{{ addslashes($store->name) }}', {{ $store->products_count }}, {{ $store->users_count }})" 
                            class="py-1.5 px-3 rounded-xl border border-error/30 text-error text-xs font-bold"
                        >
                            Hapus
                        </button>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-4xl block mb-2 opacity-30">storefront</span>
                    <p class="text-sm font-semibold">Belum ada toko yang terdaftar.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($stores->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant bg-surface-container-lowest">
                {{ $stores->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Delete Confirmation Modal Dialog -->
<div id="delete-store-modal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl border border-outline-variant max-w-sm w-full p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
        <div class="w-12 h-12 rounded-xl bg-error-container text-error flex items-center justify-center mb-4">
            <span class="material-symbols-outlined text-2xl">warning</span>
        </div>
        <h3 class="text-lg font-bold text-on-surface mb-1">Hapus Cabang Toko?</h3>
        <p class="text-xs text-on-surface-variant leading-relaxed mb-4">
            Apakah Anda yakin ingin menghapus cabang toko <strong id="delete-store-name" class="text-on-surface"></strong>?
        </p>

        <div id="delete-store-warning" class="hidden p-3 bg-amber-50 border border-amber-200 rounded-xl mb-5 text-[11px] text-amber-800">
            <div class="flex items-center gap-1.5 font-bold mb-0.5">
                <span class="material-symbols-outlined text-xs">info</span>
                <span>Perhatian Data Terkait</span>
            </div>
            <span id="delete-warning-text"></span>
        </div>

        <form id="delete-store-form" method="POST" action="" class="flex items-center justify-end gap-2.5">
            @csrf
            @method('DELETE')
            <button type="button" onclick="closeDeleteStoreModal()" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface-container hover:bg-surface-container-high text-xs font-bold text-on-surface transition">
                Batal
            </button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-error text-white hover:bg-red-700 text-xs font-bold transition">
                Ya, Hapus Toko
            </button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function confirmDeleteStore(storeId, storeName, productCount, userCount) {
        const modal = document.getElementById('delete-store-modal');
        const nameSpan = document.getElementById('delete-store-name');
        const form = document.getElementById('delete-store-form');
        const warningBox = document.getElementById('delete-store-warning');
        const warningText = document.getElementById('delete-warning-text');
        
        nameSpan.textContent = storeName;
        form.action = `/stores/${storeId}`;

        if (productCount > 0 || userCount > 0) {
            warningText.textContent = `Toko ini memiliki ${productCount} produk dan ${userCount} staf terkait. Menghapus toko akan menghapus semua data inventaris di cabang ini.`;
            warningBox.classList.remove('hidden');
        } else {
            warningBox.classList.add('hidden');
        }

        modal.classList.remove('hidden');
    }

    function closeDeleteStoreModal() {
        document.getElementById('delete-store-modal').classList.add('hidden');
    }

    // Close modal on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteStoreModal();
        }
    });
</script>
@endpush
