@extends('layouts.app')

@section('title', 'Admin Kehadiran - Daftar Kehadiran Karyawan')

@section('content')
<div class="space-y-6">
    <!-- Header & Commercial Sub-Navigation Tabs -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-[#006c49] text-white flex items-center justify-center shadow-md shadow-emerald-900/10 shrink-0">
                <span class="material-symbols-outlined text-2xl">co_present</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-secondary">Human Resources</span>
                    <span class="text-outline-variant font-bold">•</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">Presensi &amp; Penggajian</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-on-surface tracking-tight">Daftar Kehadiran</h1>
                <p class="text-xs text-on-surface-variant">Rekapitulasi kehadiran, keterlambatan, dan jam kerja seluruh karyawan.</p>
            </div>
        </div>

        <!-- Action Buttons (Ekspor Excel) -->
        <div class="flex items-center gap-2.5 shrink-0">
            @can('Attendance Export')
            <a 
                href="{{ route('commercial.attendances.export', request()->query()) }}" 
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] transition-all cursor-pointer shadow-xs active:scale-95 no-underline"
            >
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Ekspor Excel</span>
            </a>
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
        <form action="{{ route('commercial.attendances.index') }}" method="GET" id="attendance-filter-form" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                <!-- 1. Search Karyawan (span 3) -->
                <div class="lg:col-span-3">
                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Cari Karyawan</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-base">search</span>
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $search }}" 
                            placeholder="Cari nama, ID, posisi..." 
                            class="w-full pl-9 pr-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary transition-colors"
                        />
                    </div>
                </div>

                <!-- 2. Lokasi Kerja (Store) (span 2) -->
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Lokasi Kerja</label>
                    <select name="store_id" id="attendance_store_id" class="w-full">
                        <option value="">Semua Lokasi Kerja</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Bulan (span 2) -->
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Bulan</label>
                    <select name="month" id="attendance_month" class="w-full">
                        @foreach($months as $mNum => $mName)
                            <option value="{{ $mNum }}" {{ $month == $mNum ? 'selected' : '' }}>{{ $mName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 4. Tahun (span 1) -->
                <div class="lg:col-span-1">
                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Tahun</label>
                    <select name="year" id="attendance_year" class="w-full">
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ $year == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 5. Tanggal Range (span 3) - Sesuai ukuran field date range -->
                <div class="lg:col-span-3">
                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Periode Tanggal</label>
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

                <!-- 6. Filter Action Button (span 1) -->
                <div class="lg:col-span-1">
                    <button type="submit" class="w-full py-2 px-3 bg-secondary text-white font-bold text-xs rounded-xl hover:bg-[#00553a] transition-all cursor-pointer shadow-xs flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-[17px]">filter_alt</span>
                        <span>Filter</span>
                    </button>
                </div>
            </div>

            <!-- Checkbox Absensi Bolong & Quick Filter Clear -->
            <div class="flex flex-wrap items-center justify-between pt-2 border-t border-outline-variant/50 text-xs">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input 
                        type="checkbox" 
                        name="absensi_bolong" 
                        value="1" 
                        {{ $absensiBolong ? 'checked' : '' }} 
                        onchange="document.getElementById('attendance-filter-form').submit()"
                        class="w-4 h-4 rounded text-primary focus:ring-primary border-outline-variant cursor-pointer"
                    />
                    <span class="font-bold text-on-surface">Absensi Bolong / Tidak Lengkap</span>
                    <span class="text-[11px] text-on-surface-variant">(Filter karyawan yang ada catatan tidak masuk/terlambat)</span>
                </label>

                <div class="flex items-center gap-3 text-xs mt-2 sm:mt-0">
                    <span class="text-on-surface-variant font-medium">
                        Periode Aktif: <strong class="text-on-surface">{{ \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('D MMMM Y') }} — {{ \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('D MMMM Y') }}</strong>
                    </span>
                    <a href="{{ route('commercial.attendances.index') }}" class="text-primary hover:underline font-bold text-[11px] flex items-center gap-0.5">
                        <span>Bersihkan Filter Cepat</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Employee Attendance Table Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs space-y-4">
        <!-- Table Top Stats Bar -->
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
            <div class="flex items-center gap-2">
                <span class="text-xs font-extrabold text-on-surface">Total:</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-primary/10 text-primary border border-primary/20">
                    {{ $totalEmployees }} Karyawan
                </span>
            </div>

            <div class="flex items-center gap-2 text-xs text-on-surface-variant">
                <span>Tampilkan:</span>
                <select onchange="window.location.href = this.value" class="py-1 px-2.5 text-xs font-bold rounded-lg bg-surface-container-low border border-outline-variant focus:outline-none">
                    @foreach([10, 20, 50, 100] as $opt)
                        <option value="{{ request()->fullUrlWithQuery(['per_page' => $opt]) }}" {{ $perPage == $opt ? 'selected' : '' }}>
                            {{ $opt }} / page
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($users->isEmpty())
        <div class="text-center py-16 space-y-2">
            <div class="w-12 h-12 rounded-full bg-surface-container-low flex items-center justify-center mx-auto text-on-surface-variant/40">
                <span class="material-symbols-outlined text-2xl">person_off</span>
            </div>
            <p class="font-bold text-sm text-on-surface">Tidak ada data karyawan ditemukan</p>
            <p class="text-xs text-on-surface-variant">Coba sesuaikan kata kunci pencarian atau bersihkan filter di atas.</p>
        </div>
        @else
        <!-- Responsive Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-container-low text-on-surface-variant text-[11px] font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">
                            <div class="flex items-center gap-1.5">
                                <span>Nama Karyawan</span>
                                <span class="material-symbols-outlined text-xs">arrow_upward</span>
                            </div>
                        </th>
                        <th class="py-3 px-4">
                            <div class="flex items-center gap-1.5">
                                <span>ID Username</span>
                                <span class="material-symbols-outlined text-xs">swap_vert</span>
                            </div>
                        </th>
                        <th class="py-3 px-4">Lokasi Kerja</th>
                        <th class="py-3 px-4">Posisi</th>
                        <th class="py-3 px-4">Peran</th>
                        <th class="py-3 px-4 text-center">Rekap Kehadiran</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60 font-medium">
                    @foreach($users as $emp)
                    @php
                        $idUsername = $emp->username ?: ($emp->employee_number ?: 'ID-' . $emp->id);
                        $assignedStore = $emp->stores->first();
                    @endphp
                    <tr 
                        class="hover:bg-surface-container-low/70 transition-colors cursor-pointer group"
                        onclick="window.location.href = '{{ route('commercial.attendances.show', array_merge(['user' => $emp->id], request()->except('page'))) }}'"
                    >
                        <!-- 1. Employee Name & Avatar -->
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                @if($emp->photo)
                                    <img 
                                        src="{{ Storage::url($emp->photo) }}" 
                                        alt="{{ $emp->name }}" 
                                        class="w-9 h-9 rounded-full object-cover border border-outline-variant shadow-xs shrink-0 group-hover:scale-105 transition-transform"
                                    />
                                @else
                                    <div class="w-9 h-9 rounded-full bg-[#006c49] text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0 group-hover:scale-105 transition-transform">
                                        {{ strtoupper(substr($emp->name, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="font-extrabold text-sm text-on-surface group-hover:text-primary transition-colors">
                                    {{ strtoupper($emp->name) }}
                                </span>
                            </div>
                        </td>

                        <!-- 2. ID Username -->
                        <td class="py-3.5 px-4 font-mono font-bold text-xs text-on-surface">
                            <span class="px-2 py-1 rounded-lg bg-surface-container-low border border-outline-variant/70 text-slate-700">
                                {{ $idUsername }}
                            </span>
                        </td>

                        <!-- 3. Lokasi Kerja -->
                        <td class="py-3.5 px-4 text-xs font-semibold text-on-surface">
                            @if($assignedStore)
                                <div class="inline-flex items-center gap-1.5 text-secondary font-semibold">
                                    <span class="material-symbols-outlined text-[15px]">storefront</span>
                                    <span>{{ $assignedStore->name }}</span>
                                </div>
                            @else
                                <span class="text-on-surface-variant text-xs italic">-</span>
                            @endif
                        </td>

                        <!-- 4. Posisi -->
                        <td class="py-3.5 px-4 text-xs text-on-surface font-medium">
                            <span>{{ $emp->position ?: 'Staff Operasional' }}</span>
                        </td>

                        <!-- 5. Peran -->
                        <td class="py-3.5 px-4 text-xs">
                            @if($emp->roles->isNotEmpty())
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-surface-container text-on-surface-variant border border-outline-variant/60">
                                    {{ $emp->roles->first()->name }}
                                </span>
                            @else
                                <span class="text-on-surface-variant text-xs italic">-</span>
                            @endif
                        </td>

                        <!-- 3. Rekap Kehadiran di Periode -->
                        <td class="py-3.5 px-4 text-center">
                            <div class="inline-flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-emerald-500/10 text-emerald-800 border border-emerald-500/20" title="Total Hari Hadir">
                                    {{ $emp->total_attendances_count }} Hadir
                                </span>
                                @if($emp->late_attendances_count > 0)
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-500/10 text-amber-800 border border-amber-500/20" title="Total Hari Terlambat">
                                    {{ $emp->late_attendances_count }} Telat
                                </span>
                                @endif
                            </div>
                        </td>

                        <!-- 4. Action Arrow -->
                        <td class="py-3.5 px-4 text-right">
                            <a 
                                href="{{ route('commercial.attendances.show', array_merge(['user' => $emp->id], request()->except('page'))) }}" 
                                class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-surface-container-low hover:bg-primary hover:text-white text-on-surface-variant transition-all shadow-xs group-hover:translate-x-1"
                                title="Buka Detail Kehadiran Karyawan"
                                onclick="event.stopPropagation();"
                            >
                                <span class="material-symbols-outlined text-base">chevron_right</span>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pt-4 border-t border-outline-variant/60 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-on-surface-variant">
            <div>
                Menampilkan <strong>{{ $users->firstItem() ?? 0 }}</strong> - <strong>{{ $users->lastItem() ?? 0 }}</strong> dari <strong>{{ $users->total() }}</strong> karyawan
            </div>
            <div>
                {{ $users->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('attendance-filter-form');
    if (!filterForm) return;

    let tsStore = null;
    let tsMonth = null;
    let tsYear = null;

    const monthSelect = document.getElementById('attendance_month');
    const yearSelect = document.getElementById('attendance_year');
    const startDateInput = filterForm.querySelector('input[name="start_date"]');
    const endDateInput = filterForm.querySelector('input[name="end_date"]');

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

    if (typeof TomSelect !== 'undefined') {
        const storeEl = document.getElementById('attendance_store_id');
        if (storeEl) {
            tsStore = new TomSelect(storeEl, {
                create: false,
                allowEmptyOption: true,
                placeholder: 'Semua Lokasi Kerja',
                maxOptions: 100
            });
        }

        if (monthSelect) {
            tsMonth = new TomSelect(monthSelect, {
                create: false,
                allowEmptyOption: false,
                controlInput: null,
                maxOptions: 20,
                onChange: syncMonthYearToDates
            });
        }

        if (yearSelect) {
            tsYear = new TomSelect(yearSelect, {
                create: false,
                allowEmptyOption: false,
                controlInput: null,
                maxOptions: 20,
                onChange: syncMonthYearToDates
            });
        }
    }

    if (!tsMonth && monthSelect) monthSelect.addEventListener('change', syncMonthYearToDates);
    if (!tsYear && yearSelect) yearSelect.addEventListener('change', syncMonthYearToDates);

    if (startDateInput) {
        startDateInput.addEventListener('change', function () {
            if (!this.value) return;
            const parts = this.value.split('-');
            if (parts.length === 3) {
                const y = parseInt(parts[0], 10);
                const m = parseInt(parts[1], 10);
                if (tsMonth) {
                    tsMonth.setValue(m, true);
                } else if (monthSelect) {
                    monthSelect.value = m;
                }
                if (tsYear) {
                    tsYear.setValue(y, true);
                } else if (yearSelect) {
                    yearSelect.value = y;
                }
            }
        });
    }
});
</script>
@endpush

