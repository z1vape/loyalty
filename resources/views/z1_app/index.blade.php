@extends('layouts.z1_employee')

@section('title', 'Z1 App - Portal Karyawan')

@section('content')
<div class="space-y-6">

    <!-- TAB 1: ABSENSI KARYAWAN -->
    @if($activeTab === 'attendance')
    <div class="space-y-6">
        @php
            $hasSchedule = isset($todaySchedule) && $todaySchedule;
            $isOffDay = $hasSchedule && $todaySchedule->is_off;
            $infoShift = $todayAttendance?->shift ?? (($hasSchedule && !$isOffDay) ? $todaySchedule->shift : null);
            $toleranceMinutes = $infoShift?->late_tolerance_minutes ?? 15;
            $geofenceRadius = ($activeStore && $activeStore->radius_meters) ? (int) $activeStore->radius_meters : 100;
            $notClockedIn = !$todayAttendance || !$todayAttendance->clock_in;
            $cleanInfoShiftName = $infoShift ? preg_replace('/\s*\(\d{2}:\d{2}\s*-\s*\d{2}:\d{2}\)$/', '', $infoShift->name) : null;
            $isOvernightShift = $todayAttendance && !\Carbon\Carbon::parse($todayAttendance->date)->isToday();
            $effectiveDate = $todayAttendance ? \Carbon\Carbon::parse($todayAttendance->date) : \Carbon\Carbon::today();

            // Attendance Window checks
            $nowTime = \Carbon\Carbon::now();
            $earliestClockIn = ($infoShift && !$isOffDay) ? $infoShift->getEarliestClockInTime($effectiveDate) : null;
            $isTooEarlyToClockIn = ($notClockedIn && $earliestClockIn) ? $nowTime->lt($earliestClockIn) : false;

            $latestClockOut = ($todayAttendance && $todayAttendance->clock_in && !$todayAttendance->clock_out && $infoShift)
                ? $infoShift->getLatestClockOutTime($todayAttendance->date)
                : null;
            $isPastClockOutCutoff = ($latestClockOut) ? $nowTime->gt($latestClockOut) : false;
        @endphp

        <!-- ═════ HERO: Status Kehadiran Hari Ini + Jam Live ═════ -->
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-5 bg-gradient-to-br from-white via-white to-emerald-50/40">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div class="space-y-2 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        @if($notClockedIn)
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-700 border border-amber-500/30 inline-flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Belum Clock In
                            </span>
                        @elseif(!$todayAttendance->clock_out)
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-700 border border-emerald-500/30 inline-flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Sedang Bekerja {{ $isOvernightShift ? '(Shift Lintas Hari)' : '' }}
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-500/10 text-blue-700 border border-blue-500/30 inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[13px]">task_alt</span>
                                Selesai Bekerja
                            </span>
                        @endif
                        <span class="text-outline-variant">•</span>
                        <span class="text-[11px] text-on-surface-variant font-medium inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                            {{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
                        </span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-on-surface tracking-tight">Status Kehadiran Hari Ini</h2>
                    <p class="text-xs text-on-surface-variant">Pastikan Anda berada di area toko dan melakukan selfie sebelum waktu operasional shift dimulai.</p>
                </div>

                <!-- Live Clock -->
                <div class="shrink-0 flex items-center gap-3.5 px-4 py-3 rounded-2xl bg-surface-container-low/80 border border-outline-variant/70 shadow-2xs">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[22px]">schedule</span>
                    </div>
                    <div>
                        <div class="flex items-baseline gap-1.5">
                            <span id="live-clock-time" class="text-2xl font-black font-mono tracking-wider text-on-surface leading-none">--:--:--</span>
                            <span class="text-[10px] font-bold text-on-surface-variant">WIB</span>
                        </div>
                        <p class="text-[10px] text-on-surface-variant mt-1">Zona Waktu: Asia/Jakarta (GMT+7)</p>
                    </div>
                </div>
            </div>

            @if($notClockedIn && !$hasSchedule)
            <!-- Banner: Jadwal belum ditetapkan -->
            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <span class="w-9 h-9 rounded-lg bg-amber-500/15 text-amber-700 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[20px]">event_busy</span>
                    </span>
                    <div class="min-w-0">
                        <span class="text-[11px] font-black uppercase tracking-wider block text-amber-900">Jadwal Shift Belum Ditetapkan</span>
                        <p class="text-xs text-amber-900/80 mt-0.5">Anda belum memiliki penugasan shift untuk hari ini di <strong>User Schedule</strong>. Tombol clock in dikunci secara otomatis.</p>
                    </div>
                </div>
            </div>
            @elseif($notClockedIn && $isOffDay)
            <!-- Banner: Hari libur / lembur -->
            <div class="p-3.5 rounded-xl bg-gradient-to-r from-amber-500/15 to-orange-500/10 border border-amber-300/80 flex items-start gap-3">
                <span class="w-9 h-9 rounded-lg bg-amber-500/20 text-amber-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[20px]">beach_access</span>
                </span>
                <div>
                    <span class="text-[11px] font-black uppercase tracking-wider block text-amber-900">Jadwal Anda: Libur (OFF) • Mode Lembur Hari Libur</span>
                    <p class="text-xs text-amber-900/80 mt-0.5">Anda tidak perlu memilih shift. Durasi jam lembur dihitung otomatis dari jam Clock In hingga Clock Out.</p>
                </div>
            </div>
            @elseif($notClockedIn && $hasSchedule && !$isOffDay && $isTooEarlyToClockIn)
            <!-- Banner: Belum Masuk Waktu Buka Clock In -->
            <div class="p-3.5 rounded-xl bg-sky-50 border border-sky-200 flex items-start gap-3">
                <span class="w-9 h-9 rounded-lg bg-sky-500/20 text-sky-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[20px]">schedule</span>
                </span>
                <div>
                    <span class="text-[11px] font-black uppercase tracking-wider block text-sky-900">Clock In Belum Dibuka</span>
                    <p class="text-xs text-sky-900/80 mt-0.5">
                        Absen masuk untuk <strong>{{ $cleanInfoShiftName }}</strong> baru dibuka mulai pukul <strong>{{ $earliestClockIn->format('H:i') }} WIB</strong> ({{ $infoShift->early_clock_in_minutes ?? 120 }} menit sebelum jam shift dimulai).
                    </p>
                </div>
            </div>
            @endif

            @if($todayAttendance && $todayAttendance->status === 'late')
            <div class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-800 font-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-sm text-amber-600">warning</span>
                <span>Terlambat {{ $todayAttendance->late_minutes }} menit dari jadwal shift.</span>
            </div>
            @endif
        </div>

        <!-- ═════ GRID: Info Shift + Lokasi (kiri) | Selfie / Aksi (kanan) ═════ -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ───── KOLOM KIRI ───── -->
            <div class="lg:col-span-5 space-y-6">

                <!-- Informasi Shift Hari Ini -->
                <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-xs font-extrabold text-on-surface inline-flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-primary">storefront</span>
                            Informasi Shift Hari Ini
                        </h3>
                        <span class="text-[10px] font-medium text-on-surface-variant truncate max-w-[45%]">{{ $activeStore->name ?? 'Toko' }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3.5 rounded-xl bg-surface-container-low/70 border border-outline-variant/50">
                            <span class="text-[10px] text-on-surface-variant font-medium block">Jam Masuk (Clock In)</span>
                            <span class="text-lg font-black font-mono text-on-surface block mt-1">
                                {{ $todayAttendance && $todayAttendance->clock_in ? \Carbon\Carbon::parse($todayAttendance->clock_in)->format('H:i') : '— : —' }}
                            </span>
                        </div>
                        <div class="p-3.5 rounded-xl bg-surface-container-low/70 border border-outline-variant/50">
                            <span class="text-[10px] text-on-surface-variant font-medium block">Jam Pulang (Clock Out)</span>
                            <span class="text-lg font-black font-mono text-on-surface block mt-1">
                                {{ $todayAttendance && $todayAttendance->clock_out ? \Carbon\Carbon::parse($todayAttendance->clock_out)->format('H:i') : '— : —' }}
                            </span>
                        </div>
                    </div>

                    <dl class="text-xs divide-y divide-dashed divide-outline-variant/70">
                        <div class="flex items-center justify-between py-2.5">
                            <dt class="text-on-surface-variant">Shift Kerja:</dt>
                            <dd>
                                @if($cleanInfoShiftName)
                                    <span class="px-2.5 py-0.5 rounded-md bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 font-bold">
                                        {{ $cleanInfoShiftName }} • {{ substr($infoShift->start_time, 0, 5) }}-{{ substr($infoShift->end_time, 0, 5) }}
                                    </span>
                                @elseif($isOffDay)
                                    <span class="px-2.5 py-0.5 rounded-md bg-slate-500/10 text-slate-700 border border-slate-400/30 font-bold">Libur (OFF)</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-md bg-rose-500/10 text-rose-600 border border-rose-500/20 font-bold">Belum ditentukan</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex items-center justify-between py-2.5">
                            <dt class="text-on-surface-variant">Durasi Kerja Aktif:</dt>
                            <dd class="font-bold {{ $todayAttendance ? 'text-primary' : 'text-on-surface-variant' }}">{{ $todayAttendance ? $todayAttendance->work_hours : '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between py-2.5">
                            <dt class="text-on-surface-variant">Toleransi Keterlambatan:</dt>
                            <dd class="font-bold text-on-surface">Maks. {{ $toleranceMinutes }} Menit</dd>
                        </div>
                    </dl>

                    @if($todayAttendance && $todayAttendance->clock_in_photo)
                    <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-700 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20 inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">verified</span>
                                Selfie Terverifikasi
                            </span>
                            <span class="text-xs font-bold text-on-surface block mt-1">Foto Masuk Hari Ini</span>
                        </div>
                        <img
                            src="{{ asset('storage/' . $todayAttendance->clock_in_photo) }}"
                            alt="Foto Selfie Masuk"
                            onclick="openSelfieModal('{{ asset('storage/' . $todayAttendance->clock_in_photo) }}', 'Foto Masuk Hari Ini ({{ \Carbon\Carbon::parse($todayAttendance->clock_in)->format('H:i') }} WIB)')"
                            class="w-14 h-14 rounded-xl object-cover border-2 border-emerald-500/30 shadow-xs cursor-pointer hover:scale-105 transition-all"
                            title="Klik untuk memperbesar foto"
                        >
                    </div>
                    @endif

                    @if($todayAttendance && ($todayAttendance->clock_in_distance_meters !== null || ($todayAttendance->clock_in_lat && $todayAttendance->clock_in_lng)))
                    <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1.5 text-on-surface-variant min-w-0">
                            <span class="material-symbols-outlined text-primary text-base shrink-0">pin_drop</span>
                            <span class="truncate">
                                @if($todayAttendance->clock_in_distance_meters !== null)
                                    Radius: <strong>{{ $todayAttendance->clock_in_distance_meters }}m</strong> dari toko
                                @else
                                    Koordinat GPS tersimpan
                                @endif
                            </span>
                        </div>
                        @if($todayAttendance->clock_in_lat && $todayAttendance->clock_in_lng)
                        <a href="https://www.google.com/maps?q={{ $todayAttendance->clock_in_lat }},{{ $todayAttendance->clock_in_lng }}" target="_blank" class="text-primary hover:underline font-bold text-[11px] inline-flex items-center gap-1 shrink-0" title="Buka titik koordinat di Google Maps">
                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                            <span>Cek Maps</span>
                        </a>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- Lokasi Presensi (Geofencing GPS) -->
                <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 shadow-xs space-y-3.5" id="gps-status-container">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-xs font-extrabold text-on-surface inline-flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-rose-500">location_on</span>
                            Lokasi Presensi (Geofencing GPS)
                        </h3>
                        <button type="button" onclick="refreshGeolocation()" class="px-2 py-1 text-[11px] font-bold text-primary hover:bg-primary/10 rounded-lg transition-colors shrink-0 flex items-center gap-1 cursor-pointer" title="Perbarui Lokasi">
                            <span class="material-symbols-outlined text-sm">sync</span>
                            <span>Cek GPS</span>
                        </button>
                    </div>

                    <!-- Pill status (dipakai JS, disembunyikan karena info utama ada di kotak di bawah) -->
                    <div class="hidden">
                        <span id="gps-status-pill" class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-surface-container text-on-surface-variant border border-outline-variant/60 uppercase tracking-wider flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-pulse"></span>
                            <span>Mendeteksi Lokasi...</span>
                        </span>
                    </div>

                    <div id="gps-info-box" class="p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/70 text-xs flex items-start gap-3">
                        <div id="gps-icon-box" class="w-8 h-8 rounded-lg bg-surface-container text-on-surface-variant flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-lg" id="gps-icon">location_searching</span>
                        </div>
                        <div class="min-w-0">
                            <h5 id="gps-title" class="font-extrabold text-[11px] uppercase tracking-wide text-on-surface">Mendeteksi koordinat GPS...</h5>
                            <p id="gps-desc" class="text-[11px] text-on-surface-variant mt-0.5">
                                @if($activeStore && $activeStore->hasGeofence())
                                    Cabang: <strong>{{ $activeStore->name }}</strong> (Radius toleransi: {{ $activeStore->radius_meters }}m)
                                @else
                                    Cabang: <strong>{{ $activeStore->name ?? 'Toko' }}</strong>
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Alert error GPS (dikendalikan JS; pesan sama sudah tampil di kotak di atas) -->
                    <div id="gps-error-alert" class="hidden">
                        <span id="gps-error-title"></span>
                        <span id="gps-error-desc"></span>
                    </div>

                    <!-- Peta target toko -->
                    <div class="relative h-32 rounded-xl border border-outline-variant/60 bg-surface-container-low/70 overflow-hidden flex flex-col items-center justify-center text-center px-4"
                         style="background-image: linear-gradient(to right, rgba(148,163,184,0.18) 1px, transparent 1px), linear-gradient(to bottom, rgba(148,163,184,0.18) 1px, transparent 1px); background-size: 22px 22px;">
                        <span class="w-9 h-9 rounded-full bg-rose-500 text-white flex items-center justify-center shadow-md ring-4 ring-rose-500/20">
                            <span class="material-symbols-outlined text-[18px]">storefront</span>
                        </span>
                        <span class="text-[11px] font-extrabold text-on-surface mt-2">Target: {{ $activeStore->name ?? 'Toko' }}</span>
                        @if($activeStore && $activeStore->address)
                            <span class="text-[10px] text-on-surface-variant leading-snug line-clamp-1 max-w-full">{{ $activeStore->address }}</span>
                        @endif
                        @if($activeStore && $activeStore->hasGeofence())
                            <a href="https://www.google.com/maps?q={{ $activeStore->latitude }},{{ $activeStore->longitude }}" target="_blank" rel="noopener" class="absolute top-2 right-2 text-[10px] font-bold text-primary hover:underline inline-flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-[13px]">open_in_new</span>Maps
                            </a>
                        @endif
                    </div>

                    <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-surface-container-low/70 border border-outline-variant/50 text-[10px]">
                        <span class="text-on-surface-variant inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[14px]">my_location</span>
                            Akurasi GPS: <span id="gps-accuracy">—</span>
                        </span>
                        <span id="gps-signal" class="font-bold text-on-surface-variant inline-flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Mencari sinyal
                        </span>
                    </div>
                </div>
            </div>

            <!-- ───── KOLOM KANAN ───── -->
            <div class="lg:col-span-7">
                @if($notClockedIn)
                <form action="{{ route('z1-app.clock-in') }}" method="POST" id="clockin-form" onsubmit="return validateClockInForm(event)" class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
                    @csrf
                    <input type="hidden" name="shift_id" id="clockin-shift-id-input" value="{{ ($hasSchedule && !$isOffDay) ? ($todaySchedule->shift?->id ?? '') : '' }}">

                    <!-- Header kartu -->
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-extrabold text-on-surface inline-flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px] text-primary">photo_camera</span>
                                Foto Selfie Kehadiran <span class="text-rose-500 text-[11px] font-bold">*(Wajib)</span>
                            </h3>
                            <p class="text-[11px] text-on-surface-variant mt-0.5">Verifikasi biometrik pengenalan wajah langsung di tempat</p>
                        </div>
                        <span class="text-[10px] font-medium text-on-surface-variant shrink-0">Step 1 dari 2</span>
                    </div>

                    <!-- Hidden GPS inputs -->
                    <input type="hidden" name="lat" id="clockin-lat-input">
                    <input type="hidden" name="lng" id="clockin-lng-input">

                    <!-- Error banner foto wajib -->
                    <div id="camera-error-alert" class="hidden p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 text-xs font-semibold flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-rose-600 text-lg shrink-0">error</span>
                        <div>
                            <span class="font-extrabold block">Foto Selfie Wajib Diambil!</span>
                            <span class="text-[11px] text-rose-600 font-normal">Silakan buka kamera dan ambil foto wajah Anda terlebih dahulu sebelum menekan Clock In.</span>
                        </div>
                    </div>

                    <!-- Area kamera -->
                    <div class="bg-surface-container-low/40 border-2 border-dashed border-outline-variant hover:border-primary/50 rounded-2xl overflow-hidden transition-all relative" id="camera-card-box">
                        <!-- 1. State: Placeholder -->
                        <div id="camera-placeholder" class="py-10 px-4 flex flex-col items-center justify-center text-center space-y-3">
                            <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shadow-xs">
                                <span class="material-symbols-outlined text-3xl">photo_camera</span>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-xs text-on-surface">Ambil Foto Wajah (Selfie)</h4>
                                <p class="text-[11px] text-on-surface-variant max-w-sm mt-1 leading-relaxed">
                                    Ambil foto selfie langsung di toko untuk verifikasi kehadiran sebelum Clock In. Pastikan wajah berada di area terang dan tidak memakai masker.
                                </p>
                            </div>
                            <button type="button" onclick="startCamera()" class="px-5 py-2.5 bg-primary hover:bg-[#00553a] text-on-primary font-bold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer active:scale-95">
                                <span class="material-symbols-outlined text-base">videocam</span>
                                <span>Buka Kamera Selfie</span>
                            </button>
                            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-[10px] text-on-surface-variant">
                                <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[13px] text-emerald-600">check</span>Wajah Terlihat Jelas</span>
                                <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[13px] text-emerald-600">check</span>Cahaya Cukup</span>
                                <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[13px] text-emerald-600">check</span>Seragam Rapi</span>
                            </div>
                        </div>

                        <!-- 2. State: Live Video -->
                        <div id="camera-active-box" class="hidden relative w-full bg-slate-950 aspect-4/3 sm:aspect-16/10 flex items-center justify-center overflow-hidden">
                            <video id="clockin-video" autoplay playsinline muted class="w-full h-full object-cover -scale-x-100"></video>

                            <div class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center">
                                <div class="w-40 h-52 sm:w-48 sm:h-60 border-2 border-dashed border-emerald-400/80 rounded-[45%] shadow-[0_0_0_9999px_rgba(0,0,0,0.45)] flex items-center justify-center">
                                    <span class="text-[10px] font-bold text-white/95 bg-black/60 px-2.5 py-0.5 rounded-full backdrop-blur-xs uppercase tracking-wider">
                                        Posisikan Wajah
                                    </span>
                                </div>
                            </div>

                            <div class="absolute bottom-3 inset-x-0 flex items-center justify-center gap-4 px-4 z-10">
                                <button type="button" onclick="stopCamera()" class="w-9 h-9 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center backdrop-blur-md transition-all cursor-pointer shadow-md" title="Tutup Kamera">
                                    <span class="material-symbols-outlined text-lg">close</span>
                                </button>
                                <button type="button" onclick="takeSnapshot()" class="w-13 h-13 rounded-full bg-white hover:bg-slate-100 text-primary flex items-center justify-center shadow-2xl transition-all cursor-pointer ring-4 ring-primary/40 active:scale-90" title="Ambil Foto Sekarang">
                                    <span class="material-symbols-outlined text-2xl font-black text-primary">camera</span>
                                </button>
                                <button type="button" onclick="switchCameraFacing()" class="w-9 h-9 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center backdrop-blur-md transition-all cursor-pointer shadow-md" title="Ganti Kamera">
                                    <span class="material-symbols-outlined text-lg">flip_camera_ios</span>
                                </button>
                            </div>
                        </div>

                        <!-- 3. State: Preview Captured -->
                        <div id="camera-preview-box" class="hidden relative w-full aspect-4/3 sm:aspect-16/10 bg-slate-900 flex items-center justify-center overflow-hidden">
                            <img id="clockin-photo-preview" src="" alt="Selfie Absensi" class="w-full h-full object-cover">

                            <div class="absolute top-2.5 left-2.5 bg-emerald-600/90 text-white backdrop-blur-md px-2.5 py-1 rounded-lg text-xs font-bold flex items-center gap-1 shadow-md">
                                <span class="material-symbols-outlined text-sm">verified</span>
                                <span>Foto Siap Digunakan</span>
                            </div>

                            <div class="absolute bottom-2.5 inset-x-0 flex justify-center px-4">
                                <button type="button" onclick="retakePhoto()" class="px-3.5 py-1.5 bg-black/75 hover:bg-black/90 text-white text-xs font-bold rounded-xl backdrop-blur-md transition-all flex items-center gap-1.5 shadow-lg cursor-pointer active:scale-95">
                                    <span class="material-symbols-outlined text-sm">refresh</span>
                                    <span>Foto Ulang</span>
                                </button>
                            </div>
                        </div>

                        <canvas id="clockin-canvas" class="hidden"></canvas>
                        <input type="hidden" name="photo" id="clockin-photo-input" required>
                    </div>

                    <!-- Catatan -->
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface mb-1.5">Catatan Kehadiran <span class="font-normal text-on-surface-variant">(Opsional)</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[16px] text-on-surface-variant/70">chat_bubble</span>
                            <input type="text" name="note" placeholder="Contoh: Masuk tepat waktu, bersiap buka rolling door & briefing pagi..." class="w-full pl-9 pr-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary" />
                        </div>
                    </div>

                    <!-- Tombol aksi -->
                    <div class="space-y-2">
                        @if(!$hasSchedule)
                            <button type="button" disabled class="w-full py-3 px-4 bg-slate-200 text-slate-500 font-bold text-xs uppercase tracking-wider rounded-xl shadow-none flex items-center justify-center gap-2 cursor-not-allowed">
                                <span class="material-symbols-outlined text-lg">lock</span>
                                <span>Jadwal Belum Ditetapkan (Clock In Dikunci)</span>
                            </button>
                            <p class="text-[10px] text-center text-on-surface-variant">Tombol akan aktif otomatis saat jadwal shift aktif dan GPS terdeteksi dalam radius {{ $geofenceRadius }}m.</p>
                        @elseif($isTooEarlyToClockIn)
                            <button type="button" disabled class="w-full py-3 px-4 bg-sky-100 text-sky-800 font-bold text-xs uppercase tracking-wider rounded-xl shadow-none flex items-center justify-center gap-2 cursor-not-allowed border border-sky-300">
                                <span class="material-symbols-outlined text-lg">schedule</span>
                                <span>Clock In Dibuka Pukul {{ $earliestClockIn->format('H:i') }} WIB</span>
                            </button>
                            <p class="text-[10px] text-center text-sky-800 font-semibold">Anda baru dapat clock in {{ $infoShift->early_clock_in_minutes ?? 120 }} menit sebelum jam shift dimulai.</p>
                        @else
                            <button type="submit" id="clockin-submit-btn" class="w-full py-3 px-4 bg-primary text-on-primary hover:bg-[#00553a] font-bold text-xs uppercase tracking-wider rounded-xl shadow-xs transition-all flex items-center justify-center gap-2 active:scale-98 cursor-pointer">
                                <span class="material-symbols-outlined text-lg">login</span>
                                <span>Clock In Sekarang</span>
                            </button>
                            <p class="text-[10px] text-center text-on-surface-variant">Clock In memerlukan selfie dan GPS terdeteksi dalam radius {{ $geofenceRadius }}m dari toko.</p>
                        @endif
                    </div>
                </form>

                @elseif(!$todayAttendance->clock_out)
                <form action="{{ route('z1-app.clock-out') }}" method="POST" class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
                    @csrf
                    <input type="hidden" name="attendance_id" value="{{ $todayAttendance->id }}">
                    <input type="hidden" name="lat" id="clockout-lat-input">
                    <input type="hidden" name="lng" id="clockout-lng-input">

                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-extrabold text-on-surface inline-flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px] text-error">logout</span>
                                Clock Out
                            </h3>
                            <p class="text-[11px] text-on-surface-variant mt-0.5">Akhiri shift kerja Anda</p>
                        </div>
                        <span class="text-[10px] font-medium text-on-surface-variant shrink-0">Step 2 dari 2</span>
                    </div>

                    <div class="p-3.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-xs text-emerald-800">
                        @if($isOvernightShift)
                            <p class="font-bold mb-0.5">Sesi Kerja Lintas Hari: Clock In pada {{ \Carbon\Carbon::parse($todayAttendance->date)->isoFormat('dddd, D MMMM Y') }} pukul {{ \Carbon\Carbon::parse($todayAttendance->clock_in)->format('H:i') }} WIB.</p>
                        @else
                            <p class="font-bold mb-0.5">Anda sudah Clock In hari ini pada {{ \Carbon\Carbon::parse($todayAttendance->clock_in)->format('H:i') }} WIB.</p>
                        @endif
                        <p class="text-[11px] text-emerald-700">Pastikan seluruh pekerjaan atau shift telah selesai sebelum menekan tombol Clock Out di bawah.</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-on-surface mb-1.5">Catatan Pulang <span class="font-normal text-on-surface-variant">(Opsional)</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[16px] text-on-surface-variant/70">chat_bubble</span>
                            <input type="text" name="note" placeholder="Contoh: Shift selesai, serah terima kasir beres" class="w-full pl-9 pr-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary" />
                        </div>
                    </div>

                    @if($isPastClockOutCutoff)
                        <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 space-y-1">
                            <span class="font-extrabold flex items-center gap-1.5 text-rose-900">
                                <span class="material-symbols-outlined text-sm">lock_clock</span>
                                Batas Waktu Clock Out Telah Berakhir
                            </span>
                            <p class="text-[11px] leading-relaxed">
                                Batas maksimal clock out untuk shift ini telah berakhir pada pukul <strong>{{ $latestClockOut->format('H:i') }} WIB</strong> (+{{ $infoShift->max_clock_out_delay_minutes ?? 240 }} menit setelah shift). Silakan hubungi Admin Toko untuk bantuan koreksi absensi.
                            </p>
                        </div>
                        <button type="button" disabled class="w-full py-3 px-4 bg-slate-200 text-slate-500 font-bold text-xs uppercase tracking-wider rounded-xl shadow-none flex items-center justify-center gap-2 cursor-not-allowed">
                            <span class="material-symbols-outlined text-lg">lock</span>
                            <span>Batas Waktu Clock Out Berakhir</span>
                        </button>
                    @else
                        <button type="submit" class="w-full py-3 px-4 bg-error text-white hover:bg-red-700 font-bold text-xs uppercase tracking-wider rounded-xl shadow-xs transition-all flex items-center justify-center gap-2 active:scale-98 cursor-pointer">
                            <span class="material-symbols-outlined text-lg">logout</span>
                            <span>Clock Out Sekarang</span>
                        </button>
                    @endif
                </form>

                @else
                <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-8 shadow-xs text-center space-y-2">
                    <div class="w-14 h-14 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto">
                        <span class="material-symbols-outlined text-3xl font-bold">check_circle</span>
                    </div>
                    <h4 class="font-extrabold text-sm text-on-surface">Absensi Hari Ini Telah Lengkap</h4>
                    <p class="text-xs text-on-surface-variant max-w-sm mx-auto">
                        Terima kasih atas kerja keras Anda hari ini. Seluruh jam kerja telah tersimpan rapi di sistem.
                    </p>
                </div>
                @endif
            </div>
        </div>

        <!-- ═════ RIWAYAT ABSENSI ═════ -->
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl p-5 sm:p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="font-extrabold text-sm text-on-surface inline-flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-primary">bar_chart</span>
                        Riwayat Absensi Bulan Ini
                    </h3>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">Rekap kehadiran pribadi {{ $user->name }} periode {{ \Carbon\Carbon::createFromFormat('Y-m', $attendanceMonth)->locale('id')->isoFormat('MMMM Y') }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                    <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-800 font-bold border border-emerald-500/20 inline-flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ $attSummary['present'] }} Hadir
                    </span>
                    <span class="px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-800 font-bold border border-amber-500/20 inline-flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>{{ $attSummary['late'] }} Terlambat
                    </span>
                    <span class="px-2.5 py-1 rounded-full bg-blue-500/10 text-blue-800 font-bold border border-blue-500/20 inline-flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>{{ $attSummary['other'] }} Izin/Cuti
                    </span>
                    <span class="px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-800 font-bold border border-rose-500/20 inline-flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>{{ $attSummary['alpha'] }} Alpha
                    </span>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex items-center gap-5 border-b border-outline-variant/70 mb-5 text-xs font-semibold">
                <button type="button" id="att-tab-btn-month" onclick="switchAttTab('month')" class="att-tab-btn pb-2.5 -mb-px border-b-2 border-primary text-primary cursor-pointer">
                    Bulan Ini ({{ \Carbon\Carbon::createFromFormat('Y-m', $attendanceMonth)->locale('id')->isoFormat('MMMM Y') }})
                </button>
                <button type="button" id="att-tab-btn-last30" onclick="switchAttTab('last30')" class="att-tab-btn pb-2.5 -mb-px border-b-2 border-transparent text-on-surface-variant hover:text-on-surface cursor-pointer">
                    Rekap 30 Hari Terakhir
                </button>
            </div>

            <div id="att-panel-month">
            @if($attendanceHistory->isEmpty())
                <div class="text-center py-12">
                    <div class="w-12 h-12 rounded-xl bg-surface-container-low border border-outline-variant/70 flex items-center justify-center mx-auto mb-3 text-on-surface-variant">
                        <span class="material-symbols-outlined">folder_open</span>
                    </div>
                    <p class="text-xs font-extrabold text-on-surface">Belum ada rekaman absensi pada bulan ini</p>
                    <p class="text-[11px] text-on-surface-variant mt-1 max-w-sm mx-auto">Data absensi harian akan tercatat otomatis di tabel ini setelah Anda sukses melakukan Clock In dan Clock Out.</p>
                </div>
            @else
                <!-- Desktop Table -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-surface-container-low text-on-surface-variant text-[11px] font-bold uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-3.5">Tanggal</th>
                                <th class="py-3 px-3.5 text-center">Foto</th>
                                <th class="py-3 px-3.5 text-center">Lokasi</th>
                                <th class="py-3 px-3.5">Shift</th>
                                <th class="py-3 px-3.5">Clock In</th>
                                <th class="py-3 px-3.5">Clock Out</th>
                                <th class="py-3 px-3.5">Total Jam</th>
                                <th class="py-3 px-3.5">Status</th>
                                <th class="py-3 px-3.5">Catatan</th>
                                <th class="py-3 px-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/60 font-medium">
                            @foreach($attendanceHistory as $att)
                            @php
                                $attUser = $att->user ?? $user;
                                $lateMinutes = (int) ($att->late_minutes ?? 0);
                                $lateDuration = $att->late_duration;
                                $lateDurationLong = $att->late_duration_long;
                                if (!$lateDuration && ($att->status === 'late' || $lateMinutes > 0)) {
                                    if (!$lateMinutes && $att->shift && $att->clock_in) {
                                        $shiftStart = \Carbon\Carbon::parse(\Carbon\Carbon::parse($att->date)->toDateString() . ' ' . $att->shift->start_time);
                                        $clockIn = \Carbon\Carbon::parse($att->clock_in);
                                        if ($clockIn->gt($shiftStart)) {
                                            $lateMinutes = $shiftStart->diffInMinutes($clockIn);
                                        }
                                    }
                                    if ($lateMinutes > 0) {
                                        $lh = intdiv($lateMinutes, 60);
                                        $lm = $lateMinutes % 60;
                                        $lateDuration = ($lh > 0 && $lm > 0) ? "{$lh}j {$lm}m" : ($lh > 0 ? "{$lh} Jam" : "{$lm} Menit");
                                        $lateDurationLong = ($lh > 0 && $lm > 0) ? "{$lh} jam {$lm} menit" : ($lh > 0 ? "{$lh} jam" : "{$lm} menit");
                                    }
                                }
                                $statusLabel = ($att->status === 'late' || $lateMinutes > 0) ? ($lateDuration ? "Terlambat ({$lateDuration})" : ($att->status_label ?? 'Terlambat')) : $att->status_label;
                                $detailData = [
                                    'date' => \Carbon\Carbon::parse($att->date)->isoFormat('dddd, D MMMM Y'),
                                    'date_short' => \Carbon\Carbon::parse($att->date)->isoFormat('D MMM Y'),
                                    'shift' => $att->shift?->name ?? 'Shift Regular',
                                    'shift_time' => ($att->shift ? \Carbon\Carbon::parse($att->shift->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($att->shift->end_time)->format('H:i') . ' WIB' : 'Waktu Fleksibel'),
                                    'clock_in' => $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') . ' WIB' : '—',
                                    'clock_out' => $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') . ' WIB' : '—',
                                    'clock_in_photo' => $att->clock_in_photo ? asset('storage/' . $att->clock_in_photo) : null,
                                    'clock_out_photo' => $att->clock_out_photo ? asset('storage/' . $att->clock_out_photo) : null,
                                    'clock_in_distance' => $att->clock_in_distance_meters,
                                    'clock_out_distance' => $att->clock_out_distance_meters,
                                    'clock_in_lat' => $att->clock_in_lat,
                                    'clock_in_lng' => $att->clock_in_lng,
                                    'status_label' => $statusLabel,
                                    'status' => ($lateMinutes > 0) ? 'late' : $att->status,
                                    'late_minutes' => $lateMinutes,
                                    'late_duration' => $lateDuration,
                                    'late_duration_long' => $lateDurationLong,
                                    'work_hours' => $att->work_hours,
                                    'note' => $att->note,
                                    'user_name' => $attUser->name ?? $user->name,
                                    'user_photo' => ($attUser && $attUser->photo) ? Storage::url($attUser->photo) : null,
                                    'store_name' => $att->store?->name ?? $activeStore?->name ?? 'Cabang Toko',
                                ];
                            @endphp
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="py-3 px-3.5 font-bold text-on-surface">
                                    {{ \Carbon\Carbon::parse($att->date)->isoFormat('D MMM Y') }}
                                </td>
                                <td class="py-3 px-3.5 text-center">
                                    @if($attUser && $attUser->photo)
                                        <img src="{{ Storage::url($attUser->photo) }}" alt="{{ $attUser->name }}" class="w-8 h-8 rounded-full object-cover border border-outline-variant shadow-xs mx-auto" title="{{ $attUser->name }}">
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-[#006c49] text-white flex items-center justify-center font-bold text-xs shadow-xs mx-auto" title="{{ $attUser->name ?? 'User' }}">
                                            {{ strtoupper(substr($attUser->name ?? 'U', 0, 1)) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3 px-3.5 text-center">
                                    @if($att->clock_in_lat && $att->clock_in_lng)
                                        <a 
                                            href="https://www.google.com/maps?q={{ $att->clock_in_lat }},{{ $att->clock_in_lng }}" 
                                            target="_blank" 
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors border border-primary/20"
                                            title="Buka titik koordinat di Google Maps"
                                        >
                                            <span class="material-symbols-outlined text-xs">pin_drop</span>
                                            <span>{{ $att->clock_in_distance_meters !== null ? $att->clock_in_distance_meters . 'm' : 'Maps' }}</span>
                                        </a>
                                    @else
                                        <span class="text-on-surface-variant/40">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3.5">{{ $att->shift?->name ?? '—' }}</td>
                                <td class="py-3 px-3.5 font-semibold text-primary">
                                    {{ $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '—' }}
                                </td>
                                <td class="py-3 px-3.5 font-semibold text-error">
                                    {{ $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '—' }}
                                </td>
                                <td class="py-3 px-3.5">{{ $att->work_hours }}</td>
                                <td class="py-3 px-3.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ ($att->status === 'late' || $lateMinutes > 0) ? 'bg-amber-500/10 text-amber-700' : 'bg-emerald-500/10 text-emerald-700' }}" title="{{ $lateDurationLong ? 'Terlambat ' . $lateDurationLong : $statusLabel }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="py-3 px-3.5 text-on-surface-variant truncate max-w-[180px]">
                                    {{ $att->note ?? '—' }}
                                </td>
                                <td class="py-3 px-3.5 text-center">
                                    <button 
                                        type="button" 
                                        onclick='openAttendanceDetailModal(@json($detailData))'
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-surface-container hover:bg-primary hover:text-white text-on-surface transition-all cursor-pointer shadow-xs group"
                                        title="Lihat Detail Absensi (Tanggal, Shift, Foto Clock In & Out)"
                                    >
                                        <span class="material-symbols-outlined text-[19px] group-hover:scale-110 transition-transform">visibility</span>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="block sm:hidden space-y-2.5">
                    @foreach($attendanceHistory as $att)
                    @php
                        $attUser = $att->user ?? $user;
                        $lateMinutes = (int) ($att->late_minutes ?? 0);
                        $lateDuration = $att->late_duration;
                        $lateDurationLong = $att->late_duration_long;
                        if (!$lateDuration && ($att->status === 'late' || $lateMinutes > 0)) {
                            if (!$lateMinutes && $att->shift && $att->clock_in) {
                                $shiftStart = \Carbon\Carbon::parse(\Carbon\Carbon::parse($att->date)->toDateString() . ' ' . $att->shift->start_time);
                                $clockIn = \Carbon\Carbon::parse($att->clock_in);
                                if ($clockIn->gt($shiftStart)) {
                                    $lateMinutes = $shiftStart->diffInMinutes($clockIn);
                                }
                            }
                            if ($lateMinutes > 0) {
                                $lh = intdiv($lateMinutes, 60);
                                $lm = $lateMinutes % 60;
                                $lateDuration = ($lh > 0 && $lm > 0) ? "{$lh}j {$lm}m" : ($lh > 0 ? "{$lh} Jam" : "{$lm} Menit");
                                $lateDurationLong = ($lh > 0 && $lm > 0) ? "{$lh} jam {$lm} menit" : ($lh > 0 ? "{$lh} jam" : "{$lm} menit");
                            }
                        }
                        $statusLabel = ($att->status === 'late' || $lateMinutes > 0) ? ($lateDuration ? "Terlambat ({$lateDuration})" : ($att->status_label ?? 'Terlambat')) : $att->status_label;
                        $detailData = [
                            'date' => \Carbon\Carbon::parse($att->date)->isoFormat('dddd, D MMMM Y'),
                            'date_short' => \Carbon\Carbon::parse($att->date)->isoFormat('D MMM Y'),
                            'shift' => $att->shift?->name ?? 'Shift Regular',
                            'shift_time' => ($att->shift ? \Carbon\Carbon::parse($att->shift->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($att->shift->end_time)->format('H:i') . ' WIB' : 'Waktu Fleksibel'),
                            'clock_in' => $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') . ' WIB' : '—',
                            'clock_out' => $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') . ' WIB' : '—',
                            'clock_in_photo' => $att->clock_in_photo ? asset('storage/' . $att->clock_in_photo) : null,
                            'clock_out_photo' => $att->clock_out_photo ? asset('storage/' . $att->clock_out_photo) : null,
                            'clock_in_distance' => $att->clock_in_distance_meters,
                            'clock_out_distance' => $att->clock_out_distance_meters,
                            'clock_in_lat' => $att->clock_in_lat,
                            'clock_in_lng' => $att->clock_in_lng,
                            'status_label' => $statusLabel,
                            'status' => ($lateMinutes > 0) ? 'late' : $att->status,
                            'late_minutes' => $lateMinutes,
                            'late_duration' => $lateDuration,
                            'late_duration_long' => $lateDurationLong,
                            'work_hours' => $att->work_hours,
                            'note' => $att->note,
                            'user_name' => $attUser->name ?? $user->name,
                            'user_photo' => ($attUser && $attUser->photo) ? Storage::url($attUser->photo) : null,
                            'store_name' => $att->store?->name ?? $activeStore?->name ?? 'Cabang Toko',
                        ];
                    @endphp
                    <div class="p-3.5 rounded-xl border border-outline-variant bg-surface-container-low/60 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 min-w-0">
                                @if($attUser && $attUser->photo)
                                    <img src="{{ Storage::url($attUser->photo) }}" alt="{{ $attUser->name }}" class="w-7 h-7 rounded-full object-cover border border-outline-variant shadow-xs shrink-0" title="{{ $attUser->name }}">
                                @else
                                    <div class="w-7 h-7 rounded-full bg-[#006c49] text-white flex items-center justify-center font-bold text-[10px] shadow-xs shrink-0">
                                        {{ strtoupper(substr($attUser->name ?? 'U', 0, 1)) }}
                                    </div>
                                @endif
                                <span class="font-bold text-xs text-on-surface truncate">{{ \Carbon\Carbon::parse($att->date)->isoFormat('dddd, D MMM Y') }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ ($att->status === 'late' || $lateMinutes > 0) ? 'bg-amber-500/10 text-amber-700' : 'bg-emerald-500/10 text-emerald-700' }}" title="{{ $lateDurationLong ? 'Terlambat ' . $lateDurationLong : $statusLabel }}">
                                    {{ $statusLabel }}
                                </span>
                                <button 
                                    type="button" 
                                    onclick='openAttendanceDetailModal(@json($detailData))'
                                    class="p-1 rounded-lg bg-surface-container hover:bg-primary hover:text-white text-on-surface transition-colors cursor-pointer shadow-xs"
                                    title="Lihat Detail Absensi"
                                >
                                    <span class="material-symbols-outlined text-[17px]">visibility</span>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center p-2 rounded-lg bg-surface-container-lowest border border-outline-variant/60 text-xs">
                            <div>
                                <span class="text-[10px] text-on-surface-variant block">Clock In</span>
                                <span class="font-bold text-primary">{{ $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '—' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-on-surface-variant block">Clock Out</span>
                                <span class="font-bold text-error">{{ $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '—' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-on-surface-variant block">Durasi</span>
                                <span class="font-bold text-on-surface">{{ $att->work_hours }}</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-on-surface-variant">
                            <span>Shift: <strong class="text-on-surface">{{ $att->shift?->name ?? '—' }}</strong></span>
                            @if($att->clock_in_lat && $att->clock_in_lng)
                                <a 
                                    href="https://www.google.com/maps?q={{ $att->clock_in_lat }},{{ $att->clock_in_lng }}" 
                                    target="_blank" 
                                    class="text-primary font-bold hover:underline inline-flex items-center gap-0.5"
                                >
                                    <span class="material-symbols-outlined text-xs">pin_drop</span>
                                    <span>{{ $att->clock_in_distance_meters !== null ? $att->clock_in_distance_meters . 'm dari toko' : 'Lihat Lokasi' }}</span>
                                </a>
                            @endif
                        </div>
                        @if($att->note)
                            <div class="text-[11px] text-on-surface-variant italic truncate pt-1 border-t border-outline-variant/40">
                                "{{ $att->note }}"
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif
            </div>

            <!-- Panel: Rekap 30 Hari Terakhir -->
            <div id="att-panel-last30" class="hidden">
                @if($attendanceLast30->isEmpty())
                    <div class="text-center py-10">
                        <div class="w-12 h-12 rounded-xl bg-surface-container-low border border-outline-variant/70 flex items-center justify-center mx-auto mb-3 text-on-surface-variant">
                            <span class="material-symbols-outlined">folder_open</span>
                        </div>
                        <p class="text-xs font-extrabold text-on-surface">Belum ada rekaman absensi 30 hari terakhir</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-surface-container-low text-on-surface-variant text-[11px] font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="py-3 px-3.5">Tanggal</th>
                                    <th class="py-3 px-3.5">Shift</th>
                                    <th class="py-3 px-3.5">Clock In</th>
                                    <th class="py-3 px-3.5">Clock Out</th>
                                    <th class="py-3 px-3.5">Total Jam</th>
                                    <th class="py-3 px-3.5">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/60 font-medium">
                                @foreach($attendanceLast30 as $a30)
                                <tr class="hover:bg-surface-container-low transition-colors">
                                    <td class="py-3 px-3.5 font-bold text-on-surface whitespace-nowrap">{{ \Carbon\Carbon::parse($a30->date)->locale('id')->isoFormat('ddd, D MMM Y') }}</td>
                                    <td class="py-3 px-3.5">{{ $a30->shift?->name ?? '—' }}</td>
                                    <td class="py-3 px-3.5 font-semibold text-primary">{{ $a30->clock_in ? \Carbon\Carbon::parse($a30->clock_in)->format('H:i') : '—' }}</td>
                                    <td class="py-3 px-3.5 font-semibold text-error">{{ $a30->clock_out ? \Carbon\Carbon::parse($a30->clock_out)->format('H:i') : '—' }}</td>
                                    <td class="py-3 px-3.5">{{ $a30->work_hours }}</td>
                                    <td class="py-3 px-3.5">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $a30->status === 'late' ? 'bg-amber-500/10 text-amber-700' : ($a30->status === 'absent' ? 'bg-rose-500/10 text-rose-700' : 'bg-emerald-500/10 text-emerald-700') }}">
                                            {{ $a30->status_label }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
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
                    <h4 class="font-extrabold text-sm text-on-surface">Detail Kehadiran Absensi</h4>
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
        <div class="px-5 py-3 border-t border-outline-variant/60 bg-surface-container-low flex justify-end">
            <button type="button" onclick="closeAttendanceDetailModal()" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-bold rounded-xl transition-all cursor-pointer">
                Tutup
            </button>
        </div>
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

    <!-- Script for Tab 1: Shift Dropdown & Camera Selfie Attendance -->
    <script>
        // --- 1. SHIFT DROPDOWN LOGIC ---
        function toggleShiftDropdown(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            const menu = document.getElementById('shift-dropdown-menu');
            const arrow = document.getElementById('shift-dropdown-arrow');
            const trigger = document.getElementById('shift-dropdown-trigger');
            if (!menu) return;

            const isHidden = menu.classList.contains('hidden');
            if (isHidden) {
                menu.classList.remove('hidden');
                if (arrow) arrow.classList.add('rotate-180');
                if (trigger) trigger.setAttribute('aria-expanded', 'true');
            } else {
                closeShiftDropdown();
            }
        }

        function closeShiftDropdown() {
            const menu = document.getElementById('shift-dropdown-menu');
            const arrow = document.getElementById('shift-dropdown-arrow');
            const trigger = document.getElementById('shift-dropdown-trigger');
            if (menu) menu.classList.add('hidden');
            if (arrow) arrow.classList.remove('rotate-180');
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        }

        function selectShift(id, name, timeRange, icon, iconBg) {
            const input = document.getElementById('clockin-shift-id-input');
            if (input) input.value = id;

            const nameEl = document.getElementById('shift-trigger-name');
            const timeEl = document.getElementById('shift-trigger-time');
            const iconEl = document.getElementById('shift-trigger-icon');
            const iconBoxEl = document.getElementById('shift-trigger-icon-box');

            if (nameEl) nameEl.textContent = name;
            if (timeEl) timeEl.textContent = timeRange;
            if (iconEl) iconEl.textContent = icon;
            if (iconBoxEl) iconBoxEl.className = 'w-7 h-7 rounded-lg flex items-center justify-center shrink-0 ' + iconBg;

            document.querySelectorAll('.shift-check').forEach(el => el.classList.add('hidden'));
            const activeCheck = document.getElementById('shift-check-' + id);
            if (activeCheck) activeCheck.classList.remove('hidden');

            closeShiftDropdown();
        }

        document.addEventListener('click', function(e) {
            const container = document.getElementById('shift-dropdown-container');
            if (container && !container.contains(e.target)) {
                closeShiftDropdown();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeShiftDropdown();
            }
        });

        // --- 2. CAMERA SELFIE ATTENDANCE LOGIC ---
        let mediaStream = null;
        let currentFacingMode = 'user'; // front camera default

        async function startCamera() {
            const video = document.getElementById('clockin-video');
            const placeholder = document.getElementById('camera-placeholder');
            const activeBox = document.getElementById('camera-active-box');
            const previewBox = document.getElementById('camera-preview-box');
            const statusPill = document.getElementById('camera-status-pill');
            const errorAlert = document.getElementById('camera-error-alert');
            const box = document.getElementById('camera-card-box');

            if (errorAlert) errorAlert.classList.add('hidden');
            if (box) {
                box.classList.remove('border-rose-500', 'bg-rose-500/5');
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Browser atau perangkat Anda tidak mendukung akses kamera langsung. Harap gunakan perangkat atau browser yang memiliki kamera aktif.');
                return;
            }

            stopCameraStream();

            try {
                const constraints = {
                    audio: false,
                    video: {
                        facingMode: currentFacingMode,
                        width: { ideal: 1280 },
                        height: { ideal: 960 }
                    }
                };

                mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
                if (video) {
                    video.srcObject = mediaStream;
                    video.play();
                }

                if (placeholder) placeholder.classList.add('hidden');
                if (previewBox) previewBox.classList.add('hidden');
                if (activeBox) activeBox.classList.remove('hidden');

                if (statusPill) {
                    statusPill.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold bg-sky-500/10 text-sky-700 border border-sky-500/20 uppercase tracking-wider flex items-center gap-1';
                    statusPill.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-ping"></span><span>Kamera Aktif</span>';
                }
            } catch (err) {
                console.error('Camera access error:', err);
                let errorMsg = 'Gagal mengakses kamera. Harap berikan izin akses kamera (Camera Permission) pada browser Anda untuk verifikasi kehadiran.';
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    errorMsg = 'Akses kamera ditolak. Silakan buka pengaturan browser dan izinkan akses kamera untuk dapat melakukan Clock In.';
                }
                alert(errorMsg);
                if (placeholder) placeholder.classList.remove('hidden');
                if (activeBox) activeBox.classList.add('hidden');
            }
        }

        function stopCameraStream() {
            if (mediaStream) {
                mediaStream.getTracks().forEach(track => track.stop());
                mediaStream = null;
            }
        }

        function stopCamera() {
            stopCameraStream();
            const video = document.getElementById('clockin-video');
            if (video) video.srcObject = null;

            const placeholder = document.getElementById('camera-placeholder');
            const activeBox = document.getElementById('camera-active-box');
            const photoInput = document.getElementById('clockin-photo-input');
            const previewBox = document.getElementById('camera-preview-box');
            const statusPill = document.getElementById('camera-status-pill');

            if (photoInput && photoInput.value) {
                if (activeBox) activeBox.classList.add('hidden');
                if (previewBox) previewBox.classList.remove('hidden');
            } else {
                if (activeBox) activeBox.classList.add('hidden');
                if (placeholder) placeholder.classList.remove('hidden');
                if (statusPill) {
                    statusPill.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-500/10 text-amber-700 border border-amber-500/20 uppercase tracking-wider flex items-center gap-1';
                    statusPill.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span><span>Wajib Ambil Foto</span>';
                }
            }
        }

        function switchCameraFacing() {
            currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
            const video = document.getElementById('clockin-video');
            if (video) {
                if (currentFacingMode === 'user') {
                    video.classList.add('-scale-x-100');
                } else {
                    video.classList.remove('-scale-x-100');
                }
            }
            startCamera();
        }

        function takeSnapshot() {
            const video = document.getElementById('clockin-video');
            const canvas = document.getElementById('clockin-canvas');
            const preview = document.getElementById('clockin-photo-preview');
            const photoInput = document.getElementById('clockin-photo-input');
            const activeBox = document.getElementById('camera-active-box');
            const previewBox = document.getElementById('camera-preview-box');
            const statusPill = document.getElementById('camera-status-pill');
            const errorAlert = document.getElementById('camera-error-alert');
            const box = document.getElementById('camera-card-box');

            if (!video || !canvas || !photoInput) return;

            const width = video.videoWidth || 640;
            const height = video.videoHeight || 480;

            canvas.width = width;
            canvas.height = height;

            const ctx = canvas.getContext('2d');
            if (currentFacingMode === 'user') {
                ctx.translate(width, 0);
                ctx.scale(-1, 1);
            }
            ctx.drawImage(video, 0, 0, width, height);

            const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
            photoInput.value = dataUrl;

            if (preview) preview.src = dataUrl;

            stopCameraStream();

            if (activeBox) activeBox.classList.add('hidden');
            if (previewBox) previewBox.classList.remove('hidden');

            if (statusPill) {
                statusPill.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-500/15 text-emerald-700 border border-emerald-500/30 uppercase tracking-wider flex items-center gap-1';
                statusPill.innerHTML = '<span class="material-symbols-outlined text-xs">verified</span><span>Foto Siap (Terverifikasi)</span>';
            }

            if (errorAlert) errorAlert.classList.add('hidden');
            if (box) {
                box.classList.remove('border-rose-500', 'bg-rose-500/5', 'border-dashed');
                box.classList.add('border-emerald-500/60');
            }
        }

        function retakePhoto() {
            const photoInput = document.getElementById('clockin-photo-input');
            if (photoInput) photoInput.value = '';

            const preview = document.getElementById('clockin-photo-preview');
            if (preview) preview.src = '';

            const box = document.getElementById('camera-card-box');
            if (box) {
                box.classList.remove('border-emerald-500/60');
                box.classList.add('border-dashed');
            }

            startCamera();
        }

        // --- 3. GEOFENCING GPS ATTENDANCE LOGIC ---
        const STORE_GEOFENCE = {
            hasGeofence: {{ ($activeStore && $activeStore->hasGeofence()) ? 'true' : 'false' }},
            lat: {{ ($activeStore && $activeStore->latitude) ? (float)$activeStore->latitude : 'null' }},
            lng: {{ ($activeStore && $activeStore->longitude) ? (float)$activeStore->longitude : 'null' }},
            radius: {{ ($activeStore && $activeStore->radius_meters) ? (int)$activeStore->radius_meters : 100 }},
            name: "{{ addslashes($activeStore->name ?? 'Toko') }}"
        };

        let currentCoords = { lat: null, lng: null };
        let currentDistance = null;

        function calculateHaversineDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000; // Earth radius in meters
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return Math.round(R * c);
        }

        // --- UI helpers: nada kotak GPS, akurasi/sinyal, jam live, tab riwayat ---
        function setGpsTone(tone) {
            const box = document.getElementById('gps-info-box');
            const iconBox = document.getElementById('gps-icon-box');
            const title = document.getElementById('gps-title');
            if (!box) return;
            const tones = {
                ok:      { box: 'bg-emerald-500/10 border-emerald-500/30', icon: 'bg-emerald-500/15 text-emerald-700', title: 'text-emerald-800' },
                bad:     { box: 'bg-rose-500/10 border-rose-500/30',       icon: 'bg-rose-500/15 text-rose-700',       title: 'text-rose-700' },
                info:    { box: 'bg-sky-500/10 border-sky-500/30',         icon: 'bg-sky-500/15 text-sky-700',         title: 'text-sky-800' },
                neutral: { box: 'bg-surface-container-low border-outline-variant/70', icon: 'bg-surface-container text-on-surface-variant', title: 'text-on-surface' }
            };
            const t = tones[tone] || tones.neutral;
            box.className = 'p-3.5 rounded-xl border text-xs flex items-start gap-3 ' + t.box;
            if (iconBox) iconBox.className = 'w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ' + t.icon;
            if (title) title.className = 'font-extrabold text-[11px] uppercase tracking-wide ' + t.title;
        }

        function setGpsSignal(accuracyMeters) {
            const accEl = document.getElementById('gps-accuracy');
            const sigEl = document.getElementById('gps-signal');
            if (!accEl || !sigEl) return;
            if (accuracyMeters === null || accuracyMeters === undefined) {
                accEl.textContent = '—';
                sigEl.className = 'font-bold text-rose-600 inline-flex items-center gap-1';
                sigEl.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Tidak ada sinyal';
                return;
            }
            const acc = Math.round(accuracyMeters);
            accEl.textContent = '± ' + acc + ' meter';
            let cls = 'text-emerald-700', dot = 'bg-emerald-500', label = 'Sinyal Stabil';
            if (acc > 100) { cls = 'text-rose-600'; dot = 'bg-rose-500'; label = 'Sinyal Lemah'; }
            else if (acc > 30) { cls = 'text-amber-600'; dot = 'bg-amber-500'; label = 'Sinyal Cukup'; }
            sigEl.className = 'font-bold inline-flex items-center gap-1 ' + cls;
            sigEl.innerHTML = '<span class="w-1.5 h-1.5 rounded-full ' + dot + '"></span> ' + label;
        }

        function tickLiveClock() {
            const el = document.getElementById('live-clock-time');
            if (!el) return;
            el.textContent = new Date().toLocaleTimeString('en-GB', { timeZone: 'Asia/Jakarta', hour12: false });
        }
        tickLiveClock();
        setInterval(tickLiveClock, 1000);

        function switchAttTab(tab) {
            const panels = { month: document.getElementById('att-panel-month'), last30: document.getElementById('att-panel-last30') };
            Object.keys(panels).forEach(function(key) {
                if (panels[key]) panels[key].classList.toggle('hidden', key !== tab);
                const btn = document.getElementById('att-tab-btn-' + key);
                if (btn) {
                    btn.classList.toggle('border-primary', key === tab);
                    btn.classList.toggle('text-primary', key === tab);
                    btn.classList.toggle('border-transparent', key !== tab);
                    btn.classList.toggle('text-on-surface-variant', key !== tab);
                }
            });
        }

        function refreshGeolocation() {
            const statusPill = document.getElementById('gps-status-pill');
            const iconBox = document.getElementById('gps-icon-box');
            const icon = document.getElementById('gps-icon');
            const title = document.getElementById('gps-title');
            const desc = document.getElementById('gps-desc');
            const errorAlert = document.getElementById('gps-error-alert');

            if (!navigator.geolocation) {
                if (title) title.textContent = 'Browser tidak mendukung GPS';
                if (statusPill) {
                    statusPill.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold bg-rose-500/10 text-rose-700 border border-rose-500/20 uppercase tracking-wider flex items-center gap-1';
                    statusPill.innerHTML = '<span class="material-symbols-outlined text-xs">location_off</span><span>GPS Tidak Didukung</span>';
                }
                return;
            }

            if (statusPill) {
                statusPill.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold bg-sky-500/10 text-sky-700 border border-sky-500/20 uppercase tracking-wider flex items-center gap-1';
                statusPill.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-ping"></span><span>Mencari Sinyal GPS...</span>';
            }
            if (title) title.textContent = 'Mendeteksi posisi koordinat GPS presisi...';
            setGpsTone('neutral');

            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    currentCoords = { lat, lng };
                    setGpsSignal(pos.coords.accuracy);

                    // Set hidden inputs
                    const inLat = document.getElementById('clockin-lat-input');
                    const inLng = document.getElementById('clockin-lng-input');
                    const outLat = document.getElementById('clockout-lat-input');
                    const outLng = document.getElementById('clockout-lng-input');

                    if (inLat) inLat.value = lat;
                    if (inLng) inLng.value = lng;
                    if (outLat) outLat.value = lat;
                    if (outLng) outLng.value = lng;

                    if (STORE_GEOFENCE.hasGeofence) {
                        currentDistance = calculateHaversineDistance(lat, lng, STORE_GEOFENCE.lat, STORE_GEOFENCE.lng);

                        if (currentDistance <= STORE_GEOFENCE.radius) {
                            // Within radius
                            if (statusPill) {
                                statusPill.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-500/15 text-emerald-700 border border-emerald-500/30 uppercase tracking-wider flex items-center gap-1';
                                statusPill.innerHTML = `<span class="material-symbols-outlined text-xs">verified</span><span>Dalam Radius (${currentDistance}m)</span>`;
                            }
                            if (iconBox) iconBox.className = 'w-8 h-8 rounded-lg bg-emerald-500/15 text-emerald-700 flex items-center justify-center shrink-0';
                            if (icon) icon.textContent = 'check_circle';
                            setGpsTone('ok');
                            if (title) title.textContent = `Lokasi Terverifikasi di ${STORE_GEOFENCE.name}`;
                            if (desc) desc.innerHTML = `Jarak Anda saat ini: <strong class="text-emerald-700">${currentDistance} meter</strong> dari toko (Batas toleransi: ${STORE_GEOFENCE.radius}m).`;
                            if (errorAlert) errorAlert.classList.add('hidden');
                        } else {
                            // Outside radius
                            if (statusPill) {
                                statusPill.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold bg-rose-500/15 text-rose-700 border border-rose-500/30 uppercase tracking-wider flex items-center gap-1';
                                statusPill.innerHTML = `<span class="material-symbols-outlined text-xs">warning</span><span>Di Luar Radius (${currentDistance}m)</span>`;
                            }
                            if (iconBox) iconBox.className = 'w-8 h-8 rounded-lg bg-rose-500/15 text-rose-700 flex items-center justify-center shrink-0';
                            if (icon) icon.textContent = 'wrong_location';
                            setGpsTone('bad');
                            if (title) title.textContent = `Di Luar Radius Toko ${STORE_GEOFENCE.name}`;
                            if (desc) desc.innerHTML = `Jarak Anda: <strong class="text-rose-700">${currentDistance}m</strong> dari toko (Toleransi: ${STORE_GEOFENCE.radius}m).`;
                            if (errorAlert) errorAlert.classList.add('hidden');
                        }
                    } else {
                        // Store doesn't have geofence coordinates configured
                        if (statusPill) {
                            statusPill.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold bg-sky-500/10 text-sky-700 border border-sky-500/20 uppercase tracking-wider flex items-center gap-1';
                            statusPill.innerHTML = '<span class="material-symbols-outlined text-xs">pin_drop</span><span>GPS Terdeteksi</span>';
                        }
                        if (iconBox) iconBox.className = 'w-8 h-8 rounded-lg bg-sky-500/10 text-sky-700 flex items-center justify-center shrink-0';
                        if (icon) icon.textContent = 'my_location';
                        setGpsTone('info');
                        if (title) title.textContent = 'Koordinat GPS Berhasil Dicatat';
                        if (desc) desc.innerHTML = `Koordinat: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                        if (errorAlert) errorAlert.classList.add('hidden');
                    }
                },
                function(err) {
                    console.error('Geolocation error:', err);
                    let errTitle = 'Gagal Mengakses GPS';
                    let errMsg = 'Pastikan GPS HP aktif dan berikan izin lokasi pada browser.';
                    if (err.code === 1) {
                        errTitle = 'Izin Akses Lokasi Ditolak';
                        errMsg = 'Silakan izinkan akses lokasi di pengaturan browser agar dapat melakukan absensi.';
                    } else if (err.code === 2) {
                        errMsg = 'Posisi perangkat tidak dapat ditentukan. Coba pindah ke area terbuka.';
                    } else if (err.code === 3) {
                        errMsg = 'Waktu permintaan lokasi GPS habis. Silakan tekan tombol "Cek GPS" kembali.';
                    }

                    if (statusPill) {
                        statusPill.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold bg-rose-500/10 text-rose-700 border border-rose-500/20 uppercase tracking-wider flex items-center gap-1';
                        statusPill.innerHTML = '<span class="material-symbols-outlined text-xs">location_disabled</span><span>GPS Belum Aktif</span>';
                    }
                    if (title) title.textContent = errTitle;
                    setGpsTone('bad');
                    setGpsSignal(null);
                    if (desc) desc.textContent = errMsg;

                    if (errorAlert) {
                        errorAlert.classList.remove('hidden');
                        const errTitleEl = document.getElementById('gps-error-title');
                        const errDescEl = document.getElementById('gps-error-desc');
                        if (errTitleEl) errTitleEl.textContent = errTitle;
                        if (errDescEl) errDescEl.textContent = errMsg;
                    }
                },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
            );
        }

        // Initialize geolocation on page load
        document.addEventListener('DOMContentLoaded', function() {
            refreshGeolocation();
        });

        function validateClockInForm(e) {
            // 1. Photo validation (Wajib)
            const photoInput = document.getElementById('clockin-photo-input');
            const photoValue = photoInput ? photoInput.value.trim() : '';

            if (!photoValue || photoValue.length < 50) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                const box = document.getElementById('camera-card-box');
                const errorAlert = document.getElementById('camera-error-alert');

                if (box) {
                    box.classList.remove('border-outline-variant', 'border-emerald-500/60');
                    box.classList.add('border-rose-500', 'bg-rose-500/5');
                    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                if (errorAlert) {
                    errorAlert.classList.remove('hidden');
                }

                // Auto open camera if currently on placeholder
                const activeBox = document.getElementById('camera-active-box');
                const previewBox = document.getElementById('camera-preview-box');
                if (activeBox && activeBox.classList.contains('hidden') && previewBox && previewBox.classList.contains('hidden')) {
                    startCamera();
                }

                return false;
            }

            // 2. Geofence & GPS validation
            if (STORE_GEOFENCE.hasGeofence) {
                const latInput = document.getElementById('clockin-lat-input');
                const lngInput = document.getElementById('clockin-lng-input');
                const hasCoords = latInput && latInput.value && lngInput && lngInput.value;

                if (!hasCoords) {
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    const gpsContainer = document.getElementById('gps-status-container');
                    if (gpsContainer) gpsContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    refreshGeolocation();
                    alert('Lokasi GPS Anda belum terdeteksi. Harap izinkan akses lokasi (GPS) di browser untuk melanjutkan Clock In.');
                    return false;
                }

                if (currentDistance !== null && currentDistance > STORE_GEOFENCE.radius) {
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    const gpsContainer = document.getElementById('gps-status-container');
                    if (gpsContainer) gpsContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    alert(`Gagal Clock In: Anda berada di luar radius toko ${STORE_GEOFENCE.name}. Jarak Anda saat ini: ${currentDistance} meter (Batas toleransi maksimal: ${STORE_GEOFENCE.radius} meter).`);
                    return false;
                }
            }

            // Photo and Location valid, proceed with submission
            const submitBtn = document.getElementById('clockin-submit-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                submitBtn.innerHTML = '<span class="material-symbols-outlined text-lg animate-spin">progress_activity</span><span>Memproses Clock In...</span>';
            }
            return true;
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

        // --- 4. ATTENDANCE DETAIL MODAL LOGIC ---
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
                } else {
                    statusEl.className = 'px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-500/10 text-emerald-700 border border-emerald-500/20';
                }
                statusEl.textContent = label;
            }

            const durEl = document.getElementById('att-detail-duration');
            if (durEl) durEl.textContent = 'Total: ' + (data.work_hours || '—');

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
    </script>
    @endif

    <!-- TAB 2: PURCHASE REQUEST (ORDER BARANG) - INDEX & DETAIL -->
    @if($activeTab === 'purchase_request')
    <div class="space-y-6">
        {{-- Header & Action Button --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
            <div>
                <h1 class="font-headline-lg text-lg sm:text-headline-lg text-on-surface font-black">Order Barang (Purchase Request)</h1>
                <p class="text-on-surface-variant text-xs sm:text-sm mt-0.5">Kelola daftar pengajuan order barang, rincian item, dan status persetujuan restock toko.</p>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button
                    type="button"
                    onclick="openCreatePrModal()"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-[#00553a] text-on-primary font-bold rounded-xl text-xs sm:text-sm hover:opacity-95 active:scale-95 transition-all shadow-xs cursor-pointer border-0"
                >
                    <span class="material-symbols-outlined text-base">calendar_add_on</span>
                    <span>Buat Order Barang</span>
                </button>
            </div>
        </div>

        {{-- Metric Cards (3 Cards sesuai screenshot Stock In) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            <!-- Card 1: Total Logs -->
            <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-surface-container-high text-on-surface-variant flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl text-primary">receipt_long</span>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Log Transaksi</p>
                    <h3 class="text-xl font-headline-md font-extrabold text-on-surface mt-0.5 truncate">{{ number_format($totalPrOrders ?? 0) }} Log</h3>
                </div>
            </div>

            <!-- Card 2: Total Units -->
            <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-secondary-container/40 text-on-secondary-container flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">inventory_2</span>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Unit Dipesan</p>
                    <h3 class="text-xl font-headline-md font-extrabold text-on-surface mt-0.5 truncate">{{ number_format($totalPrUnits ?? 0) }} Pcs</h3>
                </div>
            </div>

            <!-- Card 3: Total Nilai Transaksi -->
            <div class="p-4 sm:p-5 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">payments</span>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider truncate">Total Nilai Estimasi</p>
                    <h3 class="text-xl font-headline-md font-extrabold text-emerald-800 font-mono mt-0.5 truncate">Rp {{ number_format($totalPrAmount ?? 0, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 shadow-xs">
            <form id="prFilterForm" method="GET" action="{{ route('z1-app.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                <input type="hidden" name="tab" value="purchase_request">

                <!-- Tanggal Sesi -->
                <div class="sm:col-span-4">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Tanggal Sesi</label>
                    <input type="date" name="pr_date" value="{{ $prDate ?? '' }}" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:border-primary">
                </div>

                <!-- Filter Status (Custom Styled Dropdown) -->
                <div class="sm:col-span-5 relative" id="pr-status-dropdown-container">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Filter Status</label>
                    <input type="hidden" name="pr_status" id="pr_status_input" value="{{ $prStatus ?? 'all' }}">

                    <!-- Trigger Button -->
                    <button 
                        type="button" 
                        id="pr-status-btn"
                        onclick="togglePrStatusDropdown(event)"
                        class="w-full px-3 py-2 bg-surface-container-low hover:bg-surface-container border border-outline-variant hover:border-primary/60 focus:border-primary rounded-xl text-xs font-medium text-on-surface flex items-center justify-between gap-2 transition-all shadow-2xs cursor-pointer select-none"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                    >
                        <span class="flex items-center gap-2 truncate">
                            <span id="pr-status-dot" class="w-2 h-2 rounded-full shrink-0 {{ ($prStatus ?? 'all') === 'pending' ? 'bg-amber-500' : (($prStatus ?? 'all') === 'approved' ? 'bg-emerald-500' : (($prStatus ?? 'all') === 'completed' ? 'bg-blue-500' : (($prStatus ?? 'all') === 'rejected' ? 'bg-rose-500' : 'bg-slate-400'))) }}"></span>
                            <span id="pr-status-label" class="truncate font-semibold">
                                @switch($prStatus ?? 'all')
                                    @case('pending') Pending (Menunggu Review) @break
                                    @case('approved') Approved (Disetujui Gudang) @break
                                    @case('completed') Completed (Barang Diterima) @break
                                    @case('rejected') Rejected (Ditolak) @break
                                    @default Semua Status
                                @endswitch
                            </span>
                        </span>
                        <span class="material-symbols-outlined text-[18px] text-on-surface-variant transition-transform duration-200" id="pr-status-chevron">expand_more</span>
                    </button>

                    <!-- Dropdown Options Menu -->
                    <div 
                        id="pr-status-menu" 
                        class="hidden absolute left-0 right-0 top-full mt-1.5 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-xl p-1.5 z-50 space-y-0.5"
                        role="listbox"
                    >
                        <!-- Option: Semua Status -->
                        <button 
                            type="button" 
                            onclick="setPrStatus('all', 'Semua Status', 'bg-slate-400')" 
                            class="w-full px-3 py-2 rounded-xl text-xs font-medium flex items-center justify-between gap-2 transition-colors hover:bg-surface-container-low cursor-pointer {{ ($prStatus ?? 'all') === 'all' ? 'bg-primary/10 text-primary font-bold' : 'text-on-surface' }}"
                        >
                            <span class="flex items-center gap-2 truncate">
                                <span class="w-2 h-2 rounded-full bg-slate-400 shrink-0"></span>
                                <span>Semua Status</span>
                            </span>
                            @if(($prStatus ?? 'all') === 'all')
                                <span class="material-symbols-outlined text-base text-primary">check</span>
                            @endif
                        </button>

                        <!-- Option: Pending -->
                        <button 
                            type="button" 
                            onclick="setPrStatus('pending', 'Pending (Menunggu Review)', 'bg-amber-500')" 
                            class="w-full px-3 py-2 rounded-xl text-xs font-medium flex items-center justify-between gap-2 transition-colors hover:bg-amber-500/10 cursor-pointer {{ ($prStatus ?? '') === 'pending' ? 'bg-amber-500/15 text-amber-800 font-bold' : 'text-on-surface' }}"
                        >
                            <span class="flex items-center gap-2 truncate">
                                <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                                <span>Pending (Menunggu Review)</span>
                            </span>
                            @if(($prStatus ?? '') === 'pending')
                                <span class="material-symbols-outlined text-base text-amber-700">check</span>
                            @endif
                        </button>

                        <!-- Option: Approved -->
                        <button 
                            type="button" 
                            onclick="setPrStatus('approved', 'Approved (Disetujui Gudang)', 'bg-emerald-500')" 
                            class="w-full px-3 py-2 rounded-xl text-xs font-medium flex items-center justify-between gap-2 transition-colors hover:bg-emerald-500/10 cursor-pointer {{ ($prStatus ?? '') === 'approved' ? 'bg-emerald-500/15 text-emerald-800 font-bold' : 'text-on-surface' }}"
                        >
                            <span class="flex items-center gap-2 truncate">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                <span>Approved (Disetujui Gudang)</span>
                            </span>
                            @if(($prStatus ?? '') === 'approved')
                                <span class="material-symbols-outlined text-base text-emerald-700">check</span>
                            @endif
                        </button>

                        <!-- Option: Completed -->
                        <button 
                            type="button" 
                            onclick="setPrStatus('completed', 'Completed (Barang Diterima)', 'bg-blue-500')" 
                            class="w-full px-3 py-2 rounded-xl text-xs font-medium flex items-center justify-between gap-2 transition-colors hover:bg-blue-500/10 cursor-pointer {{ ($prStatus ?? '') === 'completed' ? 'bg-blue-500/15 text-blue-800 font-bold' : 'text-on-surface' }}"
                        >
                            <span class="flex items-center gap-2 truncate">
                                <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                                <span>Completed (Barang Diterima)</span>
                            </span>
                            @if(($prStatus ?? '') === 'completed')
                                <span class="material-symbols-outlined text-base text-blue-700">check</span>
                            @endif
                        </button>

                        <!-- Option: Rejected -->
                        <button 
                            type="button" 
                            onclick="setPrStatus('rejected', 'Rejected (Ditolak)', 'bg-rose-500')" 
                            class="w-full px-3 py-2 rounded-xl text-xs font-medium flex items-center justify-between gap-2 transition-colors hover:bg-rose-500/10 cursor-pointer {{ ($prStatus ?? '') === 'rejected' ? 'bg-rose-500/15 text-rose-800 font-bold' : 'text-on-surface' }}"
                        >
                            <span class="flex items-center gap-2 truncate">
                                <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span>
                                <span>Rejected (Ditolak)</span>
                            </span>
                            @if(($prStatus ?? '') === 'rejected')
                                <span class="material-symbols-outlined text-base text-rose-700">check</span>
                            @endif
                        </button>
                    </div>
                </div>

                <!-- Button Filter -->
                <div class="sm:col-span-3 flex items-end gap-2 pt-2 sm:pt-0">
                    <button type="submit" class="flex-1 py-2 px-4 bg-primary hover:bg-[#00553a] text-on-primary font-bold text-xs rounded-xl shadow-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-sm">filter_alt</span>
                        <span>Filter</span>
                    </button>
                    @if(!empty($prDate) || (!empty($prStatus) && $prStatus !== 'all'))
                        <a href="{{ route('z1-app.index', ['tab' => 'purchase_request']) }}" class="p-2 rounded-xl border border-outline-variant bg-surface-container-low text-on-surface-variant hover:text-on-surface text-xs font-bold flex items-center justify-center" title="Reset Filter">
                            <span class="material-symbols-outlined text-sm">restart_alt</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table List Index (Desktop: Table, Mobile: Cards) --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xs overflow-hidden">
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-surface-container-low border-b border-outline-variant/60 text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                        <tr>
                            <th class="py-3 px-4 w-10 text-center">
                                <input type="checkbox" disabled class="rounded border-outline-variant text-primary focus:ring-primary/20">
                            </th>
                            <th class="py-3 px-4">Tanggal Sesi</th>
                            <th class="py-3 px-4">No. Order</th>
                            <th class="py-3 px-4 text-center">Total Item</th>
                            <th class="py-3 px-4 text-right">Total Nilai</th>
                            <th class="py-3 px-4">Rincian Pembayaran / Catatan</th>
                            <th class="py-3 px-4">Dicatat Oleh</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/60">
                        @forelse($purchaseRequests as $pr)
                        @php
                            $prItemsCount = $pr->items->count();
                            $prTotalUnits = $pr->items->sum('quantity_requested');
                            $prEstTotal = $pr->items->sum(fn($i) => ($i->product?->purchase_price ?? $i->product?->price ?? 0) * $i->quantity_requested);
                        @endphp
                        <tr class="hover:bg-surface-container-low/70 transition-colors">
                            <td class="py-3.5 px-4 text-center">
                                <input type="checkbox" class="rounded border-outline-variant text-primary focus:ring-primary/20">
                            </td>

                            <!-- Tanggal Sesi -->
                            <td class="py-3.5 px-4">
                                <a href="{{ route('z1-app.purchase-request.show', $pr->id) }}" class="flex items-center gap-2.5 group">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                        <span class="material-symbols-outlined text-base">calendar_today</span>
                                    </div>
                                    <div>
                                        <span class="font-bold text-on-surface group-hover:text-primary transition-colors block">{{ \Carbon\Carbon::parse($pr->request_date)->isoFormat('D MMMM Y') }}</span>
                                        <span class="text-[10px] font-mono text-on-surface-variant">{{ $pr->request_date }}</span>
                                    </div>
                                </a>
                            </td>

                            <!-- No. Order Saja -->
                            <td class="py-3.5 px-4">
                                <a href="{{ route('z1-app.purchase-request.show', $pr->id) }}" class="font-mono text-xs font-bold text-on-surface hover:text-primary transition-colors block">
                                    {{ $pr->pr_number }}
                                </a>
                            </td>

                            <!-- Total Item (Hanya Total Unit Pcs) -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="font-bold text-on-surface text-xs block">{{ $prTotalUnits }} pcs</span>
                            </td>

                            <!-- Total Nilai -->
                            <td class="py-3.5 px-4 text-right">
                                <span class="font-black text-on-surface block font-mono">
                                    Rp {{ number_format($prEstTotal, 0, ',', '.') }}
                                </span>
                            </td>

                            <!-- Rincian / Catatan -->
                            <td class="py-3.5 px-4 max-w-[200px]">
                                <span class="text-on-surface-variant text-[11px] truncate block" title="{{ $pr->notes }}">
                                    {{ $pr->notes ? '"' . $pr->notes . '"' : '—' }}
                                </span>
                            </td>

                            <!-- Dicatat Oleh -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-[#006c49] text-white flex items-center justify-center font-bold text-[10px]">
                                        {{ strtoupper(substr($pr->user?->name ?? 'Staff', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-on-surface text-xs block truncate">{{ $pr->user?->name ?? 'Staff Toko' }}</span>
                                        <span class="text-[10px] text-on-surface-variant block">
                                            {{ $pr->created_at ? $pr->created_at->format('H:i') . ' WIB' : '—' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Status Column -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="inline-flex flex-col items-center gap-1">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $pr->status_badge }}">
                                        {{ ucfirst($pr->status) }}
                                    </span>
                                    @if($pr->priority === 'urgent')
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-rose-500/10 text-rose-700 border border-rose-500/20 uppercase">
                                            Mendesak
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a
                                        href="{{ route('z1-app.purchase-request.show', $pr->id) }}"
                                        class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-colors cursor-pointer"
                                        title="Buka Halaman Rincian Order"
                                    >
                                        <span class="material-symbols-outlined text-base">visibility</span>
                                    </a>

                                    @php
                                        $userIsSuperAdmin = $isSuperAdmin ?? (auth()->user()?->hasRole('Super Admin') || auth()->id() === 1);
                                    @endphp
                                    @if(!in_array($pr->status, ['approved', 'completed']))
                                        @if(($pr->status === 'pending' && ($prTotalUnits == 0 || $prItemsCount == 0)) || $userIsSuperAdmin || $pr->user_id === auth()->id())
                                        <form action="{{ route('z1-app.purchase-request.destroy', $pr->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan order #{{ $pr->pr_number }}?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-on-surface-variant hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Hapus Order">
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </button>
                                        </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-12 text-on-surface-variant text-xs">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-3xl text-outline-variant">inventory_2</span>
                                    <p class="font-bold">Belum ada data pengajuan order barang yang tercatat.</p>
                                    <button type="button" onclick="openCreatePrModal()" class="mt-1 text-xs text-primary font-bold hover:underline cursor-pointer">
                                        + Buat Order Barang Sekarang
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards View (Khusus Tampilan HP) -->
            <div class="block md:hidden p-3 space-y-3">
                @forelse($purchaseRequests as $pr)
                @php
                    $prItemsCount = $pr->items->count();
                    $prTotalUnits = $pr->items->sum('quantity_requested');
                    $prEstTotal = $pr->items->sum(fn($i) => ($i->product?->purchase_price ?? $i->product?->price ?? 0) * $i->quantity_requested);
                @endphp
                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 shadow-xs space-y-3">
                    <!-- Top: Tanggal & Badges -->
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-lg">calendar_today</span>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-sm text-on-surface leading-tight">
                                    {{ \Carbon\Carbon::parse($pr->request_date)->isoFormat('D MMMM Y') }}
                                </h4>
                                <span class="text-[11px] font-mono text-on-surface-variant font-semibold">
                                    {{ $pr->pr_number }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-1">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $pr->status_badge }}">
                                {{ ucfirst($pr->status) }}
                            </span>
                            @if($pr->priority === 'urgent')
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-rose-500/10 text-rose-700 border border-rose-500/20 uppercase">
                                    Mendesak
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Middle: Stats Box -->
                    <div class="grid grid-cols-2 gap-2 p-2.5 rounded-xl bg-surface-container-low border border-outline-variant/60 text-xs">
                        <div>
                            <span class="text-[10px] text-on-surface-variant font-medium block">Total Barang</span>
                            <span class="font-black text-on-surface">{{ $prItemsCount }} Item ({{ $prTotalUnits }} Pcs)</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-on-surface-variant font-medium block">Total Nilai</span>
                            <span class="font-black font-mono text-emerald-800">Rp {{ number_format($prEstTotal, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @if($pr->notes)
                    <div class="text-[11px] text-on-surface-variant italic bg-surface-container-low/40 px-2.5 py-1.5 rounded-lg border border-outline-variant/40">
                        Catatan: "{{ $pr->notes }}"
                    </div>
                    @endif

                    <!-- Bottom: User & Action Link -->
                    <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50 text-xs">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-[#006c49] text-white flex items-center justify-center font-bold text-[10px]">
                                {{ strtoupper(substr($pr->user?->name ?? 'Staff', 0, 1)) }}
                            </div>
                            <span class="text-xs font-semibold text-on-surface-variant truncate max-w-[120px]">
                                {{ $pr->user?->name ?? 'Staff' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            @if(!in_array($pr->status, ['approved', 'completed']))
                                @if(($pr->status === 'pending' && ($prTotalUnits == 0 || $prItemsCount == 0)) || $userIsSuperAdmin || $pr->user_id === auth()->id())
                                <form action="{{ route('z1-app.purchase-request.destroy', $pr->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan order #{{ $pr->pr_number }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-xl text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition-colors cursor-pointer" title="Hapus Order">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </form>
                                @endif
                            @endif
                            <a href="{{ route('z1-app.purchase-request.show', $pr->id) }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-primary text-on-primary rounded-xl font-bold text-xs shadow-xs hover:bg-[#00553a] transition-all">
                                <span>Rincian</span>
                                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-10 text-on-surface-variant text-xs space-y-2">
                    <span class="material-symbols-outlined text-3xl text-outline-variant block">inventory_2</span>
                    <p class="font-bold">Belum ada data pengajuan order barang yang tercatat.</p>
                    <button type="button" onclick="openCreatePrModal()" class="text-xs text-primary font-bold hover:underline cursor-pointer">
                        + Buat Order Barang Sekarang
                    </button>
                </div>
                @endforelse
            </div>
        </div>

        {{-- MODAL 1: BUAT ORDER BARANG --}}
        <dialog id="modal-create-pr" class="rounded-2xl p-0 bg-surface-container-lowest border border-outline-variant shadow-2xl backdrop:bg-black/40 backdrop:backdrop-blur-xs w-[94vw] max-w-lg m-auto">
            <div class="flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 sm:p-5 border-b border-outline-variant/60">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-xl">add_shopping_cart</span>
                        </span>
                        <div>
                            <h3 class="font-black text-sm sm:text-base text-on-surface">Buat Order Barang (Purchase Request)</h3>
                            <p class="text-[11px] sm:text-xs text-on-surface-variant">Tentukan cabang tujuan restock dan jadwal order</p>
                        </div>
                    </div>
                    <button type="button" onclick="document.getElementById('modal-create-pr').close()" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low cursor-pointer">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>

                <!-- Modal Body with Form -->
                <form id="pr-create-form" action="{{ route('z1-app.purchase-request.store') }}" method="POST" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
                    @csrf
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-on-surface mb-1">Tanggal Order / Request: <span class="text-rose-500">*</span></label>
                            <input type="date" name="request_date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface mb-1">Tingkat Urgensi: <span class="text-rose-500">*</span></label>
                            <select name="priority" required class="w-full px-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-semibold text-on-surface focus:outline-none focus:border-primary">
                                <option value="normal">Normal (Restock Berkala)</option>
                                <option value="urgent">Mendesak (Stok Toko Menipis)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Catatan / Catatan Pengadaan (Opsional):</label>
                        <textarea name="notes" rows="3" placeholder="Contoh: Order restock mingguan untuk etalase utama..." class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary resize-none"></textarea>
                    </div>

                    <!-- Footer / Submit -->
                    <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2.5">
                        <button type="button" onclick="document.getElementById('modal-create-pr').close()" class="px-4 py-2.5 rounded-xl border border-outline-variant text-xs font-bold text-on-surface-variant hover:bg-surface-container cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary hover:bg-[#00553a] text-on-primary text-xs font-bold shadow-xs hover:opacity-95 transition-all cursor-pointer">
                            Lanjut Tambah Barang &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </dialog>

    </div>

    <!-- Script for Tab 2 Modals & Custom Dropdowns -->
    <script>
        // Custom Dropdown: Filter Status Purchase Request
        function togglePrStatusDropdown(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            const menu = document.getElementById('pr-status-menu');
            const chevron = document.getElementById('pr-status-chevron');
            const btn = document.getElementById('pr-status-btn');
            if (!menu) return;

            const isHidden = menu.classList.contains('hidden');
            if (isHidden) {
                closePriorityDropdown();
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
                if (btn) btn.setAttribute('aria-expanded', 'true');
            } else {
                closePrStatusDropdown();
            }
        }

        function closePrStatusDropdown() {
            const menu = document.getElementById('pr-status-menu');
            const chevron = document.getElementById('pr-status-chevron');
            const btn = document.getElementById('pr-status-btn');
            if (menu) menu.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }

        function setPrStatus(val, label, dotColorClass) {
            const input = document.getElementById('pr_status_input');
            const labelEl = document.getElementById('pr-status-label');
            const dotEl = document.getElementById('pr-status-dot');

            if (input) input.value = val;
            if (labelEl) labelEl.textContent = label;
            if (dotEl) {
                dotEl.className = 'w-2 h-2 rounded-full shrink-0 ' + dotColorClass;
            }

            closePrStatusDropdown();

            // Auto-submit filter form on select
            const form = document.getElementById('prFilterForm');
            if (form) form.submit();
        }

        function openCreatePrModal() {
            const modal = document.getElementById('modal-create-pr');
            if (modal) {
                modal.showModal();
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            const prStatusContainer = document.getElementById('pr-status-dropdown-container');
            if (prStatusContainer && !prStatusContainer.contains(e.target)) {
                closePrStatusDropdown();
            }
        });

        // Close dropdowns when pressing Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePrStatusDropdown();
            }
        });
    </script>
    @endif

    <!-- TAB 3: REPORT PENJUALAN PER STAFF -->
    @if($activeTab === 'sales_report')
    <div class="space-y-6">
        <!-- Period Switcher & Filter -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-surface-container-lowest border border-outline-variant p-4 rounded-2xl shadow-xs">
            <div class="flex items-center gap-2">
                <a href="{{ route('z1-app.index', ['tab' => 'sales_report', 'period' => 'today']) }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all {{ $period === 'today' ? 'bg-primary text-on-primary shadow-xs' : 'bg-surface-container-low text-on-surface hover:bg-surface-container' }}">
                    Hari Ini
                </a>
                <a href="{{ route('z1-app.index', ['tab' => 'sales_report', 'period' => 'this_month']) }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all {{ $period === 'this_month' ? 'bg-primary text-on-primary shadow-xs' : 'bg-surface-container-low text-on-surface hover:bg-surface-container' }}">
                    Bulan Ini
                </a>
                <a href="{{ route('z1-app.index', ['tab' => 'sales_report', 'period' => 'last_month']) }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all {{ $period === 'last_month' ? 'bg-primary text-on-primary shadow-xs' : 'bg-surface-container-low text-on-surface hover:bg-surface-container' }}">
                    Bulan Lalu
                </a>
            </div>

            <div class="text-xs text-on-surface-variant font-medium">
                Periode: <strong class="text-on-surface">{{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMM Y') }}</strong> s/d <strong class="text-on-surface">{{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMM Y') }}</strong>
            </div>
        </div>

        <!-- 4 Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Omzet -->
            <div class="p-5 rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-xs">
                <div class="flex items-center justify-between text-xs text-on-surface-variant font-bold mb-2">
                    <span>Total Omzet Penjualan</span>
                    <span class="material-symbols-outlined text-primary text-xl">payments</span>
                </div>
                <div class="text-2xl font-black text-on-surface tracking-tight">
                    Rp {{ number_format($totalSalesAmount, 0, ',', '.') }}
                </div>
                <span class="text-[10px] text-on-surface-variant block mt-1">Hasil transaksi yang Anda layani</span>
            </div>

            <!-- Transaksi -->
            <div class="p-5 rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-xs">
                <div class="flex items-center justify-between text-xs text-on-surface-variant font-bold mb-2">
                    <span>Total Transaksi</span>
                    <span class="material-symbols-outlined text-secondary text-xl">receipt_long</span>
                </div>
                <div class="text-2xl font-black text-on-surface tracking-tight">
                    {{ $totalTransactions }} <span class="text-xs font-bold text-on-surface-variant">Nota</span>
                </div>
                <span class="text-[10px] text-on-surface-variant block mt-1">Pelanggan berhasil dilayani</span>
            </div>

            <!-- Rata-rata per Nota -->
            <div class="p-5 rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-xs">
                <div class="flex items-center justify-between text-xs text-on-surface-variant font-bold mb-2">
                    <span>Rata-rata per Nota</span>
                    <span class="material-symbols-outlined text-blue-600 text-xl">query_stats</span>
                </div>
                <div class="text-2xl font-black text-on-surface tracking-tight">
                    Rp {{ number_format($avgTransaction, 0, ',', '.') }}
                </div>
                <span class="text-[10px] text-on-surface-variant block mt-1">Rata-rata nilai belanja</span>
            </div>

            <!-- Barang Terjual -->
            <div class="p-5 rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-xs">
                <div class="flex items-center justify-between text-xs text-on-surface-variant font-bold mb-2">
                    <span>Total Barang Terjual</span>
                    <span class="material-symbols-outlined text-amber-600 text-xl">shopping_bag</span>
                </div>
                <div class="text-2xl font-black text-on-surface tracking-tight">
                    {{ $totalItemsSold }} <span class="text-xs font-bold text-on-surface-variant">Pcs</span>
                </div>
                <span class="text-[10px] text-on-surface-variant block mt-1">Total unit produk terjual</span>
            </div>
        </div>

        <!-- 2 Columns: Top Products & Recent Sales -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Top 10 Products (7 cols) -->
            <div class="lg:col-span-7 bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-xs">
                <div class="flex items-center gap-2 mb-4 pb-3 border-b border-outline-variant/60">
                    <span class="material-symbols-outlined text-amber-500 text-xl">military_tech</span>
                    <h3 class="font-extrabold text-sm text-on-surface">Top Produk yang Anda Jual ({{ $topProducts->count() }})</h3>
                </div>

                @if($topProducts->isEmpty())
                    <div class="text-center py-12 text-on-surface-variant text-xs">
                        Belum ada data produk terjual dalam periode ini.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-surface-container-low text-on-surface-variant text-[10px] uppercase font-bold">
                                <tr>
                                    <th class="py-2.5 px-3">#</th>
                                    <th class="py-2.5 px-3">Nama Produk</th>
                                    <th class="py-2.5 px-3 text-right">Qty Terjual</th>
                                    <th class="py-2.5 px-3 text-right">Total Nilai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/60 font-medium">
                                @foreach($topProducts as $idx => $p)
                                <tr class="hover:bg-surface-container-low transition-colors">
                                    <td class="py-2.5 px-3 font-bold text-on-surface-variant">{{ $idx + 1 }}</td>
                                    <td class="py-2.5 px-3">
                                        <span class="font-bold text-on-surface block">{{ $p['name'] }}</span>
                                        <span class="text-[10px] font-mono text-on-surface-variant">{{ $p['sku'] }}</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-bold text-on-surface">{{ $p['qty'] }} pcs</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-primary">
                                        Rp {{ number_format($p['total_amount'], 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Recent Transactions (5 cols) -->
            <div class="lg:col-span-5 bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-xs">
                <div class="flex items-center gap-2 mb-4 pb-3 border-b border-outline-variant/60">
                    <span class="material-symbols-outlined text-secondary text-xl">history</span>
                    <h3 class="font-extrabold text-sm text-on-surface">Riwayat Transaksi Terakhir ({{ $staffSales->take(15)->count() }})</h3>
                </div>

                @if($staffSales->isEmpty())
                    <div class="text-center py-12 text-on-surface-variant text-xs">
                        Belum ada riwayat transaksi dalam periode ini.
                    </div>
                @else
                    <div class="space-y-2.5 max-h-[440px] overflow-y-auto pr-1">
                        @foreach($staffSales->take(15) as $s)
                        <div class="p-3 rounded-xl border border-outline-variant bg-surface-container-low flex items-center justify-between text-xs">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-on-surface">{{ $s->reference_number }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] uppercase font-bold bg-surface-container text-on-surface-variant">
                                        {{ $s->payment_method ?? 'cash' }}
                                    </span>
                                </div>
                                <span class="text-[10px] text-on-surface-variant block mt-0.5">
                                    {{ \Carbon\Carbon::parse($s->sale_date)->format('d/m/Y') }} • {{ $s->created_at ? $s->created_at->format('H:i') : '' }} • {{ $s->items->count() }} item
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-primary block">
                                    Rp {{ number_format($s->total_amount, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
