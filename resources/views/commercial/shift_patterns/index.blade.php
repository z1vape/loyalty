@extends('layouts.app')

@section('title', 'Shift Pattern - Master Data Shift')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-[#006c49] text-white flex items-center justify-center shadow-md shadow-emerald-900/10 shrink-0">
                <span class="material-symbols-outlined text-2xl">published_with_changes</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-secondary">Human Resources</span>
                    <span class="text-outline-variant font-bold">•</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">Master Data Shift</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-on-surface tracking-tight">Shift Pattern</h1>
                <p class="text-xs text-on-surface-variant">Buat template pola perulangan rotasi shift (e.g. 6 Hari Masuk 1 Hari Libur) untuk penjadwalan otomatis.</p>
            </div>
        </div>

        <!-- Action Button (Tambah Pola) -->
        <div class="flex items-center gap-2.5 shrink-0">
            @can('Shift Pattern Create')
            <button 
                type="button" 
                onclick="openPatternModal()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] transition-all cursor-pointer shadow-xs active:scale-95"
            >
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Buat Pola Shift Baru</span>
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

    <!-- Patterns List -->
    <div class="space-y-4">
        @if($patterns->isEmpty())
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-12 text-center text-on-surface-variant">
            <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">published_with_changes</span>
            <p class="text-sm font-bold text-on-surface">Belum ada pola shift yang dibuat.</p>
            <p class="text-xs mt-1">Buat template pola rotasi shift untuk menjadwalkan roster karyawan secara otomatis dalam hitungan detik.</p>
            <button 
                type="button" 
                onclick="openPatternModal()"
                class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] transition-all shadow-xs"
            >
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Buat Pola Shift Sekarang</span>
            </button>
        </div>
        @else
        <div class="grid grid-cols-1 gap-4">
            @foreach($patterns as $pat)
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs hover:border-primary/50 transition-all space-y-4">
                <!-- Pattern Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-outline-variant/60">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-base text-on-surface">{{ $pat->name }}</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary border border-primary/20">
                                Siklus {{ $pat->cycle_days }} Hari
                            </span>
                            @if($pat->store)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-secondary/10 text-secondary border border-secondary/20">
                                    {{ $pat->store->name }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-surface-container-high text-on-surface-variant border border-outline-variant">
                                    Semua Toko (Global)
                                </span>
                            @endif
                        </div>
                        @if($pat->description)
                            <p class="text-xs text-on-surface-variant mt-1">{{ $pat->description }}</p>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 shrink-0">
                        @can('Shift Pattern Edit')
                        <form action="{{ route('commercial.shift-patterns.toggle', $pat) }}" method="POST" class="inline-block">
                            @csrf
                            <button 
                                type="submit" 
                                class="px-2.5 py-1 rounded-xl text-xs font-bold border transition-all cursor-pointer {{ $pat->is_active ? 'bg-emerald-500/10 text-emerald-800 border-emerald-500/20' : 'bg-slate-500/10 text-slate-700 border-slate-500/20' }}"
                            >
                                {{ $pat->is_active ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </form>

                        <button 
                            type="button" 
                            onclick='openEditPatternModal(@json($pat))'
                            class="p-2 rounded-xl bg-surface-container-low hover:bg-primary hover:text-white text-on-surface-variant border border-outline-variant transition-colors"
                            title="Edit Pola"
                        >
                            <span class="material-symbols-outlined text-[17px]">edit</span>
                        </button>
                        @else
                        <span class="px-2.5 py-1 rounded-xl text-xs font-bold border {{ $pat->is_active ? 'bg-emerald-500/10 text-emerald-800 border-emerald-500/20' : 'bg-slate-500/10 text-slate-700 border-slate-500/20' }}">
                            {{ $pat->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        @endcan

                        @can('Shift Pattern Destroy')
                        <form action="{{ route('commercial.shift-patterns.destroy', $pat) }}" method="POST" onsubmit="return confirm('Hapus pola shift \'{{ $pat->name }}\'?')" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button 
                                type="submit" 
                                class="p-2 rounded-xl bg-surface-container-low hover:bg-rose-600 hover:text-white text-rose-700 border border-outline-variant transition-colors"
                                title="Hapus Pola"
                            >
                                <span class="material-symbols-outlined text-[17px]">delete</span>
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>

                <!-- Visual Cycle Days Flow -->
                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Urutan Siklus Harian (Rotasi Berulang):</label>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach($pat->items as $item)
                        <div class="flex items-center gap-1.5 p-2 rounded-xl bg-surface-container-low border border-outline-variant/70 min-w-[100px]">
                            <div class="w-6 h-6 rounded-lg bg-surface-container-highest text-on-surface font-mono font-bold text-xs flex items-center justify-center shrink-0">
                                #{{ $item->day_number }}
                            </div>
                            <div class="truncate">
                                @if($item->is_off || !$item->shift)
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-black bg-slate-500/15 text-slate-700 border border-slate-500/25">
                                        OFF (Libur)
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-black text-white shadow-2xs truncate" style="background-color: {{ $item->shift->color ?: '#006c49' }}">
                                        {{ $item->shift->code ?: $item->shift->name }}
                                    </span>
                                    <div class="text-[9px] font-mono text-on-surface-variant leading-none mt-0.5">
                                        {{ \Carbon\Carbon::parse($item->shift->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($item->shift->end_time)->format('H:i') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        @if(!$loop->last)
                            <span class="material-symbols-outlined text-outline-variant text-base">arrow_forward</span>
                        @else
                            <span class="material-symbols-outlined text-primary text-base font-bold" title="Siklus berulang kembali ke Hari #1">restart_alt</span>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="pt-3">
            {{ $patterns->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Tambah / Edit Shift Pattern -->
<div id="pattern-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs hidden overflow-y-auto">
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl w-full max-w-2xl shadow-2xl p-6 space-y-4 my-8 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">published_with_changes</span>
                <h3 id="pattern-modal-title" class="font-black text-base text-on-surface">Buat Pola Shift Baru</h3>
            </div>
            <button type="button" onclick="closePatternModal()" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="pattern-form" action="{{ route('commercial.shift-patterns.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="pattern-form-method" value="POST">

            <!-- Nama & Panjang Siklus -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-on-surface mb-1">Nama Pola Shift <span class="text-rose-600">*</span></label>
                    <input 
                        type="text" 
                        name="name" 
                        id="pattern-name" 
                        required 
                        placeholder="Contoh: Pola 6 Hari Masuk 1 Hari Libur" 
                        class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                    />
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Panjang Siklus (Hari) <span class="text-rose-600">*</span></label>
                    <select name="cycle_days" id="pattern-cycle-days" onchange="renderCycleDaysInputs()" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                        @for($i = 2; $i <= 14; $i++)
                            <option value="{{ $i }}" {{ $i == 7 ? 'selected' : '' }}>{{ $i }} Hari {{ $i == 7 ? '(1 Minggu)' : ($i == 14 ? '(2 Minggu)' : '') }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <!-- Lokasi Toko & Deskripsi -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Berlaku untuk Toko</label>
                    <select name="store_id" id="pattern-store-id" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                        <option value="">Semua Toko (Global)</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Keterangan / Deskripsi</label>
                    <input 
                        type="text" 
                        name="description" 
                        id="pattern-description" 
                        placeholder="Opsional: catatan ketentuan rotasi..." 
                        class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                    />
                </div>
            </div>

            <!-- Dynamic Day Config Section -->
            <div class="pt-3 border-t border-outline-variant/60">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-black text-on-surface uppercase tracking-wider">Atur Shift Tiap Hari Siklus</label>
                    <span class="text-[11px] text-on-surface-variant">Pilih shift kerja atau centang OFF untuk hari libur</span>
                </div>
                <div id="cycle-days-container" class="space-y-2 max-h-64 overflow-y-auto p-1 bg-surface-container-low/50 rounded-xl border border-outline-variant/60">
                    <!-- Dynamically rendered by JS -->
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-outline-variant/60">
                <button type="button" onclick="closePatternModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-on-surface hover:bg-surface-container border border-outline-variant transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] transition-all shadow-xs cursor-pointer">
                    Simpan Pola Shift
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const availableShifts = @json($shifts);
    let currentEditItems = null;

    function renderCycleDaysInputs() {
        const cycleDays = parseInt(document.getElementById('pattern-cycle-days').value, 10) || 7;
        const container = document.getElementById('cycle-days-container');
        container.innerHTML = '';

        for (let d = 1; d <= cycleDays; d++) {
            const item = currentEditItems ? currentEditItems.find(it => it.day_number === d) : null;
            const isOff = item ? Boolean(item.is_off) : (d === cycleDays); // default last day is OFF
            const selectedShiftId = item ? item.shift_id : (availableShifts.length > 0 ? availableShifts[0].id : '');

            const row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-3 p-2.5 rounded-lg bg-surface-container-lowest border border-outline-variant/70';

            let optionsHtml = '';
            availableShifts.forEach(sh => {
                const isSelected = (!isOff && selectedShiftId == sh.id) ? 'selected' : '';
                optionsHtml += `<option value="${sh.id}" ${isSelected}>${sh.name} (${(sh.start_time || '').substring(0,5)} - ${(sh.end_time || '').substring(0,5)})</option>`;
            });

            row.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-primary/10 text-primary font-mono font-black text-xs flex items-center justify-center shrink-0">
                        #${d}
                    </span>
                    <span class="text-xs font-bold text-on-surface">Hari ke-${d}</span>
                </div>
                <div class="flex items-center gap-3">
                    <select name="days[${d}][shift_id]" id="day-shift-${d}" class="px-2.5 py-1.5 bg-surface-container-low border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary ${isOff ? 'opacity-40 pointer-events-none' : ''}">
                        ${optionsHtml}
                    </select>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs font-bold select-none text-on-surface">
                        <input type="checkbox" name="days[${d}][is_off]" value="1" ${isOff ? 'checked' : ''} onchange="toggleDayOff(${d}, this.checked)" class="w-4 h-4 rounded text-primary focus:ring-primary border-outline-variant cursor-pointer">
                        <span>OFF (Libur)</span>
                    </label>
                </div>
            `;
            container.appendChild(row);
        }
    }

    function toggleDayOff(dayNum, isChecked) {
        const selectEl = document.getElementById(`day-shift-${dayNum}`);
        if (selectEl) {
            if (isChecked) {
                selectEl.classList.add('opacity-40', 'pointer-events-none');
            } else {
                selectEl.classList.remove('opacity-40', 'pointer-events-none');
            }
        }
    }

    function openPatternModal() {
        currentEditItems = null;
        document.getElementById('pattern-modal-title').textContent = 'Buat Pola Shift Baru';
        document.getElementById('pattern-form').action = "{{ route('commercial.shift-patterns.store') }}";
        document.getElementById('pattern-form-method').value = 'POST';

        document.getElementById('pattern-name').value = '';
        document.getElementById('pattern-cycle-days').value = 7;
        document.getElementById('pattern-store-id').value = '';
        document.getElementById('pattern-description').value = '';

        renderCycleDaysInputs();
        document.getElementById('pattern-modal').classList.remove('hidden');
    }

    function openEditPatternModal(pattern) {
        currentEditItems = pattern.items || [];
        document.getElementById('pattern-modal-title').textContent = 'Edit Pola: ' + pattern.name;
        document.getElementById('pattern-form').action = "/commercial/shift-patterns/" + pattern.id;
        document.getElementById('pattern-form-method').value = 'PUT';

        document.getElementById('pattern-name').value = pattern.name || '';
        document.getElementById('pattern-cycle-days').value = pattern.cycle_days || 7;
        document.getElementById('pattern-store-id').value = pattern.store_id || '';
        document.getElementById('pattern-description').value = pattern.description || '';

        renderCycleDaysInputs();
        document.getElementById('pattern-modal').classList.remove('hidden');
    }

    function closePatternModal() {
        document.getElementById('pattern-modal').classList.add('hidden');
    }
</script>
@endpush
@endsection
