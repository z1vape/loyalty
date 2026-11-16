@extends('layouts.app')

@section('title', 'Peran & Hak Akses | InvenTrack')

@section('content')
<div class="flex flex-col gap-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Data Peran & Hak Akses</h1>
            <p class="text-on-surface-variant text-body-lg">Kelola kelompok pengguna, konfigurasi hak akses modul sistem (Permission), dan otorisasi staf.</p>
        </div>
    </div>

    <!-- 1. Top Section: Roles Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($roles as $role)
            <div class="bg-white border border-outline-variant rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between min-h-[170px] hover:shadow-md transition-all duration-300 group">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-[#eff4ff] text-[#006c49] border border-[#006c49]/20 flex items-center justify-center font-bold shrink-0">
                                <span class="material-symbols-outlined text-xl">shield_person</span>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-on-surface capitalize tracking-tight">{{ $role->name }}</h3>
                                <p class="text-[11px] text-on-surface-variant truncate max-w-[180px]">{{ $role->description ?: 'Kelompok akses sistem' }}</p>
                            </div>
                        </div>
                        @if(in_array($role->name, ['Super Admin', 'Manager', 'Staff']))
                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant border border-outline-variant/60">System</span>
                        @else
                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-emerald-50 text-[#006c49] border border-emerald-200">Custom</span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-between mt-5 pt-3.5 border-t border-outline-variant/50">
                    <!-- Overlapping Avatar stack & User count -->
                    <div class="flex items-center gap-2">
                        <div class="flex -space-x-2 overflow-hidden">
                            @forelse($role->users->take(3) as $u)
                                <div class="inline-block h-7 w-7 rounded-full ring-2 ring-white bg-[#eff4ff] text-[#006c49] border border-[#006c49]/20 flex items-center justify-center text-[10px] font-extrabold uppercase" title="{{ $u->name }}">
                                    {{ substr($u->name, 0, 2) }}
                                </div>
                            @empty
                                <span class="text-[11px] italic text-on-surface-variant font-medium">0 Pengguna</span>
                            @endforelse
                            @if($role->users->count() > 3)
                                <div class="inline-block h-7 w-7 rounded-full ring-2 ring-white bg-surface-container border border-outline-variant/60 flex items-center justify-center text-[10px] font-bold text-on-surface">
                                    +{{ $role->users->count() - 3 }}
                                </div>
                            @endif
                        </div>
                        @if($role->users->count() > 0)
                            <span class="text-xs font-semibold text-on-surface-variant">{{ $role->users->count() }} staf</span>
                        @endif
                    </div>

                    <!-- Permission Modal Trigger -->
                    @can('Role Update')
                    <button 
                        type="button"
                        onclick="openPermissionsModal({{ $role->id }})" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#eff4ff] text-[#006c49] hover:bg-[#006c49] hover:text-white font-bold text-xs transition-all active:scale-95 border border-[#006c49]/20 shadow-2xs cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-sm">tune</span>
                        <span>Atur Akses</span>
                    </button>
                    @endcan
                </div>
            </div>

            <!-- Set Role Permission Modal (Interactive HTML5 Dialog) -->
            <dialog id="permissions-modal-{{ $role->id }}" class="permissions-dialog-box backdrop:bg-black/40 backdrop:backdrop-blur-xs p-0 rounded-2xl border border-outline-variant shadow-2xl max-w-4xl w-full bg-white overflow-hidden" data-role-id="{{ $role->id }}">
                <!-- Header -->
                <div class="p-5 sm:p-6 bg-surface-container-low border-b border-outline-variant text-on-surface flex justify-between items-center relative">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-[#eff4ff] border border-[#006c49]/20 flex items-center justify-center text-[#006c49] shrink-0 shadow-sm">
                            <span class="material-symbols-outlined text-2xl font-bold">shield</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-extrabold text-lg text-on-surface">Atur Hak Akses Peran</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-white text-[#006c49] border border-[#006c49]/30">
                                    {{ $role->name }}
                                </span>
                            </div>
                            <p class="text-xs text-on-surface-variant mt-0.5">Tentukan izin dan batas akses modul untuk peran ini dalam sistem.</p>
                        </div>
                    </div>
                    <button type="button" onclick="closePermissionsModal({{ $role->id }})" class="p-2 text-on-surface-variant hover:text-on-surface hover:bg-surface-container rounded-xl transition cursor-pointer" title="Tutup">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>

                <form id="permission-form-{{ $role->id }}" action="{{ route('roles.permissions.update', $role) }}" method="POST" class="flex flex-col h-[560px] max-h-[82vh]">
                    @csrf
                    @method('PUT')

                    <!-- Subheader: Search bar & Quick Select All / Deselect All -->
                    <div class="px-5 py-3 bg-surface-container-lowest border-b border-outline-variant/60 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                        <div class="relative flex-1">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-base">search</span>
                            <input 
                                type="text" 
                                id="perm-search-{{ $role->id }}" 
                                oninput="filterPermissions({{ $role->id }}, this.value)" 
                                placeholder="Cari hak akses permission (contoh: list, create, edit, destroy)..." 
                                class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-outline-variant bg-surface-container-low/40 text-xs text-on-surface placeholder:text-on-surface-variant/70 focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] transition"
                            />
                        </div>

                        @if($role->name !== 'Super Admin')
                        <div class="flex items-center gap-2 shrink-0">
                            <button 
                                type="button" 
                                onclick="toggleAllModalPermissions({{ $role->id }})" 
                                id="btn-toggle-all-{{ $role->id }}"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-on-surface hover:bg-surface-container-high rounded-xl transition border border-outline-variant/60 bg-white shadow-2xs cursor-pointer active:scale-95"
                            >
                                <span class="material-symbols-outlined text-sm text-[#006c49]" id="toggle-icon-{{ $role->id }}">select_all</span>
                                <span id="toggle-text-{{ $role->id }}">Pilih Semua</span>
                            </button>
                        </div>
                        @endif
                    </div>

                    <div class="flex flex-1 min-h-0">
                        <!-- Sidebar on the Left -->
                        <div class="w-56 sm:w-60 bg-surface-container-low/60 border-r border-outline-variant/60 flex flex-col p-3 shrink-0 overflow-y-auto no-scrollbar">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant px-2 pb-2">Modul Sistem</span>
                            
                            <!-- Sidebar Categories Tabs -->
                            <div class="flex flex-col gap-1 flex-1">
                                @php
                                    $categoriesMetadata = [
                                        'Commercial' => ['icon' => 'badge', 'label' => 'Commercial', 'slug' => 'Commercial', 'desc' => 'Daftar kehadiran & absensi serta modul payroll penggajian karyawan'],
                                        'Operation' => ['icon' => 'precision_manufacturing', 'label' => 'Operation', 'slug' => 'Operation', 'desc' => 'Katalog inventaris produk, stok masuk, stok keluar, sales records, dan mutasi'],
                                        'Purchasing' => ['icon' => 'shopping_bag', 'label' => 'Purchasing', 'slug' => 'Purchasing', 'desc' => 'Pengadaan barang, purchase order, dan pelunasan hutang supplier'],
                                        'Finance' => ['icon' => 'account_balance_wallet', 'label' => 'Finance', 'slug' => 'Finance', 'desc' => 'Pencatatan pemasukan non-penjualan dan pengeluaran operasional toko'],
                                        'Accounting' => ['icon' => 'account_balance', 'label' => 'Accounting', 'slug' => 'Accounting', 'desc' => 'Bagan akun (CoA), jurnal transaksi, buku besar, laporan keuangan, dan tutup buku'],
                                        'Reports' => ['icon' => 'analytics', 'label' => 'Reports', 'slug' => 'Reports', 'desc' => 'Laporan penjualan by SKU, kategori produk, dan performa staf kasir'],
                                        'Master Data' => ['icon' => 'dataset', 'label' => 'Master Data', 'slug' => 'Master-Data', 'desc' => 'Master cabang toko, shift kerja, status absensi, roster jadwal, pola shift, dan opsi sistem'],
                                        'Z1 APP' => ['icon' => 'smartphone', 'label' => 'Z1 APP', 'slug' => 'Z1-APP', 'desc' => 'Akses portal mobile staf untuk clock-in/out, pengajuan PR, dan laporan sales'],
                                        'Z1 Admin' => ['icon' => 'admin_panel_settings', 'label' => 'Z1 Admin', 'slug' => 'Z1-Admin', 'desc' => 'Dashboard analitik, manajemen pengguna, peran (role), dan hak akses permission'],
                                    ];

                                    // Dynamic fallback for any category from DB not in metadata
                                    foreach ($permissions->pluck('category')->unique() as $cat) {
                                        if (!empty($cat) && !isset($categoriesMetadata[$cat])) {
                                            $categoriesMetadata[$cat] = [
                                                'icon' => 'folder',
                                                'label' => $cat,
                                                'slug' => \Illuminate\Support\Str::slug($cat),
                                                'desc' => "Kelola hak akses modul {$cat}"
                                            ];
                                        }
                                    }
                                @endphp
                                @foreach($categoriesMetadata as $catKey => $meta)
                                    @php
                                        $catTotal = $permissions->where('category', $catKey)->count();
                                    @endphp
                                    <button 
                                        type="button"
                                        onclick="showTabContent({{ $role->id }}, '{{ $meta['slug'] }}')"
                                        id="tab-btn-{{ $role->id }}-{{ $meta['slug'] }}"
                                        class="tab-button flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 text-left cursor-pointer"
                                        data-role-id="{{ $role->id }}"
                                        data-tab-key="{{ $meta['slug'] }}"
                                    >
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="material-symbols-outlined text-base font-normal shrink-0">{{ $meta['icon'] }}</span>
                                            <span class="truncate">{{ $meta['label'] }}</span>
                                        </div>
                                        <span 
                                            id="badge-{{ $role->id }}-{{ $meta['slug'] }}" 
                                            class="tab-count-badge text-[10px] px-2 py-0.5 rounded-full font-extrabold bg-[#eff4ff] text-[#006c49] shrink-0 border border-[#006c49]/20"
                                            data-total="{{ $catTotal }}"
                                        >
                                            0/{{ $catTotal }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Content on the Right -->
                        <div class="flex-1 overflow-y-auto p-5 sm:p-6 no-scrollbar bg-white" id="perms-scroll-container-{{ $role->id }}">
                            @if($role->name === 'Super Admin')
                                <div class="h-full flex items-center justify-center py-10">
                                    <div class="p-6 bg-emerald-50 text-[#006c49] border border-emerald-200 rounded-2xl flex flex-col items-center text-center gap-3 max-w-md shadow-sm">
                                        <div class="w-12 h-12 rounded-full bg-[#006c49] text-white flex items-center justify-center">
                                            <span class="material-symbols-outlined text-2xl font-bold">verified_user</span>
                                        </div>
                                        <div>
                                            <h4 class="font-extrabold text-base text-on-surface">Super Admin Bypass</h4>
                                            <p class="text-xs font-medium text-on-surface-variant leading-relaxed mt-1">
                                                Peran Super Admin memiliki akses penuh tanpa batas ke semua modul sistem secara otomatis. Tidak diperlukan konfigurasi izin khusus untuk peran ini.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                @foreach($categoriesMetadata as $catKey => $meta)
                                    @php
                                        // Retrieve group mapping for this category
                                        $groupedPerms = $permissions->where('category', $catKey)->groupBy('group_name');
                                    @endphp
                                    <div 
                                        id="tab-content-{{ $role->id }}-{{ $meta['slug'] }}" 
                                        class="tab-content hidden space-y-5"
                                    >
                                        <!-- Category Header Banner -->
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3.5 border-b border-outline-variant/60 gap-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-xl bg-[#eff4ff] text-[#006c49] border border-[#006c49]/20 flex items-center justify-center shrink-0">
                                                    <span class="material-symbols-outlined text-lg">{{ $meta['icon'] }}</span>
                                                </div>
                                                <div>
                                                    <h4 class="font-extrabold text-sm text-on-surface">{{ $meta['label'] }}</h4>
                                                    <p class="text-xs text-on-surface-variant font-medium">{{ $meta['desc'] }}</p>
                                                </div>
                                            </div>
                                            <!-- Select All For Category Checkbox -->
                                            <label class="flex items-center gap-2 text-xs font-bold text-on-surface cursor-pointer select-none bg-surface-container-low px-3 py-1.5 rounded-xl border border-outline-variant/60 self-start sm:self-auto hover:bg-surface-container transition">
                                                <input 
                                                    type="checkbox"
                                                    id="chk-category-all-{{ $role->id }}-{{ $meta['slug'] }}"
                                                    onclick="toggleCategoryPermissions({{ $role->id }}, '{{ $meta['slug'] }}', this.checked)"
                                                    class="h-4 w-4 rounded border-outline-variant text-[#006c49] focus:ring-secondary"
                                                />
                                                <span>Pilih Semua di Kategori Ini</span>
                                            </label>
                                        </div>

                                        <!-- Group Sub-panels -->
                                        <div class="space-y-4">
                                            @forelse($groupedPerms as $grpName => $perms)
                                                @php
                                                    $grpTotal = $perms->count();
                                                @endphp
                                                <div class="border border-outline-variant/60 rounded-xl overflow-hidden bg-surface-container-lowest shadow-2xs group-panel">
                                                    <!-- Group Header -->
                                                    <div class="px-4 py-2.5 bg-surface-container-low/70 border-b border-outline-variant/60 flex justify-between items-center">
                                                        <div class="flex items-center gap-2">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-[#006c49]"></span>
                                                            <span class="text-xs font-bold text-on-surface tracking-wider font-mono">{{ $grpName }}</span>
                                                        </div>
                                                        <label class="flex items-center gap-1.5 text-[11px] font-bold text-on-surface-variant hover:text-on-surface cursor-pointer select-none">
                                                            <input 
                                                                type="checkbox"
                                                                id="chk-group-all-{{ $role->id }}-{{ $meta['slug'] }}-{{ str_replace([' ', '&', '(', ')', '/'], '-', $grpName) }}"
                                                                onclick="toggleGroupPermissions({{ $role->id }}, '{{ $meta['slug'] }}', '{{ str_replace([' ', '&', '(', ')', '/'], '-', $grpName) }}', this.checked)"
                                                                class="h-3.5 w-3.5 rounded border-outline-variant text-[#006c49] focus:ring-secondary"
                                                            />
                                                            <span>Pilih Grup Ini</span>
                                                        </label>
                                                    </div>

                                                    <!-- Checkable cards grid -->
                                                    <div class="p-3.5 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                                        @foreach($perms as $permission)
                                                            @php
                                                                $isAssigned = $role->permissions->contains($permission->id);
                                                            @endphp
                                                            <label 
                                                                class="perm-card flex items-start gap-2.5 p-3 rounded-xl border transition-all cursor-pointer select-none {{ $isAssigned ? 'bg-[#eff4ff] border-[#006c49]/40 text-[#006c49]' : 'bg-white border-outline-variant/60 hover:bg-surface-container-lowest text-on-surface' }}"
                                                                id="lbl-permission-{{ $role->id }}-{{ $permission->id }}"
                                                            >
                                                                <input 
                                                                    type="checkbox" 
                                                                    name="permissions[]" 
                                                                    value="{{ $permission->id }}" 
                                                                    data-permission-id="{{ $permission->id }}"
                                                                    data-category="{{ $meta['slug'] }}"
                                                                    data-group="{{ str_replace([' ', '&', '(', ')', '/'], '-', $grpName) }}"
                                                                    data-name="{{ strtolower($permission->name) }}"
                                                                    data-desc="{{ strtolower($permission->description ?? '') }}"
                                                                    {{ $isAssigned ? 'checked' : '' }}
                                                                    class="permission-checkbox-node h-4 w-4 rounded border-outline-variant text-[#006c49] focus:ring-[#006c49]/20 mt-0.5"
                                                                    onchange="handlePermissionChange({{ $role->id }})"
                                                                />
                                                                <div class="flex-1 min-w-0">
                                                                    <div class="text-xs font-bold font-mono truncate leading-snug">{{ $permission->name }}</div>
                                                                    @if($permission->description)
                                                                        <p class="text-[11px] text-on-surface-variant line-clamp-1 mt-0.5 leading-normal font-sans">{{ $permission->description }}</p>
                                                                    @endif
                                                                </div>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="p-8 text-center text-on-surface-variant italic text-xs font-medium border border-dashed border-outline-variant rounded-xl">
                                                    — Tidak ada permission dalam kategori ini —
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <!-- Footer Details -->
                    <div class="p-4 sm:p-5 bg-surface-container-low border-t border-outline-variant flex flex-col sm:flex-row justify-between items-center gap-3 select-none">
                        <div class="flex items-center gap-2 self-start sm:self-auto">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#006c49]"></span>
                            <span id="footer-count-text-{{ $role->id }}" class="text-xs font-bold text-on-surface">
                                0 dari {{ $permissions->count() }} permission dipilih
                            </span>
                        </div>
                        
                        <div class="flex items-center gap-2.5 self-end sm:self-auto">
                            <button 
                                type="button" 
                                onclick="closePermissionsModal({{ $role->id }})" 
                                class="px-5 py-2.5 bg-white border border-outline-variant hover:bg-surface-container-high text-on-surface font-bold rounded-xl text-xs active:scale-95 duration-150 transition cursor-pointer"
                            >
                                Batal
                            </button>
                            @if($role->name !== 'Super Admin')
                                <button 
                                    type="submit" 
                                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#006c49] text-white hover:bg-[#00553a] font-bold rounded-xl text-xs active:scale-95 duration-150 transition shadow-sm hover:shadow cursor-pointer"
                                >
                                    <span class="material-symbols-outlined text-base">save</span>
                                    <span>Simpan Permission</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </form>
            </dialog>
        @endforeach
    </div>

    <!-- 2. Bottom Section: Datatable of Users -->
    <div class="bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden mt-2">
        <div class="p-5 sm:p-6 border-b border-outline-variant flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-lg font-bold text-on-surface tracking-tight">Daftar Pengguna & Hak Akses</h2>
                <p class="text-xs text-on-surface-variant mt-0.5 font-medium">Ringkasan akun staf terdaftar, alamat email, dan peran otorisasi aktif.</p>
            </div>
            <div class="flex items-center gap-2.5 self-stretch sm:self-auto">
                @can('Role Create')
                <button 
                    onclick="document.getElementById('create-role-modal').showModal()" 
                    class="flex items-center gap-1.5 px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface border border-outline-variant/60 text-xs font-bold rounded-xl active:scale-95 duration-150 transition shadow-2xs cursor-pointer"
                >
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    <span>Peran Baru</span>
                </button>
                @endcan
                @can('User Create')
                <a 
                    href="{{ route('users.create') }}" 
                    class="flex items-center gap-1.5 px-4 py-2 bg-[#006c49] text-white hover:bg-[#00553a] text-xs font-bold rounded-xl active:scale-95 duration-150 transition-all shadow-sm hover:shadow cursor-pointer"
                >
                    <span class="material-symbols-outlined text-sm">person_add</span>
                    <span>Pengguna Baru</span>
                </a>
                @endcan
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface border-b border-outline-variant font-bold text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 w-16">No.</th>
                        <th class="px-6 py-4 w-20">Foto</th>
                        <th class="px-6 py-4">Peran</th>
                        <th class="px-6 py-4">Nama Pengguna</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Jabatan</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant text-sm font-semibold">
                    @forelse($users as $index => $u)
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-6 py-4 text-on-surface-variant font-mono text-xs">
                                {{ $users->firstItem() ? ($users->firstItem() + $index) : ($index + 1) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="w-9 h-9 rounded-full overflow-hidden bg-surface-container text-on-surface font-extrabold flex items-center justify-center text-xs uppercase border border-outline-variant/50">
                                    @if($u->photo)
                                        <img src="{{ Storage::url($u->photo) }}" alt="{{ $u->name }}" class="w-full h-full object-cover" />
                                    @else
                                        <span>{{ substr($u->name, 0, 2) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $roleName = $u->roles->first()?->name ?? 'No Role';
                                    $badgeStyle = match($roleName) {
                                        'Super Admin' => 'bg-emerald-50 text-[#006c49] border-emerald-200',
                                        'Manager' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        default => 'bg-surface-container text-on-surface border-outline-variant/60'
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $badgeStyle }}">
                                    {{ $roleName }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-on-surface font-bold">
                                {{ $u->name }}
                            </td>
                            <td class="px-6 py-4 text-on-surface-variant font-mono text-xs">
                                {{ $u->email }}
                            </td>
                            <td class="px-6 py-4 text-on-surface-variant text-xs font-medium">
                                {{ $u->position ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a 
                                    href="{{ route('users.show', $u) }}" 
                                    class="p-1.5 hover:bg-surface-container-high rounded-xl text-on-surface-variant hover:text-black inline-flex transition-colors" 
                                    title="Detail Pengguna"
                                >
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                </a>
                                @can('User Edit')
                                <a 
                                    href="{{ route('users.edit', $u) }}" 
                                    class="p-1.5 hover:bg-surface-container-high rounded-xl text-on-surface-variant hover:text-[#006c49] inline-flex transition-colors" 
                                    title="Edit Pengguna"
                                >
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-on-surface-variant italic">
                                Belum ada data pengguna ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant bg-surface-container-lowest">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Create New Role -->
<dialog id="create-role-modal" class="backdrop:bg-black/40 backdrop:backdrop-blur-xs p-0 rounded-2xl border border-outline-variant shadow-2xl max-w-sm w-full bg-white overflow-hidden">
    <div class="p-5 border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-[#eff4ff] text-[#006c49] border border-[#006c49]/20 flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-lg">add_moderator</span>
            </div>
            <h3 class="font-extrabold text-on-surface text-base">Tambah Peran Baru</h3>
        </div>
        <button onclick="document.getElementById('create-role-modal').close()" class="p-1.5 hover:bg-surface-container-high rounded-xl transition text-on-surface-variant hover:text-black cursor-pointer">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
    </div>
    <form action="{{ route('roles.store') }}" method="POST" class="p-5 space-y-4">
        @csrf
        <div>
            <label for="new_role_name" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                Nama Peran (Role) <span class="text-error">*</span>
            </label>
            <input 
                type="text" 
                name="name" 
                id="new_role_name" 
                required 
                placeholder="Contoh: Kasir, Keuangan, dsb" 
                class="w-full px-3.5 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest text-sm focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] transition font-medium"
            />
        </div>
        <div>
            <label for="new_role_desc" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                Deskripsi Peran
            </label>
            <textarea 
                name="description" 
                id="new_role_desc" 
                rows="2"
                placeholder="Contoh: Mengelola transaksi kasir dan laporan harian" 
                class="w-full px-3.5 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest text-sm focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] transition font-medium resize-none"
            ></textarea>
        </div>
        <div class="flex items-center justify-end gap-2.5 border-t border-outline-variant pt-4 mt-5">
            <button type="button" onclick="document.getElementById('create-role-modal').close()" class="px-4 py-2 bg-white border border-outline-variant hover:bg-surface-container text-on-surface font-bold rounded-xl text-xs transition active:scale-95 cursor-pointer">
                Batal
            </button>
            <button type="submit" class="px-5 py-2 bg-[#006c49] text-white hover:bg-[#00553a] font-bold rounded-xl text-xs transition shadow-sm hover:shadow cursor-pointer active:scale-95">
                Simpan Peran
            </button>
        </div>
    </form>
</dialog>

<!-- Interactive Vanilla JS to coordinate dialog changes dynamically in-page -->
<script>
    // Helper to get all tab slugs inside a modal
    function getAllTabSlugs(modal) {
        const buttons = modal.querySelectorAll('.tab-button');
        const slugs = [];
        buttons.forEach(b => {
            const key = b.getAttribute('data-tab-key');
            if (key) slugs.push(key);
        });
        return slugs;
    }

    // Tab switching inside permissions modal
    function showTabContent(roleId, tabKey) {
        const modal = document.getElementById(`permissions-modal-${roleId}`);
        if (!modal) return;

        // Hide all contents for this role
        const contents = modal.querySelectorAll('.tab-content');
        contents.forEach(c => c.classList.add('hidden'));

        // Reset all tab button styles
        const buttons = modal.querySelectorAll('.tab-button');
        buttons.forEach(b => {
            b.classList.remove('bg-[#006c49]', 'text-white', 'shadow-xs');
            b.classList.add('text-on-surface', 'hover:bg-surface-container-high/60');
            
            const badge = b.querySelector('.tab-count-badge');
            if (badge) {
                badge.classList.remove('bg-white/20', 'text-white');
                badge.classList.add('bg-[#eff4ff]', 'text-[#006c49]');
            }
        });

        // Show active content
        const activeContent = modal.querySelector(`#tab-content-${roleId}-${tabKey}`);
        if (activeContent) activeContent.classList.remove('hidden');

        // Add active style to active button
        const activeBtn = modal.querySelector(`#tab-btn-${roleId}-${tabKey}`);
        if (activeBtn) {
            activeBtn.classList.remove('text-on-surface', 'hover:bg-surface-container-high/60');
            activeBtn.classList.add('bg-[#006c49]', 'text-white', 'shadow-xs');

            const badge = activeBtn.querySelector('.tab-count-badge');
            if (badge) {
                badge.classList.remove('bg-[#eff4ff]', 'text-[#006c49]');
                badge.classList.add('bg-white/20', 'text-white');
            }
        }
    }

    // Initialize counts and statuses when modal is opened
    function openPermissionsModal(roleId) {
        const modal = document.getElementById(`permissions-modal-${roleId}`);
        if (!modal) return;

        // Reset search input
        const searchInput = document.getElementById(`perm-search-${roleId}`);
        if (searchInput) {
            searchInput.value = '';
            filterPermissions(roleId, '');
        }

        // Show first tab by default
        const firstTabBtn = modal.querySelector('.tab-button');
        if (firstTabBtn) {
            const tabKey = firstTabBtn.getAttribute('data-tab-key');
            showTabContent(roleId, tabKey);
        }

        // Compute initially checked states and card styles
        handlePermissionChange(roleId);

        modal.showModal();
    }

    function closePermissionsModal(roleId) {
        const modal = document.getElementById(`permissions-modal-${roleId}`);
        if (modal) modal.close();
    }

    // Toggle all permissions in the whole modal
    function toggleAllModalPermissions(roleId) {
        const modal = document.getElementById(`permissions-modal-${roleId}`);
        if (!modal) return;

        const checkboxes = modal.querySelectorAll('.permission-checkbox-node');
        
        let checkedCount = 0;
        checkboxes.forEach(c => { if(c.checked) checkedCount++; });

        const shouldCheckAll = (checkedCount < checkboxes.length);

        checkboxes.forEach(c => {
            c.checked = shouldCheckAll;
        });

        handlePermissionChange(roleId);
    }

    // Toggle all permissions in a specific category/tab
    function toggleCategoryPermissions(roleId, tabKey, checked) {
        const modal = document.getElementById(`permissions-modal-${roleId}`);
        if (!modal) return;

        const checkboxes = modal.querySelectorAll(`.permission-checkbox-node[data-category="${tabKey}"]`);
        checkboxes.forEach(c => {
            c.checked = checked;
        });

        handlePermissionChange(roleId);
    }

    // Toggle all permissions in a specific subgroup inside a category
    function toggleGroupPermissions(roleId, tabKey, groupKey, checked) {
        const modal = document.getElementById(`permissions-modal-${roleId}`);
        if (!modal) return;

        const checkboxes = modal.querySelectorAll(`.permission-checkbox-node[data-category="${tabKey}"][data-group="${groupKey}"]`);
        checkboxes.forEach(c => {
            c.checked = checked;
        });

        handlePermissionChange(roleId);
    }

    // Recalculates counts, sidebar badges, and group check states dynamically
    function handlePermissionChange(roleId) {
        const modal = document.getElementById(`permissions-modal-${roleId}`);
        if (!modal) return;

        const checkboxes = modal.querySelectorAll('.permission-checkbox-node');
        let totalChecked = 0;
        
        checkboxes.forEach(c => { 
            if(c.checked) totalChecked++; 

            // Update card styling
            const card = c.closest('.perm-card');
            if (card) {
                if (c.checked) {
                    card.classList.add('bg-[#eff4ff]', 'border-[#006c49]/40', 'text-[#006c49]');
                    card.classList.remove('bg-white', 'border-outline-variant/60', 'text-on-surface');
                } else {
                    card.classList.remove('bg-[#eff4ff]', 'border-[#006c49]/40', 'text-[#006c49]');
                    card.classList.add('bg-white', 'border-outline-variant/60', 'text-on-surface');
                }
            }
        });

        // Update footer summary text
        const footerCountText = document.getElementById(`footer-count-text-${roleId}`);
        if (footerCountText) {
            footerCountText.innerText = `${totalChecked} dari ${checkboxes.length} permission dipilih`;
        }

        // Update Toggle All Button text and icon
        const toggleIcon = document.getElementById(`toggle-icon-${roleId}`);
        const toggleText = document.getElementById(`toggle-text-${roleId}`);
        if (toggleIcon && toggleText) {
            if (totalChecked === checkboxes.length) {
                toggleIcon.innerText = 'remove_done';
                toggleText.innerText = 'Kosongkan Semua';
            } else {
                toggleIcon.innerText = 'select_all';
                toggleText.innerText = 'Pilih Semua';
            }
        }

        // Update each category
        const allTabSlugs = getAllTabSlugs(modal);
        allTabSlugs.forEach(cat => {
            const catCheckboxes = modal.querySelectorAll(`.permission-checkbox-node[data-category="${cat}"]`);
            if (catCheckboxes.length === 0) return;

            let catChecked = 0;
            catCheckboxes.forEach(c => { if(c.checked) catChecked++; });

            // Update tab badge text
            const badge = document.getElementById(`badge-${roleId}-${cat}`);
            if (badge) {
                const total = badge.getAttribute('data-total');
                badge.innerText = `${catChecked}/${total}`;
            }

            // Sync Category Header Checkbox
            const catAllCheckbox = document.getElementById(`chk-category-all-${roleId}-${cat}`);
            if (catAllCheckbox) {
                catAllCheckbox.checked = (catChecked === catCheckboxes.length);
            }

            // Sync Group Checkboxes inside this category
            const groups = new Set();
            catCheckboxes.forEach(c => groups.add(c.getAttribute('data-group')));

            groups.forEach(group => {
                const grpCheckboxes = modal.querySelectorAll(`.permission-checkbox-node[data-category="${cat}"][data-group="${group}"]`);
                let grpChecked = 0;
                grpCheckboxes.forEach(c => { if(c.checked) grpChecked++; });

                const grpAllCheckbox = document.getElementById(`chk-group-all-${roleId}-${cat}-${group}`);
                if (grpAllCheckbox) {
                    grpAllCheckbox.checked = (grpChecked === grpCheckboxes.length);
                }
            });
        });
    }

    // Client-side search and filtering of permissions
    function filterPermissions(roleId, query) {
        const modal = document.getElementById(`permissions-modal-${roleId}`);
        if (!modal) return;

        const q = (query || '').toLowerCase().trim();
        const allCards = modal.querySelectorAll('.perm-card');
        const panels = modal.querySelectorAll('.group-panel');

        if (!q) {
            // Restore all cards and panels
            allCards.forEach(card => card.style.display = '');
            panels.forEach(p => p.style.display = '');
            return;
        }

        let firstMatchingTab = null;

        allCards.forEach(card => {
            const input = card.querySelector('.permission-checkbox-node');
            const name = (input?.getAttribute('data-name') || '').toLowerCase();
            const desc = (input?.getAttribute('data-desc') || '').toLowerCase();
            const category = input?.getAttribute('data-category') || '';

            if (name.includes(q) || desc.includes(q)) {
                card.style.display = 'flex';
                if (!firstMatchingTab) {
                    firstMatchingTab = category;
                }
            } else {
                card.style.display = 'none';
            }
        });

        // Hide empty group panels
        panels.forEach(p => {
            const visibleCards = p.querySelectorAll('.perm-card:not([style*="display: none"])');
            p.style.display = (visibleCards.length > 0) ? '' : 'none';
        });

        // Switch to the first tab that has matching items if current tab has none
        if (firstMatchingTab) {
            const currentTab = modal.querySelector('.tab-content:not(.hidden)');
            const currentVisible = currentTab ? currentTab.querySelectorAll('.perm-card:not([style*="display: none"])').length : 0;
            if (currentVisible === 0) {
                showTabContent(roleId, firstMatchingTab);
            }
        }
    }
</script>
@endsection
