@extends('layouts.app')

@section('title', 'Data Pengguna | InvenTrack')

@section('content')
<div class="flex flex-col gap-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Data Pengguna Sistem</h1>
            <p class="text-on-surface-variant text-body-lg">Kelola akun staf, hak akses peran sistem, dan penugasan lokasi cabang toko.</p>
        </div>

        @can('User Create')
        <a href="{{ route('users.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-90 active:scale-95 transition-all shadow-xs cursor-pointer no-underline shrink-0 self-start sm:self-auto">
            <span class="material-symbols-outlined text-base">person_add</span>
            <span>Tambah Pengguna</span>
        </a>
        @endcan
    </div>


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
        <!-- Total Users -->
        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-surface-container-high text-on-surface flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl text-[#006c49]">group</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Pengguna</p>
                <h3 class="text-2xl font-extrabold text-on-surface mt-0.5">{{ $stats['total_users'] ?? 0 }}</h3>
            </div>
        </div>

        <!-- Admins & Managers -->
        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-[#eff4ff] text-[#006c49] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">admin_panel_settings</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Admin & Manager</p>
                <h3 class="text-2xl font-extrabold text-on-surface mt-0.5">{{ $stats['admin_manager'] ?? 0 }}</h3>
            </div>
        </div>

        <!-- Staff -->
        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-secondary-container/30 text-on-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl text-[#006c49]">badge</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Staff Operasional</p>
                <h3 class="text-2xl font-extrabold text-on-surface mt-0.5">{{ $stats['staff'] ?? 0 }}</h3>
            </div>
        </div>

        <!-- Stores -->
        <div class="p-5 bg-white border border-outline-variant rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-surface-container text-on-surface-variant flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">storefront</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Cabang Toko</p>
                <h3 class="text-2xl font-extrabold text-on-surface mt-0.5">{{ $stats['total_stores'] ?? 0 }}</h3>
            </div>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden flex flex-col">

        <!-- Search & Filter Controls -->
        <div class="p-4 sm:p-5 border-b border-outline-variant flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
            <form action="{{ route('users.index') }}" method="GET" class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                <!-- Search input -->
                <div class="relative sm:col-span-2 lg:col-span-1">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">search</span>
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Cari nama, email, NIP..." 
                        value="{{ request('search') }}"
                        class="w-full pl-9 pr-8 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-xs font-medium text-on-surface transition"
                    />
                    @if(request('search'))
                        <a href="{{ route('users.index', request()->except('search')) }}" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-black">
                            <span class="material-symbols-outlined text-sm">close</span>
                        </a>
                    @endif
                </div>

                <!-- Role Filter -->
                <div>
                    <select 
                        name="role_id" 
                        onchange="this.form.submit()" 
                        class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-xs font-medium text-on-surface transition cursor-pointer"
                    >
                        <option value="">Semua Peran (Role)</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Store Filter -->
                <div>
                    <select 
                        name="store_id" 
                        onchange="this.form.submit()" 
                        class="w-full px-3 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-xs font-medium text-on-surface transition cursor-pointer"
                    >
                        <option value="">Semua Cabang Toko</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>
                                {{ $st->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="flex items-center gap-1.5">
                    <select 
                        name="status" 
                        onchange="this.form.submit()" 
                        class="flex-1 px-3 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] text-xs font-medium text-on-surface transition cursor-pointer"
                    >
                        <option value="">Semua Status</option>
                        @foreach($statuses as $statusOption)
                            <option value="{{ $statusOption }}" {{ request('status') == $statusOption ? 'selected' : '' }}>
                                {{ $statusOption }}
                            </option>
                        @endforeach
                    </select>

                    @if(request()->anyFilled(['search', 'role_id', 'store_id', 'status']))
                        <a href="{{ route('users.index') }}" class="p-2 rounded-xl border border-outline-variant hover:bg-surface-container-high text-on-surface-variant hover:text-black transition" title="Reset Filter">
                            <span class="material-symbols-outlined text-base">refresh</span>
                        </a>
                    @endif
                </div>
            </form>

            <div class="text-xs font-bold text-on-surface-variant text-right shrink-0">
                Menampilkan {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} pengguna
            </div>
        </div>

        <!-- Desktop Table (hidden on mobile) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant border-b border-outline-variant font-bold text-[11px] uppercase tracking-wider">
                        <th class="px-6 py-3.5">Pengguna</th>
                        <th class="px-6 py-3.5">Kontak & Posisi</th>
                        <th class="px-6 py-3.5">Cabang Toko</th>
                        <th class="px-6 py-3.5">Peran (Role)</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60 text-sm font-medium">
                    @forelse($users as $user)
                        <tr class="hover:bg-surface-container-lowest/80 hover:bg-neutral-50/80 transition-colors">
                            <!-- User Name & Avatar -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full overflow-hidden border border-outline-variant shrink-0 bg-surface-container-low flex items-center justify-center shadow-sm">
                                        @if($user->photo)
                                            <img src="{{ Storage::url($user->photo) }}" alt="{{ $user->name }}" class="w-full h-full object-cover" />
                                        @else
                                            <div class="w-full h-full bg-[#eff4ff] text-[#006c49] font-black flex items-center justify-center text-xs uppercase">
                                                {{ substr($user->name, 0, 2) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-bold text-on-surface flex items-center gap-2">
                                            <a href="{{ route('users.show', $user) }}" class="hover:text-secondary transition-colors">
                                                {{ $user->name }}
                                            </a>
                                            @if($user->employee_number)
                                                <span class="text-[10px] bg-surface-container px-1.5 py-0.2 rounded font-mono text-on-surface-variant font-bold border border-outline-variant/60">
                                                    {{ $user->employee_number }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-on-surface-variant font-mono mt-0.5">
                                            {{ '@' . $user->username }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Contact & Position -->
                            <td class="px-6 py-4">
                                <div class="text-xs font-semibold text-on-surface">
                                    {{ $user->position ?: 'Staff Operasional' }}
                                </div>
                                <div class="text-xs text-on-surface-variant font-mono mt-0.5">
                                    {{ $user->email }}
                                </div>
                                @if($user->mobile_phone)
                                    <div class="text-[11px] text-on-surface-variant mt-0.5 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[13px]">call</span>
                                        <span>{{ $user->mobile_phone }}</span>
                                    </div>
                                @endif
                            </td>

                            <!-- Stores Assigned -->
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @forelse($user->stores as $st)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-surface-container text-on-surface border border-outline-variant/50">
                                            <span class="material-symbols-outlined text-xs text-secondary">store</span>
                                            <span>{{ $st->name }}</span>
                                        </span>
                                    @empty
                                        <span class="text-xs text-on-surface-variant italic">Semua / Tidak ditugaskan</span>
                                    @endforelse
                                </div>
                            </td>

                            <!-- Roles -->
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        @php
                                            $roleBadgeClass = match($role->name) {
                                                'Super Admin' => 'bg-emerald-50 text-[#006c49] border-emerald-200',
                                                'Manager' => 'bg-[#eff4ff] text-[#006c49] border-[#006c49]/30',
                                                default => 'bg-surface-container text-on-surface border-outline-variant/60'
                                            };
                                        @endphp
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $roleBadgeClass }}">
                                            <span>{{ $role->name }}</span>
                                        </span>
                                    @empty
                                        <span class="text-xs text-on-surface-variant italic">No Role</span>
                                    @endforelse
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4">
                                @php
                                    $statusDotClass = match(strtolower($user->status ?? '')) {
                                        'permanent', 'aktif', 'active' => 'bg-[#00714d]',
                                        'contract', 'kontrak' => 'bg-amber-500',
                                        default => 'bg-neutral-400'
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-secondary-container/20 text-[#006c49] border border-outline-variant/60">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusDotClass }}"></span>
                                    {{ $user->status ?: 'Aktif' }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- View Detail -->
                                    <a href="{{ route('users.show', $user) }}" class="p-1.5 hover:bg-surface-container-high rounded-lg text-on-surface-variant hover:text-black transition" title="Lihat Detail Profil">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                    </a>

                                    <!-- Edit -->
                                    @can('User Edit')
                                    <a href="{{ route('users.edit', $user) }}" class="p-1.5 hover:bg-surface-container-high rounded-lg text-on-surface-variant hover:text-black transition" title="Ubah Pengguna">
                                        <span class="material-symbols-outlined text-lg">edit</span>
                                    </a>
                                    @endcan

                                    <!-- Delete -->
                                    @can('User Destroy')
                                    @if(auth()->id() !== $user->id)
                                        <button 
                                            type="button" 
                                            onclick="confirmDelete('{{ $user->id }}', '{{ addslashes($user->name) }}')"
                                            class="p-1.5 hover:bg-error-container/40 rounded-lg text-error transition cursor-pointer" 
                                            title="Hapus Pengguna"
                                        >
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </button>
                                    @else
                                        <span class="p-1.5 text-outline-variant cursor-not-allowed" title="Anda tidak dapat menghapus akun Anda sendiri">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </span>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl block mb-2 opacity-30">person_off</span>
                                <p class="text-sm font-semibold">Tidak ada pengguna yang sesuai dengan filter.</p>
                                <a href="{{ route('users.index') }}" class="text-xs text-[#006c49] font-bold hover:underline mt-1 inline-block">
                                    Reset Semua Filter
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View (hidden on desktop) -->
        <div class="block md:hidden divide-y divide-outline-variant/60">
            @forelse($users as $user)
                <div class="p-4 flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-full overflow-hidden border border-outline-variant shrink-0 bg-surface-container-low flex items-center justify-center">
                                @if($user->photo)
                                    <img src="{{ Storage::url($user->photo) }}" alt="{{ $user->name }}" class="w-full h-full object-cover" />
                                @else
                                    <div class="w-full h-full bg-[#eff4ff] text-[#006c49] font-black flex items-center justify-center text-xs uppercase">
                                        {{ substr($user->name, 0, 2) }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('users.show', $user) }}" class="font-bold text-sm text-on-surface hover:text-secondary block">
                                    {{ $user->name }}
                                </a>
                                <div class="text-xs text-on-surface-variant font-mono">{{ $user->email }}</div>
                                <div class="text-[11px] text-on-surface-variant mt-0.5">
                                    {{ $user->position ?: 'Staff' }} &bull; {{ '@' . $user->username }}
                                </div>
                            </div>
                        </div>

                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary-container/20 text-[#006c49] border border-outline-variant/60 shrink-0">
                            {{ $user->status ?: 'Aktif' }}
                        </span>
                    </div>

                    <!-- Roles & Stores -->
                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                        @foreach($user->roles as $role)
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-[#006c49] border border-emerald-200">
                                {{ $role->name }}
                            </span>
                        @endforeach
                        @foreach($user->stores as $st)
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-surface-container text-on-surface border border-outline-variant/50">
                                {{ $st->name }}
                            </span>
                        @endforeach
                    </div>

                    <!-- Mobile Action Buttons -->
                    <div class="flex items-center gap-2 pt-2 border-t border-outline-variant/40">
                        <a href="{{ route('users.show', $user) }}" class="flex-1 py-1.5 px-3 rounded-xl border border-outline-variant bg-surface-container text-on-surface text-xs font-bold text-center">
                            Detail
                        </a>
                        @can('User Edit')
                        <a href="{{ route('users.edit', $user) }}" class="flex-1 py-1.5 px-3 rounded-xl bg-[#006c49] text-white hover:bg-[#00553a] text-xs font-bold text-center transition shadow-2xs">
                            Edit
                        </a>
                        @endcan
                        @can('User Destroy')
                        @if(auth()->id() !== $user->id)
                            <button type="button" onclick="confirmDelete('{{ $user->id }}', '{{ addslashes($user->name) }}')" class="py-1.5 px-3 rounded-xl border border-error/30 text-error text-xs font-bold">
                                Hapus
                            </button>
                        @endif
                        @endcan
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-4xl block mb-2 opacity-30">person_off</span>
                    <p class="text-sm font-semibold">Tidak ada pengguna ditemukan.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant bg-surface-container-lowest">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Delete Confirmation Modal Dialog -->
<div id="delete-modal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl border border-outline-variant max-w-sm w-full p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
        <div class="w-12 h-12 rounded-xl bg-error-container text-error flex items-center justify-center mb-4">
            <span class="material-symbols-outlined text-2xl">warning</span>
        </div>
        <h3 class="text-lg font-bold text-on-surface mb-1">Konfirmasi Hapus</h3>
        <p class="text-xs text-on-surface-variant leading-relaxed mb-6">
            Apakah Anda yakin ingin menghapus akun pengguna <strong id="delete-user-name" class="text-on-surface"></strong>? Tindakan ini tidak dapat dibatalkan.
        </p>

        <form id="delete-form" method="POST" action="" class="flex items-center justify-end gap-2.5">
            @csrf
            @method('DELETE')
            <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 rounded-xl border border-outline-variant bg-surface-container hover:bg-surface-container-high text-xs font-bold text-on-surface transition">
                Batal
            </button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-error text-white hover:bg-red-700 text-xs font-bold transition">
                Ya, Hapus Akun
            </button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function confirmDelete(userId, userName) {
        const modal = document.getElementById('delete-modal');
        const nameSpan = document.getElementById('delete-user-name');
        const form = document.getElementById('delete-form');
        
        nameSpan.textContent = userName;
        form.action = `/users/${userId}`;
        modal.classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.add('hidden');
    }

    // Close modal on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });
</script>
@endpush
