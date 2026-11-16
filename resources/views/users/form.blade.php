@extends('layouts.app')

@section('title', ($user->exists ? 'Ubah Pengguna' : 'Tambah Pengguna Baru') . ' | InvenTrack')

@section('content')
<div class="max-w-5xl mx-auto flex flex-col gap-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">
                {{ $user->exists ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru' }}
            </h1>
            <p class="text-on-surface-variant text-body-lg">
                {{ $user->exists ? 'Perbarui informasi profil, kepegawaian, atau kredensial akun pengguna.' : 'Lengkapi formulir di bawah ini untuk mendaftarkan akun pengguna baru ke dalam sistem.' }}
            </p>
        </div>

        <a href="{{ route('users.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline self-start sm:self-auto">
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
    <form action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
        @csrf
        @if($user->exists)
            @method('PUT')
        @endif

        <!-- SECTION 1: Profil & Informasi Pribadi -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-7 shadow-sm">
            <div class="flex items-center gap-3 pb-4 mb-6 border-b border-outline-variant/60">
                <div class="w-10 h-10 rounded-xl bg-surface-container-high text-on-surface flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl text-[#006c49]">person</span>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-on-surface">Informasi Pribadi & Kontak</h2>
                    <p class="text-xs text-on-surface-variant">Data identitas dasar dan kontak person pengguna</p>
                </div>
            </div>

            <!-- Profile Photo Upload -->
            <div class="mb-6 pb-6 border-b border-outline-variant/40">
                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-3">Foto Profil</label>
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                    <!-- Photo Avatar Preview -->
                    <div class="relative group shrink-0">
                        <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-outline-variant bg-surface-container-low flex items-center justify-center shadow-inner">
                            @if($user->photo)
                                <img id="avatar-preview" src="{{ Storage::url($user->photo) }}" alt="{{ $user->name }}" class="w-full h-full object-cover block" />
                                <span id="avatar-placeholder" class="material-symbols-outlined text-4xl text-outline-variant hidden" style="display: none;">person</span>
                            @else
                                <img id="avatar-preview" src="" alt="Preview" class="w-full h-full object-cover hidden" style="display: none;" />
                                <span id="avatar-placeholder" class="material-symbols-outlined text-4xl text-outline-variant block">person</span>
                            @endif
                        </div>
                        <label for="photo" class="absolute bottom-0 right-0 w-8 h-8 rounded-full bg-[#006c49] text-white hover:bg-[#00553a] flex items-center justify-center shadow-md cursor-pointer transition-transform hover:scale-110" title="Pilih foto">
                            <span class="material-symbols-outlined text-sm">photo_camera</span>
                        </label>
                    </div>

                    <!-- Upload Controls & Info -->
                    <div class="flex-1 flex flex-col justify-center text-center sm:text-left">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-2">
                            <label for="photo" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface-container-low hover:bg-surface-container text-xs font-bold text-on-surface cursor-pointer transition inline-flex items-center gap-2 shadow-sm">
                                <span class="material-symbols-outlined text-base text-[#006c49]">add_photo_alternate</span>
                                <span>Pilih Berkas Foto</span>
                            </label>
                            <input type="file" name="photo" id="photo" accept="image/*" class="hidden" onchange="previewPhoto(this)" />
                            <button type="button" id="btn-clear-photo" onclick="clearSelectedPhoto()" class="hidden px-2.5 py-1.5 rounded-lg border border-error/30 text-error hover:bg-error-container/20 text-xs font-semibold transition items-center gap-1">
                                <span class="material-symbols-outlined text-sm">close</span>
                                <span>Batal</span>
                            </button>
                            <span id="file-name-display" class="text-xs text-on-surface-variant truncate max-w-xs block sm:inline">Tidak ada berkas dipilih</span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant">Format didukung: JPG, PNG, WEBP, GIF. Ukuran maks. 2MB. Rasio 1:1 direkomendasikan.</p>
                        @error('photo') <p class="text-error text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Personal Fields Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Full Name -->
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Nama Lengkap <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">badge</span>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="{{ old('name', $user->name) }}" 
                            required 
                            placeholder="Contoh: Budi Santoso" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition @error('name') border-error @enderror"
                        />
                    </div>
                    @error('name') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Gender (Segmented Radio) -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Jenis Kelamin <span class="text-error">*</span>
                    </label>
                    <input type="hidden" name="gender" id="gender-input" value="{{ old('gender', $user->gender ?? 'Male') }}">
                    <div class="grid grid-cols-2 gap-3">
                        <button 
                            type="button" 
                            id="btn-gender-male" 
                            onclick="selectGender('Male')" 
                            class="gender-btn flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border font-bold text-xs transition-all cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-base">male</span>
                            <span>Laki-laki (Male)</span>
                        </button>
                        <button 
                            type="button" 
                            id="btn-gender-female" 
                            onclick="selectGender('Female')" 
                            class="gender-btn flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border font-bold text-xs transition-all cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-base">female</span>
                            <span>Perempuan (Female)</span>
                        </button>
                    </div>
                    @error('gender') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Mobile Phone -->
                <div>
                    <label for="mobile_phone" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Nomor Handphone / WhatsApp <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">call</span>
                        <input 
                            type="text" 
                            name="mobile_phone" 
                            id="mobile_phone" 
                            value="{{ old('mobile_phone', $user->mobile_phone) }}" 
                            required 
                            placeholder="Contoh: 081234567890" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition @error('mobile_phone') border-error @enderror"
                        />
                    </div>
                    @error('mobile_phone') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Address -->
                <div class="md:col-span-2">
                    <label for="address" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Alamat Lengkap
                    </label>
                    <div class="relative">
                        <textarea 
                            name="address" 
                            id="address" 
                            rows="2" 
                            placeholder="Alamat domisili lengkap..." 
                            class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition resize-none @error('address') border-error @enderror"
                        >{{ old('address', $user->address) }}</textarea>
                    </div>
                    @error('address') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- SECTION 2: Kepegawaian & Penugasan Toko -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-7 shadow-sm">
            <div class="flex items-center gap-3 pb-4 mb-6 border-b border-outline-variant/60">
                <div class="w-10 h-10 rounded-xl bg-surface-container-high text-on-surface flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl text-[#006c49]">work</span>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-on-surface">Kepegawaian & Lokasi Penugasan</h2>
                    <p class="text-xs text-on-surface-variant">Detail jabatan kerja, status karyawan, dan cabang toko yang dapat diakses</p>
                </div>
            </div>

            <!-- Employment Fields Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
                <!-- Employee Number -->
                <div>
                    <label for="employee_number" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Nomor Induk Karyawan (NIP) <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">pin</span>
                        <input 
                            type="text" 
                            name="employee_number" 
                            id="employee_number" 
                            value="{{ old('employee_number', $user->employee_number) }}" 
                            required 
                            placeholder="Contoh: EMP-001" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-mono font-medium transition @error('employee_number') border-error @enderror"
                        />
                    </div>
                    @error('employee_number') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Position -->
                <div>
                    <label for="position" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Jabatan / Posisi <span class="text-error">*</span>
                    </label>
                    <select 
                        name="position" 
                        id="position" 
                        required 
                        class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition cursor-pointer @error('position') border-error @enderror"
                    >
                        <option value="" disabled {{ !old('position', $user->position) ? 'selected' : '' }}>-- Pilih Posisi --</option>
                        @php
                            $defaultPositions = ['Super Admin', 'Manager', 'Supervisor', 'Staff Warehouse', 'Cashier', 'Admin', 'Sales'];
                            $availablePositions = $positions->isNotEmpty() ? $positions->toArray() : $defaultPositions;
                        @endphp
                        @foreach($availablePositions as $pos)
                            <option value="{{ $pos }}" {{ old('position', $user->position) == $pos ? 'selected' : '' }}>{{ $pos }}</option>
                        @endforeach
                    </select>
                    @error('position') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Status Kepegawaian -->
                <div>
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Status Karyawan <span class="text-error">*</span>
                    </label>
                    <select 
                        name="status" 
                        id="status" 
                        required 
                        class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition cursor-pointer @error('status') border-error @enderror"
                    >
                        <option value="" disabled {{ !old('status', $user->status) ? 'selected' : '' }}>-- Pilih Status --</option>
                        @php
                            $defaultStatuses = ['Permanent', 'Contract', 'Internship', 'Freelance'];
                            $availableStatuses = $statuses->isNotEmpty() ? $statuses->toArray() : $defaultStatuses;
                        @endphp
                        @foreach($availableStatuses as $st)
                            <option value="{{ $st }}" {{ old('status', $user->status) == $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Join Date -->
                <div>
                    <label for="join_date" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Tanggal Bergabung <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="date" 
                            name="join_date" 
                            id="join_date" 
                            value="{{ old('join_date', $user->join_date ? \Carbon\Carbon::parse($user->join_date)->format('Y-m-d') : date('Y-m-d')) }}" 
                            required 
                            class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition @error('join_date') border-error @enderror"
                        />
                    </div>
                    @error('join_date') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- End Date -->
                <div>
                    <label for="end_date" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Tanggal Berakhir <span class="text-[11px] font-normal text-on-surface-variant">(Opsional)</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="date" 
                            name="end_date" 
                            id="end_date" 
                            value="{{ old('end_date', $user->end_date ? \Carbon\Carbon::parse($user->end_date)->format('Y-m-d') : '') }}" 
                            class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition @error('end_date') border-error @enderror"
                        />
                    </div>
                    @error('end_date') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Store Assignment Cards -->
            <div class="pt-5 border-t border-outline-variant/60">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                            Penugasan Cabang Toko
                        </label>
                        <p class="text-xs text-on-surface-variant mt-0.5">Pilih satu atau lebih toko yang dapat diakses oleh pengguna ini.</p>
                    </div>
                    <span class="text-xs font-semibold text-secondary" id="selected-stores-count">
                        0 Toko Dipilih
                    </span>
                </div>

                @if($stores->isEmpty())
                    <div class="p-4 bg-surface-container-low rounded-xl border border-dashed border-outline-variant text-center">
                        <p class="text-xs text-on-surface-variant">Belum ada cabang toko yang terdaftar.</p>
                        <a href="{{ route('stores.create') }}" class="text-xs text-[#006c49] font-bold hover:underline mt-1 inline-block">
                            + Tambah Toko Baru
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($stores as $store)
                            @php
                                $isStoreSelected = (is_array(old('stores')) && in_array($store->id, old('stores'))) 
                                    || ($user->exists && $user->stores->contains($store->id));
                            @endphp
                            <label class="store-card relative flex items-start gap-3 p-3.5 rounded-xl border transition-all cursor-pointer select-none {{ $isStoreSelected ? 'bg-secondary-container/20 border-secondary' : 'bg-surface-container-lowest border-outline-variant hover:bg-surface-container-low' }}">
                                <input 
                                    type="checkbox" 
                                    name="stores[]" 
                                    value="{{ $store->id }}" 
                                    class="store-checkbox h-4 w-4 rounded border-outline-variant text-[#006c49] focus:ring-secondary mt-0.5" 
                                    {{ $isStoreSelected ? 'checked' : '' }}
                                    onchange="updateStoreCardStyle(this)"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 font-bold text-sm text-on-surface truncate">
                                        <span class="material-symbols-outlined text-sm text-secondary">storefront</span>
                                        <span class="truncate">{{ $store->name }}</span>
                                    </div>
                                    @if($store->address)
                                        <p class="text-[11px] text-on-surface-variant truncate mt-0.5">{{ $store->address }}</p>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('stores') <p class="text-error text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- SECTION 3: Kredensial Akun & Peran Sistem -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-7 shadow-sm">
            <div class="flex items-center gap-3 pb-4 mb-6 border-b border-outline-variant/60">
                <div class="w-10 h-10 rounded-xl bg-surface-container-high text-on-surface flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl text-[#006c49]">lock</span>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-on-surface">Kredensial Akun & Peran Sistem</h2>
                    <p class="text-xs text-on-surface-variant">Pengaturan login pengguna dan hak akses peran (Role)</p>
                </div>
            </div>

            <!-- Credentials Fields Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                <!-- Username -->
                <div>
                    <label for="username" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Username <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">alternate_email</span>
                        <input 
                            type="text" 
                            name="username" 
                            id="username" 
                            value="{{ old('username', $user->username) }}" 
                            required 
                            placeholder="Contoh: budi_s" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition @error('username') border-error @enderror"
                        />
                    </div>
                    @error('username') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Alamat Email <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">mail</span>
                        <input 
                            type="email" 
                            name="email" 
                            id="email" 
                            value="{{ old('email', $user->email) }}" 
                            required 
                            placeholder="budi@example.com" 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition @error('email') border-error @enderror"
                        />
                    </div>
                    @error('email') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                        Password {!! $user->exists ? '<span class="text-[11px] font-normal text-on-surface-variant">(Opsional)</span>' : '<span class="text-error">*</span>' !!}
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">key</span>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            {{ $user->exists ? '' : 'required' }}
                            placeholder="{{ $user->exists ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter' }}" 
                            class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-sm text-on-surface font-medium transition @error('password') border-error @enderror"
                        />
                        <button type="button" onclick="togglePasswordVisibility('password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition" title="Lihat password">
                            <span class="material-symbols-outlined text-lg" id="password-toggle-icon">visibility</span>
                        </button>
                    </div>
                    @error('password') <p class="text-error text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Role Selection Cards -->
            <div class="pt-5 border-t border-outline-variant/60">
                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1">
                    Pilih Peran Sistem (Roles)
                </label>
                <p class="text-xs text-on-surface-variant mb-4">Pengguna akan mendapatkan izin akses sesuai dengan peran yang dicentang.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($roles as $role)
                        @php
                            $isRoleSelected = (is_array(old('roles')) && in_array($role->id, old('roles'))) 
                                || ($user->exists && $user->roles->contains($role->id));
                        @endphp
                        <label class="role-card relative flex items-start gap-3 p-4 rounded-xl border transition-all cursor-pointer select-none {{ $isRoleSelected ? 'bg-secondary-container/20 border-secondary' : 'bg-surface-container-lowest border-outline-variant hover:bg-surface-container-low' }}">
                            <input 
                                type="checkbox" 
                                name="roles[]" 
                                value="{{ $role->id }}" 
                                class="role-checkbox h-4.5 w-4.5 rounded border-outline-variant text-[#006c49] focus:ring-secondary mt-0.5" 
                                {{ $isRoleSelected ? 'checked' : '' }}
                                onchange="updateRoleCardStyle(this)"
                            />
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-sm text-on-surface">{{ $role->name }}</span>
                                    @if($role->name === 'Super Admin')
                                        <span class="px-1.5 py-0.5 text-[10px] font-extrabold uppercase rounded bg-emerald-50 text-[#006c49] border border-emerald-200">Full</span>
                                    @endif
                                </div>
                                @if($role->description)
                                    <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">{{ $role->description }}</p>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('roles') <p class="text-error text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Action Buttons Bar -->
        <div class="flex items-center justify-end gap-3 bg-white rounded-2xl border border-outline-variant p-4 sm:p-5 shadow-sm">
            <a href="{{ route('users.index') }}" class="px-5 py-2.5 rounded-xl border border-outline-variant bg-surface-container hover:bg-surface-container-high text-on-surface text-sm font-bold transition active:scale-95">
                Batal
            </a>
            <button 
                type="submit" 
                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#006c49] text-white hover:bg-[#00553a] text-sm font-bold shadow-sm hover:shadow transition-all active:scale-95 cursor-pointer"
            >
                <span class="material-symbols-outlined text-base">save</span>
                <span>{{ $user->exists ? 'Perbarui Pengguna' : 'Simpan Pengguna' }}</span>
            </button>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
    // Photo live preview
    const initialPhotoUrl = "{{ $user->photo ? Storage::url($user->photo) : '' }}";

    function previewPhoto(input) {
        if (!input || !input.files || !input.files[0]) return;
        const file = input.files[0];

        const display = document.getElementById('file-name-display');
        const clearBtn = document.getElementById('btn-clear-photo');
        if (display) {
            display.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
            display.classList.add('text-secondary', 'font-semibold');
        }
        if (clearBtn) {
            clearBtn.classList.remove('hidden');
            clearBtn.style.display = 'inline-flex';
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatar-preview');
            const placeholder = document.getElementById('avatar-placeholder');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                preview.classList.remove('hidden');
            }
            if (placeholder) {
                placeholder.style.display = 'none';
                placeholder.classList.add('hidden');
            }
        };
        reader.readAsDataURL(file);
    }

    function clearSelectedPhoto() {
        const photoInput = document.getElementById('photo');
        const preview = document.getElementById('avatar-preview');
        const placeholder = document.getElementById('avatar-placeholder');
        const display = document.getElementById('file-name-display');
        const clearBtn = document.getElementById('btn-clear-photo');

        if (photoInput) photoInput.value = '';

        if (initialPhotoUrl) {
            if (preview) {
                preview.src = initialPhotoUrl;
                preview.style.display = 'block';
                preview.classList.remove('hidden');
            }
            if (placeholder) {
                placeholder.style.display = 'none';
                placeholder.classList.add('hidden');
            }
        } else {
            if (preview) {
                preview.src = '';
                preview.style.display = 'none';
                preview.classList.add('hidden');
            }
            if (placeholder) {
                placeholder.style.display = 'flex';
                placeholder.classList.remove('hidden');
            }
        }

        if (display) {
            display.textContent = 'Tidak ada berkas dipilih';
            display.classList.remove('text-secondary', 'font-semibold');
        }
        if (clearBtn) {
            clearBtn.style.display = 'none';
            clearBtn.classList.add('hidden');
        }
    }

    // Gender selection buttons
    function selectGender(gender) {
        document.getElementById('gender-input').value = gender;
        const btnMale = document.getElementById('btn-gender-male');
        const btnFemale = document.getElementById('btn-gender-female');

        if (gender === 'Male') {
            btnMale.className = "gender-btn flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border border-secondary bg-secondary-container/20 text-[#006c49] font-bold text-xs transition-all cursor-pointer shadow-sm";
            btnFemale.className = "gender-btn flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border border-outline-variant bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-low font-semibold text-xs transition-all cursor-pointer";
        } else {
            btnFemale.className = "gender-btn flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border border-secondary bg-secondary-container/20 text-[#006c49] font-bold text-xs transition-all cursor-pointer shadow-sm";
            btnMale.className = "gender-btn flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border border-outline-variant bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-low font-semibold text-xs transition-all cursor-pointer";
        }
    }

    // Toggle store card style on checkbox change
    function updateStoreCardStyle(checkbox) {
        const card = checkbox.closest('.store-card');
        if (checkbox.checked) {
            card.classList.add('bg-secondary-container/20', 'border-secondary');
            card.classList.remove('bg-surface-container-lowest', 'border-outline-variant');
        } else {
            card.classList.remove('bg-secondary-container/20', 'border-secondary');
            card.classList.add('bg-surface-container-lowest', 'border-outline-variant');
        }
        updateStoreCount();
    }

    function updateStoreCount() {
        const count = document.querySelectorAll('.store-checkbox:checked').length;
        const label = document.getElementById('selected-stores-count');
        if (label) {
            label.textContent = count + ' Toko Dipilih';
        }
    }

    // Toggle role card style on checkbox change
    function updateRoleCardStyle(checkbox) {
        const card = checkbox.closest('.role-card');
        if (checkbox.checked) {
            card.classList.add('bg-secondary-container/20', 'border-secondary');
            card.classList.remove('bg-surface-container-lowest', 'border-outline-variant');
        } else {
            card.classList.remove('bg-secondary-container/20', 'border-secondary');
            card.classList.add('bg-surface-container-lowest', 'border-outline-variant');
        }
    }

    // Password visibility toggle
    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById('password-toggle-icon');
        if (!input) return;

        if (input.type === 'password') {
            input.type = 'text';
            if (icon) icon.textContent = 'visibility_off';
        } else {
            input.type = 'password';
            if (icon) icon.textContent = 'visibility';
        }
    }

    // Initialize states on page load
    document.addEventListener('DOMContentLoaded', function() {
        const currentGender = document.getElementById('gender-input').value || 'Male';
        selectGender(currentGender);
        updateStoreCount();

        const photoInput = document.getElementById('photo');
        if (photoInput) {
            photoInput.addEventListener('change', function() {
                previewPhoto(this);
            });
        }
    });
</script>
@endpush
