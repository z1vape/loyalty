@extends('layouts.app')

@section('title', 'Z1 VapeOS | Master Data')

@section('content')
<div class="space-y-6">

    {{-- Header Banner --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container">
                    <span class="material-symbols-outlined text-sm">dataset</span>
                    Pusat Konfigurasi
                </span>
                <span class="text-xs text-on-surface-variant font-medium">Sistem & Pengaturan</span>
            </div>
            <h1 class="font-headline-lg text-2xl sm:text-3xl font-extrabold text-on-surface">Master Data</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">
                Pusat pengelolaan cabang toko, pengguna & hak akses, serta konfigurasi sistem.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-surface-container-low text-on-surface hover:bg-surface-container-high transition-all border border-outline-variant/50 no-underline shadow-2xs">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Dashboard Utama</span>
            </a>
        </div>
    </div>

    {{-- Quick KPI Counters --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 sm:gap-4">
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-on-surface-variant">Toko / Cabang</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-[#006c49] flex items-center justify-center">
                    <span class="material-symbols-outlined text-[19px]">storefront</span>
                </span>
            </div>
            <div class="mt-2 text-2xl font-black text-on-surface">{{ $counts['stores'] ?? 0 }}</div>
            <p class="text-[11px] text-slate-500 mt-0.5">Outlet aktif</p>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-on-surface-variant">User / Staff</span>
                <span class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[19px]">group</span>
                </span>
            </div>
            <div class="mt-2 text-2xl font-black text-on-surface">{{ $counts['users'] ?? 0 }}</div>
            <p class="text-[11px] text-slate-500 mt-0.5">Akun terdaftar</p>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-on-surface-variant">Role & Akses</span>
                <span class="w-8 h-8 rounded-lg bg-teal-500/10 text-teal-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[19px]">admin_panel_settings</span>
                </span>
            </div>
            <div class="mt-2 text-2xl font-black text-on-surface">{{ $counts['roles'] ?? 0 }}</div>
            <p class="text-[11px] text-slate-500 mt-0.5">Role terdaftar</p>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-on-surface-variant">Approval Flow</span>
                <span class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[19px]">verified_user</span>
                </span>
            </div>
            <div class="mt-2 text-2xl font-black text-on-surface">{{ $counts['workflows'] ?? 0 }}</div>
            <p class="text-[11px] text-slate-500 mt-0.5">Alur persetujuan</p>
        </div>
    </div>

    {{-- Section 1: Cabang, User & Akses --}}
    <div>
        <div class="flex items-center gap-2 mb-3 px-1">
            <span class="material-symbols-outlined text-primary text-[20px]">domain</span>
            <h2 class="text-sm font-bold text-on-surface uppercase tracking-wider">Cabang, Pengguna & Konfigurasi</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            {{-- BI Performa Toko --}}
            <a href="{{ route('reports.store-performance') }}" class="group bg-gradient-to-b from-emerald-50/60 to-surface-container-lowest border-2 border-[#006c49]/40 hover:border-[#006c49] hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-[#006c49] text-white flex items-center justify-center mb-4 shadow-xs group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-2xl">monitoring</span>
                    </div>
                    <div class="inline-block px-2 py-0.5 rounded text-[10px] font-black uppercase bg-[#006c49] text-white tracking-wider mb-1.5">
                        Executive BI
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-primary transition-colors">Performa Seluruh Toko</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Dashboard Business Intelligence (BI) komparasi kinerja omzet, margin laba, transaksi, dan stok antar cabang.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-primary">
                    <span>Buka Dashboard BI</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>

            {{-- 1. Toko / Cabang --}}
            @can('Store List')
            <a href="{{ route('stores.index') }}" class="group bg-surface-container-lowest border border-outline-variant/80 hover:border-primary/50 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-[#006c49] group-hover:bg-[#006c49] group-hover:text-white transition-colors flex items-center justify-center mb-4 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">storefront</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-primary transition-colors">Toko / Cabang</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Kelola data outlet, titik koordinat GPS radius absensi, nomor rekening kasir, dan penugasan karyawan.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-primary">
                    <span>Buka Menu</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>
            @endcan

            {{-- 2. User & Karyawan --}}
            @can('User List')
            <a href="{{ route('users.index') }}" class="group bg-surface-container-lowest border border-outline-variant/80 hover:border-primary/50 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors flex items-center justify-center mb-4 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">group</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-blue-700 transition-colors">User & Karyawan</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Manajemen akun login pengguna, nomor induk karyawan (NIK), jabatan, gaji, dan penempatan cabang.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-blue-600">
                    <span>Buka Menu</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>
            @endcan

            {{-- 3. Role & Permission --}}
            @can('Role List')
            <a href="{{ route('roles.index') }}" class="group bg-surface-container-lowest border border-outline-variant/80 hover:border-primary/50 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors flex items-center justify-center mb-4 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">admin_panel_settings</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-purple-700 transition-colors">Role & Permission</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Pengaturan peran hak akses pengguna (Super Admin, Manager, Kasir, Staff) serta izin tombol per modul.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-purple-600">
                    <span>Buka Menu</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>
            @endcan

            {{-- 4. Pengaturan Sistem --}}
            @can('Setting List')
            <a href="{{ route('settings.index') }}" class="group bg-surface-container-lowest border border-outline-variant/80 hover:border-primary/50 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition-colors flex items-center justify-center mb-4 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">settings</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-amber-700 transition-colors">Pengaturan Sistem</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Kelola data master dropdown: Kategori produk, satuan unit barang, dan konfigurasi umum aplikasi.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-amber-600">
                    <span>Buka Menu</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>
            @endcan

            {{-- 5. Approval Workflow --}}
            @if(auth()->user()->hasRole('Super Admin') || auth()->user()->can('Role List') || auth()->user()->can('Setting List'))
            <a href="{{ route('master-data.approval-workflows.index') }}" class="group bg-surface-container-lowest border border-outline-variant/80 hover:border-[#006c49]/50 hover:shadow-md transition-all rounded-2xl p-5 flex flex-col justify-between no-underline">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-[#006c49] group-hover:bg-[#006c49] group-hover:text-white transition-colors flex items-center justify-center mb-4 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">verified_user</span>
                    </div>
                    <h3 class="text-base font-extrabold text-on-surface group-hover:text-[#006c49] transition-colors">Approval Workflow</h3>
                    <p class="text-xs text-on-surface-variant mt-1.5 leading-relaxed">
                        Konfigurasi hak otorisasi role untuk menyetujui transaksi Purchase Request (PR), Purchase Order (PO), dll.
                    </p>
                </div>
                <div class="mt-5 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold text-[#006c49]">
                    <span>Buka Menu</span>
                    <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </div>
            </a>
            @endif
        </div>
    </div>

    {{-- HR Relocation Notice --}}
    <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-[#006c49] border border-emerald-200/60 flex items-center justify-center shrink-0 shadow-2xs">
                <span class="material-symbols-outlined text-2xl">badge</span>
            </div>
            <div>
                <h4 class="text-sm font-black text-on-surface">Manajemen Shift &amp; Jadwal Kerja Telah Dipindahkan</h4>
                <p class="text-xs text-on-surface-variant mt-0.5">Pengaturan Shift Kerja, Status Kehadiran, Jadwal Kerja (Roster), dan Shift Pattern kini terintegrasi secara terpusat di portal <strong>Human Resources</strong>.</p>
            </div>
        </div>
        <a href="{{ route('commercial.attendances.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-[#006c49] hover:bg-[#005236] text-white transition-all no-underline shrink-0 shadow-2xs">
            <span>Buka Human Resources</span>
            <span class="material-symbols-outlined text-sm">arrow_forward</span>
        </a>
    </div>

</div>
@endsection

