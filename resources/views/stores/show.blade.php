@extends('layouts.app')

@section('title', 'Detail Toko: ' . $store->name . ' | InvenTrack')

@section('content')
<div class="max-w-5xl mx-auto flex flex-col gap-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Detail Cabang Toko</h1>
            <p class="text-on-surface-variant text-body-lg">
                Informasi operasional cabang, daftar staf bertugas, dan inventaris produk terkait.
            </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
            <a href="{{ route('stores.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                <span>Kembali</span>
            </a>
            @can('Store Edit')
            <a href="{{ route('stores.edit', $store) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base">edit</span>
                <span>Ubah Toko</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Store Hero Card -->
    <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-8 shadow-sm flex flex-col sm:flex-row items-center sm:items-start gap-6">
        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-[#eff4ff] text-[#006c49] border-2 border-[#006c49]/20 font-black flex items-center justify-center text-3xl shrink-0 shadow-inner">
            <span class="material-symbols-outlined text-4xl">store</span>
        </div>

        <div class="flex-1 text-center sm:text-left">
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 mb-2">
                <h2 class="text-xl sm:text-2xl font-extrabold text-on-surface">{{ $store->name }}</h2>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#eff4ff] text-[#006c49] border border-[#006c49]/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#006c49]"></span>
                    Cabang Aktif
                </span>
            </div>

            <div class="space-y-1 text-xs text-on-surface-variant font-medium">
                @if($store->phone)
                    <div class="flex items-center justify-center sm:justify-start gap-1.5 font-mono">
                        <span class="material-symbols-outlined text-sm text-secondary">call</span>
                        <span>{{ $store->phone }}</span>
                    </div>
                @endif
                @if($store->address)
                    <div class="flex items-start justify-center sm:justify-start gap-1.5">
                        <span class="material-symbols-outlined text-sm text-secondary shrink-0 mt-0.5">location_on</span>
                        <span>{{ $store->address }}</span>
                    </div>
                @endif
            </div>

            <p class="text-[11px] text-on-surface-variant mt-3">
                Didaftarkan pada {{ $store->created_at->format('d F Y, H:i') }}
            </p>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-3 gap-4">
        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-surface-container-high text-on-surface flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl text-[#006c49]">group</span>
            </div>
            <div>
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Staf Bertugas</p>
                <h3 class="text-xl font-extrabold text-on-surface mt-0.5">{{ $store->users_count }} Orang</h3>
            </div>
        </div>

        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-secondary-container/30 text-on-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl text-[#006c49]">inventory_2</span>
            </div>
            <div>
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Produk</p>
                <h3 class="text-xl font-extrabold text-on-surface mt-0.5">{{ $store->products_count }} Item</h3>
            </div>
        </div>

        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-surface-container text-on-surface-variant flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">receipt_long</span>
            </div>
            <div>
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Penjualan</p>
                <h3 class="text-xl font-extrabold text-on-surface mt-0.5">{{ $store->sales_count }} Transaksi</h3>
            </div>
        </div>
    </div>

    <!-- Two-Card Section: Staff & Products -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Card: Staf Bertugas -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm flex flex-col">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-outline-variant/60">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-xl text-[#006c49]">badge</span>
                    <h3 class="font-bold text-base text-on-surface">Staf yang Ditugaskan</h3>
                </div>
                <span class="text-xs text-on-surface-variant font-semibold">{{ $store->users->count() }} Staf</span>
            </div>

            @if($store->users->isEmpty())
                <p class="text-xs text-on-surface-variant italic py-6 text-center">Belum ada staf yang ditugaskan ke cabang ini.</p>
            @else
                <div class="divide-y divide-outline-variant/40 overflow-y-auto max-h-80">
                    @foreach($store->users as $u)
                        <div class="py-3 flex items-center justify-between gap-3 first:pt-0 last:pb-0">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full overflow-hidden bg-surface-container-high text-[#006c49] font-black flex items-center justify-center text-xs shrink-0 border border-outline-variant">
                                    @if($u->photo)
                                        <img src="{{ Storage::url($u->photo) }}" alt="{{ $u->name }}" class="w-full h-full object-cover" />
                                    @else
                                        <span>{{ substr($u->name, 0, 2) }}</span>
                                    @endif
                                </div>
                                <div>
                                    <a href="{{ route('users.show', $u) }}" class="font-bold text-xs text-on-surface hover:text-secondary block">
                                        {{ $u->name }}
                                    </a>
                                    <div class="text-[11px] text-on-surface-variant font-mono">{{ $u->email }}</div>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#eff4ff] text-[#006c49] border border-outline-variant/60 shrink-0">
                                {{ $u->roles->pluck('name')->first() ?? 'Staff' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Card: Ringkasan Produk di Cabang -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm flex flex-col">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-outline-variant/60">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-xl text-[#006c49]">inventory_2</span>
                    <h3 class="font-bold text-base text-on-surface">Katalog Produk Cabang</h3>
                </div>
                <a href="{{ route('inventory.index') }}" class="text-xs text-[#006c49] font-bold hover:underline">
                    Lihat Semua
                </a>
            </div>

            @if($store->products->isEmpty())
                <p class="text-xs text-on-surface-variant italic py-6 text-center">Belum ada produk yang dialokasikan ke cabang ini.</p>
            @else
                <div class="divide-y divide-outline-variant/40 overflow-y-auto max-h-80">
                    @foreach($store->products as $prod)
                        <div class="py-2.5 flex items-center justify-between gap-3 first:pt-0 last:pb-0">
                            <div>
                                <div class="font-bold text-xs text-on-surface">{{ $prod->name }}</div>
                                <div class="text-[10px] text-on-surface-variant font-mono">{{ $prod->sku }}</div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold font-mono text-on-surface">
                                    Rp {{ number_format($prod->selling_price, 0, ',', '.') }}
                                </span>
                                <div class="text-[10px] text-on-surface-variant">
                                    Stok: <strong class="{{ $prod->stock <= $prod->min_stock ? 'text-error' : 'text-on-surface' }}">{{ $prod->stock }}</strong>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
