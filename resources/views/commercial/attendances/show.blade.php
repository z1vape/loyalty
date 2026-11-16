@extends('layouts.app')

@section('title', 'Detail Kehadiran - ' . $user->name)

@section('content')
<div class="space-y-6">
    @php
        $userIdentifier = $user->username ?: ($user->employee_number ?: 'ID-' . $user->id);
    @endphp

    <!-- Breadcrumb & Top Action Header (Matching Image 3) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <!-- Breadcrumb & Employee Identity -->
        <div class="flex items-center gap-3">
            <a 
                href="{{ route('commercial.attendances.index', request()->except('user')) }}" 
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-surface-container-low hover:bg-surface-container text-on-surface border border-outline-variant transition-all cursor-pointer shadow-xs no-underline group shrink-0"
                title="Kembali ke Daftar Karyawan"
            >
                <span class="material-symbols-outlined text-base group-hover:-translate-x-0.5 transition-transform">arrow_back</span>
                <span>Karyawan</span>
            </a>

            <span class="text-outline-variant font-bold text-sm">/</span>

            <div class="flex items-center gap-2.5">
                @if($user->photo)
                    <img 
                        src="{{ Storage::url($user->photo) }}" 
                        alt="{{ $user->name }}" 
                        class="w-8 h-8 rounded-full object-cover border border-outline-variant shadow-xs shrink-0"
                    />
                @else
                    <div class="w-8 h-8 rounded-full bg-[#006c49] text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-base sm:text-lg font-black text-on-surface tracking-tight">{{ strtoupper($user->name) }}</h1>
                        <span class="font-mono text-xs font-bold text-on-surface-variant bg-surface-container-low px-2 py-0.5 rounded-md border border-outline-variant/60" title="ID Username">
                            {{ $userIdentifier }}
                        </span>
                    </div>
                    <p class="text-[11px] text-on-surface-variant">
                        {{ $user->position ?: 'Staff' }} • {{ $user->stores->first()?->name ?? 'Z1 Store' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Export Excel for this employee -->
        <div class="flex items-center gap-2 shrink-0">
            <a 
                href="{{ route('commercial.attendances.export', array_merge(['user_id' => $user->id], request()->query())) }}" 
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] transition-all cursor-pointer shadow-xs active:scale-95 no-underline"
            >
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Ekspor Excel Karyawan Ini</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 sm:p-5 shadow-xs">
        <form action="{{ route('commercial.attendances.show', $user) }}" method="GET" id="attendance-detail-filter-form" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <!-- 1. Lokasi Kerja (Store) (span 3) -->
            <div class="lg:col-span-3">
                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Lokasi Kerja</label>
                <select name="store_id" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary transition-colors">
                    <option value="">Semua Lokasi Toko</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 2. Bulan (span 2) -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Bulan</label>
                <select name="month" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary transition-colors">
                    @foreach($months as $mNum => $mName)
                        <option value="{{ $mNum }}" {{ $month == $mNum ? 'selected' : '' }}>{{ $mName }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Tahun (span 2) -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Tahun</label>
                <select name="year" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary transition-colors">
                    @foreach($years as $yr)
                        <option value="{{ $yr }}" {{ $year == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 4. Tanggal Range (span 4) -->
            <div class="lg:col-span-4">
                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Rentang Tanggal</label>
                <div class="grid grid-cols-2 gap-2">
                    <input 
                        type="date" 
                        name="start_date" 
                        value="{{ $startDate }}" 
                        class="w-full px-2.5 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:border-primary"
                        title="Tanggal Mulai"
                    />
                    <input 
                        type="date" 
                        name="end_date" 
                        value="{{ $endDate }}" 
                        class="w-full px-2.5 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:border-primary"
                        title="Tanggal Selesai"
                    />
                </div>
            </div>

            <!-- 5. Actions (span 1) -->
            <div class="lg:col-span-1">
                <button type="submit" class="w-full py-2 px-3 bg-secondary text-white font-bold text-xs rounded-xl hover:bg-[#00553a] transition-all cursor-pointer shadow-xs flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-[17px]">filter_alt</span>
                    <span>Terapkan</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Attendance Log Table Card (Matching Image 3) -->
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs space-y-4">
        <!-- Summary Stats Pills -->
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-outline-variant/60">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-extrabold text-on-surface">Total:</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-primary/10 text-primary border border-primary/20">
                        {{ $totalRecords }} Hari
                    </span>
                </div>
                <span class="text-xs text-on-surface-variant font-medium">
                    Periode: <strong class="text-on-surface">{{ \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('D MMMM Y') }} — {{ \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('D MMMM Y') }}</strong>
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-800 font-bold border border-emerald-500/20">
                    {{ $totalPresent }} Hadir
                </span>
                @if($totalLate > 0)
                <span class="px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-800 font-bold border border-amber-500/20">
                    {{ $totalLate }} Terlambat
                </span>
                @endif
                <span class="px-2.5 py-1 rounded-lg bg-slate-500/10 text-slate-700 font-bold border border-slate-500/20">
                    {{ $totalOff }} Libur (OFF)
                </span>
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-container-low text-on-surface-variant text-[11px] font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-3.5">
                            <div class="flex items-center gap-1">
                                <span>Karyawan</span>
                                <span class="material-symbols-outlined text-xs">swap_vert</span>
                            </div>
                        </th>
                        <th class="py-3 px-3.5">
                            <div class="flex items-center gap-1">
                                <span>Tanggal</span>
                                <span class="material-symbols-outlined text-xs">arrow_upward</span>
                            </div>
                        </th>
                        <th class="py-3 px-3.5">
                            <div class="flex items-center gap-1">
                                <span>Shift</span>
                                <span class="material-symbols-outlined text-xs">swap_vert</span>
                            </div>
                        </th>
                        <th class="py-3 px-3.5">
                            <div class="flex items-center gap-1">
                                <span>Clock In / Clock Out</span>
                                <span class="material-symbols-outlined text-xs">swap_vert</span>
                            </div>
                        </th>
                        <th class="py-3 px-3.5 text-center">Status</th>
                        <th class="py-3 px-3.5">Catatan</th>
                        <th class="py-3 px-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60 font-medium">
                    @foreach($records as $rec)
                    <tr class="hover:bg-surface-container-low/70 transition-colors {{ $rec['is_off'] ? 'opacity-80' : '' }}">
                        <!-- 1. Karyawan -->
                        <td class="py-3.5 px-3.5">
                            <div class="flex items-center gap-2.5">
                                @if($user->photo)
                                    <img 
                                        src="{{ Storage::url($user->photo) }}" 
                                        alt="{{ $user->name }}" 
                                        class="w-7 h-7 rounded-full object-cover border border-outline-variant shadow-xs shrink-0"
                                    />
                                @else
                                    <div class="w-7 h-7 rounded-full bg-[#006c49] text-white flex items-center justify-center font-bold text-[10px] shadow-xs shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <span class="font-extrabold text-xs text-on-surface block">{{ strtoupper($user->name) }}</span>
                                    <span class="font-mono text-[10px] text-on-surface-variant block">{{ $userIdentifier }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- 2. Tanggal -->
                        <td class="py-3.5 px-3.5 whitespace-nowrap">
                            <div class="font-bold text-on-surface text-xs">{{ $rec['date_formatted'] }}</div>
                            <span class="text-[10px] text-on-surface-variant">{{ $rec['day_name'] }}</span>
                        </td>

                        <!-- 3. Shift -->
                        <td class="py-3.5 px-3.5 whitespace-nowrap">
                            @if($rec['is_off'])
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-slate-200 text-slate-700">
                                    OFF
                                </span>
                            @else
                                <div class="font-bold text-xs text-on-surface">{{ $rec['shift_time'] }}</div>
                                <span class="text-[10px] text-on-surface-variant">{{ $rec['shift_name'] }}</span>
                            @endif
                        </td>

                        <!-- 4. Clock In / Clock Out (With Store Location Pin matching Image 3) -->
                        <td class="py-3.5 px-3.5 whitespace-nowrap space-y-1">
                            <!-- Clock In -->
                            <div class="flex flex-col">
                                <div class="flex items-center gap-1.5 text-xs">
                                    <span class="text-[10px] font-black text-emerald-700 tracking-wider">CLOCK IN</span>
                                    @if($rec['clock_in_time'])
                                        <span class="font-mono font-extrabold text-emerald-950">{{ $rec['clock_in_time'] }}</span>
                                    @else
                                        <span class="font-mono text-on-surface-variant/50">—</span>
                                    @endif
                                </div>
                                @if($rec['clock_in_time'])
                                <div class="flex items-center gap-0.5 text-[10px] text-rose-600 font-medium">
                                    <span class="material-symbols-outlined text-[13px] text-rose-600">location_on</span>
                                    <span class="truncate max-w-[180px]">{{ $rec['store_name'] }}</span>
                                </div>
                                @endif
                            </div>

                            <!-- Clock Out -->
                            <div class="flex flex-col pt-0.5">
                                <div class="flex items-center gap-1.5 text-xs">
                                    <span class="text-[10px] font-black text-rose-700 tracking-wider">CLOCK OUT</span>
                                    @if($rec['clock_out_time'])
                                        <span class="font-mono font-extrabold text-rose-950">{{ $rec['clock_out_time'] }}</span>
                                    @else
                                        <span class="font-mono text-on-surface-variant/50">—</span>
                                    @endif
                                </div>
                                @if($rec['clock_out_time'])
                                <div class="flex items-center gap-0.5 text-[10px] text-rose-600 font-medium">
                                    <span class="material-symbols-outlined text-[13px] text-rose-600">location_on</span>
                                    <span class="truncate max-w-[180px]">{{ $rec['store_name'] }}</span>
                                </div>
                                @endif
                            </div>
                        </td>

                        <!-- 5. STATUS Badges (PRS, EAI, OVT, LATE, OFF, ABS) -->
                        <td class="py-3.5 px-3.5 text-center whitespace-nowrap">
                            <div class="inline-flex flex-wrap items-center justify-center gap-1">
                                @foreach($rec['badges'] as $b)
                                    @php
                                        $badgeStyles = match($b['color']) {
                                            'emerald' => 'bg-emerald-500/15 text-emerald-800 border-emerald-500/30',
                                            'sky'     => 'bg-sky-500/15 text-sky-800 border-sky-500/30',
                                            'indigo'  => 'bg-indigo-500/15 text-indigo-800 border-indigo-500/30',
                                            'amber'   => 'bg-amber-500/15 text-amber-800 border-amber-500/30',
                                            'slate'   => 'bg-slate-200 text-slate-700 border-slate-300',
                                            default   => 'bg-rose-500/15 text-rose-800 border-rose-500/30',
                                        };
                                    @endphp
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-black border uppercase tracking-wider {{ $badgeStyles }}" title="{{ $b['label'] }}">
                                        @if($b['code'] === 'LATE' && !empty($rec['detail_data']['late_duration']))
                                            LATE ({{ $rec['detail_data']['late_duration'] }})
                                        @elseif($b['code'] === 'OVT' && !empty($rec['attendance']->overtime_minutes) && $rec['attendance']->overtime_minutes >= 60)
                                            OVT ({{ intdiv($rec['attendance']->overtime_minutes, 60) }}j)
                                        @else
                                            {{ $b['code'] }}
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </td>

                        <!-- 6. Catatan -->
                        <td class="py-3.5 px-3.5 text-on-surface-variant text-[11px] truncate max-w-[150px]">
                            {{ $rec['note'] ?: '—' }}
                        </td>

                        <!-- 7. Aksi (Detail & Edit Jam Kehadiran) -->
                        <td class="py-3.5 px-3.5 text-center whitespace-nowrap">
                            <div class="inline-flex items-center justify-center gap-1.5">
                                <button 
                                    type="button" 
                                    onclick='openAttendanceDetailModal(@json($rec['detail_data']))'
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-surface-container hover:bg-primary hover:text-white text-on-surface transition-all cursor-pointer shadow-xs group"
                                    title="Lihat Detail Foto Clock In & Clock Out"
                                >
                                    <span class="material-symbols-outlined text-[19px] group-hover:scale-110 transition-transform">visibility</span>
                                </button>
                                <button 
                                    type="button" 
                                    onclick='openEditAttendanceModal(@json($rec['edit_data']))'
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-500/10 hover:bg-amber-600 hover:text-white text-amber-700 transition-all cursor-pointer shadow-xs group"
                                    title="Edit Jam Clock In & Clock Out"
                                >
                                    <span class="material-symbols-outlined text-[19px] group-hover:scale-110 transition-transform">edit</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Detail Absensi (Show: Tanggal | Shift Kerja, Foto Clock In, Foto Clock Out) -->
<dialog id="modal-attendance-detail" class="rounded-2xl p-0 bg-surface-container-lowest border border-outline-variant shadow-2xl backdrop:bg-black/75 backdrop:backdrop-blur-sm w-[94vw] max-w-2xl m-auto overflow-hidden">
    <!-- Modal Header -->
    <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/60 bg-surface-container-low">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-lg">visibility</span>
            </div>
            <div>
                <h4 class="font-extrabold text-sm text-on-surface">Detail Kehadiran Karyawan</h4>
                <p id="att-detail-user-store" class="text-[11px] text-on-surface-variant leading-none mt-0.5">—</p>
            </div>
        </div>
        <button type="button" onclick="closeAttendanceDetailModal()" class="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-on-surface flex items-center justify-center cursor-pointer transition-colors" title="Tutup">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
    </div>

    <!-- Modal Body -->
    <div class="p-5 space-y-4 max-h-[80vh] overflow-y-auto custom-slim-scroll">
        <!-- 1. Section: Tanggal | Shift Kerja Banner -->
        <div class="p-4 rounded-xl bg-gradient-to-r from-surface-container-low via-surface-container-lowest to-surface-container-low border border-outline-variant/80 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#006c49] text-white flex items-center justify-center shadow-xs shrink-0">
                        <span class="material-symbols-outlined text-xl">event_available</span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span id="att-detail-date" class="font-black text-sm text-on-surface">Tanggal</span>
                            <span class="text-outline-variant font-bold hidden sm:inline">|</span>
                            <span id="att-detail-shift" class="font-extrabold text-xs text-primary px-2.5 py-0.5 rounded-md bg-primary/10 border border-primary/20">Shift Kerja</span>
                        </div>
                        <p id="att-detail-shift-time" class="text-[11px] text-on-surface-variant font-medium mt-0.5">Jam Shift: —</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-center">
                    <span id="att-detail-status" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-500/10 text-emerald-700 border border-emerald-500/20">
                        Status
                    </span>
                    <span id="att-detail-duration" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-surface-container-high text-on-surface border border-outline-variant">
                        0j 0m
                    </span>
                </div>
            </div>
        </div>

        <!-- 2. Section: Foto Clock In & Foto Clock Out (2 Columns) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Foto Clock In Box -->
            <div class="rounded-xl border border-outline-variant bg-surface-container-lowest overflow-hidden flex flex-col shadow-xs">
                <div class="px-3.5 py-2.5 bg-emerald-500/10 border-b border-emerald-500/20 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-emerald-800 font-extrabold text-xs">
                        <span class="material-symbols-outlined text-base text-emerald-700">login</span>
                        <span>Foto Clock In</span>
                    </div>
                    <span id="att-detail-clockin-time" class="text-[11px] font-bold text-emerald-800 bg-white/80 px-2 py-0.5 rounded border border-emerald-500/20">
                        —
                    </span>
                </div>

                <div class="p-3 bg-surface-container-lowest flex-1 flex flex-col items-center justify-center min-h-[220px]">
                    <div id="att-detail-clockin-img-wrapper" class="w-full relative group">
                        <img 
                            id="att-detail-clockin-img" 
                            src="" 
                            alt="Foto Clock In" 
                            class="w-full h-52 rounded-lg object-cover border border-outline-variant shadow-xs transition-all cursor-pointer group-hover:opacity-90"
                            onclick="zoomDetailPhoto('in')"
                        >
                        <div 
                            onclick="zoomDetailPhoto('in')"
                            class="absolute inset-0 bg-black/40 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer text-white gap-1.5 text-xs font-bold"
                        >
                            <span class="material-symbols-outlined text-base">zoom_in</span>
                            <span>Perbesar Foto</span>
                        </div>
                    </div>
                    <div id="att-detail-clockin-empty" class="hidden flex-col items-center justify-center py-10 text-on-surface-variant/60 text-center">
                        <span class="material-symbols-outlined text-4xl mb-1 text-on-surface-variant/40">no_photography</span>
                        <p class="text-xs font-semibold">Tidak ada foto Clock In</p>
                    </div>
                </div>

                <div id="att-detail-clockin-meta" class="px-3.5 py-2 bg-surface-container-low border-t border-outline-variant/60 text-[11px] text-on-surface-variant flex items-center justify-between">
                    <span class="inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs text-primary">pin_drop</span>
                        <span id="att-detail-clockin-distance">Lokasi GPS</span>
                    </span>
                    <a id="att-detail-clockin-maplink" href="#" target="_blank" class="text-primary font-bold hover:underline hidden">
                        Lihat Maps
                    </a>
                </div>
            </div>

            <!-- Foto Clock Out Box -->
            <div class="rounded-xl border border-outline-variant bg-surface-container-lowest overflow-hidden flex flex-col shadow-xs">
                <div class="px-3.5 py-2.5 bg-red-500/10 border-b border-red-500/20 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-error font-extrabold text-xs">
                        <span class="material-symbols-outlined text-base text-error">logout</span>
                        <span>Foto Clock Out</span>
                    </div>
                    <span id="att-detail-clockout-time" class="text-[11px] font-bold text-error bg-white/80 px-2 py-0.5 rounded border border-red-500/20">
                        —
                    </span>
                </div>

                <div class="p-3 bg-surface-container-lowest flex-1 flex flex-col items-center justify-center min-h-[220px]">
                    <div id="att-detail-clockout-img-wrapper" class="w-full relative group">
                        <img 
                            id="att-detail-clockout-img" 
                            src="" 
                            alt="Foto Clock Out" 
                            class="w-full h-52 rounded-lg object-cover border border-outline-variant shadow-xs transition-all cursor-pointer group-hover:opacity-90"
                            onclick="zoomDetailPhoto('out')"
                        >
                        <div 
                            onclick="zoomDetailPhoto('out')"
                            class="absolute inset-0 bg-black/40 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer text-white gap-1.5 text-xs font-bold"
                        >
                            <span class="material-symbols-outlined text-base">zoom_in</span>
                            <span>Perbesar Foto</span>
                        </div>
                    </div>
                    <div id="att-detail-clockout-empty" class="hidden flex-col items-center justify-center py-10 text-on-surface-variant/60 text-center">
                        <span class="material-symbols-outlined text-4xl mb-1 text-on-surface-variant/40">no_photography</span>
                        <p class="text-xs font-semibold">Tidak ada foto Clock Out</p>
                    </div>
                </div>

                <div id="att-detail-clockout-meta" class="px-3.5 py-2 bg-surface-container-low border-t border-outline-variant/60 text-[11px] text-on-surface-variant flex items-center justify-between">
                    <span class="inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs text-error">pin_drop</span>
                        <span id="att-detail-clockout-distance">Lokasi GPS</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- 3. Section: Catatan (Jika ada) -->
        <div id="att-detail-note-container" class="p-3 rounded-xl bg-surface-container-low border border-outline-variant/60 text-xs text-on-surface-variant hidden">
            <span class="font-bold text-on-surface block mb-0.5">Catatan Kehadiran:</span>
            <p id="att-detail-note" class="italic text-[11px] leading-relaxed"></p>
        </div>
    </div>

    <!-- Modal Footer -->
    <div class="px-5 py-3 border-t border-outline-variant/60 bg-surface-container-low flex items-center justify-between">
        <button type="button" onclick="editFromDetailModal()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-amber-500/10 hover:bg-amber-600 hover:text-white text-amber-700 text-xs font-bold rounded-xl transition-all cursor-pointer border border-amber-500/20">
            <span class="material-symbols-outlined text-base">edit</span>
            <span>Edit Jam Absensi</span>
        </button>
        <button type="button" onclick="closeAttendanceDetailModal()" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-bold rounded-xl transition-all cursor-pointer">
            Tutup
        </button>
    </div>
</dialog>

<!-- Modal: Edit Jam Absensi Karyawan (Manual Koreksi Admin) -->
<dialog id="modal-edit-attendance" class="rounded-2xl p-0 bg-surface-container-lowest border border-outline-variant shadow-2xl backdrop:bg-black/75 backdrop:backdrop-blur-sm w-[94vw] max-w-xl m-auto overflow-hidden">
    <form action="{{ route('commercial.attendances.save-record', $user) }}" method="POST">
        @csrf
        <input type="hidden" name="date" id="edit-att-date" value="">
        <input type="hidden" name="store_id" id="edit-att-store-id" value="">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/60 bg-surface-container-low">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-700 flex items-center justify-center font-bold">
                    <span class="material-symbols-outlined text-lg">edit_calendar</span>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-on-surface">Koreksi Jam & Kehadiran</h4>
                    <p class="text-[11px] text-on-surface-variant leading-none mt-0.5">{{ $user->name }}</p>
                </div>
            </div>
            <button type="button" onclick="closeEditAttendanceModal()" class="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-on-surface flex items-center justify-center cursor-pointer transition-colors" title="Tutup">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-5 space-y-4 max-h-[80vh] overflow-y-auto custom-slim-scroll">
            <!-- Date info banner -->
            <div class="p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/70 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-primary text-xl">calendar_today</span>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider font-extrabold text-on-surface-variant">Tanggal Absensi</div>
                        <div id="edit-att-date-display" class="font-extrabold text-sm text-on-surface">—</div>
                    </div>
                </div>
                <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-primary/10 text-primary border border-primary/20">
                    Koreksi Admin
                </span>
            </div>

            <!-- Shift & Status -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1.5">Shift Kerja</label>
                    <select name="shift_id" id="edit-att-shift-id" class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant focus:outline-hidden focus:border-primary">
                        <option value="">-- Tanpa Shift --</option>
                        @foreach($shifts as $s)
                            <option value="{{ $s->id }}" data-start="{{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}" data-end="{{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}" data-overnight="{{ $s->is_overnight ? '1' : '0' }}">
                                {{ $s->name }} ({{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1.5">Status Kehadiran</label>
                    <select name="status" id="edit-att-status" class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant focus:outline-hidden focus:border-primary">
                        <option value="present">Hadir</option>
                        <option value="late">Terlambat</option>
                        <option value="permission">Izin</option>
                        <option value="sick">Sakit</option>
                        <option value="annual_leave">Cuti</option>
                        <option value="holiday">Libur (OFF)</option>
                        <option value="absent">Alpha / Tidak Hadir</option>
                    </select>
                </div>
            </div>

            <!-- Clock In & Clock Out Section -->
            <div class="p-4 rounded-xl border border-outline-variant/80 bg-surface-container-lowest space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-outline-variant/50">
                    <span class="text-xs font-black text-on-surface flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-primary">schedule</span>
                        Jam Masuk & Pulang
                    </span>
                    <span class="text-[11px] text-on-surface-variant font-medium">Format 24 Jam (JJ:MM)</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Clock In -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-emerald-800 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">login</span> Clock In
                            </label>
                            <button type="button" onclick="setShiftTime('in')" class="text-[10px] font-bold text-primary hover:underline cursor-pointer">
                                Sesuai Shift
                            </button>
                        </div>
                        <input type="time" name="clock_in_time" id="edit-att-clock-in" class="w-full text-sm font-black px-3 py-2 rounded-xl bg-surface-container-low border border-outline-variant focus:border-emerald-600 focus:outline-hidden">
                    </div>

                    <!-- Clock Out -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-error flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">logout</span> Clock Out
                            </label>
                            <button type="button" onclick="setShiftTime('out')" class="text-[10px] font-bold text-primary hover:underline cursor-pointer">
                                Sesuai Shift
                            </button>
                        </div>
                        <input type="time" name="clock_out_time" id="edit-att-clock-out" class="w-full text-sm font-black px-3 py-2 rounded-xl bg-surface-container-low border border-outline-variant focus:border-red-600 focus:outline-hidden">
                    </div>
                </div>

                <!-- Next Day Checkbox (Overnight shift) -->
                <div class="pt-2 border-t border-outline-variant/50">
                    <label class="flex items-start gap-2.5 cursor-pointer select-none">
                        <input type="checkbox" name="clock_out_next_day" id="edit-att-next-day" value="1" class="mt-0.5 rounded border-outline-variant text-primary focus:ring-primary">
                        <div>
                            <span class="text-xs font-bold text-on-surface">Clock Out melewati tengah malam (+1 Hari)</span>
                            <p class="text-[11px] text-on-surface-variant">Centang jika karyawan pulang di hari berikutnya (contoh: shift malam 16:00 - 00:00 atau pulang subuh 01:00).</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Catatan / Note -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-1.5">Catatan Koreksi (Opsional)</label>
                <textarea name="note" id="edit-att-note" rows="2" placeholder="Contoh: Lupa absen pulang, diverifikasi admin cabang..." class="w-full text-xs font-medium px-3 py-2 rounded-xl bg-surface-container-lowest border border-outline-variant focus:outline-hidden focus:border-primary resize-none"></textarea>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-5 py-3 border-t border-outline-variant/60 bg-surface-container-low flex items-center justify-between">
            <button type="button" onclick="closeEditAttendanceModal()" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-bold rounded-xl transition-all cursor-pointer">
                Batal
            </button>
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary hover:bg-primary-hover text-white text-xs font-bold rounded-xl transition-all cursor-pointer shadow-sm">
                <span class="material-symbols-outlined text-base">save</span>
                <span>Simpan Perubahan</span>
            </button>
        </div>
    </form>
</dialog>

<!-- Modal: Preview Full Size Selfie Photo -->
<dialog id="modal-view-selfie" class="rounded-2xl p-0 bg-surface-container-lowest border border-outline-variant shadow-2xl backdrop:bg-black/75 backdrop:backdrop-blur-sm w-[92vw] max-w-md m-auto overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 border-b border-outline-variant/60 bg-surface-container-low">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-base">verified</span>
            <h4 id="selfie-modal-title" class="font-bold text-xs text-on-surface">Foto Selfie Kehadiran</h4>
        </div>
        <button type="button" onclick="closeSelfieModal()" class="w-7 h-7 rounded-lg hover:bg-surface-container text-on-surface-variant flex items-center justify-center cursor-pointer transition-colors">
            <span class="material-symbols-outlined text-base">close</span>
        </button>
    </div>
    <div class="p-4 bg-slate-950 flex items-center justify-center min-h-[220px]">
        <img id="selfie-modal-img" src="" alt="Foto Selfie Absensi" class="max-h-[65vh] w-auto max-w-full rounded-xl object-contain shadow-2xl">
    </div>
    <div class="px-4 py-3 bg-surface-container-low flex justify-end">
        <button type="button" onclick="closeSelfieModal()" class="px-4 py-1.5 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-bold rounded-xl transition-all cursor-pointer">
            Tutup
        </button>
    </div>
</dialog>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const detailForm = document.getElementById('attendance-detail-filter-form');
        if (!detailForm) return;

        const monthSelect = detailForm.querySelector('select[name="month"]');
        const yearSelect = detailForm.querySelector('select[name="year"]');
        const startDateInput = detailForm.querySelector('input[name="start_date"]');
        const endDateInput = detailForm.querySelector('input[name="end_date"]');

        function syncMonthYearToDates() {
            if (!monthSelect || !yearSelect || !startDateInput || !endDateInput) return;
            const m = parseInt(monthSelect.value, 10);
            const y = parseInt(yearSelect.value, 10);
            if (!m || !y) return;

            const mm = String(m).padStart(2, '0');
            const lastDayNum = new Date(y, m, 0).getDate();
            const dd = String(lastDayNum).padStart(2, '0');

            startDateInput.value = `${y}-${mm}-01`;
            endDateInput.value = `${y}-${mm}-${dd}`;
        }

        if (monthSelect) monthSelect.addEventListener('change', syncMonthYearToDates);
        if (yearSelect) yearSelect.addEventListener('change', syncMonthYearToDates);

        if (startDateInput) {
            startDateInput.addEventListener('change', function () {
                if (!this.value) return;
                const parts = this.value.split('-');
                if (parts.length === 3) {
                    const y = parseInt(parts[0], 10);
                    const m = parseInt(parts[1], 10);
                    if (monthSelect) monthSelect.value = m;
                    if (yearSelect) yearSelect.value = y;
                }
            });
        }
    });

    let currentDetailData = null;

    function openAttendanceDetailModal(data) {
        currentDetailData = data;
        const modal = document.getElementById('modal-attendance-detail');
        if (!modal) return;

        // Header info
        const userStoreEl = document.getElementById('att-detail-user-store');
        if (userStoreEl) {
            userStoreEl.textContent = (data.user_name || 'Karyawan') + ' • ' + (data.store_name || 'Toko');
        }

        // Tanggal | Shift Kerja
        const dateEl = document.getElementById('att-detail-date');
        if (dateEl) dateEl.textContent = data.date || data.date_short || '—';

        const shiftEl = document.getElementById('att-detail-shift');
        if (shiftEl) shiftEl.textContent = data.shift || 'Shift Regular';

        const shiftTimeEl = document.getElementById('att-detail-shift-time');
        if (shiftTimeEl) shiftTimeEl.textContent = 'Jam Shift: ' + (data.shift_time || '—');

        // Status & Durasi
        const statusEl = document.getElementById('att-detail-status');
        if (statusEl) {
            let label = data.status_label || 'Hadir';
            statusEl.title = '';

            if (data.status === 'late' || (data.status_label && data.status_label.toLowerCase().includes('terlambat')) || data.late_duration || (data.late_minutes && data.late_minutes > 0)) {
                let lateText = data.late_duration;
                if (!lateText && data.late_minutes > 0) {
                    const h = Math.floor(data.late_minutes / 60);
                    const m = data.late_minutes % 60;
                    lateText = (h > 0 && m > 0) ? `${h}j ${m}m` : (h > 0 ? `${h} Jam` : `${m} Menit`);
                }
                // Fallback: calculate from shift_time & clock_in
                if (!lateText && data.shift_time && data.clock_in && data.clock_in !== '—') {
                    const shiftMatch = data.shift_time.match(/(\d{1,2}):(\d{2})/);
                    const clockMatch = data.clock_in.match(/(\d{1,2}):(\d{2})/);
                    if (shiftMatch && clockMatch) {
                        const shiftMins = parseInt(shiftMatch[1], 10) * 60 + parseInt(shiftMatch[2], 10);
                        const clockMins = parseInt(clockMatch[1], 10) * 60 + parseInt(clockMatch[2], 10);
                        const diff = clockMins - shiftMins;
                        if (diff > 0) {
                            const h = Math.floor(diff / 60);
                            const m = diff % 60;
                            lateText = (h > 0 && m > 0) ? `${h}j ${m}m` : (h > 0 ? `${h} Jam` : `${m} Menit`);
                        }
                    }
                }

                if (lateText) {
                    label = (data.status_label && data.status_label.includes(lateText))
                        ? data.status_label 
                        : `Terlambat (${lateText})`;
                    statusEl.title = data.late_duration_long ? `Terlambat ${data.late_duration_long}` : label;
                } else {
                    label = data.status_label || 'Terlambat';
                }
                statusEl.className = 'px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-500/10 text-amber-700 border border-amber-500/20';
            } else if (data.status === 'holiday') {
                statusEl.className = 'px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-200 text-slate-700 border border-slate-300';
            } else if (data.status === 'absent') {
                statusEl.className = 'px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-500/10 text-rose-700 border border-rose-500/20';
            } else {
                statusEl.className = 'px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-500/10 text-emerald-700 border border-emerald-500/20';
            }
            statusEl.textContent = label;
        }

        const durEl = document.getElementById('att-detail-duration');
        if (durEl) {
            let durText = 'Total: ' + (data.work_hours || '—');
            if (data.overtime_hours && data.overtime_hours > 0) {
                durText += ' • Lembur: ' + data.overtime_hours + ' Jam';
            }
            durEl.textContent = durText;
        }

        // Clock In Box
        const inTimeEl = document.getElementById('att-detail-clockin-time');
        if (inTimeEl) inTimeEl.textContent = data.clock_in || '—';

        const inWrapper = document.getElementById('att-detail-clockin-img-wrapper');
        const inEmpty = document.getElementById('att-detail-clockin-empty');
        const inImg = document.getElementById('att-detail-clockin-img');
        if (data.clock_in_photo) {
            if (inImg) inImg.src = data.clock_in_photo;
            if (inWrapper) inWrapper.classList.remove('hidden');
            if (inEmpty) inEmpty.classList.add('hidden');
        } else {
            if (inWrapper) inWrapper.classList.add('hidden');
            if (inEmpty) inEmpty.classList.remove('hidden');
        }

        const inDistEl = document.getElementById('att-detail-clockin-distance');
        const inMapLink = document.getElementById('att-detail-clockin-maplink');
        if (inDistEl) {
            if (data.clock_in_distance !== null && data.clock_in_distance !== undefined) {
                inDistEl.textContent = data.clock_in_distance + ' meter dari toko';
            } else if (data.clock_in_lat && data.clock_in_lng) {
                inDistEl.textContent = 'GPS terdeteksi';
            } else {
                inDistEl.textContent = 'Titik lokasi tidak tercatat';
            }
        }

        if (inMapLink) {
            if (data.clock_in_lat && data.clock_in_lng) {
                inMapLink.href = 'https://www.google.com/maps?q=' + data.clock_in_lat + ',' + data.clock_in_lng;
                inMapLink.classList.remove('hidden');
            } else {
                inMapLink.classList.add('hidden');
            }
        }

        // Clock Out Box
        const outTimeEl = document.getElementById('att-detail-clockout-time');
        if (outTimeEl) outTimeEl.textContent = data.clock_out || '—';

        const outWrapper = document.getElementById('att-detail-clockout-img-wrapper');
        const outEmpty = document.getElementById('att-detail-clockout-empty');
        const outImg = document.getElementById('att-detail-clockout-img');
        if (data.clock_out_photo) {
            if (outImg) outImg.src = data.clock_out_photo;
            if (outWrapper) outWrapper.classList.remove('hidden');
            if (outEmpty) outEmpty.classList.add('hidden');
        } else {
            if (outWrapper) outWrapper.classList.add('hidden');
            if (outEmpty) outEmpty.classList.remove('hidden');
        }

        const outDistEl = document.getElementById('att-detail-clockout-distance');
        if (outDistEl) {
            if (data.clock_out_distance !== null && data.clock_out_distance !== undefined) {
                outDistEl.textContent = data.clock_out_distance + ' meter dari toko';
            } else {
                outDistEl.textContent = (data.clock_out && data.clock_out !== '—') ? 'Selesai shift' : 'Belum clock out';
            }
        }

        // Catatan
        const noteContainer = document.getElementById('att-detail-note-container');
        const noteEl = document.getElementById('att-detail-note');
        if (data.note && data.note !== '—') {
            if (noteEl) noteEl.textContent = data.note;
            if (noteContainer) noteContainer.classList.remove('hidden');
        } else {
            if (noteContainer) noteContainer.classList.add('hidden');
        }

        modal.showModal();
    }

    function closeAttendanceDetailModal() {
        const modal = document.getElementById('modal-attendance-detail');
        if (modal) modal.close();
    }

    function zoomDetailPhoto(type) {
        if (!currentDetailData) return;
        const photoUrl = type === 'in' ? currentDetailData.clock_in_photo : currentDetailData.clock_out_photo;
        const label = type === 'in' ? 'Foto Selfie Clock In' : 'Foto Selfie Clock Out';
        const timeStr = type === 'in' ? currentDetailData.clock_in : currentDetailData.clock_out;
        const title = label + ' - ' + (currentDetailData.date_short || currentDetailData.date) + ' (' + timeStr + ')';
        if (photoUrl && typeof openSelfieModal === 'function') {
            openSelfieModal(photoUrl, title);
        }
    }

    function openSelfieModal(url, title) {
        const modal = document.getElementById('modal-view-selfie');
        const img = document.getElementById('selfie-modal-img');
        const titleEl = document.getElementById('selfie-modal-title');
        if (modal && img) {
            img.src = url;
            if (titleEl && title) titleEl.textContent = title;
            modal.showModal();
        }
    }

    function closeSelfieModal() {
        const modal = document.getElementById('modal-view-selfie');
        if (modal) modal.close();
    }

    function openEditAttendanceModal(data) {
        if (!data) return;
        const modal = document.getElementById('modal-edit-attendance');
        if (!modal) return;

        document.getElementById('edit-att-date').value = data.date || '';
        document.getElementById('edit-att-date-display').textContent = data.date_formatted || data.date || '—';
        document.getElementById('edit-att-store-id').value = data.store_id || '';

        const shiftSelect = document.getElementById('edit-att-shift-id');
        if (shiftSelect) {
            shiftSelect.value = data.shift_id || '';
        }

        const statusSelect = document.getElementById('edit-att-status');
        if (statusSelect) {
            statusSelect.value = data.status || 'present';
        }

        const clockInInput = document.getElementById('edit-att-clock-in');
        if (clockInInput) {
            clockInInput.value = data.clock_in || '';
        }

        const clockOutInput = document.getElementById('edit-att-clock-out');
        if (clockOutInput) {
            clockOutInput.value = data.clock_out || '';
        }

        const nextDayCheck = document.getElementById('edit-att-next-day');
        if (nextDayCheck) {
            nextDayCheck.checked = Boolean(data.clock_out_next_day);
        }

        const noteInput = document.getElementById('edit-att-note');
        if (noteInput) {
            noteInput.value = data.note || '';
        }

        modal.showModal();
    }

    function closeEditAttendanceModal() {
        const modal = document.getElementById('modal-edit-attendance');
        if (modal) modal.close();
    }

    function editFromDetailModal() {
        if (currentDetailData && currentDetailData.edit_data) {
            closeAttendanceDetailModal();
            openEditAttendanceModal(currentDetailData.edit_data);
        }
    }

    function setShiftTime(type) {
        const shiftSelect = document.getElementById('edit-att-shift-id');
        if (!shiftSelect || !shiftSelect.value) return;
        const selected = shiftSelect.options[shiftSelect.selectedIndex];
        if (!selected) return;

        if (type === 'in') {
            const start = selected.getAttribute('data-start');
            if (start) {
                document.getElementById('edit-att-clock-in').value = start;
            }
        } else if (type === 'out') {
            const end = selected.getAttribute('data-end');
            if (end) {
                document.getElementById('edit-att-clock-out').value = end;
                const isOvernight = selected.getAttribute('data-overnight') === '1' || end === '00:00';
                if (isOvernight) {
                    const nextDayCheck = document.getElementById('edit-att-next-day');
                    if (nextDayCheck) nextDayCheck.checked = true;
                }
            }
        }
    }
</script>
@endpush
@endsection
