@extends('layouts.app')

@section('title', 'User Schedule - Master Data Shift')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-[#006c49] text-white flex items-center justify-center shadow-md shadow-emerald-900/10 shrink-0">
                <span class="material-symbols-outlined text-2xl">calendar_month</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-secondary">Human Resources</span>
                    <span class="text-outline-variant font-bold">•</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">Master Data Shift</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-on-surface tracking-tight">User Schedule</h1>
                <p class="text-xs text-on-surface-variant">Matriks roster shift kerja bulanan per karyawan dan toko cabang.</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2 shrink-0">
            @can('User Schedule Manage')
            <button 
                type="button" 
                onclick="openBulkGenerateModal()"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] transition-all cursor-pointer shadow-xs active:scale-95"
                title="Terapkan pola rotasi shift secara otomatis ke banyak karyawan"
            >
                <span class="material-symbols-outlined text-[18px]">auto_fix_high</span>
                <span>Terapkan Pola Shift</span>
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

    <!-- Filter Bar Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 sm:p-5 shadow-xs">
        <form action="{{ route('commercial.schedules.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <!-- Search -->
            <div class="lg:col-span-4">
                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Cari Karyawan</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-base">search</span>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Nama, ID, atau posisi..." 
                        class="w-full pl-9 pr-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary transition-colors"
                    />
                </div>
            </div>

            <!-- Store -->
            <div class="lg:col-span-3">
                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Lokasi Toko</label>
                <select name="store_id" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary transition-colors">
                    <option value="">Semua Lokasi Toko</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Bulan -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Bulan</label>
                <select name="month" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary transition-colors">
                    @foreach($months as $mNum => $mName)
                        <option value="{{ $mNum }}" {{ $month == $mNum ? 'selected' : '' }}>{{ $mName }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tahun -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Tahun</label>
                <select name="year" class="w-full px-2.5 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary transition-colors">
                    @foreach($years as $yr)
                        <option value="{{ $yr }}" {{ $year == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Button -->
            <div class="lg:col-span-1">
                <button type="submit" class="w-full py-2 px-3 bg-secondary text-white font-bold text-xs rounded-xl hover:bg-[#00553a] transition-all cursor-pointer shadow-xs flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-[17px]">filter_alt</span>
                    <span>Filter</span>
                </button>
            </div>
        </form>

        <!-- Active Period Information Bar -->
        <div class="flex flex-wrap items-center justify-between pt-3 mt-3 border-t border-outline-variant/50 text-xs">
            <span class="text-on-surface-variant font-medium">
                Periode Roster: <strong class="text-on-surface">{{ \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('D MMMM Y') }} — {{ \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('D MMMM Y') }}</strong>
            </span>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-on-surface">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span> Shift Masuk
                </span>
                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-on-surface">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span> Libur (OFF)
                </span>
                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-on-surface-variant">
                    <span class="w-2.5 h-2.5 rounded-full bg-outline-variant"></span> Belum Dijadwal
                </span>
            </div>
        </div>
    </div>

    <!-- Roster Grid Matrix Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="px-5 py-3.5 border-b border-outline-variant/60 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-extrabold text-on-surface">Total:</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-primary/10 text-primary border border-primary/20">
                    {{ $users->count() }} Karyawan
                </span>
                <span class="text-[11px] text-on-surface-variant hidden sm:inline">• Kolom nama karyawan beku (frozen), geser ke kanan/kiri untuk melihat seluruh tanggal</span>
            </div>
            <div class="flex items-center gap-1.5 text-[11px] text-on-surface-variant">
                <span class="material-symbols-outlined text-[15px] text-primary">touch_app</span>
                <span class="italic">Klik sel tanggal untuk mengubah shift</span>
            </div>
        </div>

        @if($users->isEmpty())
        <div class="p-12 text-center text-on-surface-variant">
            <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">person_off</span>
            <p class="text-sm font-bold text-on-surface">Tidak ada data karyawan ditemukan.</p>
        </div>
        @else
        <div class="overflow-x-auto relative">
            <table class="w-full text-left text-xs border-separate border-spacing-0">
                <!-- Header: Dates -->
                <thead class="bg-surface-container-low text-on-surface-variant text-[11px] font-bold uppercase tracking-wider">
                    <tr>
                        <!-- Sticky Frozen Employee Column -->
                        <th class="py-3 px-4 sticky left-0 z-20 bg-surface-container-low min-w-[260px] max-w-[260px] w-[260px] border-b border-r-2 border-outline-variant/80 shadow-[4px_0_10px_-2px_rgba(0,0,0,0.08)]">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-on-surface text-[11px] uppercase tracking-wider">Karyawan</span>
                                <span class="text-[10px] font-bold text-on-surface-variant/80 lowercase">({{ $users->count() }} orang)</span>
                            </div>
                        </th>
                        @foreach($dates as $d)
                        <th class="py-2.5 px-2 text-center min-w-[88px] w-[88px] border-b border-r border-outline-variant/40 {{ $d['is_today'] ? 'bg-primary/15' : ($d['is_weekend'] ? 'bg-rose-500/10' : 'bg-surface-container-low') }}">
                            <div class="text-[10px] uppercase font-bold tracking-wider {{ $d['is_weekend'] ? 'text-rose-600' : 'text-on-surface-variant' }}">
                                {{ strtoupper($d['day_name']) }}
                            </div>
                            <div class="text-xs font-mono font-black mt-0.5 flex items-center justify-center">
                                @if($d['is_today'])
                                    <span class="w-6 h-6 rounded-full bg-primary text-white flex items-center justify-center text-[11px] font-bold shadow-xs">
                                        {{ $d['day_num'] }}
                                    </span>
                                @else
                                    <span class="{{ $d['is_weekend'] ? 'text-rose-700' : 'text-on-surface' }}">
                                        {{ $d['day_num'] }}
                                    </span>
                                @endif
                            </div>
                        </th>
                        @endforeach
                    </tr>
                </thead>

                <!-- Body: Rows of Employees -->
                <tbody class="divide-y divide-outline-variant/60">
                    @foreach($users as $emp)
                    <tr class="group hover:bg-surface-container-low/50 transition-colors">
                        <!-- Sticky Frozen Employee Column -->
                        <td class="py-2.5 px-4 sticky left-0 z-10 bg-surface-container-lowest group-hover:bg-surface-container-low transition-colors min-w-[260px] max-w-[260px] w-[260px] border-b border-r-2 border-outline-variant/80 shadow-[4px_0_10px_-2px_rgba(0,0,0,0.08)]">
                            <div class="flex items-center gap-3">
                                @if($emp->photo)
                                    <img src="{{ Storage::url($emp->photo) }}" alt="{{ $emp->name }}" class="w-8 h-8 rounded-full object-cover border border-outline-variant shrink-0 shadow-2xs">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-[#006c49] text-white flex items-center justify-center font-black text-xs shrink-0 shadow-2xs">
                                        {{ strtoupper(substr($emp->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <span class="font-bold text-xs text-on-surface truncate block" title="{{ $emp->name }}">
                                        {{ $emp->name }}
                                    </span>
                                    <div class="flex items-center gap-1.5 text-[10px] text-on-surface-variant truncate mt-0.5">
                                        <span class="font-medium">{{ $emp->position ?: 'Staff' }}</span>
                                        @if($emp->stores->first())
                                            <span>•</span>
                                            <span class="truncate opacity-80">{{ $emp->stores->first()->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Date Cells -->
                        @foreach($dates as $d)
                        @php
                            $scheduleKey = $emp->id . '_' . $d['date_str'];
                            $sched = $schedules->get($scheduleKey);
                        @endphp
                        <td class="p-1 text-center border-b border-r border-outline-variant/30 min-w-[88px] w-[88px] {{ $d['is_today'] ? 'bg-primary/[0.04]' : ($d['is_weekend'] ? 'bg-rose-500/[0.02]' : '') }}">
                            @can('User Schedule Manage')
                            <button 
                                type="button" 
                                onclick='openCellEditModal(@json($emp), @json($d), @json($sched))'
                                class="w-full h-12 rounded-lg flex flex-col items-center justify-center p-0.5 transition-all cursor-pointer hover:scale-[1.03] active:scale-95 select-none relative group/btn"
                                title="{{ $sched ? ($sched->is_off ? 'Libur (OFF)' : ($sched->shift?->name ?? 'Shift')) . ($sched->note ? ' • Catatan: ' . $sched->note : '') : 'Klik untuk jadwalkan' }}"
                            >
                                @if($sched)
                                    @if($sched->is_off)
                                        <div class="w-full h-full rounded-md bg-slate-100 hover:bg-slate-200/90 text-slate-700 border border-slate-300/80 flex items-center justify-center font-black tracking-wider text-[11px] transition-colors shadow-2xs">
                                            OFF
                                        </div>
                                    @elseif($sched->shift)
                                        <div class="w-full h-full rounded-md text-white shadow-2xs flex flex-col items-center justify-center leading-tight px-1 py-1 transition-all" style="background-color: {{ $sched->shift->color ?: '#006c49' }}">
                                            <span class="font-extrabold text-[10.5px] truncate max-w-full tracking-tight">
                                                {{ $sched->shift->code ?: $sched->shift->name }}
                                            </span>
                                            <span class="text-[9px] font-mono opacity-90 tracking-tight whitespace-nowrap mt-0.5">
                                                {{ \Carbon\Carbon::parse($sched->shift->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($sched->shift->end_time)->format('H:i') }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="w-full h-full rounded-md bg-surface-container text-on-surface-variant flex items-center justify-center font-bold text-xs">?</div>
                                    @endif

                                    @if($sched->note)
                                        <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-amber-400 ring-1 ring-white" title="Catatan: {{ $sched->note }}"></span>
                                    @endif
                                @else
                                    <div class="w-full h-full rounded-md border-2 border-dashed border-outline-variant/60 text-outline-variant hover:border-primary hover:text-primary hover:bg-primary/5 flex items-center justify-center transition-colors">
                                        <span class="material-symbols-outlined text-[16px]">add</span>
                                    </div>
                                @endif
                            </button>
                            @else
                            <div class="w-full h-12 rounded-lg flex flex-col items-center justify-center p-0.5 select-none cursor-default relative">
                                @if($sched)
                                    @if($sched->is_off)
                                        <div class="w-full h-full rounded-md bg-slate-100 text-slate-700 border border-slate-300/80 flex items-center justify-center font-black tracking-wider text-[11px] shadow-2xs">
                                            OFF
                                        </div>
                                    @elseif($sched->shift)
                                        <div class="w-full h-full rounded-md text-white shadow-2xs flex flex-col items-center justify-center leading-tight px-1 py-1" style="background-color: {{ $sched->shift->color ?: '#006c49' }}">
                                            <span class="font-extrabold text-[10.5px] truncate max-w-full tracking-tight">
                                                {{ $sched->shift->code ?: $sched->shift->name }}
                                            </span>
                                            <span class="text-[9px] font-mono opacity-90 tracking-tight whitespace-nowrap mt-0.5">
                                                {{ \Carbon\Carbon::parse($sched->shift->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($sched->shift->end_time)->format('H:i') }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="w-full h-full rounded-md bg-surface-container text-on-surface-variant flex items-center justify-center font-bold text-xs">?</div>
                                    @endif

                                    @if($sched->note)
                                        <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-amber-400 ring-1 ring-white" title="Catatan: {{ $sched->note }}"></span>
                                    @endif
                                @else
                                    <div class="w-full h-full rounded-md text-outline-variant/40 flex items-center justify-center text-xs">—</div>
                                @endif
                            </div>
                            @endcan
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<!-- Modal Quick Cell Edit (Single Schedule Assignment) -->
<div id="cell-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs hidden">
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl w-full max-w-sm shadow-2xl p-5 space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-outline-variant/60">
            <div>
                <h3 id="cell-modal-emp-name" class="font-black text-sm text-on-surface">Penjadwalan Shift</h3>
                <p id="cell-modal-date" class="text-[11px] text-on-surface-variant font-medium"></p>
            </div>
            <button type="button" onclick="closeCellModal()" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>

        <form id="cell-form" action="{{ route('commercial.schedules.store') }}" method="POST" class="space-y-3">
            @csrf
            <input type="hidden" name="user_id" id="cell-user-id">
            <input type="hidden" name="date" id="cell-date">
            <input type="hidden" name="store_id" id="cell-store-id">
            <input type="hidden" name="is_off" id="cell-is-off" value="0">

            <!-- Pilihan Status Shift -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-on-surface">Pilih Shift Kerja:</label>
                
                <div class="space-y-1.5 max-h-48 overflow-y-auto">
                    @foreach($shifts as $sh)
                    <label class="flex items-center gap-2 p-2 rounded-xl bg-surface-container-low hover:bg-surface-container border border-outline-variant/70 cursor-pointer text-xs">
                        <input type="radio" name="shift_id" value="{{ $sh->id }}" onchange="document.getElementById('cell-is-off').value='0'" class="cell-radio-shift text-primary focus:ring-primary">
                        <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $sh->color ?: '#006c49' }}"></span>
                        <div class="flex-1 truncate">
                            <span class="font-bold text-on-surface block">{{ $sh->name }}</span>
                            <span class="text-[10px] text-on-surface-variant font-mono">{{ \Carbon\Carbon::parse($sh->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($sh->end_time)->format('H:i') }} WIB</span>
                        </div>
                    </label>
                    @endforeach

                    <!-- Option OFF -->
                    <label class="flex items-center gap-2 p-2 rounded-xl bg-surface-container-low hover:bg-surface-container border border-outline-variant/70 cursor-pointer text-xs">
                        <input type="radio" name="shift_id" value="OFF" id="cell-radio-off" onchange="document.getElementById('cell-is-off').value='1'" class="text-primary focus:ring-primary">
                        <span class="w-3 h-3 rounded-full bg-slate-400 shrink-0"></span>
                        <div class="flex-1">
                            <span class="font-bold text-on-surface block">Libur (Jadwal OFF)</span>
                            <span class="text-[10px] text-on-surface-variant">Hari istirahat rutin mingguan</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Catatan -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Catatan Jadwal</label>
                <input 
                    type="text" 
                    name="note" 
                    id="cell-note" 
                    placeholder="Opsional: e.g. Tukar shift..." 
                    class="w-full px-3 py-1.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                />
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-2 border-t border-outline-variant/60">
                <button type="button" onclick="resetCellSchedule()" class="text-xs text-rose-700 hover:underline font-bold">
                    Hapus / Reset
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeCellModal()" class="px-3 py-1.5 rounded-xl text-xs font-bold text-on-surface hover:bg-surface-container border border-outline-variant">
                        Batal
                    </button>
                    <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] shadow-xs">
                        Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Bulk Generate Schedules from Pattern -->
<div id="bulk-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs hidden overflow-y-auto">
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl w-full max-w-lg shadow-2xl p-6 space-y-4 my-8">
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">auto_fix_high</span>
                <h3 class="font-black text-base text-on-surface">Terapkan Pola Shift Otomatis</h3>
            </div>
            <button type="button" onclick="closeBulkGenerateModal()" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('commercial.schedules.generate') }}" method="POST" class="space-y-4">
            @csrf

            <!-- 1. Pilih Shift Pattern -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Pilih Pola Shift <span class="text-rose-600">*</span></label>
                <select name="shift_pattern_id" required class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                    <option value="">-- Pilih Template Pola Rotasi Shift --</option>
                    @foreach($patterns as $pat)
                        <option value="{{ $pat->id }}">{{ $pat->name }} (Siklus {{ $pat->cycle_days }} Hari) {{ $pat->store ? '• ' . $pat->store->name : '• Global' }}</option>
                    @endforeach
                </select>
                @if($patterns->isEmpty())
                <div class="mt-2 p-2.5 bg-amber-500/10 border border-amber-500/30 rounded-xl flex items-center justify-between text-xs text-amber-800 dark:text-amber-300">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[17px] text-amber-600 shrink-0">info</span>
                        <span>Belum ada template pola shift.</span>
                    </div>
                    <a href="{{ route('commercial.shift-patterns.index') }}" class="font-bold underline text-primary hover:opacity-80 shrink-0 ml-2">
                        Buat Pola Shift &rarr;
                    </a>
                </div>
                @endif
            </div>

            <!-- 2. Rentang Tanggal Target -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Tanggal Mulai <span class="text-rose-600">*</span></label>
                    <input 
                        type="date" 
                        name="start_date" 
                        required 
                        value="{{ $startDate->toDateString() }}" 
                        class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                    />
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Tanggal Selesai <span class="text-rose-600">*</span></label>
                    <input 
                        type="date" 
                        name="end_date" 
                        required 
                        value="{{ $endDate->toDateString() }}" 
                        class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                    />
                </div>
            </div>

            <!-- 3. Mulai dari Hari Siklus ke- -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Mulai Hari Siklus ke-</label>
                <input 
                    type="number" 
                    name="start_day_number" 
                    value="1" 
                    min="1" 
                    max="31" 
                    class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary"
                />
                <p class="text-[10px] text-on-surface-variant mt-1">Gunakan angka 1 jika perputaran rotasi dimulai dari Hari #1 pola shift.</p>
            </div>

            <!-- 4. Pilih Karyawan Target -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-on-surface">Pilih Karyawan Target <span class="text-rose-600">*</span></label>
                    <button type="button" onclick="toggleSelectAllEmployees()" class="text-xs text-primary font-bold hover:underline">
                        Pilih Semua
                    </button>
                </div>
                <div class="max-h-48 overflow-y-auto p-2 bg-surface-container-low/60 rounded-xl border border-outline-variant/70 space-y-1.5">
                    @foreach($users as $emp)
                    <label class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-surface-container cursor-pointer text-xs">
                        <input type="checkbox" name="user_ids[]" value="{{ $emp->id }}" checked class="bulk-emp-checkbox w-4 h-4 rounded text-primary focus:ring-primary border-outline-variant">
                        <span class="font-bold text-on-surface">{{ $emp->name }}</span>
                        <span class="text-[10px] text-on-surface-variant">({{ $emp->position ?: 'Staff' }} • {{ $emp->stores->first()?->name ?? 'Toko' }})</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-outline-variant/60">
                <button type="button" onclick="closeBulkGenerateModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-on-surface hover:bg-surface-container border border-outline-variant">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] shadow-xs cursor-pointer">
                    Generate Jadwal Roster
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openCellEditModal(emp, dateObj, sched) {
        document.getElementById('cell-user-id').value = emp.id;
        document.getElementById('cell-date').value = dateObj.date_str;
        document.getElementById('cell-store-id').value = emp.stores && emp.stores.length > 0 ? emp.stores[0].id : '';

        document.getElementById('cell-modal-emp-name').textContent = emp.name;
        document.getElementById('cell-modal-date').textContent = dateObj.formatted_date || (dateObj.day_name + ', ' + dateObj.day_num);

        // Reset radio
        document.querySelectorAll('.cell-radio-shift').forEach(r => r.checked = false);
        document.getElementById('cell-radio-off').checked = false;
        document.getElementById('cell-is-off').value = '0';
        document.getElementById('cell-note').value = sched ? (sched.note || '') : '';

        if (sched) {
            if (sched.is_off) {
                document.getElementById('cell-radio-off').checked = true;
                document.getElementById('cell-is-off').value = '1';
            } else if (sched.shift_id) {
                const radio = document.querySelector(`.cell-radio-shift[value="${sched.shift_id}"]`);
                if (radio) radio.checked = true;
            }
        }

        document.getElementById('cell-modal').classList.remove('hidden');
    }

    function closeCellModal() {
        document.getElementById('cell-modal').classList.add('hidden');
    }

    function resetCellSchedule() {
        if (!confirm('Hapus jadwal karyawan pada tanggal ini?')) return;
        const form = document.getElementById('cell-form');
        document.querySelectorAll('.cell-radio-shift').forEach(r => r.checked = false);
        document.getElementById('cell-radio-off').checked = false;
        document.getElementById('cell-is-off').value = '0';
        form.submit();
    }

    function openBulkGenerateModal() {
        document.getElementById('bulk-modal').classList.remove('hidden');
    }

    function closeBulkGenerateModal() {
        document.getElementById('bulk-modal').classList.add('hidden');
    }

    let allSelected = true;
    function toggleSelectAllEmployees() {
        allSelected = !allSelected;
        document.querySelectorAll('.bulk-emp-checkbox').forEach(cb => cb.checked = allSelected);
    }
</script>
@endpush
@endsection
