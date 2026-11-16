@extends('layouts.app')

@section('title', 'Shift - Master Data Shift')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-[#006c49] text-white flex items-center justify-center shadow-md shadow-emerald-900/10 shrink-0">
                <span class="material-symbols-outlined text-2xl">schedule</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-secondary">Human Resources</span>
                    <span class="text-outline-variant font-bold">•</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">Master Data Shift</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-on-surface tracking-tight">Shift</h1>
                <p class="text-xs text-on-surface-variant">Konfigurasi jam masuk, jam pulang, toleransi keterlambatan, dan warna shift.</p>
            </div>
        </div>

        <!-- Action Button (Tambah Shift) -->
        <div class="flex items-center gap-2.5 shrink-0">
            @can('Shift Create')
            <button 
                type="button" 
                onclick="openShiftModal()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] transition-all cursor-pointer shadow-xs active:scale-95"
            >
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Tambah Shift Baru</span>
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

    @if(session('info'))
    <div class="p-4 rounded-xl bg-sky-500/10 border border-sky-500/20 text-sky-800 text-xs font-semibold flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-base">info</span>
            <span>{{ session('info') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-sky-700 hover:text-sky-900"><span class="material-symbols-outlined text-base">close</span></button>
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-800 text-xs font-semibold space-y-1">
        <div class="font-bold flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base">error</span>
            <span>Terjadi kesalahan pengisian form:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-[11px]">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Filter Bar Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 sm:p-5 shadow-xs">
        <form action="{{ route('commercial.shifts.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <!-- Search -->
            <div class="lg:col-span-6">
                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Cari Shift</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-base">search</span>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Cari nama shift, kode (e.g. PAGI), atau deskripsi..." 
                        class="w-full pl-9 pr-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary transition-colors"
                    />
                </div>
            </div>

            <!-- Lokasi Kerja -->
            <div class="lg:col-span-4">
                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Lokasi Toko</label>
                <select name="store_id" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary transition-colors">
                    <option value="">Semua Lokasi Toko (Global)</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Button -->
            <div class="lg:col-span-2 flex items-center gap-2">
                <button type="submit" class="w-full py-2 px-3 bg-secondary text-white font-bold text-xs rounded-xl hover:bg-[#00553a] transition-all cursor-pointer shadow-xs flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-[17px]">filter_alt</span>
                    <span>Filter</span>
                </button>
                @if(!empty($search) || !empty($storeId))
                    <a href="{{ route('commercial.shifts.index') }}" class="p-2 bg-surface-container-low hover:bg-surface-container rounded-xl text-on-surface-variant border border-outline-variant" title="Reset Filter">
                        <span class="material-symbols-outlined text-[17px]">close</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Shifts Table Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs space-y-4">
        <!-- Top Stats -->
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
            <div class="flex items-center gap-2">
                <span class="text-xs font-extrabold text-on-surface">Total:</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-primary/10 text-primary border border-primary/20">
                    {{ $totalShifts }} Shift
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-800 border border-emerald-500/20">
                    {{ $activeShifts }} Aktif
                </span>
            </div>
        </div>

        @if($shifts->isEmpty())
        <div class="py-12 text-center text-on-surface-variant">
            <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">schedule</span>
            <p class="text-sm font-bold text-on-surface">Belum ada master shift yang terdaftar.</p>
            <p class="text-xs mt-1">Klik tombol "+ Tambah Shift Baru" untuk membuat jadwal shift pertama Anda.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-container-low text-on-surface-variant text-[11px] font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Shift & Kode</th>
                        <th class="py-3 px-4">Jam Masuk - Pulang</th>
                        <th class="py-3 px-4">Durasi & Toleransi</th>
                        <th class="py-3 px-4">Toko / Cabang</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @foreach($shifts as $sh)
                    <tr class="hover:bg-surface-container-low/60 transition-colors {{ !$sh->is_active ? 'opacity-60' : '' }}">
                        <!-- 1. Shift & Kode -->
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2.5">
                                <span class="w-3.5 h-3.5 rounded-full shrink-0 shadow-xs border border-black/10" style="background-color: {{ $sh->color ?: '#006c49' }}"></span>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-extrabold text-on-surface text-sm">{{ $sh->name }}</span>
                                        @if($sh->code)
                                        <span class="px-1.5 py-0.5 rounded font-mono font-bold text-[10px] bg-surface-container-high border border-outline-variant/70 text-on-surface">
                                            {{ $sh->code }}
                                        </span>
                                        @endif
                                        @if($sh->is_overnight)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-500/10 text-indigo-700 border border-indigo-500/20" title="Shift Lintas Hari (Overnight)">
                                            Lintas Hari
                                        </span>
                                        @endif
                                    </div>
                                    @if($sh->description)
                                    <p class="text-[11px] text-on-surface-variant mt-0.5">{{ $sh->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- 2. Jam Masuk - Pulang -->
                        <td class="py-3 px-4">
                            <div class="inline-flex items-center gap-1.5 font-mono font-bold text-xs bg-surface-container-low px-2.5 py-1 rounded-lg border border-outline-variant/60">
                                <span>{{ \Carbon\Carbon::parse($sh->start_time)->format('H:i') }}</span>
                                <span class="text-outline-variant">—</span>
                                <span>{{ \Carbon\Carbon::parse($sh->end_time)->format('H:i') }}</span>
                                <span class="text-[10px] text-on-surface-variant font-sans font-normal">WIB</span>
                            </div>
                        </td>

                        <!-- 3. Durasi & Toleransi -->
                        <td class="py-3 px-4">
                            <div class="space-y-1">
                                <div class="font-bold text-on-surface">{{ $sh->work_duration }}</div>
                                <div class="text-[11px] text-on-surface-variant flex flex-wrap gap-x-2 gap-y-0.5">
                                    <span>Toleransi: <strong class="text-on-surface">{{ $sh->late_tolerance_minutes }} mnt</strong></span>
                                    <span class="text-outline-variant">•</span>
                                    <span title="Bisa clock in lebih awal">Masuk: <strong class="text-primary">-{{ $sh->early_clock_in_minutes ?? 120 }}m</strong></span>
                                    <span class="text-outline-variant">•</span>
                                    <span title="Batas maksimal clock out sebelum sesi ditutup">Keluar: <strong class="text-error">+{{ $sh->max_clock_out_delay_minutes ?? 240 }}m</strong></span>
                                </div>
                            </div>
                        </td>

                        <!-- 4. Toko / Cabang -->
                        <td class="py-3 px-4">
                            @if($sh->store)
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-secondary">
                                <span class="material-symbols-outlined text-sm">storefront</span>
                                <span>{{ $sh->store->name }}</span>
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-on-surface-variant">
                                <span class="material-symbols-outlined text-sm text-outline-variant">public</span>
                                <span>Semua Toko (Global)</span>
                            </span>
                            @endif
                        </td>

                        <!-- 5. Status -->
                        <td class="py-3 px-4 text-center">
                            @can('Shift Edit')
                            <form action="{{ route('commercial.shifts.toggle', $sh) }}" method="POST" class="inline-block">
                                @csrf
                                <button 
                                    type="submit" 
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border transition-all cursor-pointer {{ $sh->is_active ? 'bg-emerald-500/10 text-emerald-800 border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-slate-500/10 text-slate-700 border-slate-500/20 hover:bg-slate-500/20' }}"
                                    title="Klik untuk mengubah status aktif"
                                >
                                    {{ $sh->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </form>
                            @else
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $sh->is_active ? 'bg-emerald-500/10 text-emerald-800 border-emerald-500/20' : 'bg-slate-500/10 text-slate-700 border-slate-500/20' }}">
                                {{ $sh->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            @endcan
                        </td>

                        <!-- 6. Aksi -->
                        <td class="py-3 px-4 text-right">
                            <div class="inline-flex items-center gap-1">
                                @can('Shift Edit')
                                <button 
                                    type="button" 
                                    onclick='openEditShiftModal(@json($sh))'
                                    class="p-1.5 rounded-lg bg-surface-container-low hover:bg-primary hover:text-white text-on-surface-variant border border-outline-variant transition-colors"
                                    title="Edit Shift"
                                >
                                    <span class="material-symbols-outlined text-[17px]">edit</span>
                                </button>
                                @endcan
                                @can('Shift Destroy')
                                <form action="{{ route('commercial.shifts.destroy', $sh) }}" method="POST" onsubmit="return confirm('Hapus master shift \'{{ $sh->name }}\'?')" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button 
                                        type="submit" 
                                        class="p-1.5 rounded-lg bg-surface-container-low hover:bg-rose-600 hover:text-white text-rose-700 border border-outline-variant transition-colors"
                                        title="Hapus Shift"
                                    >
                                        <span class="material-symbols-outlined text-[17px]">delete</span>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pt-3 border-t border-outline-variant/60">
            {{ $shifts->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Tambah / Edit Shift -->
<div id="shift-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs hidden">
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl w-full max-w-lg shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">schedule</span>
                <h3 id="shift-modal-title" class="font-black text-base text-on-surface">Tambah Master Shift Baru</h3>
            </div>
            <button type="button" onclick="closeShiftModal()" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="shift-form" action="{{ route('commercial.shifts.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="shift-form-method" value="POST">

            <!-- Nama & Kode -->
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-on-surface mb-1">Nama Shift <span class="text-rose-600">*</span></label>
                    <input 
                        type="text" 
                        name="name" 
                        id="shift-name" 
                        required 
                        placeholder="Contoh: Shift Pagi Operasional" 
                        class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                    />
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Kode Shift</label>
                    <input 
                        type="text" 
                        name="code" 
                        id="shift-code" 
                        placeholder="e.g. PAGI" 
                        maxlength="10"
                        class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono uppercase text-on-surface focus:outline-none focus:border-primary"
                    />
                </div>
            </div>

            <!-- Jam Masuk & Jam Keluar -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Jam Masuk <span class="text-rose-600">*</span></label>
                    <input 
                        type="time" 
                        name="start_time" 
                        id="shift-start-time" 
                        required 
                        class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                    />
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Jam Pulang <span class="text-rose-600">*</span></label>
                    <input 
                        type="time" 
                        name="end_time" 
                        id="shift-end-time" 
                        required 
                        class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                    />
                </div>
            </div>

            <!-- Toleransi & Warna -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Toleransi Terlambat (Menit)</label>
                    <input 
                        type="number" 
                        name="late_tolerance_minutes" 
                        id="shift-late-tolerance" 
                        min="0" 
                        max="180" 
                        value="15" 
                        class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                    />
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Warna Label / Badge</label>
                    <div class="flex items-center gap-2">
                        <input 
                            type="color" 
                            name="color" 
                            id="shift-color" 
                            value="#006c49" 
                            class="w-10 h-9 p-0.5 rounded-lg border border-outline-variant cursor-pointer bg-surface-container-low"
                        />
                        <span class="text-[11px] text-on-surface-variant">Warna untuk kalender roster</span>
                    </div>
                </div>
            </div>

            <!-- Jendela Batas Waktu Absensi (Bisa Masuk Lebih Awal & Batas Maksimal Pulang) -->
            <div class="p-3.5 rounded-xl border border-outline-variant/80 bg-surface-container-low/50 space-y-2.5">
                <div class="flex items-center gap-1.5 text-xs font-black text-on-surface">
                    <span class="material-symbols-outlined text-sm text-primary">more_time</span>
                    <span>Jendela Batas Waktu Absensi (Attendance Window)</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface mb-1">
                            Bisa Clock In Lebih Awal
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                name="early_clock_in_minutes" 
                                id="shift-early-clock-in" 
                                min="0" 
                                max="1440" 
                                value="120" 
                                class="w-full pl-3 pr-14 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary font-bold"
                            />
                            <span class="absolute right-2.5 top-2 text-[11px] font-bold text-on-surface-variant">Menit</span>
                        </div>
                        <p class="text-[10px] text-on-surface-variant mt-1">Boleh absen masuk $X$ menit sebelum shift (default: 120 mnt / 2 jam).</p>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface mb-1">
                            Batas Clock Out Terlambat
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                name="max_clock_out_delay_minutes" 
                                id="shift-max-clock-out" 
                                min="0" 
                                max="1440" 
                                value="240" 
                                class="w-full pl-3 pr-14 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary font-bold"
                            />
                            <span class="absolute right-2.5 top-2 text-[11px] font-bold text-on-surface-variant">Menit</span>
                        </div>
                        <p class="text-[10px] text-on-surface-variant mt-1">Batas tombol Clock Out aktif setelah shift berakhir (default: 240 mnt / 4 jam).</p>
                    </div>
                </div>
            </div>

            <!-- Lokasi Toko -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Penugasan Toko / Cabang</label>
                <select name="store_id" id="shift-store-id" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                    <option value="">Semua Toko (Berlaku Global)</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
                <p class="text-[10px] text-on-surface-variant mt-1">Pilih "Semua Toko" jika shift ini berlaku umum di seluruh cabang.</p>
            </div>

            <!-- Deskripsi -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Keterangan / Deskripsi</label>
                <textarea 
                    name="description" 
                    id="shift-description" 
                    rows="2" 
                    placeholder="Opsional: catatan ketentuan shift..." 
                    class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                ></textarea>
            </div>

            <!-- Toggles: Overnight & Aktif -->
            <div class="flex items-center justify-between pt-2 border-t border-outline-variant/60">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none text-xs">
                    <input type="checkbox" name="is_overnight" id="shift-is-overnight" value="1" class="w-4 h-4 rounded text-primary focus:ring-primary border-outline-variant cursor-pointer">
                    <span class="font-bold text-on-surface">Shift Lintas Hari (Overnight)</span>
                </label>

                <label class="inline-flex items-center gap-2 cursor-pointer select-none text-xs">
                    <input type="checkbox" name="is_active" id="shift-is-active" value="1" checked class="w-4 h-4 rounded text-primary focus:ring-primary border-outline-variant cursor-pointer">
                    <span class="font-bold text-on-surface">Status Aktif</span>
                </label>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-outline-variant/60">
                <button type="button" onclick="closeShiftModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-on-surface hover:bg-surface-container border border-outline-variant transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] transition-all shadow-xs cursor-pointer">
                    Simpan Master Shift
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openShiftModal() {
        document.getElementById('shift-modal-title').textContent = 'Tambah Master Shift Baru';
        document.getElementById('shift-form').action = "{{ route('commercial.shifts.store') }}";
        document.getElementById('shift-form-method').value = 'POST';

        document.getElementById('shift-name').value = '';
        document.getElementById('shift-code').value = '';
        document.getElementById('shift-start-time').value = '08:00';
        document.getElementById('shift-end-time').value = '17:00';
        document.getElementById('shift-late-tolerance').value = 15;
        document.getElementById('shift-early-clock-in').value = 120;
        document.getElementById('shift-max-clock-out').value = 240;
        document.getElementById('shift-color').value = '#006c49';
        document.getElementById('shift-store-id').value = '';
        document.getElementById('shift-description').value = '';
        document.getElementById('shift-is-overnight').checked = false;
        document.getElementById('shift-is-active').checked = true;

        document.getElementById('shift-modal').classList.remove('hidden');
    }

    function openEditShiftModal(shift) {
        document.getElementById('shift-modal-title').textContent = 'Edit Master Shift: ' + shift.name;
        document.getElementById('shift-form').action = "/commercial/shifts/" + shift.id;
        document.getElementById('shift-form-method').value = 'PUT';

        document.getElementById('shift-name').value = shift.name || '';
        document.getElementById('shift-code').value = shift.code || '';
        document.getElementById('shift-start-time').value = (shift.start_time || '').substring(0, 5);
        document.getElementById('shift-end-time').value = (shift.end_time || '').substring(0, 5);
        document.getElementById('shift-late-tolerance').value = shift.late_tolerance_minutes ?? 15;
        document.getElementById('shift-early-clock-in').value = shift.early_clock_in_minutes ?? 120;
        document.getElementById('shift-max-clock-out').value = shift.max_clock_out_delay_minutes ?? 240;
        document.getElementById('shift-color').value = shift.color || '#006c49';
        document.getElementById('shift-store-id').value = shift.store_id || '';
        document.getElementById('shift-description').value = shift.description || '';
        document.getElementById('shift-is-overnight').checked = Boolean(shift.is_overnight);
        document.getElementById('shift-is-active').checked = Boolean(shift.is_active);

        document.getElementById('shift-modal').classList.remove('hidden');
    }

    function closeShiftModal() {
        document.getElementById('shift-modal').classList.add('hidden');
    }
</script>
@endpush
@endsection
