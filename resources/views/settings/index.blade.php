@extends('layouts.app')

@section('title', 'Pengaturan Dropdown | InvenTrack')

@php
    $typeMetadata = [
        'category' => [
            'title' => 'Kategori Produk',
            'singular' => 'Kategori',
            'group' => 'Inventori',
            'icon' => 'category',
            'desc' => 'Daftar klasifikasi kategori barang yang tersedia saat mendaftarkan produk baru di modul Inventori.',
            'placeholder' => 'Contoh: Liquid Saltnic, Pods, Device, Coil, Cartridge...',
            'badge_bg' => 'bg-emerald-50 text-[#006c49] border-emerald-200',
        ],
        'variant' => [
            'title' => 'Varian Produk',
            'singular' => 'Varian',
            'group' => 'Inventori',
            'icon' => 'tune',
            'desc' => 'Daftar pilihan spesifikasi varian produk seperti rasa, ukuran kemasan, kadar nikotin, atau warna barang.',
            'placeholder' => 'Contoh: 15ml, 30ml, Merah, Biru, 3mg, 6mg, 0.6 Ohm...',
            'badge_bg' => 'bg-teal-50 text-teal-700 border-teal-200',
        ],
        'position' => [
            'title' => 'Jabatan Karyawan',
            'singular' => 'Jabatan',
            'group' => 'Kepegawaian',
            'icon' => 'badge',
            'desc' => 'Daftar posisi jabatan struktural atau operasional untuk staf pengguna sistem di cabang toko.',
            'placeholder' => 'Contoh: Store Leader, Kasir, Inventory Staff, Barista...',
            'badge_bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        ],
        'status' => [
            'title' => 'Status Kepegawaian',
            'singular' => 'Status',
            'group' => 'Kepegawaian',
            'icon' => 'how_to_reg',
            'desc' => 'Daftar status ikatan kerja karyawan seperti karyawan tetap, kontrak, magang, atau masa percobaan.',
            'placeholder' => 'Contoh: Karyawan Tetap, Kontrak, Magang, Probation...',
            'badge_bg' => 'bg-amber-50 text-amber-700 border-amber-200',
        ],
        'supplier' => [
            'title' => 'Supplier / Distributor',
            'singular' => 'Supplier',
            'group' => 'Pengadaan',
            'icon' => 'local_shipping',
            'desc' => 'Daftar vendor rekanan, distributor, dan supplier pengadaan barang masuk (Stock In) serta pencatatan hutang dagang.',
            'placeholder' => 'Contoh: PT Vape Distributor, CV Sumber Makmur, Vape Indo Utama...',
            'badge_bg' => 'bg-sky-50 text-sky-700 border-sky-200',
        ],
    ];

    $currentMeta = $typeMetadata[$type] ?? $typeMetadata['category'];
@endphp

@section('content')
<div class="flex flex-col gap-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Pengaturan Opsi Master Data</h1>
            <p class="text-on-surface-variant text-body-lg">Kelola data referensi dropdown sistem untuk kategori produk, varian inventori, supplier / distributor, jabatan, dan status karyawan.</p>
        </div>
    </div>

    <!-- Error Alert Banner -->
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

    <!-- KPI Summary Cards (5 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- Kategori Produk -->
        <a href="{{ route('settings.index', ['type' => 'category']) }}" 
           class="p-5 bg-white border {{ $type === 'category' ? 'border-[#006c49] ring-2 ring-[#006c49]/15' : 'border-outline-variant' }} rounded-2xl shadow-sm hover:shadow-md transition-all duration-200 flex items-center gap-4 group cursor-pointer">
            <div class="w-12 h-12 rounded-xl {{ $type === 'category' ? 'bg-[#006c49] text-white' : 'bg-[#eff4ff] text-[#006c49]' }} flex items-center justify-center shrink-0 transition-colors">
                <span class="material-symbols-outlined text-2xl">category</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Kategori Produk</p>
                <h3 class="text-2xl font-extrabold text-on-surface mt-0.5">{{ $counts['category'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-on-surface-variant">Klasifikasi barang</span>
            </div>
        </a>

        <!-- Varian Produk -->
        <a href="{{ route('settings.index', ['type' => 'variant']) }}" 
           class="p-5 bg-white border {{ $type === 'variant' ? 'border-[#006c49] ring-2 ring-[#006c49]/15' : 'border-outline-variant' }} rounded-2xl shadow-sm hover:shadow-md transition-all duration-200 flex items-center gap-4 group cursor-pointer">
            <div class="w-12 h-12 rounded-xl {{ $type === 'variant' ? 'bg-[#006c49] text-white' : 'bg-teal-50 text-teal-700' }} flex items-center justify-center shrink-0 transition-colors">
                <span class="material-symbols-outlined text-2xl">tune</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Varian Produk</p>
                <h3 class="text-2xl font-extrabold text-on-surface mt-0.5">{{ $counts['variant'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-on-surface-variant">Rasa, warna & size</span>
            </div>
        </a>

        <!-- Supplier / Distributor -->
        <a href="{{ route('settings.index', ['type' => 'supplier']) }}" 
           class="p-5 bg-white border {{ $type === 'supplier' ? 'border-[#006c49] ring-2 ring-[#006c49]/15' : 'border-outline-variant' }} rounded-2xl shadow-sm hover:shadow-md transition-all duration-200 flex items-center gap-4 group cursor-pointer">
            <div class="w-12 h-12 rounded-xl {{ $type === 'supplier' ? 'bg-[#006c49] text-white' : 'bg-sky-50 text-sky-700' }} flex items-center justify-center shrink-0 transition-colors">
                <span class="material-symbols-outlined text-2xl">local_shipping</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Supplier</p>
                <h3 class="text-2xl font-extrabold text-on-surface mt-0.5">{{ $counts['supplier'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-on-surface-variant">Vendor pengadaan</span>
            </div>
        </a>

        <!-- Jabatan Karyawan -->
        <a href="{{ route('settings.index', ['type' => 'position']) }}" 
           class="p-5 bg-white border {{ $type === 'position' ? 'border-[#006c49] ring-2 ring-[#006c49]/15' : 'border-outline-variant' }} rounded-2xl shadow-sm hover:shadow-md transition-all duration-200 flex items-center gap-4 group cursor-pointer">
            <div class="w-12 h-12 rounded-xl {{ $type === 'position' ? 'bg-[#006c49] text-white' : 'bg-indigo-50 text-indigo-700' }} flex items-center justify-center shrink-0 transition-colors">
                <span class="material-symbols-outlined text-2xl">badge</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Jabatan Staf</p>
                <h3 class="text-2xl font-extrabold text-on-surface mt-0.5">{{ $counts['position'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-on-surface-variant">Posisi operasional</span>
            </div>
        </a>

        <!-- Status Kepegawaian -->
        <a href="{{ route('settings.index', ['type' => 'status']) }}" 
           class="p-5 bg-white border {{ $type === 'status' ? 'border-[#006c49] ring-2 ring-[#006c49]/15' : 'border-outline-variant' }} rounded-2xl shadow-sm hover:shadow-md transition-all duration-200 flex items-center gap-4 group cursor-pointer">
            <div class="w-12 h-12 rounded-xl {{ $type === 'status' ? 'bg-[#006c49] text-white' : 'bg-amber-50 text-amber-700' }} flex items-center justify-center shrink-0 transition-colors">
                <span class="material-symbols-outlined text-2xl">how_to_reg</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Status Staf</p>
                <h3 class="text-2xl font-extrabold text-on-surface mt-0.5">{{ $counts['status'] ?? 0 }}</h3>
                <span class="text-[11px] font-medium text-on-surface-variant">Ikatan kerja aktif</span>
            </div>
        </a>
    </div>

    <!-- Main Workspace Layout: Sidebar Tabs + Content Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Sidebar: Settings Navigation Menu -->
        <div class="lg:col-span-4 flex flex-col gap-4">
            <div class="bg-white rounded-2xl border border-outline-variant shadow-sm p-5 flex flex-col gap-5">
                <div class="flex items-center gap-2.5 pb-3 border-b border-outline-variant">
                    <div class="w-8 h-8 rounded-lg bg-[#eff4ff] text-[#006c49] flex items-center justify-center font-bold shrink-0">
                        <span class="material-symbols-outlined text-lg">tune</span>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-on-surface">Tipe Opsi Dropdown</h2>
                        <p class="text-[11px] text-on-surface-variant font-medium">Pilih modul master data</p>
                    </div>
                </div>

                <!-- Group 1: Modul Inventori -->
                <div class="flex flex-col gap-2">
                    <p class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider px-1">Modul Inventori & Produk</p>
                    
                    <!-- Category Link -->
                    <a href="{{ route('settings.index', ['type' => 'category']) }}" 
                       class="flex items-center justify-between p-3 rounded-xl transition-all duration-150 {{ $type === 'category' ? 'bg-[#006c49] text-white shadow-sm font-bold' : 'bg-surface-container-low/50 hover:bg-surface-container text-on-surface border border-outline-variant/60 font-medium' }}">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-xl {{ $type === 'category' ? 'text-white' : 'text-[#006c49]' }}">category</span>
                            <div class="text-left">
                                <p class="text-xs font-bold leading-tight">Kategori Produk</p>
                                <p class="text-[10px] {{ $type === 'category' ? 'text-white/80' : 'text-on-surface-variant' }} font-normal">Klasifikasi barang</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $type === 'category' ? 'bg-white/20 text-white' : 'bg-surface-container-high text-on-surface' }}">
                            {{ $counts['category'] ?? 0 }}
                        </span>
                    </a>

                    <!-- Variant Link -->
                    <a href="{{ route('settings.index', ['type' => 'variant']) }}" 
                       class="flex items-center justify-between p-3 rounded-xl transition-all duration-150 {{ $type === 'variant' ? 'bg-[#006c49] text-white shadow-sm font-bold' : 'bg-surface-container-low/50 hover:bg-surface-container text-on-surface border border-outline-variant/60 font-medium' }}">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-xl {{ $type === 'variant' ? 'text-white' : 'text-teal-700' }}">tune</span>
                            <div class="text-left">
                                <p class="text-xs font-bold leading-tight">Varian Produk</p>
                                <p class="text-[10px] {{ $type === 'variant' ? 'text-white/80' : 'text-on-surface-variant' }} font-normal">Rasa, ukuran & warna</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $type === 'variant' ? 'bg-white/20 text-white' : 'bg-surface-container-high text-on-surface' }}">
                            {{ $counts['variant'] ?? 0 }}
                        </span>
                    </a>
                </div>

                <!-- Group 2: Modul Pengadaan & Supplier -->
                <div class="flex flex-col gap-2 pt-2 border-t border-outline-variant/60">
                    <p class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider px-1">Modul Pengadaan & Supplier</p>
                    
                    <!-- Supplier Link -->
                    <a href="{{ route('settings.index', ['type' => 'supplier']) }}" 
                       class="flex items-center justify-between p-3 rounded-xl transition-all duration-150 {{ $type === 'supplier' ? 'bg-[#006c49] text-white shadow-sm font-bold' : 'bg-surface-container-low/50 hover:bg-surface-container text-on-surface border border-outline-variant/60 font-medium' }}">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-xl {{ $type === 'supplier' ? 'text-white' : 'text-sky-700' }}">local_shipping</span>
                            <div class="text-left">
                                <p class="text-xs font-bold leading-tight">Supplier / Distributor</p>
                                <p class="text-[10px] {{ $type === 'supplier' ? 'text-white/80' : 'text-on-surface-variant' }} font-normal">Vendor rekanan pengadaan</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $type === 'supplier' ? 'bg-white/20 text-white' : 'bg-surface-container-high text-on-surface' }}">
                            {{ $counts['supplier'] ?? 0 }}
                        </span>
                    </a>
                </div>

                <!-- Group 3: Modul Pengguna & Staf -->
                <div class="flex flex-col gap-2 pt-2 border-t border-outline-variant/60">
                    <p class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider px-1">Modul Pengguna & Staf</p>
                    
                    <!-- Position Link -->
                    <a href="{{ route('settings.index', ['type' => 'position']) }}" 
                       class="flex items-center justify-between p-3 rounded-xl transition-all duration-150 {{ $type === 'position' ? 'bg-[#006c49] text-white shadow-sm font-bold' : 'bg-surface-container-low/50 hover:bg-surface-container text-on-surface border border-outline-variant/60 font-medium' }}">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-xl {{ $type === 'position' ? 'text-white' : 'text-indigo-700' }}">badge</span>
                            <div class="text-left">
                                <p class="text-xs font-bold leading-tight">Jabatan Staf</p>
                                <p class="text-[10px] {{ $type === 'position' ? 'text-white/80' : 'text-on-surface-variant' }} font-normal">Struktur posisi peran</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $type === 'position' ? 'bg-white/20 text-white' : 'bg-surface-container-high text-on-surface' }}">
                            {{ $counts['position'] ?? 0 }}
                        </span>
                    </a>

                    <!-- Status Link -->
                    <a href="{{ route('settings.index', ['type' => 'status']) }}" 
                       class="flex items-center justify-between p-3 rounded-xl transition-all duration-150 {{ $type === 'status' ? 'bg-[#006c49] text-white shadow-sm font-bold' : 'bg-surface-container-low/50 hover:bg-surface-container text-on-surface border border-outline-variant/60 font-medium' }}">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-xl {{ $type === 'status' ? 'text-white' : 'text-amber-700' }}">how_to_reg</span>
                            <div class="text-left">
                                <p class="text-xs font-bold leading-tight">Status Kepegawaian</p>
                                <p class="text-[10px] {{ $type === 'status' ? 'text-white/80' : 'text-on-surface-variant' }} font-normal">Ikatan kontrak & kerja</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $type === 'status' ? 'bg-white/20 text-white' : 'bg-surface-container-high text-on-surface' }}">
                            {{ $counts['status'] ?? 0 }}
                        </span>
                    </a>
                </div>

                <!-- Info Card -->
                <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl p-3.5 flex items-start gap-2.5">
                    <span class="material-symbols-outlined text-base text-[#006c49] shrink-0 mt-0.5">tips_and_updates</span>
                    <p class="text-[11px] text-on-surface-variant leading-relaxed">
                        Opsi dropdown yang dikelola di sini langsung terhubung dan otomatis muncul pada pilihan input modul Inventori, Stock In, & Pengguna sistem.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Content Panel: Manage Selected Setting Type -->
        <div class="lg:col-span-8 flex flex-col gap-6">
            <div class="bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden flex flex-col">
                
                <!-- Panel Header -->
                <div class="p-5 sm:p-6 border-b border-outline-variant flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#eff4ff] text-[#006c49] border border-[#006c49]/20 flex items-center justify-center font-bold shrink-0">
                            <span class="material-symbols-outlined text-xl">{{ $currentMeta['icon'] }}</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold text-on-surface tracking-tight">{{ $currentMeta['title'] }}</h2>
                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full border {{ $currentMeta['badge_bg'] }}">
                                    {{ $currentMeta['group'] }}
                                </span>
                            </div>
                            <p class="text-xs text-on-surface-variant mt-0.5 font-medium">{{ $currentMeta['desc'] }}</p>
                        </div>
                    </div>
                    <div class="shrink-0 self-stretch sm:self-auto flex items-center justify-between sm:justify-end gap-2">
                        <span class="text-xs font-semibold px-3 py-1 rounded-full bg-surface-container text-on-surface-variant border border-outline-variant/60">
                            Total: <strong class="text-on-surface">{{ count($settings) }} Opsi</strong>
                        </span>
                    </div>
                </div>

                <!-- Add New Option Input Form -->
                @can('Setting Create')
                <div class="p-5 sm:p-6 border-b border-outline-variant bg-[#f8fafc]/60">
                    <form action="{{ route('settings.store') }}" method="POST" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        
                        <div class="flex-1">
                            <label for="new_setting_value" class="block text-xs font-bold text-on-surface mb-1.5">
                                Tambah {{ $currentMeta['singular'] }} Baru <span class="text-error">*</span>
                            </label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-base">add_circle</span>
                                <input 
                                    type="text" 
                                    id="new_setting_value"
                                    name="value" 
                                    required 
                                    value="{{ old('type') === $type ? old('value') : '' }}"
                                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-outline-variant rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-[#006c49] focus:ring-2 focus:ring-[#006c49]/20 transition-all" 
                                    placeholder="{{ $currentMeta['placeholder'] }}"
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        <button 
                            type="submit" 
                            class="px-5 py-2.5 bg-[#006c49] text-white hover:bg-[#00553a] font-bold rounded-xl active:scale-95 duration-150 transition-all shadow-sm hover:shadow flex items-center justify-center gap-1.5 text-xs sm:text-sm shrink-0 cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-base">add</span>
                            <span>Tambah Opsi</span>
                        </button>
                    </form>
                </div>
                @endcan

                <!-- Search & Quick Filter Toolbar -->
                <div class="p-4 sm:p-5 border-b border-outline-variant flex flex-col sm:flex-row justify-between items-center gap-3 bg-white">
                    <div class="relative w-full sm:w-72">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-base">search</span>
                        <input 
                            type="text" 
                            id="setting-filter-input"
                            oninput="filterSettingOptions(this.value)"
                            placeholder="Cari opsi {{ strtolower($currentMeta['singular']) }}..." 
                            class="w-full pl-9 pr-3 py-1.5 bg-surface-container-low/60 border border-outline-variant/60 rounded-xl text-xs text-on-surface placeholder:text-outline focus:outline-none focus:border-[#006c49] focus:bg-white transition-all"
                        >
                    </div>
                    <div class="text-[11px] text-on-surface-variant self-end sm:self-auto font-medium">
                        Menampilkan <span id="filtered-count" class="font-bold text-on-surface">{{ count($settings) }}</span> dari {{ count($settings) }} opsi
                    </div>
                </div>

                <!-- Options Table List -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-outline-variant bg-surface-container/40">
                                <th class="py-3 px-4 sm:px-6 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider w-14">#</th>
                                <th class="py-3 px-4 sm:px-6 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Nama Opsi / Nilai Dropdown</th>
                                <th class="py-3 px-4 sm:px-6 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider hidden md:table-cell">Modul</th>
                                <th class="py-3 px-4 sm:px-6 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider hidden sm:table-cell">Ditambahkan</th>
                                <th class="py-3 px-4 sm:px-6 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider text-right w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/60" id="settings-table-body">
                            @forelse($settings as $index => $setting)
                                <tr class="setting-row hover:bg-surface-container-lowest/70 transition-colors group" data-name="{{ strtolower($setting->value) }}">
                                    <td class="py-3.5 px-4 sm:px-6 text-xs text-on-surface-variant font-medium">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="py-3.5 px-4 sm:px-6">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-surface-container flex items-center justify-center text-on-surface-variant group-hover:text-[#006c49] group-hover:bg-[#eff4ff] transition-colors shrink-0">
                                                <span class="material-symbols-outlined text-sm">label</span>
                                            </div>
                                            <span class="text-xs sm:text-sm font-bold text-on-surface group-hover:text-[#006c49] transition-colors setting-name-display">
                                                {{ $setting->value }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 sm:px-6 hidden md:table-cell">
                                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full border {{ $currentMeta['badge_bg'] }}">
                                            {{ $currentMeta['group'] }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 sm:px-6 text-xs text-on-surface-variant font-medium hidden sm:table-cell">
                                        {{ $setting->created_at ? $setting->created_at->format('d M Y') : '-' }}
                                    </td>
                                    <td class="py-3.5 px-4 sm:px-6 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @can('Setting Create')
                                            <!-- Edit Button -->
                                            <button 
                                                type="button" 
                                                onclick="openEditModal('{{ $setting->id }}', '{{ addslashes($setting->value) }}')"
                                                class="p-1.5 text-on-surface-variant hover:text-[#006c49] hover:bg-[#eff4ff] rounded-lg transition-colors cursor-pointer"
                                                title="Ubah opsi"
                                            >
                                                <span class="material-symbols-outlined text-base">edit</span>
                                            </button>
                                            @endcan

                                            @can('Setting Destroy')
                                            <!-- Delete Button -->
                                            <button 
                                                type="button" 
                                                onclick="openDeleteModal('{{ $setting->id }}', '{{ addslashes($setting->value) }}')"
                                                class="p-1.5 text-on-surface-variant hover:text-error hover:bg-error-container/20 rounded-lg transition-colors cursor-pointer"
                                                title="Hapus opsi"
                                            >
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr id="empty-state-row">
                                    <td colspan="5" class="py-12 px-6 text-center">
                                        <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                            <div class="w-12 h-12 rounded-2xl bg-surface-container flex items-center justify-center text-on-surface-variant mb-3">
                                                <span class="material-symbols-outlined text-2xl">inbox</span>
                                            </div>
                                            <h4 class="text-sm font-bold text-on-surface mb-1">Belum Ada Opsi {{ $currentMeta['singular'] }}</h4>
                                            <p class="text-xs text-on-surface-variant leading-relaxed">
                                                Gunakan form di atas untuk mendaftarkan opsi dropdown {{ strtolower($currentMeta['singular']) }} pertama Anda.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- No Search Results Row (Dynamic) -->
                <div id="no-search-results" class="hidden py-10 px-6 text-center border-t border-outline-variant/40">
                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                        <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-on-surface-variant mb-2">
                            <span class="material-symbols-outlined text-xl">search_off</span>
                        </div>
                        <h4 class="text-xs font-bold text-on-surface mb-0.5">Opsi Tidak Ditemukan</h4>
                        <p class="text-[11px] text-on-surface-variant">Tidak ada opsi dropdown yang cocok dengan kata kunci pencarian Anda.</p>
                    </div>
                </div>

            </div>
        </div>

    </div>

</div>

<!-- Modal 1: Edit Setting Dialog -->
@can('Setting Create')
<dialog id="edit-setting-modal" class="backdrop:bg-black/40 backdrop:backdrop-blur-xs p-0 rounded-2xl border border-outline-variant shadow-2xl max-w-md w-full bg-white overflow-hidden m-auto">
    <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant bg-[#f8fafc]">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-[#eff4ff] text-[#006c49] border border-[#006c49]/20 flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-lg">edit_note</span>
            </div>
            <div>
                <h3 class="font-extrabold text-on-surface text-sm">Ubah Opsi {{ $currentMeta['singular'] }}</h3>
                <p class="text-[11px] text-on-surface-variant font-medium">Perbarui nilai nama dropdown</p>
            </div>
        </div>
        <button type="button" onclick="document.getElementById('edit-setting-modal').close()" class="p-1.5 hover:bg-surface-container-high rounded-xl transition text-on-surface-variant hover:text-black cursor-pointer">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
    </div>

    <form id="edit-setting-form" method="POST" action="" class="p-5 flex flex-col gap-4">
        @csrf
        @method('PUT')

        <div>
            <label for="edit_setting_value" class="block text-xs font-bold text-on-surface mb-1.5">
                Nama Opsi <span class="text-error">*</span>
            </label>
            <input 
                type="text" 
                id="edit_setting_value" 
                name="value" 
                required 
                class="w-full px-3.5 py-2.5 bg-surface-container-low/50 border border-outline-variant rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-[#006c49] focus:ring-2 focus:ring-[#006c49]/20 transition-all font-medium"
                placeholder="Masukkan nama opsi..."
                autocomplete="off"
            >
            <p class="text-[10px] text-on-surface-variant mt-1">Perubahan akan langsung berlaku pada data terkait.</p>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-outline-variant">
            <button 
                type="button" 
                onclick="document.getElementById('edit-setting-modal').close()" 
                class="px-4 py-2 bg-white border border-outline-variant hover:bg-surface-container text-on-surface font-bold rounded-xl text-xs transition active:scale-95 cursor-pointer"
            >
                Batal
            </button>
            <button 
                type="submit" 
                class="px-4 py-2 bg-[#006c49] hover:bg-[#00553a] text-white font-bold rounded-xl text-xs transition active:scale-95 shadow-sm cursor-pointer flex items-center gap-1.5"
            >
                <span class="material-symbols-outlined text-sm">save</span>
                <span>Simpan Perubahan</span>
            </button>
        </div>
    </form>
</dialog>
@endcan

<!-- Modal 2: Delete Setting Confirmation Dialog -->
@can('Setting Destroy')
<dialog id="delete-setting-modal" class="backdrop:bg-black/40 backdrop:backdrop-blur-xs p-0 rounded-2xl border border-outline-variant shadow-2xl max-w-sm w-full bg-white overflow-hidden m-auto">
    <div class="p-5 flex flex-col items-center text-center gap-3">
        <div class="w-12 h-12 rounded-2xl bg-error-container/40 text-error border border-error/20 flex items-center justify-center">
            <span class="material-symbols-outlined text-2xl">delete_forever</span>
        </div>
        <div>
            <h3 class="font-extrabold text-on-surface text-base">Hapus Opsi Dropdown?</h3>
            <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">
                Apakah Anda yakin ingin menghapus opsi <strong id="delete-setting-target" class="text-on-surface"></strong>? Opsi ini tidak akan lagi dapat dipilih pada form baru.
            </p>
        </div>

        <form id="delete-setting-form" method="POST" action="" class="w-full flex items-center justify-center gap-2 pt-2">
            @csrf
            @method('DELETE')
            <button 
                type="button" 
                onclick="document.getElementById('delete-setting-modal').close()" 
                class="w-1/2 px-4 py-2 bg-white border border-outline-variant hover:bg-surface-container text-on-surface font-bold rounded-xl text-xs transition active:scale-95 cursor-pointer"
            >
                Batal
            </button>
            <button 
                type="submit" 
                class="w-1/2 px-4 py-2 bg-error hover:bg-red-700 text-white font-bold rounded-xl text-xs transition active:scale-95 shadow-sm cursor-pointer"
            >
                Ya, Hapus
            </button>
        </form>
    </div>
</dialog>
@endcan

<script>
    // Live filter options table
    function filterSettingOptions(query) {
        const q = (query || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.setting-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            if (name.includes(q)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update count indicator
        const counter = document.getElementById('filtered-count');
        if (counter) {
            counter.innerText = visibleCount;
        }

        // Show/hide no results row
        const noResults = document.getElementById('no-search-results');
        if (noResults) {
            if (visibleCount === 0 && rows.length > 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }
    }

    // Open Edit Modal
    function openEditModal(id, value) {
        const modal = document.getElementById('edit-setting-modal');
        const form = document.getElementById('edit-setting-form');
        const input = document.getElementById('edit_setting_value');
        if (!modal || !form || !input) return;

        form.action = `/settings/${id}`;
        input.value = value;
        modal.showModal();
        setTimeout(() => input.focus(), 50);
    }

    // Open Delete Modal
    function openDeleteModal(id, value) {
        const modal = document.getElementById('delete-setting-modal');
        const form = document.getElementById('delete-setting-form');
        const targetText = document.getElementById('delete-setting-target');
        if (!modal || !form) return;

        form.action = `/settings/${id}`;
        if (targetText) {
            targetText.innerText = `"${value}"`;
        }
        modal.showModal();
    }
</script>
@endsection
