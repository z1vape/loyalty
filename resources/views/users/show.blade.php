@extends('layouts.app')

@section('title', 'Detail Pengguna: ' . $user->name . ' | InvenTrack')

@section('content')
<div class="max-w-5xl mx-auto flex flex-col gap-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Detail Pengguna</h1>
            <p class="text-on-surface-variant text-body-lg">
                Informasi lengkap akun, data kepegawaian, dan hak akses pengguna.
            </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
            <a href="{{ route('users.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                <span>Kembali</span>
            </a>
            <a href="{{ route('users.edit', $user) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer no-underline">
                <span class="material-symbols-outlined text-base">edit</span>
                <span>Ubah Data</span>
            </a>
        </div>
    </div>

    <!-- User Profile Hero Card -->
    <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-8 shadow-sm flex flex-col sm:flex-row items-center sm:items-start gap-6">
        <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl overflow-hidden border-2 border-outline-variant/60 bg-surface-container-low flex items-center justify-center shrink-0 shadow-inner">
            @if($user->photo)
                <img src="{{ Storage::url($user->photo) }}" alt="{{ $user->name }}" class="w-full h-full object-cover" />
            @else
                <div class="w-full h-full bg-[#eff4ff] text-[#006c49] font-black flex items-center justify-center text-3xl uppercase tracking-wider">
                    {{ substr($user->name, 0, 2) }}
                </div>
            @endif
        </div>

        <div class="flex-1 text-center sm:text-left">
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 mb-2">
                <h2 class="text-xl sm:text-2xl font-extrabold text-on-surface">{{ $user->name }}</h2>
                @if($user->employee_number)
                    <span class="text-xs bg-surface-container px-2.5 py-0.5 rounded-lg font-mono font-bold text-on-surface-variant border border-outline-variant/60">
                        {{ $user->employee_number }}
                    </span>
                @endif
                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-secondary-container/30 text-[#006c49] border border-[#006c49]/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#006c49]"></span>
                    {{ $user->status ?? 'Aktif' }}
                </span>
            </div>

            <p class="text-sm font-semibold text-on-surface-variant flex items-center justify-center sm:justify-start gap-2 flex-wrap">
                <span>{{ $user->position ?: 'Staff Operasional' }}</span>
                <span>&bull;</span>
                <span class="font-mono text-xs">{{ $user->email }}</span>
                <span>&bull;</span>
                <span class="text-xs">Terdaftar sejak {{ $user->created_at->format('d M Y') }}</span>
            </p>

            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-1.5 mt-4">
                @forelse($user->roles as $role)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold bg-[#eff4ff] text-[#006c49] border border-outline-variant/60">
                        <span class="material-symbols-outlined text-sm">shield_person</span>
                        <span>{{ $role->name }}</span>
                    </span>
                @empty
                    <span class="text-xs text-on-surface-variant italic">Belum ada peran sistem</span>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Card: Informasi Pribadi & Kontak -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm flex flex-col gap-4">
            <div class="flex items-center gap-2.5 pb-3 border-b border-outline-variant/60">
                <span class="material-symbols-outlined text-xl text-[#006c49]">person</span>
                <h3 class="font-bold text-base text-on-surface">Informasi Pribadi & Kontak</h3>
            </div>

            <div class="space-y-3.5 text-sm">
                <div class="flex justify-between items-center py-1">
                    <span class="text-on-surface-variant text-xs uppercase font-bold tracking-wider">Nama Lengkap</span>
                    <span class="font-semibold text-on-surface">{{ $user->name }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-t border-outline-variant/30">
                    <span class="text-on-surface-variant text-xs uppercase font-bold tracking-wider">Jenis Kelamin</span>
                    <span class="font-semibold text-on-surface">{{ $user->gender === 'Female' ? 'Perempuan (Female)' : 'Laki-laki (Male)' }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-t border-outline-variant/30">
                    <span class="text-on-surface-variant text-xs uppercase font-bold tracking-wider">Nomor Handphone</span>
                    <span class="font-semibold text-on-surface">{{ $user->mobile_phone ?: '-' }}</span>
                </div>
                <div class="flex justify-between items-start py-1 border-t border-outline-variant/30">
                    <span class="text-on-surface-variant text-xs uppercase font-bold tracking-wider">Alamat</span>
                    <span class="font-medium text-on-surface text-right max-w-xs">{{ $user->address ?: '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Card: Kepegawaian & Tanggal -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm flex flex-col gap-4">
            <div class="flex items-center gap-2.5 pb-3 border-b border-outline-variant/60">
                <span class="material-symbols-outlined text-xl text-[#006c49]">badge</span>
                <h3 class="font-bold text-base text-on-surface">Detail Kepegawaian</h3>
            </div>

            <div class="space-y-3.5 text-sm">
                <div class="flex justify-between items-center py-1">
                    <span class="text-on-surface-variant text-xs uppercase font-bold tracking-wider">Nomor Karyawan (NIP)</span>
                    <span class="font-mono font-bold text-on-surface">{{ $user->employee_number ?: '-' }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-t border-outline-variant/30">
                    <span class="text-on-surface-variant text-xs uppercase font-bold tracking-wider">Jabatan / Posisi</span>
                    <span class="font-semibold text-on-surface">{{ $user->position ?: '-' }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-t border-outline-variant/30">
                    <span class="text-on-surface-variant text-xs uppercase font-bold tracking-wider">Status Kepegawaian</span>
                    <span class="font-semibold text-on-surface">{{ $user->status ?: '-' }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-t border-outline-variant/30">
                    <span class="text-on-surface-variant text-xs uppercase font-bold tracking-wider">Tanggal Bergabung</span>
                    <span class="font-semibold text-on-surface">{{ $user->join_date ? \Carbon\Carbon::parse($user->join_date)->format('d F Y') : '-' }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-t border-outline-variant/30">
                    <span class="text-on-surface-variant text-xs uppercase font-bold tracking-wider">Tanggal Berakhir</span>
                    <span class="font-semibold text-on-surface">{{ $user->end_date ? \Carbon\Carbon::parse($user->end_date)->format('d F Y') : 'Tidak ditentukan' }}</span>
                </div>
            </div>
        </div>

        <!-- Card: Penugasan Toko -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm flex flex-col gap-4">
            <div class="flex items-center gap-2.5 pb-3 border-b border-outline-variant/60">
                <span class="material-symbols-outlined text-xl text-[#006c49]">storefront</span>
                <h3 class="font-bold text-base text-on-surface">Cabang Toko yang Ditugaskan</h3>
            </div>

            @if($user->stores->isEmpty())
                <p class="text-xs text-on-surface-variant italic py-2">Pengguna ini belum ditugaskan ke cabang toko manapun.</p>
            @else
                <div class="space-y-2">
                    @foreach($user->stores as $store)
                        <div class="p-3 rounded-xl border border-outline-variant/60 bg-surface-container-low flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-secondary text-lg">store</span>
                                <div>
                                    <div class="font-bold text-sm text-on-surface">{{ $store->name }}</div>
                                    @if($store->address)
                                        <div class="text-[11px] text-on-surface-variant">{{ $store->address }}</div>
                                    @endif
                                </div>
                            </div>
                            @if($store->phone)
                                <span class="text-xs text-on-surface-variant font-mono">{{ $store->phone }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Card: Kredensial Akun -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 shadow-sm flex flex-col gap-4">
            <div class="flex items-center gap-2.5 pb-3 border-b border-outline-variant/60">
                <span class="material-symbols-outlined text-xl text-[#006c49]">security</span>
                <h3 class="font-bold text-base text-on-surface">Kredensial & Keamanan</h3>
            </div>

            <div class="space-y-3.5 text-sm">
                <div class="flex justify-between items-center py-1">
                    <span class="text-on-surface-variant text-xs uppercase font-bold tracking-wider">Username</span>
                    <span class="font-mono font-bold text-on-surface">{{ '@' . $user->username }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-t border-outline-variant/30">
                    <span class="text-on-surface-variant text-xs uppercase font-bold tracking-wider">Email</span>
                    <span class="font-mono font-semibold text-on-surface">{{ $user->email }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-t border-outline-variant/30">
                    <span class="text-on-surface-variant text-xs uppercase font-bold tracking-wider">Password</span>
                    <span class="text-xs text-on-surface-variant font-mono">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull; (Terenkripsi)</span>
                </div>
                <div class="flex justify-between items-center py-1 border-t border-outline-variant/30">
                    <span class="text-on-surface-variant text-xs uppercase font-bold tracking-wider">Status Email</span>
                    <span class="inline-flex items-center gap-1 text-xs font-bold text-[#006c49]">
                        <span class="material-symbols-outlined text-sm">verified</span> Terverifikasi
                    </span>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
