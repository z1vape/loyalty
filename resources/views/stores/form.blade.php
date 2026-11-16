@extends('layouts.app')

@section('title', ($store->exists ? 'Ubah Toko' : 'Tambah Toko Baru') . ' | InvenTrack')

@section('content')
<div class="max-w-5xl mx-auto flex flex-col gap-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">
                {{ $store->exists ? 'Edit Cabang Toko' : 'Tambah Cabang Toko Baru' }}
            </h1>
            <p class="text-on-surface-variant text-body-lg">
                {{ $store->exists ? 'Perbarui informasi operasional, nomor kontak, atau staf yang ditugaskan di cabang ini.' : 'Daftarkan lokasi cabang toko baru untuk pengelolaan stok dan operasional penjualan.' }}
            </p>
        </div>

        <a href="{{ route('stores.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline self-start sm:self-auto">
            <span class="material-symbols-outlined text-base">arrow_back</span>
            <span>Kembali ke Daftar</span>
        </a>
    </div>

    <!-- Validation Error Alert Banner -->
    @if ($errors->any())
        <div class="p-4 bg-error-container/40 border border-error/30 rounded-2xl flex items-start gap-3">
            <span class="material-symbols-outlined text-error text-xl shrink-0 mt-0.5">error</span>
            <div class="flex-1">
                <h4 class="text-sm font-bold text-error">Terdapat beberapa kesalahan input:</h4>
                <ul class="text-xs text-on-surface-variant mt-1 list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Main Form -->
    <form action="{{ $store->exists ? route('stores.update', $store) : route('stores.store') }}" method="POST" class="flex flex-col gap-6">
        @csrf
        @if($store->exists)
            @method('PUT')
        @endif

        <!-- SECTION 1: Informasi Cabang Toko -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-7 shadow-sm">
            <div class="flex items-center gap-3 pb-4 mb-6 border-b border-outline-variant/60">
                <div class="w-10 h-10 rounded-xl bg-surface-container-high text-on-surface flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl text-[#006c49]">storefront</span>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-on-surface">Detail Lokasi & Kontak Toko</h2>
                    <p class="text-xs text-on-surface-variant">Nama cabang, nomor kontak darurat/operasional, dan alamat lengkap</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Store Name -->
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Nama Cabang Toko <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">store</span>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="{{ old('name', $store->name) }}" 
                            required 
                            placeholder="Contoh: Toko Bandung (Pusat)" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition @error('name') border-error @enderror"
                        />
                    </div>
                    @error('name') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Phone Number -->
                <div>
                    <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Nomor Telepon / WhatsApp
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">call</span>
                        <input 
                            type="text" 
                            name="phone" 
                            id="phone" 
                            value="{{ old('phone', $store->phone) }}" 
                            placeholder="Contoh: 022-1234567 / 081234567890" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition @error('phone') border-error @enderror"
                        />
                    </div>
                    @error('phone') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Address -->
                <div class="md:col-span-2">
                    <label for="address" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Alamat Lengkap Cabang
                    </label>
                    <div class="relative">
                        <textarea 
                            name="address" 
                            id="address" 
                            rows="3" 
                            placeholder="Contoh: Jl. Merdeka No. 10, RT 01/RW 02, Kec. Sumur Bandung, Kota Bandung" 
                            class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition resize-none @error('address') border-error @enderror"
                        >{{ old('address', $store->address) }}</textarea>
                    </div>
                    @error('address') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- SECTION: Pengaturan Geofencing & Titik Lokasi Absensi GPS -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-7 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 mb-6 border-b border-outline-variant/60 gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-surface-container-high text-on-surface flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl text-[#006c49]">location_on</span>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-on-surface">Titik Lokasi & Geofencing Absensi</h2>
                        <p class="text-xs text-on-surface-variant">Tentukan titik koordinat GPS dan batas radius absensi karyawan untuk cabang ini</p>
                    </div>
                </div>

                <button 
                    type="button" 
                    onclick="getCurrentLocationForStore()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary/10 hover:bg-primary/20 text-primary text-xs font-bold transition-all cursor-pointer border border-primary/20 self-start sm:self-auto"
                >
                    <span class="material-symbols-outlined text-base">my_location</span>
                    <span id="btn-gps-text">Ambil Lokasi Saya Saat Ini</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Latitude -->
                <div>
                    <label for="latitude" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Latitude (Lintang)
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">explore</span>
                        <input 
                            type="number" 
                            step="any"
                            name="latitude" 
                            id="latitude" 
                            value="{{ old('latitude', $store->latitude) }}" 
                            placeholder="-6.917464" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-mono transition @error('latitude') border-error @enderror"
                        />
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1">Koordinat lintang toko (antara -90 s/d 90).</p>
                    @error('latitude') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Longitude -->
                <div>
                    <label for="longitude" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Longitude (Bujur)
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">explore</span>
                        <input 
                            type="number" 
                            step="any"
                            name="longitude" 
                            id="longitude" 
                            value="{{ old('longitude', $store->longitude) }}" 
                            placeholder="107.619123" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-mono transition @error('longitude') border-error @enderror"
                        />
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1">Koordinat bujur toko (antara -180 s/d 180).</p>
                    @error('longitude') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Radius (Meter) -->
                <div>
                    <label for="radius_meters" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Radius Toleransi (Meter) <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">radar</span>
                        <input 
                            type="number" 
                            name="radius_meters" 
                            id="radius_meters" 
                            value="{{ old('radius_meters', $store->radius_meters ?? 100) }}" 
                            placeholder="100" 
                            min="10"
                            max="50000"
                            required
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-semibold font-mono transition @error('radius_meters') border-error @enderror"
                        />
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1">Jarak radius maksimal karyawan diizinkan Clock In (disarankan: 50 - 100 m).</p>
                    @error('radius_meters') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Info Banner & Quick Map Link -->
                <div class="md:col-span-3 p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs">
                    <div class="flex items-center gap-2 text-on-surface-variant">
                        <span class="material-symbols-outlined text-primary text-base">info</span>
                        <span>Karyawan yang ditugaskan ke cabang ini wajib berada di dalam radius toko saat Clock In di Z1 App.</span>
                    </div>
                    <a 
                        id="link-google-maps" 
                        href="https://www.google.com/maps?q={{ $store->latitude ?? '' }},{{ $store->longitude ?? '' }}" 
                        target="_blank" 
                        class="text-primary hover:underline font-bold inline-flex items-center gap-1 shrink-0 {{ ($store->latitude && $store->longitude) ? '' : 'hidden' }}"
                    >
                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                        <span>Cek Titik di Google Maps</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- SECTION: Rekening Bank & Saldo Awal Toko -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-7 shadow-sm">
            <div class="flex items-center gap-3 pb-4 mb-6 border-b border-outline-variant/60">
                <div class="w-10 h-10 rounded-xl bg-surface-container-high text-on-surface flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl text-[#006c49]">account_balance</span>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-on-surface">Rekening Bank & Saldo Awal Toko</h2>
                    <p class="text-xs text-on-surface-variant">Konfigurasi rekening penerimaan kas/bank dan modal saldo awal untuk perhitungan Ending Balance berjalan</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <!-- Bank Name -->
                <div>
                    <label for="bank_name" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Nama Bank
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">account_balance</span>
                        <input 
                            type="text" 
                            name="bank_name" 
                            id="bank_name" 
                            value="{{ old('bank_name', $store->bank_name) }}" 
                            placeholder="Contoh: BCA / Mandiri / BRI" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition @error('bank_name') border-error @enderror"
                        />
                    </div>
                    @error('bank_name') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Bank Account Number -->
                <div>
                    <label for="bank_account_number" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Nomor Rekening
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">tag</span>
                        <input 
                            type="text" 
                            name="bank_account_number" 
                            id="bank_account_number" 
                            value="{{ old('bank_account_number', $store->bank_account_number) }}" 
                            placeholder="Contoh: 1234567890" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition @error('bank_account_number') border-error @enderror"
                        />
                    </div>
                    @error('bank_account_number') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Bank Account Name -->
                <div>
                    <label for="bank_account_name" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Atas Nama (A/N Rekening)
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">badge</span>
                        <input 
                            type="text" 
                            name="bank_account_name" 
                            id="bank_account_name" 
                            value="{{ old('bank_account_name', $store->bank_account_name) }}" 
                            placeholder="Contoh: Z1 Vape Official" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition @error('bank_account_name') border-error @enderror"
                        />
                    </div>
                    @error('bank_account_name') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Initial Balance -->
                <div>
                    <label for="initial_balance" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Saldo Awal Toko / Bank (Rp)
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-xs font-bold">Rp</span>
                        <input 
                            type="number" 
                            step="0.01" 
                            min="0" 
                            name="initial_balance" 
                            id="initial_balance" 
                            value="{{ old('initial_balance', $store->initial_balance ?? 0) }}" 
                            placeholder="0" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-semibold font-mono transition @error('initial_balance') border-error @enderror"
                        />
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1">Saldo awal kas/bank yang digunakan sebagai dasar perhitungan ending balance.</p>
                    @error('initial_balance') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Initial Balance Date -->
                <div>
                    <label for="initial_balance_date" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Tanggal Cut-Off Saldo Awal
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">calendar_today</span>
                        <input 
                            type="date" 
                            name="initial_balance_date" 
                            id="initial_balance_date" 
                            value="{{ old('initial_balance_date', $store->initial_balance_date ? $store->initial_balance_date->format('Y-m-d') : date('Y-m-d')) }}" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition @error('initial_balance_date') border-error @enderror"
                        />
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1">Penjualan dan pengeluaran mulai tanggal ini akan dihitung ke saldo berjalan.</p>
                    @error('initial_balance_date') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Auto Cash & Expense to Bank Toggle -->
                <div class="md:col-span-2 lg:col-span-3 pt-4 border-t border-outline-variant/60">
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input 
                            type="checkbox" 
                            name="auto_cash_to_bank" 
                            value="1" 
                            {{ old('auto_cash_to_bank', $store->auto_cash_to_bank ?? true) ? 'checked' : '' }}
                            class="mt-1 w-4 h-4 rounded text-[#006c49] focus:ring-[#006c49] border-outline-variant transition cursor-pointer"
                        />
                        <div>
                            <span class="text-sm font-bold text-on-surface group-hover:text-[#006c49] transition-colors">
                                Otomatisasi Saldo Bank: Omset Tunai (Cash) & Beban Operasional Langsung Masuk/Keluar ke Rekening Bank
                            </span>
                            <p class="text-xs text-on-surface-variant mt-0.5">
                                Aktifkan opsi ini jika toko menganggap seluruh omset penjualan tunai otomatis langsung disetor ke rekening bank toko dan beban operasional toko dipotong dari rekening bank (mencegah kas toko menjadi minus).
                            </p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Penugasan Staf (Staff Assignment Dropdown Multiple) -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-7 shadow-sm">
            <div class="flex items-center justify-between pb-4 mb-5 border-b border-outline-variant/60">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-surface-container-high text-on-surface flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl text-[#006c49]">group</span>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-on-surface">Penugasan Staf Toko</h2>
                        <p class="text-xs text-on-surface-variant">Pilih pengguna yang diberikan hak akses untuk mengoperasikan toko ini</p>
                    </div>
                </div>
                <span class="text-xs font-bold px-3 py-1 rounded-full bg-[#eff4ff] text-[#006c49] border border-[#006c49]/20" id="selected-staff-count">
                    0 Staf Dipilih
                </span>
            </div>

            @if($users->isEmpty())
                <div class="py-6 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-3xl block mb-1 opacity-40">person_off</span>
                    <p class="text-sm font-semibold">Belum ada data staf/pengguna dalam sistem.</p>
                    <a href="{{ route('users.create') }}" class="text-xs text-[#006c49] font-bold hover:underline mt-1 inline-block">
                        + Tambah Pengguna Baru
                    </a>
                </div>
            @else
                <div class="flex flex-col gap-2">
                    <label for="users-select" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                        Daftar Staf Cabang <span class="text-on-surface-variant font-normal normal-case">(Pilih satu atau lebih staf)</span>
                    </label>

                    <div class="relative">
                        <select 
                            name="users[]" 
                            id="users-select" 
                            multiple 
                            placeholder="Cari atau pilih nama staf..."
                            class="w-full"
                        >
                            @foreach($users as $user)
                                @php
                                    $isUserAssigned = old('users') !== null 
                                        ? in_array($user->id, old('users', [])) 
                                        : ($store->exists && $store->users->contains($user->id));
                                    $avatarUrl = $user->photo ? Storage::url($user->photo) : '';
                                    $initials = strtoupper(substr($user->name, 0, 2));
                                    $roleName = $user->roles->pluck('name')->first() ?? 'Staff';
                                    $posName = $user->position ?: 'Staff';
                                @endphp
                                <option 
                                    value="{{ $user->id }}" 
                                    data-avatar="{{ $avatarUrl }}"
                                    data-initials="{{ $initials }}"
                                    data-role="{{ $roleName }}"
                                    data-position="{{ $posName }}"
                                    {{ $isUserAssigned ? 'selected' : '' }}
                                >
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mt-1.5 text-xs text-on-surface-variant">
                        <span class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm text-[#006c49]">info</span>
                            Ketik nama staf untuk pencarian cepat, klik dropdown untuk memilih, atau klik tanda silang (&times;) untuk menghapus.
                        </span>
                        <div class="flex items-center gap-2 font-semibold shrink-0">
                            <button type="button" onclick="selectAllStaff()" class="text-[#006c49] hover:underline cursor-pointer">
                                Pilih Semua
                            </button>
                            <span class="text-outline-variant">&bull;</span>
                            <button type="button" onclick="clearAllStaff()" class="text-error hover:underline cursor-pointer">
                                Kosongkan
                            </button>
                        </div>
                    </div>
                </div>
            @endif
            @error('users') <p class="text-error text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
        </div>

        <!-- Action Buttons Bar -->
        <div class="flex items-center justify-end gap-3 bg-white rounded-2xl border border-outline-variant p-4 sm:p-5 shadow-sm">
            <a href="{{ route('stores.index') }}" class="px-5 py-2.5 rounded-xl border border-outline-variant bg-surface-container hover:bg-surface-container-high text-on-surface text-sm font-bold transition active:scale-95">
                Batal
            </a>
            <button 
                type="submit" 
                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#006c49] text-white hover:bg-[#00553a] text-sm font-bold shadow-sm hover:shadow transition-all active:scale-95 cursor-pointer"
            >
                <span class="material-symbols-outlined text-base">save</span>
                <span>{{ $store->exists ? 'Perbarui Toko' : 'Simpan Toko' }}</span>
            </button>
        </div>

    </form>
</div>
@endsection

@push('styles')
<style>
    /* Clean Material TomSelect Customization for Store Staff Selection */
    .ts-wrapper.multi .ts-control {
        border-radius: 0.75rem !important;
        border: 1px solid #c6c6cd !important;
        padding: 0.5rem 0.75rem !important;
        background-color: #f8f9ff !important;
        min-height: 48px !important;
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 0.375rem !important;
        box-shadow: none !important;
        transition: all 0.15s ease-in-out !important;
    }
    .ts-wrapper.multi.focus .ts-control {
        background-color: #ffffff !important;
        border-color: #006c49 !important;
        box-shadow: 0 0 0 3px rgba(0, 108, 73, 0.15) !important;
    }
    .ts-wrapper.multi .ts-control > .item {
        background: #eff4ff !important;
        color: #006c49 !important;
        border: 1px solid rgba(0, 108, 73, 0.25) !important;
        border-radius: 0.5rem !important;
        padding: 0.2rem 0.5rem !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.35rem !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        margin: 0 !important;
    }
    .ts-wrapper.multi .ts-control > .item .remove {
        border-left: 1px solid rgba(0, 108, 73, 0.2) !important;
        color: #006c49 !important;
        padding-left: 0.35rem !important;
        margin-left: 0.25rem !important;
        font-size: 0.95rem !important;
        font-weight: bold !important;
        line-height: 1 !important;
        opacity: 0.7 !important;
        transition: opacity 0.15s, color 0.15s !important;
        cursor: pointer !important;
    }
    .ts-wrapper.multi .ts-control > .item .remove:hover {
        opacity: 1 !important;
        color: #ba1a1a !important;
    }
    .ts-wrapper .ts-control input {
        color: #0b1c30 !important;
        font-size: 0.875rem !important;
        padding: 0.25rem 0 !important;
    }
    .ts-dropdown {
        border-radius: 0.75rem !important;
        border: 1px solid #c6c6cd !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        background-color: #ffffff !important;
        overflow: hidden !important;
        margin-top: 0.35rem !important;
        padding: 0.35rem !important;
        z-index: 50 !important;
    }
    .ts-dropdown .option {
        border-radius: 0.5rem !important;
        padding: 0.5rem 0.75rem !important;
        transition: all 0.15s ease-in-out !important;
        cursor: pointer !important;
    }
    .ts-dropdown .option.active, .ts-dropdown .option:hover {
        background-color: #eff4ff !important;
        color: #006c49 !important;
    }
    .ts-dropdown .option.selected {
        background-color: rgba(0, 108, 73, 0.08) !important;
        color: #006c49 !important;
        font-weight: 700 !important;
    }
</style>
@endpush

@push('scripts')
<script>
    let staffTomSelect = null;

    document.addEventListener('DOMContentLoaded', function() {
        const selectEl = document.getElementById('users-select');
        if (!selectEl) return;

        staffTomSelect = new TomSelect('#users-select', {
            plugins: {
                remove_button: {
                    title: 'Hapus staf ini'
                }
            },
            maxItems: null,
            closeAfterSelect: false,
            persist: false,
            create: false,
            placeholder: 'Cari atau pilih staf yang ditugaskan...',
            render: {
                item: function(data, escape) {
                    const avatar = data.avatar || (data.$option ? data.$option.dataset.avatar : '');
                    const initials = data.initials || (data.$option ? data.$option.dataset.initials : 'ST');
                    
                    const avatarHtml = avatar
                        ? `<img src="${escape(avatar)}" class="w-4 h-4 rounded-full object-cover shrink-0" alt="">`
                        : `<span class="w-4 h-4 rounded-full bg-[#006c49]/20 text-[#006c49] text-[9px] font-extrabold flex items-center justify-center uppercase shrink-0">${escape(initials)}</span>`;

                    return `<div class="inline-flex items-center gap-1.5 font-bold text-xs py-0.5">
                        ${avatarHtml}
                        <span>${escape(data.text)}</span>
                    </div>`;
                },
                option: function(data, escape) {
                    const avatar = data.avatar || (data.$option ? data.$option.dataset.avatar : '');
                    const initials = data.initials || (data.$option ? data.$option.dataset.initials : 'ST');
                    const role = data.role || (data.$option ? data.$option.dataset.role : 'Staff');
                    const position = data.position || (data.$option ? data.$option.dataset.position : '');

                    const avatarHtml = avatar
                        ? `<img src="${escape(avatar)}" class="w-7 h-7 rounded-full object-cover shrink-0" alt="">`
                        : `<span class="w-7 h-7 rounded-full bg-[#eff4ff] text-[#006c49] text-xs font-extrabold flex items-center justify-center shrink-0 uppercase border border-[#006c49]/20">${escape(initials)}</span>`;

                    return `<div class="flex items-center justify-between py-1.5 px-2 w-full">
                        <div class="flex items-center gap-2.5 min-w-0">
                            ${avatarHtml}
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-on-surface truncate">${escape(data.text)}</div>
                                <div class="text-[10px] text-on-surface-variant truncate">${escape(position)}</div>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant border border-outline-variant/60 shrink-0 ml-2">
                            ${escape(role)}
                        </span>
                    </div>`;
                }
            },
            onChange: function(values) {
                updateCount(Array.isArray(values) ? values.length : (values ? 1 : 0));
            }
        });

        // Initialize count on load
        if (staffTomSelect) {
            const currentVals = staffTomSelect.getValue();
            updateCount(Array.isArray(currentVals) ? currentVals.length : (currentVals ? 1 : 0));
        }
    });

    function updateCount(count) {
        const countBadge = document.getElementById('selected-staff-count');
        if (countBadge) {
            countBadge.textContent = count + ' Staf Dipilih';
        }
    }

    function selectAllStaff() {
        if (!staffTomSelect) return;
        const allKeys = Object.keys(staffTomSelect.options);
        staffTomSelect.setValue(allKeys);
    }

    function clearAllStaff() {
        if (!staffTomSelect) return;
        staffTomSelect.clear();
    }

    // Geolocation helper for Store geofencing
    function getCurrentLocationForStore() {
        const btnText = document.getElementById('btn-gps-text');
        if (!navigator.geolocation) {
            alert('Browser Anda tidak mendukung deteksi lokasi Geolocation.');
            return;
        }

        if (btnText) btnText.textContent = 'Mendeteksi GPS...';

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude.toFixed(8);
                const lng = position.coords.longitude.toFixed(8);

                document.getElementById('latitude').value = lat;
                document.getElementById('longitude').value = lng;

                updateStoreMapLink(lat, lng);

                if (btnText) btnText.textContent = '✓ Lokasi Berhasil Diambil';
                setTimeout(() => {
                    if (btnText) btnText.textContent = 'Ambil Lokasi Saya Saat Ini';
                }, 3000);
            },
            function(error) {
                console.error('Error getting location:', error);
                let msg = 'Gagal mendeteksi lokasi GPS.';
                if (error.code === 1) msg = 'Izin lokasi ditolak oleh browser/pengguna.';
                else if (error.code === 2) msg = 'Posisi GPS tidak dapat ditentukan.';
                else if (error.code === 3) msg = 'Waktu permintaan lokasi habis (timeout).';
                alert(msg);
                if (btnText) btnText.textContent = 'Ambil Lokasi Saya Saat Ini';
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    function updateStoreMapLink(lat, lng) {
        const mapLink = document.getElementById('link-google-maps');
        if (mapLink) {
            if (lat && lng) {
                mapLink.href = `https://www.google.com/maps?q=${lat},${lng}`;
                mapLink.classList.remove('hidden');
            } else {
                mapLink.classList.add('hidden');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');

        if (latInput && lngInput) {
            const handleCoordChange = () => {
                updateStoreMapLink(latInput.value.trim(), lngInput.value.trim());
            };
            latInput.addEventListener('input', handleCoordChange);
            lngInput.addEventListener('input', handleCoordChange);
        }
    });
</script>
@endpush
