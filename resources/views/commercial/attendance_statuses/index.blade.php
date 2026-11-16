@extends('layouts.app')

@section('title', 'Attendance Status - Master Data Shift')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-[#006c49] text-white flex items-center justify-center shadow-md shadow-emerald-900/10 shrink-0">
                <span class="material-symbols-outlined text-2xl">fact_check</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-secondary">Human Resources</span>
                    <span class="text-outline-variant font-bold">•</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">Master Data Shift</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-on-surface tracking-tight">Attendance Status</h1>
                <p class="text-xs text-on-surface-variant">Konfigurasi kode status absensi, badge warna, dan aturan perhitungan hari kerja.</p>
            </div>
        </div>

        <!-- Action Button (Tambah Status) -->
        <div class="flex items-center gap-2.5 shrink-0">
            @can('Attendance Status Create')
            <button 
                type="button" 
                onclick="openStatusModal()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] transition-all cursor-pointer shadow-xs active:scale-95"
            >
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Tambah Status Baru</span>
            </button>
            @endcan
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 text-xs font-semibold flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900"><span class="material-symbols-outlined text-base">close</span></button>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-800 text-xs font-semibold flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-base">error</span>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-700 hover:text-rose-900"><span class="material-symbols-outlined text-base">close</span></button>
    </div>
    @endif

    <!-- Search Bar Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 sm:p-5 shadow-xs">
        <form action="{{ route('commercial.attendance-statuses.index') }}" method="GET" class="flex items-center gap-3">
            <div class="relative flex-1">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-base">search</span>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}" 
                    placeholder="Cari kode (e.g. PRS, SKT), nama status kehadiran, atau tipe..." 
                    class="w-full pl-9 pr-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary transition-colors"
                />
            </div>
            <button type="submit" class="py-2 px-4 bg-secondary text-white font-bold text-xs rounded-xl hover:bg-[#00553a] transition-all cursor-pointer shadow-xs flex items-center gap-1.5 shrink-0">
                <span class="material-symbols-outlined text-[17px]">search</span>
                <span>Cari</span>
            </button>
            @if(!empty($search))
                <a href="{{ route('commercial.attendance-statuses.index') }}" class="p-2 bg-surface-container-low hover:bg-surface-container rounded-xl text-on-surface-variant border border-outline-variant shrink-0" title="Reset">
                    <span class="material-symbols-outlined text-[17px]">close</span>
                </a>
            @endif
        </form>
    </div>

    <!-- Attendance Status Table Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
            <div class="flex items-center gap-2">
                <span class="text-xs font-extrabold text-on-surface">Total:</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-primary/10 text-primary border border-primary/20">
                    {{ $statuses->count() }} Status
                </span>
            </div>
            <span class="text-[11px] text-on-surface-variant">Badge warna digunakan seragam pada tabel absensi & rekap roster</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-container-low text-on-surface-variant text-[11px] font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Kode & Preview Badge</th>
                        <th class="py-3 px-4">Nama Status</th>
                        <th class="py-3 px-4">Kategori Tipe</th>
                        <th class="py-3 px-4 text-center">Hitung Hari Kerja</th>
                        <th class="py-3 px-4 text-center">Wajib Lampiran</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @foreach($statuses as $st)
                    @php
                        $colorClass = match($st->badge_color) {
                            'emerald' => 'bg-emerald-500/10 text-emerald-800 border-emerald-500/30',
                            'amber'   => 'bg-amber-500/10 text-amber-800 border-amber-500/30',
                            'sky'     => 'bg-sky-500/10 text-sky-800 border-sky-500/30',
                            'indigo'  => 'bg-indigo-500/10 text-indigo-800 border-indigo-500/30',
                            'purple'  => 'bg-purple-500/10 text-purple-800 border-purple-500/30',
                            'slate'   => 'bg-slate-500/10 text-slate-800 border-slate-500/30',
                            'rose'    => 'bg-rose-500/10 text-rose-800 border-rose-500/30',
                            'orange'  => 'bg-orange-500/10 text-orange-800 border-orange-500/30',
                            default   => 'bg-slate-500/10 text-slate-800 border-slate-500/30',
                        };
                    @endphp
                    <tr class="hover:bg-surface-container-low/60 transition-colors {{ !$st->is_active ? 'opacity-50' : '' }}">
                        <!-- 1. Kode & Badge Preview -->
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-mono font-bold border {{ $colorClass }}">
                                <span>{{ $st->code }}</span>
                            </span>
                        </td>

                        <!-- 2. Nama Status -->
                        <td class="py-3.5 px-4 font-bold text-on-surface">
                            <div class="flex items-center gap-2">
                                <span>{{ $st->name }}</span>
                                @if($st->is_system)
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-surface-container-high text-on-surface-variant border border-outline-variant">Sistem</span>
                                @endif
                            </div>
                        </td>

                        <!-- 3. Kategori Tipe -->
                        <td class="py-3.5 px-4 text-on-surface-variant font-medium">
                            {{ $types[$st->type] ?? ucfirst($st->type) }}
                        </td>

                        <!-- 4. Hitung Hari Kerja -->
                        <td class="py-3.5 px-4 text-center">
                            @if($st->counts_as_workday)
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-800 bg-emerald-500/10 px-2 py-0.5 rounded-md border border-emerald-500/20">
                                    <span class="material-symbols-outlined text-[13px]">check_circle</span>
                                    <span>Masuk</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-700 bg-slate-500/10 px-2 py-0.5 rounded-md border border-slate-500/20">
                                    <span class="material-symbols-outlined text-[13px]">cancel</span>
                                    <span>Tidak</span>
                                </span>
                            @endif
                        </td>

                        <!-- 5. Wajib Lampiran -->
                        <td class="py-3.5 px-4 text-center">
                            @if($st->requires_attachment)
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20">
                                    <span class="material-symbols-outlined text-[13px]">attach_file</span>
                                    <span>Wajib</span>
                                </span>
                            @else
                                <span class="text-outline-variant text-xs">—</span>
                            @endif
                        </td>

                        <!-- 6. Status -->
                        <td class="py-3.5 px-4 text-center">
                            @can('Attendance Status Edit')
                            <form action="{{ route('commercial.attendance-statuses.toggle', $st) }}" method="POST" class="inline-block">
                                @csrf
                                <button 
                                    type="submit" 
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border transition-all cursor-pointer {{ $st->is_active ? 'bg-emerald-500/10 text-emerald-800 border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-slate-500/10 text-slate-700 border-slate-500/20 hover:bg-slate-500/20' }}"
                                    title="Klik untuk mengubah status aktif"
                                >
                                    {{ $st->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </form>
                            @else
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $st->is_active ? 'bg-emerald-500/10 text-emerald-800 border-emerald-500/20' : 'bg-slate-500/10 text-slate-700 border-slate-500/20' }}">
                                {{ $st->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            @endcan
                        </td>

                        <!-- 7. Aksi -->
                        <td class="py-3.5 px-4 text-right">
                            <div class="inline-flex items-center gap-1">
                                @can('Attendance Status Edit')
                                <button 
                                    type="button" 
                                    onclick='openEditStatusModal(@json($st))'
                                    class="p-1.5 rounded-lg bg-surface-container-low hover:bg-primary hover:text-white text-on-surface-variant border border-outline-variant transition-colors"
                                    title="Edit Status"
                                >
                                    <span class="material-symbols-outlined text-[17px]">edit</span>
                                </button>
                                @endcan
                                @if(!$st->is_system)
                                @can('Attendance Status Destroy')
                                <form action="{{ route('commercial.attendance-statuses.destroy', $st) }}" method="POST" onsubmit="return confirm('Hapus status kehadiran \'{{ $st->name }}\'?')" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button 
                                        type="submit" 
                                        class="p-1.5 rounded-lg bg-surface-container-low hover:bg-rose-600 hover:text-white text-rose-700 border border-outline-variant transition-colors"
                                        title="Hapus Status"
                                    >
                                        <span class="material-symbols-outlined text-[17px]">delete</span>
                                    </button>
                                </form>
                                @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah / Edit Attendance Status -->
<div id="status-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs hidden">
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl w-full max-w-md shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">fact_check</span>
                <h3 id="status-modal-title" class="font-black text-base text-on-surface">Tambah Status Kehadiran Baru</h3>
            </div>
            <button type="button" onclick="closeStatusModal()" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="status-form" action="{{ route('commercial.attendance-statuses.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="status-form-method" value="POST">

            <!-- Kode & Nama -->
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Kode <span class="text-rose-600">*</span></label>
                    <input 
                        type="text" 
                        name="code" 
                        id="status-code" 
                        required 
                        placeholder="e.g. IZN" 
                        maxlength="10"
                        class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono uppercase text-on-surface focus:outline-none focus:border-primary"
                    />
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-on-surface mb-1">Nama Status <span class="text-rose-600">*</span></label>
                    <input 
                        type="text" 
                        name="name" 
                        id="status-name" 
                        required 
                        placeholder="e.g. Izin Pribadi" 
                        class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                    />
                </div>
            </div>

            <!-- Tipe Kategori Kehadiran -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Kategori Tipe <span class="text-rose-600">*</span></label>
                <select name="type" id="status-type" required class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                    @foreach($types as $tKey => $tLabel)
                        <option value="{{ $tKey }}">{{ $tLabel }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Warna Badge -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Warna Badge <span class="text-rose-600">*</span></label>
                <select name="badge_color" id="status-badge-color" required class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                    @foreach($colors as $cKey => $cLabel)
                        <option value="{{ $cKey }}">{{ $cLabel }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Options Checkboxes -->
            <div class="space-y-2 pt-2 border-t border-outline-variant/60">
                <label class="flex items-center gap-2 cursor-pointer select-none text-xs">
                    <input type="checkbox" name="counts_as_workday" id="status-counts-as-workday" value="1" class="w-4 h-4 rounded text-primary focus:ring-primary border-outline-variant cursor-pointer">
                    <span class="font-bold text-on-surface">Hitung sebagai kehadiran kerja (Paid Day)</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer select-none text-xs">
                    <input type="checkbox" name="requires_attachment" id="status-requires-attachment" value="1" class="w-4 h-4 rounded text-primary focus:ring-primary border-outline-variant cursor-pointer">
                    <span class="font-bold text-on-surface">Wajib unggah surat / dokumen bukti</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer select-none text-xs">
                    <input type="checkbox" name="is_active" id="status-is-active" value="1" checked class="w-4 h-4 rounded text-primary focus:ring-primary border-outline-variant cursor-pointer">
                    <span class="font-bold text-on-surface">Status Aktif</span>
                </label>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-outline-variant/60">
                <button type="button" onclick="closeStatusModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-on-surface hover:bg-surface-container border border-outline-variant transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] transition-all shadow-xs cursor-pointer">
                    Simpan Status
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openStatusModal() {
        document.getElementById('status-modal-title').textContent = 'Tambah Status Kehadiran Baru';
        document.getElementById('status-form').action = "{{ route('commercial.attendance-statuses.store') }}";
        document.getElementById('status-form-method').value = 'POST';

        document.getElementById('status-code').value = '';
        document.getElementById('status-code').readOnly = false;
        document.getElementById('status-name').value = '';
        document.getElementById('status-type').value = 'present';
        document.getElementById('status-badge-color').value = 'emerald';
        document.getElementById('status-counts-as-workday').checked = true;
        document.getElementById('status-requires-attachment').checked = false;
        document.getElementById('status-is-active').checked = true;

        document.getElementById('status-modal').classList.remove('hidden');
    }

    function openEditStatusModal(status) {
        document.getElementById('status-modal-title').textContent = 'Edit Status: [' + status.code + '] ' + status.name;
        document.getElementById('status-form').action = "/commercial/attendance-statuses/" + status.id;
        document.getElementById('status-form-method').value = 'PUT';

        document.getElementById('status-code').value = status.code || '';
        document.getElementById('status-code').readOnly = Boolean(status.is_system);
        document.getElementById('status-name').value = status.name || '';
        document.getElementById('status-type').value = status.type || 'present';
        document.getElementById('status-badge-color').value = status.badge_color || 'emerald';
        document.getElementById('status-counts-as-workday').checked = Boolean(status.counts_as_workday);
        document.getElementById('status-requires-attachment').checked = Boolean(status.requires_attachment);
        document.getElementById('status-is-active').checked = Boolean(status.is_active);

        document.getElementById('status-modal').classList.remove('hidden');
    }

    function closeStatusModal() {
        document.getElementById('status-modal').classList.add('hidden');
    }
</script>
@endpush
@endsection
