@extends('layouts.app')

@section('title', 'Human Resources Hub - Z1 VapeOS')

@section('content')
<div class="space-y-6">

    <!-- Quick Stat Overview Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <!-- Stat 1 -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex flex-col justify-between">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Karyawan</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-slate-800">{{ $totalEmployees }}</span>
                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full border border-emerald-200">Staf</span>
            </div>
        </div>

        <!-- Stat 2 -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex flex-col justify-between">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Presensi Hari Ini</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-emerald-600">{{ $todayAttendanceCount }}</span>
                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded-full border border-emerald-200">Hadir</span>
            </div>
        </div>

        <!-- Stat 3 -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex flex-col justify-between">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Shift Operasional</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-amber-600">{{ $totalShifts }}</span>
                <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded-full border border-amber-200">Shift</span>
            </div>
        </div>

        <!-- Stat 4 -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex flex-col justify-between">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Status Kehadiran</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-cyan-600">{{ $totalStatuses }}</span>
                <span class="text-[10px] font-bold text-cyan-700 bg-cyan-50 px-1.5 py-0.5 rounded-full border border-cyan-200">Kategori</span>
            </div>
        </div>

        <!-- Stat 5 -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex flex-col justify-between">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Jadwal Terdaftar</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-indigo-600">{{ $totalSchedules }}</span>
                <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded-full border border-indigo-200">Roster</span>
            </div>
        </div>

        <!-- Stat 6 -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex flex-col justify-between">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Shift Pattern</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-purple-600">{{ $totalPatterns }}</span>
                <span class="text-[10px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded-full border border-purple-200">Pola</span>
            </div>
        </div>
    </div>

    <!-- 6 Core HR Sub-Menus Grid -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Modul &amp; Layanan Human Resource</h2>
                <p class="text-xs text-slate-500">Pilih modul kerja yang ingin Anda buka:</p>
            </div>
            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                6 Modul Aktif
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6">

            <!-- Card 1: Daftar Kehadiran -->
            <a href="{{ route('commercial.attendances.index') }}" class="group bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-lg hover:border-emerald-300 transition-all duration-200 overflow-hidden flex flex-col justify-between no-underline text-inherit">
                <div>
                    <!-- Card Top Header -->
                    <div class="h-28 bg-gradient-to-tr from-emerald-600 to-teal-500 p-5 flex items-center justify-between text-white relative transition-opacity group-hover:opacity-95">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white border border-white/30 shadow-inner">
                            <span class="material-symbols-outlined text-2xl">how_to_reg</span>
                        </div>
                        <span class="bg-white/20 backdrop-blur-md text-white text-[10px] font-bold px-2 py-0.5 rounded-full border border-white/30">
                            Presensi
                        </span>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5">
                        <h3 class="text-base font-black text-slate-900 group-hover:text-emerald-700 transition-colors">
                            Daftar Kehadiran
                        </h3>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Rekapitulasi absensi harian, jam clock-in dan clock-out, keterlambatan, dan ekspor data kehadiran seluruh staf toko.
                        </p>
                    </div>
                </div>

                <div class="p-5 pt-0">
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 font-bold text-[10px] border border-emerald-200">HR</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-bold text-[10px]">HARIAN</span>
                        </div>
                        <span class="font-bold text-emerald-700 flex items-center gap-1 text-xs group-hover:translate-x-0.5 transition-transform">
                            <span>Buka Modul</span>
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </span>
                    </div>
                </div>
            </a>

            <!-- Card 2: Payroll Employee -->
            <a href="{{ route('commercial.payrolls.index') }}" class="group bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-lg hover:border-emerald-300 transition-all duration-200 overflow-hidden flex flex-col justify-between no-underline text-inherit">
                <div>
                    <!-- Card Top Header -->
                    <div class="h-28 bg-gradient-to-tr from-teal-700 to-emerald-600 p-5 flex items-center justify-between text-white relative transition-opacity group-hover:opacity-95">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white border border-white/30 shadow-inner">
                            <span class="material-symbols-outlined text-2xl">payments</span>
                        </div>
                        <span class="bg-white/20 backdrop-blur-md text-white text-[10px] font-bold px-2 py-0.5 rounded-full border border-white/30">
                            Payroll
                        </span>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5">
                        <h3 class="text-base font-black text-slate-900 group-hover:text-emerald-700 transition-colors">
                            Payroll Employee
                        </h3>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Kalkulasi gaji bulanan karyawan, bonus &amp; komisi penjualan kasir, potongan kasbon/keterlambatan, dan penerbitan slip gaji.
                        </p>
                    </div>
                </div>

                <div class="p-5 pt-0">
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded-md bg-teal-50 text-teal-700 font-bold text-[10px] border border-teal-200">PAY</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-bold text-[10px]">SLIP GAJI</span>
                        </div>
                        <span class="font-bold text-emerald-700 flex items-center gap-1 text-xs group-hover:translate-x-0.5 transition-transform">
                            <span>Buka Modul</span>
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </span>
                    </div>
                </div>
            </a>

            <!-- Card 3: Shift Kerja -->
            <a href="{{ route('commercial.shifts.index') }}" class="group bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-lg hover:border-amber-300 transition-all duration-200 overflow-hidden flex flex-col justify-between no-underline text-inherit">
                <div>
                    <!-- Card Top Header -->
                    <div class="h-28 bg-gradient-to-tr from-amber-500 to-orange-500 p-5 flex items-center justify-between text-white relative transition-opacity group-hover:opacity-95">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white border border-white/30 shadow-inner">
                            <span class="material-symbols-outlined text-2xl">schedule</span>
                        </div>
                        <span class="bg-white/20 backdrop-blur-md text-white text-[10px] font-bold px-2 py-0.5 rounded-full border border-white/30">
                            Master Shift
                        </span>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5">
                        <h3 class="text-base font-black text-slate-900 group-hover:text-amber-700 transition-colors">
                            Shift Kerja
                        </h3>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Master jam operasional kerja toko, waktu mulai, batas akhir jam kerja, dan pengaturan toleransi keterlambatan presensi.
                        </p>
                    </div>
                </div>

                <div class="p-5 pt-0">
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 font-bold text-[10px] border border-amber-200">SHIFT</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-bold text-[10px]">JAM KERJA</span>
                        </div>
                        <span class="font-bold text-amber-700 flex items-center gap-1 text-xs group-hover:translate-x-0.5 transition-transform">
                            <span>Buka Modul</span>
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </span>
                    </div>
                </div>
            </a>

            <!-- Card 4: Status Kehadiran -->
            <a href="{{ route('commercial.attendance-statuses.index') }}" class="group bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-lg hover:border-cyan-300 transition-all duration-200 overflow-hidden flex flex-col justify-between no-underline text-inherit">
                <div>
                    <!-- Card Top Header -->
                    <div class="h-28 bg-gradient-to-tr from-cyan-600 to-sky-500 p-5 flex items-center justify-between text-white relative transition-opacity group-hover:opacity-95">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white border border-white/30 shadow-inner">
                            <span class="material-symbols-outlined text-2xl">label</span>
                        </div>
                        <span class="bg-white/20 backdrop-blur-md text-white text-[10px] font-bold px-2 py-0.5 rounded-full border border-white/30">
                            Status
                        </span>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5">
                        <h3 class="text-base font-black text-slate-900 group-hover:text-cyan-700 transition-colors">
                            Status Kehadiran
                        </h3>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Master kategori status presensi: Hadir, Izin, Cuti Tahunan, Sakit (SKD), Alfa, Dinas Luar, dan status pemotongan gaji.
                        </p>
                    </div>
                </div>

                <div class="p-5 pt-0">
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded-md bg-cyan-50 text-cyan-700 font-bold text-[10px] border border-cyan-200">STATUS</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-bold text-[10px]">IZIN &amp; CUTI</span>
                        </div>
                        <span class="font-bold text-cyan-700 flex items-center gap-1 text-xs group-hover:translate-x-0.5 transition-transform">
                            <span>Buka Modul</span>
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </span>
                    </div>
                </div>
            </a>

            <!-- Card 5: Jadwal Kerja (Roster) -->
            <a href="{{ route('commercial.schedules.index') }}" class="group bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-lg hover:border-indigo-300 transition-all duration-200 overflow-hidden flex flex-col justify-between no-underline text-inherit">
                <div>
                    <!-- Card Top Header -->
                    <div class="h-28 bg-gradient-to-tr from-indigo-600 to-blue-600 p-5 flex items-center justify-between text-white relative transition-opacity group-hover:opacity-95">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white border border-white/30 shadow-inner">
                            <span class="material-symbols-outlined text-2xl">calendar_month</span>
                        </div>
                        <span class="bg-white/20 backdrop-blur-md text-white text-[10px] font-bold px-2 py-0.5 rounded-full border border-white/30">
                            Roster
                        </span>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5">
                        <h3 class="text-base font-black text-slate-900 group-hover:text-indigo-700 transition-colors">
                            Jadwal Kerja (Roster)
                        </h3>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Penjadwalan kalender kerja bulanan staf per cabang toko, plotting jam shift kasir/operasional, dan hari libur bergilir.
                        </p>
                    </div>
                </div>

                <div class="p-5 pt-0">
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-bold text-[10px] border border-indigo-200">ROSTER</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-bold text-[10px]">BULANAN</span>
                        </div>
                        <span class="font-bold text-indigo-700 flex items-center gap-1 text-xs group-hover:translate-x-0.5 transition-transform">
                            <span>Buka Modul</span>
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </span>
                    </div>
                </div>
            </a>

            <!-- Card 6: Shift Pattern -->
            <a href="{{ route('commercial.shift-patterns.index') }}" class="group bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-lg hover:border-purple-300 transition-all duration-200 overflow-hidden flex flex-col justify-between no-underline text-inherit">
                <div>
                    <!-- Card Top Header -->
                    <div class="h-28 bg-gradient-to-tr from-purple-600 to-fuchsia-600 p-5 flex items-center justify-between text-white relative transition-opacity group-hover:opacity-95">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white border border-white/30 shadow-inner">
                            <span class="material-symbols-outlined text-2xl">sync_alt</span>
                        </div>
                        <span class="bg-white/20 backdrop-blur-md text-white text-[10px] font-bold px-2 py-0.5 rounded-full border border-white/30">
                            Pola Rotasi
                        </span>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5">
                        <h3 class="text-base font-black text-slate-900 group-hover:text-purple-700 transition-colors">
                            Shift Pattern
                        </h3>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Template pola perputaran shift otomatis (misal: 2 Pagi, 2 Siang, 1 Libur) untuk pembuatan jadwal staf secara instan.
                        </p>
                    </div>
                </div>

                <div class="p-5 pt-0">
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 font-bold text-[10px] border border-purple-200">PATTERN</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-bold text-[10px]">ROTASI</span>
                        </div>
                        <span class="font-bold text-purple-700 flex items-center gap-1 text-xs group-hover:translate-x-0.5 transition-transform">
                            <span>Buka Modul</span>
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </span>
                    </div>
                </div>
            </a>

        </div>
    </div>

</div>
@endsection

