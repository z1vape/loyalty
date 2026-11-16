@extends('layouts.app')

@section('title', 'Payroll Employee — Human Resources')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-surface-container-lowest p-6 rounded-3xl border border-outline-variant shadow-xs">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-secondary uppercase tracking-wider mb-1">
                <span>Human Resources</span>
                <span>•</span>
                <span>Penggajian Karyawan</span>
            </div>
            <h1 class="text-2xl font-black text-on-surface tracking-tight">Payroll Employee</h1>
            <p class="text-xs text-on-surface-variant mt-0.5">
                Kalkulasi gaji bulanan otomatis terintegrasi absensi, jam kerja, dan lembur hari libur (OFF).
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            @can('Payroll Setting')
            <a href="{{ route('commercial.payrolls.settings') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-surface-container-high hover:bg-surface-container-highest text-on-surface text-xs font-bold transition-all border border-outline-variant cursor-pointer">
                <span class="material-symbols-outlined text-[18px] text-secondary">tune</span>
                <span>Atur Komponen Gaji</span>
            </a>
            @endcan

            @can('Payroll Manage')
            <button type="button" onclick="openGenerateModal()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-primary text-white hover:bg-[#00553a] text-xs font-bold transition-all shadow-xs cursor-pointer active:scale-95">
                <span class="material-symbols-outlined text-[18px]">calculate</span>
                <span>Hitung & Generate Gaji</span>
            </button>
            @endcan
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Net Payroll -->
        <div class="p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant shadow-xs space-y-1">
            <div class="flex items-center justify-between text-on-surface-variant">
                <span class="text-xs font-semibold">Total Penggajian Periode</span>
                <span class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">payments</span>
                </span>
            </div>
            <div class="text-2xl font-black text-on-surface">
                Rp {{ number_format($summary['total_net_salary'], 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-on-surface-variant flex items-center gap-1 font-medium">
                <span>{{ $summary['total_employees'] }} Karyawan terhitung</span>
            </div>
        </div>

        <!-- 2. Lembur Hari Libur (OFF) -->
        <div class="p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant shadow-xs space-y-1">
            <div class="flex items-center justify-between text-on-surface-variant">
                <span class="text-xs font-semibold">Total Lembur Hari Libur (OFF)</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">beach_access</span>
                </span>
            </div>
            <div class="text-2xl font-black text-indigo-700 dark:text-indigo-400">
                Rp {{ number_format($summary['total_holiday_overtime'], 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-on-surface-variant font-medium">
                Dihitung dari selisih clock-in & clock-out saat jadwal OFF
            </div>
        </div>

        <!-- 3. Lembur Reguler -->
        <div class="p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant shadow-xs space-y-1">
            <div class="flex items-center justify-between text-on-surface-variant">
                <span class="text-xs font-semibold">Total Lembur Reguler</span>
                <span class="w-8 h-8 rounded-xl bg-sky-500/10 text-sky-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">schedule</span>
                </span>
            </div>
            <div class="text-2xl font-black text-on-surface">
                Rp {{ number_format($summary['total_regular_overtime'], 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-on-surface-variant font-medium">
                Kelebihan jam kerja di hari shift normal
            </div>
        </div>

        <!-- 4. Status Pembayaran -->
        <div class="p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant shadow-xs space-y-1">
            <div class="flex items-center justify-between text-on-surface-variant">
                <span class="text-xs font-semibold">Status Pembayaran</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">task_alt</span>
                </span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-emerald-700">{{ $summary['total_paid'] }}</span>
                <span class="text-xs text-on-surface-variant font-semibold">Terbayar</span>
                <span class="text-on-surface-variant/40">•</span>
                <span class="text-base font-bold text-amber-700">{{ $summary['total_draft'] + $summary['total_approved'] }}</span>
                <span class="text-xs text-on-surface-variant font-semibold">Pending</span>
            </div>
            <div class="text-[11px] text-on-surface-variant font-medium">
                Periode {{ $months[(int) $month] ?? $month }} {{ $year }}
            </div>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="bg-surface-container-lowest p-4 rounded-3xl border border-outline-variant shadow-xs">
        <form id="payrollFilterForm" action="{{ route('commercial.payrolls.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <!-- Bulan -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Bulan</label>
                <select name="month" id="filter_month" class="w-full">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" {{ (int)$month === (int)$num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tahun -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Tahun</label>
                <select name="year" id="filter_year" class="w-full">
                    @foreach($years as $yr)
                        <option value="{{ $yr }}" {{ (int)$year === (int)$yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Lokasi Toko -->
            <div class="lg:col-span-3">
                <label class="block text-[11px] font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Toko / Cabang</label>
                <select name="store_id" id="filter_store_id" class="w-full">
                    <option value="">Semua Cabang Toko</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ (string)$storeId === (string)$st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Status</label>
                <select name="status" id="filter_status" class="w-full">
                    <option value="" {{ empty($status) || $status === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Disetujui (Approved)</option>
                    <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Terbayar (Paid)</option>
                </select>
            </div>

            <!-- Search -->
            <div class="lg:col-span-3 flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1 uppercase tracking-wider">Cari Karyawan / Kode</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Nama atau kode payroll..." class="w-full pl-3 pr-8 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium text-on-surface focus:outline-none focus:ring-2 focus:ring-[#006c49]/20 focus:border-[#006c49] transition">
                        @if(!empty($search))
                            <a href="{{ route('commercial.payrolls.index', request()->except('search')) }}" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-black" title="Hapus pencarian">
                                <span class="material-symbols-outlined text-sm">close</span>
                            </a>
                        @endif
                    </div>
                </div>
                <button type="submit" class="p-2 bg-primary text-white rounded-xl hover:bg-[#00553a] shrink-0 shadow-xs cursor-pointer transition flex items-center justify-center h-[37px] w-[37px]" title="Cari">
                    <span class="material-symbols-outlined text-[18px]">search</span>
                </button>
                @if(request()->filled('search') || request()->filled('store_id') || (request()->filled('status') && request('status') !== 'all') || (request()->has('month') && (int)request('month') !== now()->month) || (request()->has('year') && (int)request('year') !== now()->year))
                    <a href="{{ route('commercial.payrolls.index') }}" class="p-2 rounded-xl border border-outline-variant hover:bg-surface-container-high text-on-surface-variant hover:text-black transition shrink-0 flex items-center justify-center h-[37px] w-[37px]" title="Reset Filter">
                        <span class="material-symbols-outlined text-[18px]">refresh</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table of Payroll Records -->
    <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-xs overflow-hidden">
        <div class="px-5 py-3.5 border-b border-outline-variant/60 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-extrabold text-on-surface">Daftar Payroll:</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-primary/10 text-primary border border-primary/20">
                    {{ $payrolls->total() }} Slip Gaji
                </span>
                <span class="text-[11px] text-on-surface-variant hidden sm:inline">• Kolom karyawan beku (frozen) saat tabel digeser ke samping</span>
            </div>
            <div class="flex items-center gap-2 text-[11px] text-on-surface-variant">
                <span class="material-symbols-outlined text-[15px] text-secondary">info</span>
                <span>Kalkulasi otomatis dari absensi, lembur OFF, dan denda keterlambatan</span>
            </div>
        </div>

        <div class="overflow-x-auto relative">
            <table class="w-full text-left border-separate border-spacing-0 text-xs">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant uppercase text-[10px] tracking-wider font-extrabold whitespace-nowrap">
                        <!-- Sticky Frozen Column: Karyawan & Kode -->
                        <th class="py-3.5 px-4 sticky left-0 z-20 bg-surface-container-low min-w-[240px] max-w-[240px] w-[240px] border-b border-r-2 border-outline-variant/80 shadow-[4px_0_10px_-2px_rgba(0,0,0,0.06)]">
                            Karyawan & Kode
                        </th>
                        <th class="py-3.5 px-4 border-b border-r border-outline-variant/30 min-w-[170px]">Toko / Posisi</th>
                        <th class="py-3.5 px-4 border-b border-r border-outline-variant/30 text-right min-w-[130px]">Gaji Pokok</th>
                        <th class="py-3.5 px-4 border-b border-r border-outline-variant/30 text-center min-w-[140px]">Hadir / Uang Makan</th>
                        <th class="py-3.5 px-4 border-b border-r border-outline-variant/30 text-right min-w-[130px]">Lembur Reguler</th>
                        <th class="py-3.5 px-4 border-b border-r border-outline-variant/30 text-right min-w-[140px]">Lembur Libur (OFF)</th>
                        <th class="py-3.5 px-4 border-b border-r border-outline-variant/30 text-right min-w-[150px]">Penyesuaian / Pot.</th>
                        <th class="py-3.5 px-4 border-b border-r border-outline-variant/30 text-right min-w-[140px]">Gaji Bersih</th>
                        <th class="py-3.5 px-4 border-b border-r border-outline-variant/30 text-center min-w-[110px]">Status</th>
                        <th class="py-3.5 px-4 border-b text-center min-w-[150px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-on-surface font-medium">
                    @forelse($payrolls as $p)
                    <tr class="group hover:bg-surface-container-low/50 transition-colors">
                        <!-- Sticky Frozen Column: Karyawan & Kode -->
                        <td class="py-3.5 px-4 sticky left-0 z-10 bg-surface-container-lowest group-hover:bg-[#eff4ff] transition-colors min-w-[240px] max-w-[240px] w-[240px] border-b border-r-2 border-outline-variant/80 shadow-[4px_0_10px_-2px_rgba(0,0,0,0.06)] whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                @if($p->user->photo)
                                    <img src="{{ Storage::url($p->user->photo) }}" alt="{{ $p->user->name }}" class="w-9 h-9 rounded-full object-cover border border-outline-variant shrink-0 shadow-2xs">
                                @else
                                    <div class="w-9 h-9 rounded-full bg-[#006c49] text-white flex items-center justify-center font-black text-xs uppercase shrink-0 shadow-2xs">
                                        {{ strtoupper(substr($p->user->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <span class="font-bold text-xs text-on-surface truncate block" title="{{ $p->user->name }}">
                                        {{ $p->user->name }}
                                    </span>
                                    <span class="text-[10px] font-mono text-on-surface-variant block mt-0.5 tracking-tight">{{ $p->payroll_code }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- Toko / Posisi -->
                        <td class="py-3.5 px-4 border-b border-r border-outline-variant/30 whitespace-nowrap min-w-[170px]">
                            <span class="text-xs font-semibold text-on-surface block truncate" title="{{ $p->store?->name ?? 'Semua Toko' }}">
                                {{ $p->store?->name ?? 'Semua Toko' }}
                            </span>
                            <span class="text-[10.5px] text-on-surface-variant flex items-center gap-1 mt-0.5">
                                <span class="material-symbols-outlined text-[13px] text-on-surface-variant/70">badge</span>
                                <span>{{ $p->user->position ?: 'Staff' }}</span>
                            </span>
                        </td>

                        <!-- Gaji Pokok -->
                        <td class="py-3.5 px-4 border-b border-r border-outline-variant/30 text-right whitespace-nowrap min-w-[130px]">
                            <span class="font-bold font-mono text-xs text-on-surface block">
                                Rp {{ number_format($p->basic_salary, 0, ',', '.') }}
                            </span>
                            @if($p->payment_type === 'daily')
                                <span class="inline-block px-1.5 py-0.5 rounded text-[9.5px] font-bold uppercase tracking-wider bg-surface-container text-on-surface-variant mt-0.5">Harian</span>
                            @else
                                <span class="inline-block px-1.5 py-0.5 rounded text-[9.5px] font-bold uppercase tracking-wider bg-surface-container text-on-surface-variant mt-0.5">Bulanan</span>
                            @endif
                        </td>

                        <!-- Kehadiran & Uang Makan -->
                        <td class="py-3.5 px-4 border-b border-r border-outline-variant/30 text-center whitespace-nowrap min-w-[140px]">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-surface-container-low border border-outline-variant/60 font-bold text-xs text-on-surface">
                                <span class="material-symbols-outlined text-[15px] text-[#006c49]">calendar_today</span>
                                <span>{{ $p->total_days_worked }} hari</span>
                            </div>
                            @if($p->total_daily_allowance > 0)
                                <div class="mt-1">
                                    <span class="text-[11px] font-mono font-bold text-emerald-700 block">
                                        +Rp {{ number_format($p->total_daily_allowance, 0, ',', '.') }}
                                    </span>
                                    <span class="text-[9px] text-on-surface-variant font-medium">Uang Makan</span>
                                </div>
                            @endif
                        </td>

                        <!-- Lembur Reguler -->
                        <td class="py-3.5 px-4 border-b border-r border-outline-variant/30 text-right whitespace-nowrap min-w-[130px]">
                            @if($p->regular_overtime_minutes > 0)
                                <span class="font-bold font-mono text-xs text-sky-700 block">
                                    Rp {{ number_format($p->regular_overtime_pay, 0, ',', '.') }}
                                </span>
                                <span class="inline-flex items-center gap-1 text-[10px] text-on-surface-variant mt-0.5">
                                    <span class="material-symbols-outlined text-[13px] text-sky-600">schedule</span>
                                    <span>{{ $p->regular_overtime_hours }}j ({{ $p->regular_overtime_minutes }}m)</span>
                                </span>
                            @else
                                <span class="text-on-surface-variant/30 font-mono text-xs">—</span>
                            @endif
                        </td>

                        <!-- Lembur Libur (OFF) -->
                        <td class="py-3.5 px-4 border-b border-r border-outline-variant/30 text-right whitespace-nowrap min-w-[140px]">
                            @if($p->holiday_overtime_minutes > 0)
                                <span class="font-bold font-mono text-xs text-indigo-700 block">
                                    Rp {{ number_format($p->holiday_overtime_pay, 0, ',', '.') }}
                                </span>
                                <span class="inline-flex items-center gap-1 text-[10px] text-indigo-700/80 mt-0.5">
                                    <span class="material-symbols-outlined text-[13px] text-indigo-600">beach_access</span>
                                    <span>{{ $p->holiday_overtime_hours }}j ({{ $p->holiday_overtime_minutes }}m)</span>
                                </span>
                            @else
                                <span class="text-on-surface-variant/30 font-mono text-xs">—</span>
                            @endif
                        </td>

                        <!-- Penyesuaian & Potongan -->
                        <td class="py-3.5 px-4 border-b border-r border-outline-variant/30 text-right whitespace-nowrap min-w-[150px]">
                            @php
                                $hasAdj = $p->late_deduction > 0 || $p->other_deductions > 0 || $p->bonus > 0;
                            @endphp
                            @if($hasAdj)
                                <div class="space-y-1 inline-flex flex-col items-end">
                                    @if($p->late_deduction > 0)
                                        <div class="text-right">
                                            <span class="text-rose-700 font-bold font-mono text-xs block">
                                                -Rp {{ number_format($p->late_deduction, 0, ',', '.') }}
                                            </span>
                                            <span class="text-[9.5px] text-rose-600/90 font-medium block">
                                                Telat {{ $p->late_days }} hr ({{ $p->late_minutes }}m)
                                            </span>
                                        </div>
                                    @endif
                                    @if($p->other_deductions > 0)
                                        <div class="text-right">
                                            <span class="text-rose-700/80 font-semibold font-mono text-xs block">
                                                -Rp {{ number_format($p->other_deductions, 0, ',', '.') }}
                                            </span>
                                            <span class="text-[9px] text-on-surface-variant block">Pot. Lain</span>
                                        </div>
                                    @endif
                                    @if($p->bonus > 0)
                                        <div class="text-right">
                                            <span class="text-emerald-700 font-bold font-mono text-xs block">
                                                +Rp {{ number_format($p->bonus, 0, ',', '.') }}
                                            </span>
                                            <span class="text-[9px] text-emerald-600/90 font-medium block">Bonus</span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span class="text-on-surface-variant/30 font-mono text-xs">—</span>
                            @endif
                        </td>

                        <!-- Gaji Bersih -->
                        <td class="py-3.5 px-4 border-b border-r border-outline-variant/30 text-right whitespace-nowrap min-w-[140px]">
                            <span class="inline-block px-3 py-1.5 rounded-xl bg-emerald-500/10 text-emerald-800 border border-emerald-500/20 text-xs font-black font-mono shadow-2xs">
                                Rp {{ number_format($p->net_salary, 0, ',', '.') }}
                            </span>
                        </td>

                        <!-- Status Badge -->
                        <td class="py-3.5 px-4 border-b border-r border-outline-variant/30 text-center whitespace-nowrap min-w-[110px]">
                            @if($p->status === 'paid')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-800 border border-emerald-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    PAID
                                </span>
                            @elseif($p->status === 'approved')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-sky-500/10 text-sky-800 border border-sky-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                    APPROVED
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/10 text-amber-800 border border-amber-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    DRAFT
                                </span>
                            @endif
                        </td>

                        <!-- Aksi -->
                        <td class="py-3.5 px-4 border-b text-center whitespace-nowrap min-w-[150px]">
                            <div class="flex items-center justify-center gap-1.5">
                                <!-- Cetak / Lihat Slip -->
                                <a href="{{ route('commercial.payrolls.show', $p) }}" target="_blank" class="w-8 h-8 rounded-xl bg-surface-container-low hover:bg-surface-container border border-outline-variant/70 flex items-center justify-center text-on-surface-variant hover:text-primary transition-all shadow-2xs" title="Lihat & Cetak Slip Gaji">
                                    <span class="material-symbols-outlined text-[17px]">receipt_long</span>
                                </a>

                                @can('Payroll Manage')
                                <!-- Edit Bonus / Potongan Modal Trigger -->
                                <button type="button" onclick="openAdjustmentModal({{ $p->id }}, '{{ addslashes($p->user->name) }}', {{ (float)$p->bonus }}, {{ (float)$p->other_deductions }}, '{{ addslashes($p->note ?? '') }}')" class="w-8 h-8 rounded-xl bg-surface-container-low hover:bg-surface-container border border-outline-variant/70 flex items-center justify-center text-on-surface-variant hover:text-primary transition-all shadow-2xs cursor-pointer" title="Sesuaikan Bonus / Potongan">
                                    <span class="material-symbols-outlined text-[17px]">edit_note</span>
                                </button>

                                <!-- Toggle Status: Draft -> Approved -> Paid -->
                                <form action="{{ route('commercial.payrolls.status', $p) }}" method="POST" class="inline-block">
                                    @csrf
                                    @if($p->status === 'draft')
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="w-8 h-8 rounded-xl bg-sky-50 hover:bg-sky-100 border border-sky-200/80 flex items-center justify-center text-sky-700 transition-all shadow-2xs cursor-pointer" title="Setujui Slip Gaji (Approve)">
                                            <span class="material-symbols-outlined text-[17px]">check_circle</span>
                                        </button>
                                    @elseif($p->status === 'approved')
                                        <input type="hidden" name="status" value="paid">
                                        <button type="submit" class="w-8 h-8 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/80 flex items-center justify-center text-emerald-700 transition-all shadow-2xs cursor-pointer" title="Tandai Sudah Dibayar (PAID)">
                                            <span class="material-symbols-outlined text-[17px]">paid</span>
                                        </button>
                                    @elseif($p->status === 'paid')
                                        <input type="hidden" name="status" value="draft">
                                        <button type="submit" onclick="return confirm('Kembalikan status gaji ini ke Draft?')" class="w-8 h-8 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-200/80 flex items-center justify-center text-amber-700 transition-all shadow-2xs cursor-pointer" title="Kembalikan ke Draft">
                                            <span class="material-symbols-outlined text-[17px]">undo</span>
                                        </button>
                                    @endif
                                </form>

                                @if($p->status !== 'paid')
                                <form action="{{ route('commercial.payrolls.destroy', $p) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus slip gaji karyawan {{ $p->user->name }} ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-8 h-8 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200/80 flex items-center justify-center text-rose-700 transition-all shadow-2xs cursor-pointer" title="Hapus Slip Gaji">
                                        <span class="material-symbols-outlined text-[17px]">delete</span>
                                    </button>
                                </form>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-4xl text-on-surface-variant/40 mb-2 block">receipt_long</span>
                            <p class="text-sm font-semibold">Belum ada rekap payroll untuk periode {{ $months[(int) $month] ?? $month }} {{ $year }}.</p>
                            <p class="text-xs text-on-surface-variant mt-1">Klik tombol <strong>"Hitung & Generate Gaji"</strong> untuk menghitung otomatis dari data absensi karyawan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payrolls->hasPages())
        <div class="p-4 border-t border-outline-variant/60">
            {{ $payrolls->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal 1: Generate Payroll Bulanan -->
<div id="generate-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs hidden overflow-y-auto">
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-3xl w-full max-w-lg shadow-2xl p-6 space-y-4 my-8">
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">calculate</span>
                </span>
                <div>
                    <h3 class="font-bold text-sm text-on-surface">Hitung & Generate Gaji Karyawan</h3>
                    <p class="text-[11px] text-on-surface-variant">Kalkulasi kehadiran, lembur reguler, dan lembur hari libur (OFF).</p>
                </div>
            </div>
            <button type="button" onclick="closeGenerateModal()" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('commercial.payrolls.generate') }}" method="POST" class="space-y-4">
            @csrf
            @if(!empty($storeId))
                <input type="hidden" name="store_id" value="{{ $storeId }}">
            @endif

            <!-- Periode Target -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Pilih Bulan <span class="text-rose-600">*</span></label>
                    <select name="period_month" id="modal_period_month" required class="w-full">
                        @foreach($months as $num => $name)
                            <option value="{{ $num }}" {{ (int)$month === (int)$num ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Pilih Tahun <span class="text-rose-600">*</span></label>
                    <select name="period_year" id="modal_period_year" required class="w-full">
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ (int)$year === (int)$yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Pilih Karyawan Target -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-on-surface">Pilih Karyawan Target <span class="text-rose-600">*</span></label>
                    <button type="button" onclick="toggleSelectAllPayrollEmployees()" class="text-[11px] font-bold text-primary hover:underline">
                        Pilih Semua
                    </button>
                </div>
                <div class="max-h-52 overflow-y-auto p-2 bg-surface-container-low/60 rounded-xl border border-outline-variant/70 space-y-1.5 custom-slim-scroll">
                    @forelse($employees as $emp)
                    <label class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-surface-container cursor-pointer text-xs">
                        <input type="checkbox" name="user_ids[]" value="{{ $emp->id }}" checked class="payroll-emp-checkbox w-4 h-4 rounded text-primary focus:ring-primary border-outline-variant">
                        <span class="font-bold text-on-surface">{{ $emp->name }}</span>
                        <span class="text-[10px] text-on-surface-variant">({{ $emp->position ?: 'Staff' }} • {{ $emp->stores->first()?->name ?? 'Semua Cabang' }})</span>
                    </label>
                    @empty
                    <div class="p-3 text-center text-xs text-on-surface-variant">
                        Tidak ada karyawan yang terdaftar.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Notice Otomasi -->
            <div class="p-3 rounded-xl bg-primary/10 border border-primary/20 text-xs text-on-surface space-y-1">
                <span class="font-bold block text-primary">Kalkulasi Otomatis Sistem:</span>
                <ul class="list-disc pl-4 text-[11px] text-on-surface-variant space-y-0.5">
                    <li>Total hari hadir dikalikan dengan Uang Makan/Kehadiran harian.</li>
                    <li>Durasi kerja pada hari <strong>Jadwal Libur (OFF)</strong> dihitung sebagai <strong>Lembur Hari Libur</strong>.</li>
                    <li>Kelebihan jam kerja pada shift kerja dihitung sebagai <strong>Lembur Reguler</strong>.</li>
                </ul>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-outline-variant/60">
                <button type="button" onclick="closeGenerateModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-on-surface hover:bg-surface-container border border-outline-variant">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] shadow-xs cursor-pointer">
                    Hitung & Simpan Payroll
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Edit Penyesuaian Bonus / Potongan -->
<div id="adjustment-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs hidden overflow-y-auto">
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-3xl w-full max-w-md shadow-2xl p-6 space-y-4 my-8">
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
            <div>
                <h3 class="font-bold text-sm text-on-surface">Penyesuaian Bonus & Potongan</h3>
                <p id="adj-emp-name" class="text-xs text-primary font-bold"></p>
            </div>
            <button type="button" onclick="closeAdjustmentModal()" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="adjustment-form" action="" method="POST" class="space-y-3">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Bonus Tambahan (Rp)</label>
                <input type="number" step="any" name="bonus" id="adj-bonus" min="0" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono text-on-surface focus:outline-none focus:border-primary">
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Potongan Lainnya (Rp)</label>
                <input type="number" step="any" name="other_deductions" id="adj-deductions" min="0" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono text-on-surface focus:outline-none focus:border-primary">
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Catatan / Keterangan Penyesuaian</label>
                <input type="text" name="note" id="adj-note" placeholder="e.g. Bonus penjualan, kasbon..." class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-outline-variant/60">
                <button type="button" onclick="closeAdjustmentModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-on-surface hover:bg-surface-container border border-outline-variant">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] shadow-xs cursor-pointer">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterForm = document.getElementById('payrollFilterForm');
        let filterInitialized = false;

        const handleFilterChange = function() {
            if (filterInitialized && filterForm) {
                filterForm.submit();
            }
        };

        if (document.getElementById('filter_month')) {
            new TomSelect('#filter_month', {
                create: false,
                allowEmptyOption: false,
                maxOptions: 50,
                onChange: handleFilterChange
            });
        }

        if (document.getElementById('filter_year')) {
            new TomSelect('#filter_year', {
                create: false,
                allowEmptyOption: false,
                maxOptions: 50,
                onChange: handleFilterChange
            });
        }

        if (document.getElementById('filter_store_id')) {
            new TomSelect('#filter_store_id', {
                create: false,
                allowEmptyOption: true,
                placeholder: 'Semua Cabang Toko',
                maxOptions: 100,
                onChange: handleFilterChange
            });
        }

        if (document.getElementById('filter_status')) {
            new TomSelect('#filter_status', {
                create: false,
                allowEmptyOption: true,
                placeholder: 'Semua Status',
                maxOptions: 20,
                onChange: handleFilterChange
            });
        }

        if (document.getElementById('modal_period_month')) {
            window.modalMonthTs = new TomSelect('#modal_period_month', {
                create: false,
                allowEmptyOption: false,
                maxOptions: 50
            });
        }

        if (document.getElementById('modal_period_year')) {
            window.modalYearTs = new TomSelect('#modal_period_year', {
                create: false,
                allowEmptyOption: false,
                maxOptions: 50
            });
        }

        setTimeout(() => {
            filterInitialized = true;
        }, 150);
    });

    function openGenerateModal() {
        document.getElementById('generate-modal').classList.remove('hidden');
        if (window.modalMonthTs) window.modalMonthTs.sync();
        if (window.modalYearTs) window.modalYearTs.sync();
    }

    function closeGenerateModal() {
        document.getElementById('generate-modal').classList.add('hidden');
    }

    let allPayrollSelected = true;
    function toggleSelectAllPayrollEmployees() {
        allPayrollSelected = !allPayrollSelected;
        document.querySelectorAll('.payroll-emp-checkbox').forEach(cb => cb.checked = allPayrollSelected);
    }

    function openAdjustmentModal(id, name, bonus, deductions, note) {
        document.getElementById('adj-emp-name').textContent = name;
        document.getElementById('adj-bonus').value = bonus || 0;
        document.getElementById('adj-deductions').value = deductions || 0;
        document.getElementById('adj-note').value = note || '';
        document.getElementById('adjustment-form').action = `/commercial/payrolls/${id}`;
        document.getElementById('adjustment-modal').classList.remove('hidden');
    }

    function closeAdjustmentModal() {
        document.getElementById('adjustment-modal').classList.add('hidden');
    }
</script>
@endpush
@endsection
