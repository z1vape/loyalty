@extends('layouts.app')

@section('title', 'Dashboard Human Resources - People & Operations')

@section('content')
<div class="space-y-6">

    <!-- Header & Controls -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-[#006c49] text-white flex items-center justify-center shadow-md shadow-emerald-900/15 shrink-0">
                <span class="material-symbols-outlined text-2xl">dashboard</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-secondary">Human Resources</span>
                    <span class="text-outline-variant font-bold">•</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">Pusat Analitik &amp; Operasional</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-on-surface tracking-tight">Dashboard Human Resources</h1>
                <p class="text-xs text-on-surface-variant">Pemantauan kesiapan staf toko, kedisiplinan presensi real-time, dan beban gaji karyawan.</p>
            </div>
        </div>

        <!-- Filter & Quick Action -->
        <div class="flex flex-wrap items-center gap-2.5">
            <form action="{{ route('commercial.hr.dashboard') }}" method="GET" class="flex flex-wrap items-center gap-2">
                <!-- Store Filter -->
                <select name="store_id" onchange="this.form.submit()" class="py-2 px-3 text-xs font-bold rounded-xl bg-surface-container-low border border-outline-variant text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary cursor-pointer">
                    <option value="">Semua Lokasi Toko</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ $selectedStoreId == $st->id ? 'selected' : '' }}>
                            {{ $st->name }}
                        </option>
                    @endforeach
                </select>

                <!-- Month Selector -->
                <select name="month" onchange="this.form.submit()" class="py-2 px-3 text-xs font-bold rounded-xl bg-surface-container-low border border-outline-variant text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary cursor-pointer">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $currentMonth == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m, 1)->locale('id')->isoFormat('MMMM') }}
                        </option>
                    @endforeach
                </select>

                <!-- Year Selector -->
                <select name="year" onchange="this.form.submit()" class="py-2 px-3 text-xs font-bold rounded-xl bg-surface-container-low border border-outline-variant text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary cursor-pointer">
                    @foreach(range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endforeach
                </select>
            </form>

            <a href="{{ route('commercial.attendances.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] transition-all shadow-xs active:scale-95 no-underline">
                <span class="material-symbols-outlined text-[17px]">how_to_reg</span>
                <span>Daftar Kehadiran</span>
            </a>
        </div>
    </div>

    <!-- Top KPI Metrics Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
        
        <!-- KPI 1: Total Karyawan -->
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Total Karyawan</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-black text-on-surface">{{ $totalEmployees }}</span>
                    <span class="text-[11px] text-slate-500 font-medium">Staf Aktif</span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">group</span>
            </div>
        </div>

        <!-- KPI 2: Hadir Hari Ini -->
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Hadir Hari Ini</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-black text-emerald-700">{{ $presentCount }}</span>
                    <span class="text-[11px] text-emerald-600 font-semibold">{{ $onTimeCount }} Tepat Waktu</span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">how_to_reg</span>
            </div>
        </div>

        <!-- KPI 3: Terlambat Hari Ini -->
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Terlambat Hari Ini</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-black text-amber-700">{{ $lateCount }}</span>
                    <span class="text-[11px] text-amber-600 font-semibold">{{ $totalLateMinutesToday }} Menit</span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">alarm_on</span>
            </div>
        </div>

        <!-- KPI 4: Belum Absen / Off -->
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Belum Clock-In</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-black {{ $unattendedCount > 0 ? 'text-rose-700' : 'text-slate-700' }}">{{ $unattendedCount }}</span>
                    <span class="text-[11px] text-on-surface-variant font-medium">Jadwal Aktif</span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl {{ $unattendedCount > 0 ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600' }} flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">person_alert</span>
            </div>
        </div>

        <!-- KPI 5: Estimasi Payroll Bulan Ini -->
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Estimasi Payroll</p>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-base font-black text-indigo-700 truncate" title="Rp {{ number_format($totalEstimatedPayroll, 0, ',', '.') }}">
                        Rp {{ number_format($totalEstimatedPayroll / 1000000, 1) }} jt
                    </span>
                    <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.2 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                        {{ $payrollStatus }}
                    </span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">payments</span>
            </div>
        </div>

    </div>

    <!-- Row 1: Kesiapan Toko Cabang & Tren Kehadiran Mingguan -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left: Store Attendance Readiness Cards (7 Cols) -->
        <div class="lg:col-span-7 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h2 class="text-sm font-black text-on-surface uppercase tracking-wider">Kesiapan Staf Toko Hari Ini</h2>
                </div>
                <span class="text-xs text-on-surface-variant font-medium">{{ $today->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                @foreach($storeReadiness as $storeCard)
                <div class="bg-surface-container-low/70 rounded-xl p-3.5 border border-outline-variant/60 flex flex-col justify-between hover:border-secondary transition-all">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="material-symbols-outlined text-[17px] text-secondary">storefront</span>
                                <span class="text-xs font-extrabold text-on-surface truncate">{{ $storeCard->name }}</span>
                            </div>
                            <span class="text-[9px] font-extrabold px-2 py-0.5 rounded-full border {{ $storeCard->badge_class }}">
                                {{ $storeCard->status }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs py-2 border-y border-outline-variant/40">
                            <div>
                                <span class="text-[10px] text-on-surface-variant font-medium block">Staf Hadir</span>
                                <span class="font-black text-sm text-on-surface">{{ $storeCard->present }} <span class="text-[10px] font-normal text-on-surface-variant">/ {{ $storeCard->scheduled ?: '-' }} Jadwal</span></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-on-surface-variant font-medium block">Terlambat</span>
                                <span class="font-bold text-xs {{ $storeCard->late > 0 ? 'text-amber-700' : 'text-slate-600' }}">{{ $storeCard->late }} Staf</span>
                            </div>
                        </div>
                    </div>

                    <!-- Small Avatar Strip of Clocked-in Staff -->
                    <div class="mt-3 flex items-center justify-between text-[11px] text-on-surface-variant">
                        <div class="flex items-center -space-x-1.5 overflow-hidden">
                            @forelse($storeCard->attendances as $att)
                                @if($att->user && $att->user->photo)
                                    <img src="{{ Storage::url($att->user->photo) }}" alt="{{ $att->user->name }}" class="w-6 h-6 rounded-full object-cover border-2 border-white shadow-2xs" title="{{ $att->user->name }} - {{ $att->clock_in ? $att->clock_in->format('H:i') : '' }}">
                                @else
                                    <div class="w-6 h-6 rounded-full bg-[#006c49] text-white flex items-center justify-center font-bold text-[9px] border-2 border-white shadow-2xs" title="{{ $att->user?->name }}">
                                        {{ strtoupper(substr($att->user?->name ?? 'U', 0, 1)) }}
                                    </div>
                                @endif
                            @empty
                                <span class="text-[10px] text-on-surface-variant italic">Belum ada absen masuk</span>
                            @endforelse
                        </div>
                        <a href="{{ route('commercial.attendances.index', ['store_id' => $storeCard->id]) }}" class="text-[11px] font-bold text-primary hover:underline flex items-center gap-0.5 no-underline">
                            <span>Detail</span>
                            <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Right: 7-Day Attendance Trend Chart (5 Cols) -->
        <div class="lg:col-span-5 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs space-y-4 flex flex-col justify-between">
            <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg text-secondary">trending_up</span>
                    <h2 class="text-sm font-black text-on-surface uppercase tracking-wider">Tren Kehadiran (7 Hari)</h2>
                </div>
                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                    Disiplin Staf
                </span>
            </div>

            <!-- Visual Bar Chart -->
            <div class="space-y-3 flex-1 flex flex-col justify-around py-1">
                @foreach($trendDays as $day)
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-on-surface">{{ $day->day_name }}</span>
                        <div class="flex items-center gap-2 text-[11px]">
                            <span class="font-black text-emerald-700">{{ $day->present }} Hadir</span>
                            @if($day->late > 0)
                            <span class="text-amber-700 font-bold">({{ $day->late }} Telat)</span>
                            @endif
                        </div>
                    </div>
                    <!-- Progress Bar -->
                    <div class="w-full h-2.5 rounded-full bg-surface-container-low overflow-hidden flex">
                        @php
                            $onTimePct = $totalEmployees > 0 ? min(100, round((($day->present - $day->late) / $totalEmployees) * 100)) : 0;
                            $latePct = $totalEmployees > 0 ? min(100, round(($day->late / $totalEmployees) * 100)) : 0;
                        @endphp
                        <div class="bg-emerald-500 h-full transition-all" style="width: {{ $onTimePct }}%" title="Tepat Waktu: {{ $onTimePct }}%"></div>
                        <div class="bg-amber-400 h-full transition-all" style="width: {{ $latePct }}%" title="Terlambat: {{ $latePct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="pt-3 border-t border-outline-variant/40 flex items-center justify-between text-[11px] text-on-surface-variant">
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Tepat Waktu</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> Terlambat</span>
                </div>
                <a href="{{ route('commercial.schedules.index') }}" class="font-bold text-primary hover:underline no-underline">Lihat Roster</a>
            </div>
        </div>

    </div>

    <!-- Row 2: Live Clock-In Feed & Monthly Intelligence -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left: Live Clock-In Feed (6 Cols) -->
        <div class="lg:col-span-6 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg text-secondary">browse_activity</span>
                    <h2 class="text-sm font-black text-on-surface uppercase tracking-wider">Aktivitas Presensi Terkini</h2>
                </div>
                <span class="text-xs text-on-surface-variant font-medium">Real-time Stream</span>
            </div>

            <div class="divide-y divide-outline-variant/40">
                @forelse($recentFeed as $feed)
                <div class="py-3 flex items-center justify-between gap-3 hover:bg-surface-container-low/40 px-2 rounded-xl transition-colors">
                    <div class="flex items-center gap-3 min-w-0">
                        @if($feed->clock_in_photo)
                            <img src="{{ Storage::url($feed->clock_in_photo) }}" alt="Selfie" class="w-10 h-10 rounded-xl object-cover border border-outline-variant shadow-xs shrink-0" title="Foto Presensi Clock-In">
                        @elseif($feed->user && $feed->user->photo)
                            <img src="{{ Storage::url($feed->user->photo) }}" alt="{{ $feed->user->name }}" class="w-10 h-10 rounded-xl object-cover border border-outline-variant shadow-xs shrink-0">
                        @else
                            <div class="w-10 h-10 rounded-xl bg-[#006c49] text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                                {{ strtoupper(substr($feed->user?->name ?? 'K', 0, 1)) }}
                            </div>
                        @endif

                        <div class="min-w-0">
                            <span class="font-extrabold text-xs text-on-surface block truncate">{{ $feed->user?->name ?? 'Karyawan' }}</span>
                            <div class="flex items-center gap-1.5 text-[10px] text-on-surface-variant mt-0.5">
                                <span class="font-semibold text-secondary truncate">{{ $feed->store?->name ?? 'Toko' }}</span>
                                <span>•</span>
                                <span>{{ $feed->shift?->name ?? 'Shift Reguler' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-right shrink-0">
                        <span class="font-mono font-bold text-xs text-on-surface block">
                            {{ $feed->clock_in ? $feed->clock_in->format('H:i') : '-' }} WIB
                        </span>
                        @if($feed->late_minutes > 0)
                            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-amber-50 text-amber-700 border border-amber-200">
                                Telat {{ $feed->late_minutes }}m
                            </span>
                        @else
                            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Tepat Waktu
                            </span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-10 text-on-surface-variant space-y-1">
                    <span class="material-symbols-outlined text-3xl text-outline-variant">schedule</span>
                    <p class="text-xs font-bold">Belum ada riwayat presensi yang tercatat hari ini.</p>
                </div>
                @endforelse
            </div>

            <div class="pt-2 text-center">
                <a href="{{ route('commercial.attendances.index') }}" class="text-xs font-bold text-primary hover:underline no-underline inline-flex items-center gap-1">
                    <span>Lihat Seluruh Log Kehadiran</span>
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </div>

        <!-- Right: Top Late Employees & Payroll Summary (6 Cols) -->
        <div class="lg:col-span-6 space-y-6">
            
            <!-- Disiplin Karyawan (Top Keterlambatan Bulan Ini) -->
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs space-y-3.5">
                <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg text-amber-700">warning</span>
                        <h2 class="text-sm font-black text-on-surface uppercase tracking-wider">Evaluasi Keterlambatan Bulan Ini</h2>
                    </div>
                    <span class="text-[10px] text-on-surface-variant font-bold uppercase tracking-wider">Top 5 Karyawan</span>
                </div>

                <div class="divide-y divide-outline-variant/40">
                    @forelse($topLateEmployees as $lateEmp)
                    <div class="py-2.5 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px] flex items-center justify-center shrink-0">
                                {{ $loop->iteration }}
                            </span>
                            <div class="min-w-0">
                                <span class="font-extrabold text-on-surface block truncate">{{ $lateEmp->user?->name ?? 'Karyawan' }}</span>
                                <span class="text-[10px] text-on-surface-variant block">{{ $lateEmp->user?->stores->first()?->name ?? 'Semua Toko' }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-amber-700 block">{{ $lateEmp->late_count }}x Terlambat</span>
                            <span class="text-[10px] text-on-surface-variant">Total {{ $lateEmp->total_late_minutes }} menit</span>
                        </div>
                    </div>
                    @empty
                    <div class="py-6 text-center text-xs text-on-surface-variant font-medium">
                        🎉 Sangat bagus! Belum ada catatan keterlambatan di bulan ini.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Rincian Beban Payroll Bulan Berjalan -->
            <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs space-y-3.5">
                <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg text-secondary">receipt_long</span>
                        <h2 class="text-sm font-black text-on-surface uppercase tracking-wider">Estimasi Komponen Payroll ({{ \Carbon\Carbon::create(null, $currentMonth, 1)->locale('id')->isoFormat('MMMM Y') }})</h2>
                    </div>
                    <a href="{{ route('commercial.payrolls.index') }}" class="text-[11px] font-bold text-primary hover:underline no-underline">Detail Payroll</a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="p-2.5 rounded-xl bg-surface-container-low/70 border border-outline-variant/50">
                        <span class="text-[10px] text-on-surface-variant font-medium block">Gaji Pokok</span>
                        <span class="font-bold text-on-surface block mt-0.5 truncate">Rp {{ number_format($totalBasicSalary, 0, ',', '.') }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-surface-container-low/70 border border-outline-variant/50">
                        <span class="text-[10px] text-on-surface-variant font-medium block">Uang Makan</span>
                        <span class="font-bold text-on-surface block mt-0.5 truncate">Rp {{ number_format($totalAllowances, 0, ',', '.') }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-surface-container-low/70 border border-outline-variant/50">
                        <span class="text-[10px] text-on-surface-variant font-medium block">Lembur (Reg/OFF)</span>
                        <span class="font-bold text-emerald-700 block mt-0.5 truncate">+ Rp {{ number_format($totalOvertime, 0, ',', '.') }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-surface-container-low/70 border border-outline-variant/50">
                        <span class="text-[10px] text-on-surface-variant font-medium block">Potongan Telat</span>
                        <span class="font-bold text-rose-700 block mt-0.5 truncate">- Rp {{ number_format($totalLateDeductions, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Row 3: Quick Navigation Modules Hub -->
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs">
        <h2 class="text-xs font-black text-on-surface-variant uppercase tracking-wider mb-4">Navigasi Langsung Modul Human Resources</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <a href="{{ route('commercial.attendances.index') }}" class="p-3 rounded-xl bg-surface-container-low hover:bg-surface-container-high transition-all border border-outline-variant/60 flex flex-col items-center text-center gap-2 no-underline group">
                <span class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">how_to_reg</span>
                </span>
                <span class="text-xs font-bold text-on-surface truncate w-full">Daftar Kehadiran</span>
            </a>

            <a href="{{ route('commercial.payrolls.index') }}" class="p-3 rounded-xl bg-surface-container-low hover:bg-surface-container-high transition-all border border-outline-variant/60 flex flex-col items-center text-center gap-2 no-underline group">
                <span class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">payments</span>
                </span>
                <span class="text-xs font-bold text-on-surface truncate w-full">Payroll Employee</span>
            </a>

            <a href="{{ route('commercial.shifts.index') }}" class="p-3 rounded-xl bg-surface-container-low hover:bg-surface-container-high transition-all border border-outline-variant/60 flex flex-col items-center text-center gap-2 no-underline group">
                <span class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">schedule</span>
                </span>
                <span class="text-xs font-bold text-on-surface truncate w-full">Shift Kerja</span>
            </a>

            <a href="{{ route('commercial.attendance-statuses.index') }}" class="p-3 rounded-xl bg-surface-container-low hover:bg-surface-container-high transition-all border border-outline-variant/60 flex flex-col items-center text-center gap-2 no-underline group">
                <span class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">fact_check</span>
                </span>
                <span class="text-xs font-bold text-on-surface truncate w-full">Status Kehadiran</span>
            </a>

            <a href="{{ route('commercial.schedules.index') }}" class="p-3 rounded-xl bg-surface-container-low hover:bg-surface-container-high transition-all border border-outline-variant/60 flex flex-col items-center text-center gap-2 no-underline group">
                <span class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                </span>
                <span class="text-xs font-bold text-on-surface truncate w-full">Jadwal Roster</span>
            </a>

            <a href="{{ route('commercial.shift-patterns.index') }}" class="p-3 rounded-xl bg-surface-container-low hover:bg-surface-container-high transition-all border border-outline-variant/60 flex flex-col items-center text-center gap-2 no-underline group">
                <span class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">published_with_changes</span>
                </span>
                <span class="text-xs font-bold text-on-surface truncate w-full">Shift Pattern</span>
            </a>
        </div>
    </div>

</div>
@endsection

