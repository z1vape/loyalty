@extends('layouts.app')

@section('title', 'Slip Gaji — ' . $payroll->payroll_code)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Action Bar (Hidden when printing) -->
    <div class="flex items-center justify-between gap-4 p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant shadow-xs print:hidden">
        <a href="{{ route('commercial.payrolls.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-on-surface hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Kembali ke Daftar Payroll</span>
        </a>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-white text-xs font-bold shadow-xs hover:bg-[#00553a] cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">print</span>
                <span>Cetak Slip Gaji</span>
            </button>
        </div>
    </div>

    <!-- Printable Slip Gaji Document Container -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-3xl p-8 sm:p-10 shadow-sm printable-report text-on-surface print:border-none print:shadow-none print:p-0">
        <!-- Header Perusahaan -->
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6 pb-6 border-b border-outline-variant/80">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#006c49] to-[#005236] text-white flex items-center justify-center shadow-xs">
                        <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                    </span>
                    <span class="text-xl font-black tracking-tight text-on-surface">Z1 VapeOS</span>
                </div>
                <p class="text-xs text-on-surface-variant font-medium">
                    {{ $payroll->store?->name ?? 'Z1 Store Central' }}
                </p>
                <p class="text-[11px] text-on-surface-variant">
                    {{ $payroll->store?->address ?? 'Pusat Manajemen Operasional Vape Store' }} • Telp: {{ $payroll->store?->phone ?? '08123456789' }}
                </p>
            </div>

            <div class="sm:text-right space-y-1">
                <div class="inline-block px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider bg-secondary-container text-on-secondary-container">
                    SLIP GAJI KARYAWAN
                </div>
                <div class="text-xs font-mono font-bold text-on-surface">
                    {{ $payroll->payroll_code }}
                </div>
                <div class="text-[11px] text-on-surface-variant font-medium">
                    Periode: {{ $payroll->start_date->isoFormat('D MMMM Y') }} — {{ $payroll->end_date->isoFormat('D MMMM Y') }}
                </div>
            </div>
        </div>

        <!-- Identitas Karyawan -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-5 border-b border-outline-variant/60 text-xs">
            <div>
                <span class="text-[10px] uppercase font-bold text-on-surface-variant block">Nama Karyawan</span>
                <span class="font-extrabold text-on-surface text-sm block">{{ $payroll->user->name }}</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-on-surface-variant block">ID / No. Karyawan</span>
                <span class="font-bold text-on-surface font-mono">{{ $payroll->user->employee_number ?: 'EMP-' . $payroll->user->id }}</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-on-surface-variant block">Jabatan / Posisi</span>
                <span class="font-bold text-on-surface">{{ $payroll->user->position ?: 'Staff' }}</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-on-surface-variant block">Cabang Toko</span>
                <span class="font-bold text-on-surface">{{ $payroll->store?->name ?? 'Semua Cabang' }}</span>
            </div>
        </div>

        <!-- Rincian Gaji: Penerimaan & Potongan (2 Kolom) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 py-6 border-b border-outline-variant/80 text-xs">
            <!-- Kolom 1: Penerimaan (Earnings) -->
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-outline-variant/60 font-bold text-secondary uppercase text-[11px] tracking-wider">
                    <span>1. Komponen Penerimaan</span>
                    <span>Jumlah (Rp)</span>
                </div>

                <div class="space-y-2">
                    <!-- Gaji Pokok -->
                    <div class="flex items-center justify-between">
                        <span class="text-on-surface font-medium">Gaji Pokok (Basic Salary)</span>
                        <span class="font-mono font-bold text-on-surface">
                            Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}
                        </span>
                    </div>

                    <!-- Uang Makan / Kehadiran -->
                    @if($payroll->total_daily_allowance > 0)
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-on-surface font-medium block">Uang Kehadiran / Makan</span>
                            <span class="text-[10px] text-on-surface-variant font-mono">
                                {{ $payroll->total_days_worked }} hari @ Rp {{ number_format($payroll->daily_allowance_rate, 0, ',', '.') }}
                            </span>
                        </div>
                        <span class="font-mono font-bold text-on-surface">
                            Rp {{ number_format($payroll->total_daily_allowance, 0, ',', '.') }}
                        </span>
                    </div>
                    @endif

                    <!-- Lembur Reguler -->
                    @if($payroll->regular_overtime_pay > 0)
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-on-surface font-medium block">Lembur Reguler (Shift Kerja)</span>
                            <span class="text-[10px] text-on-surface-variant font-mono">
                                {{ $payroll->regular_overtime_hours }} jam @ Rp {{ number_format($payroll->regular_overtime_rate, 0, ',', '.') }}
                            </span>
                        </div>
                        <span class="font-mono font-bold text-on-surface">
                            Rp {{ number_format($payroll->regular_overtime_pay, 0, ',', '.') }}
                        </span>
                    </div>
                    @endif

                    <!-- Lembur Hari Libur (OFF) -->
                    @if($payroll->holiday_overtime_pay > 0)
                    <div class="flex items-center justify-between bg-indigo-50/50 dark:bg-indigo-950/20 p-2 rounded-xl">
                        <div>
                            <span class="text-indigo-900 dark:text-indigo-200 font-bold block">Lembur Hari Libur (Jadwal OFF)</span>
                            <span class="text-[10px] text-indigo-700/80 dark:text-indigo-300 font-mono">
                                {{ $payroll->holiday_overtime_hours }} jam @ Rp {{ number_format($payroll->holiday_overtime_rate, 0, ',', '.') }}
                            </span>
                        </div>
                        <span class="font-mono font-bold text-indigo-700 dark:text-indigo-300">
                            Rp {{ number_format($payroll->holiday_overtime_pay, 0, ',', '.') }}
                        </span>
                    </div>
                    @endif

                    <!-- Bonus Tambahan -->
                    @if($payroll->bonus > 0)
                    <div class="flex items-center justify-between text-emerald-700 font-medium">
                        <span>Bonus / Insentif Tambahan</span>
                        <span class="font-mono font-bold">
                            Rp {{ number_format($payroll->bonus, 0, ',', '.') }}
                        </span>
                    </div>
                    @endif
                </div>

                @php
                    $totalGross = $payroll->basic_salary 
                        + $payroll->total_daily_allowance 
                        + $payroll->regular_overtime_pay 
                        + $payroll->holiday_overtime_pay 
                        + $payroll->bonus;
                @endphp
                <div class="flex items-center justify-between pt-2 border-t border-outline-variant/40 font-bold text-on-surface text-xs">
                    <span>Total Kotor (Gross)</span>
                    <span class="font-mono">Rp {{ number_format($totalGross, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Kolom 2: Potongan (Deductions) -->
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-outline-variant/60 font-bold text-rose-700 uppercase text-[11px] tracking-wider">
                    <span>2. Komponen Potongan</span>
                    <span>Jumlah (Rp)</span>
                </div>

                <div class="space-y-2">
                    <!-- Potongan Telat -->
                    @if($payroll->late_deduction > 0)
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-on-surface font-medium block">Keterlambatan</span>
                            <span class="text-[10px] text-on-surface-variant font-mono">
                                {{ $payroll->late_days ?: 1 }} hari terlambat @if($payroll->late_deduction_rate > 0) (@ Rp {{ number_format($payroll->late_deduction_rate, 0, ',', '.') }}/hari) @endif &bull; Total {{ $payroll->late_minutes }}m
                            </span>
                        </div>
                        <span class="font-mono font-bold text-rose-700">
                            -Rp {{ number_format($payroll->late_deduction, 0, ',', '.') }}
                        </span>
                    </div>
                    @else
                    <div class="flex items-center justify-between text-on-surface-variant/60">
                        <span>Keterlambatan (0 hari)</span>
                        <span class="font-mono">Rp 0</span>
                    </div>
                    @endif

                    <!-- Potongan Lainnya -->
                    @if($payroll->other_deductions > 0)
                    <div class="flex items-center justify-between">
                        <span class="text-on-surface font-medium">Potongan Lainnya / Kasbon</span>
                        <span class="font-mono font-bold text-rose-700">
                            Rp {{ number_format($payroll->other_deductions, 0, ',', '.') }}
                        </span>
                    </div>
                    @endif
                </div>

                @php
                    $totalDeductions = $payroll->late_deduction + $payroll->other_deductions;
                @endphp
                <div class="flex items-center justify-between pt-2 border-t border-outline-variant/40 font-bold text-rose-700 text-xs">
                    <span>Total Potongan</span>
                    <span class="font-mono">Rp {{ number_format($totalDeductions, 0, ',', '.') }}</span>
                </div>

                <!-- Informasi Rekening Transfer -->
                @php $setting = $payroll->user->payrollSetting; @endphp
                @if($setting && $setting->bank_name)
                <div class="mt-4 p-3 rounded-2xl bg-surface-container-low/70 border border-outline-variant/50 space-y-1">
                    <span class="text-[10px] font-extrabold uppercase text-secondary tracking-wider block">Ditransfer ke Rekening:</span>
                    <div class="text-xs font-mono font-bold text-on-surface">
                        {{ $setting->bank_name }} — {{ $setting->bank_account_number }}
                    </div>
                    <div class="text-[11px] text-on-surface-variant font-medium">
                        Atas Nama: {{ $setting->bank_account_holder }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Total Gaji Bersih (Take Home Pay) Banner -->
        <div class="my-6 p-6 rounded-2xl bg-secondary-container text-on-secondary-container flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs uppercase font-extrabold tracking-wider block text-on-secondary-container/80">
                    TOTAL GAJI BERSIH DITERIMA (TAKE HOME PAY)
                </span>
                <span class="text-xs font-semibold text-on-secondary-container/90">
                    Status: <strong class="uppercase font-bold">{{ $payroll->status }}</strong> {{ $payroll->paid_at ? 'pada ' . $payroll->paid_at->isoFormat('D MMMM Y HH:mm') : '' }}
                </span>
            </div>

            <div class="text-3xl sm:text-4xl font-black tracking-tight font-mono">
                Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}
            </div>
        </div>

        @if($payroll->note)
        <div class="mb-6 p-3 rounded-xl bg-surface-container-low text-xs text-on-surface">
            <strong>Catatan Tambahan:</strong> {{ $payroll->note }}
        </div>
        @endif

        <!-- Tanda Tangan Section -->
        <div class="grid grid-cols-2 gap-8 pt-8 text-center text-xs">
            <div class="space-y-16">
                <span class="text-on-surface-variant font-medium block">Penerima Gaji (Karyawan),</span>
                <span class="font-bold block border-t border-outline-variant w-48 mx-auto pt-1">{{ $payroll->user->name }}</span>
            </div>
            <div class="space-y-16">
                <span class="text-on-surface-variant font-medium block">Mengetahui (Store Manager / HR),</span>
                <span class="font-bold block border-t border-outline-variant w-48 mx-auto pt-1">{{ $payroll->creator?->name ?? 'Management' }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
