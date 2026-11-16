@extends('layouts.app')

@section('title', 'Pengaturan Komponen Gaji Karyawan — Payroll')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-surface-container-lowest p-6 rounded-3xl border border-outline-variant shadow-xs">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-secondary uppercase tracking-wider mb-1">
                <a href="{{ route('commercial.payrolls.index') }}" class="hover:underline">Payroll Employee</a>
                <span>•</span>
                <span>Pengaturan Gaji</span>
            </div>
            <h1 class="text-2xl font-black text-on-surface tracking-tight">Komponen Gaji Karyawan</h1>
            <p class="text-xs text-on-surface-variant mt-0.5">
                Konfigurasi gaji pokok, uang makan kehadiran, serta tarif lembur reguler & lembur hari libur (OFF) per staf.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('commercial.payrolls.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-surface-container-high hover:bg-surface-container-highest text-on-surface text-xs font-bold transition-all border border-outline-variant cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Kembali ke Payroll</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="bg-surface-container-lowest p-4 rounded-3xl border border-outline-variant shadow-xs">
        <form id="payrollSettingsFilterForm" action="{{ route('commercial.payrolls.settings') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="w-full sm:w-64">
                <select name="store_id" id="settings_store_id" class="w-full">
                    <option value="">Semua Cabang Toko</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:flex-1 relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama karyawan, ID staf, atau jabatan..." class="w-full pl-9 pr-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                <span class="material-symbols-outlined absolute left-2.5 top-2.5 text-on-surface-variant text-[18px]">search</span>
            </div>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-xl text-xs font-bold hover:bg-[#00553a] shrink-0 shadow-xs cursor-pointer">
                Filter
            </button>
        </form>
    </div>

    <!-- Table of Employee Settings -->
    <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-outline-variant/60 bg-surface-container-low/70 text-on-surface-variant uppercase text-[10px] tracking-wider font-extrabold whitespace-nowrap">
                        <th class="py-3.5 px-4 min-w-[200px]">Karyawan</th>
                        <th class="py-3.5 px-4 min-w-[170px]">Toko & Posisi</th>
                        <th class="py-3.5 px-4 text-right min-w-[130px]">Gaji Pokok</th>
                        <th class="py-3.5 px-4 text-right min-w-[125px]">Uang Makan / Hari</th>
                        <th class="py-3.5 px-4 text-right min-w-[135px]">Lembur Reguler / Jam</th>
                        <th class="py-3.5 px-4 text-right min-w-[150px]">Lembur Libur (OFF) / Jam</th>
                        <th class="py-3.5 px-4 text-right min-w-[125px]">Pot. Telat / Hari</th>
                        <th class="py-3.5 px-4 min-w-[190px]">Rekening Bank</th>
                        <th class="py-3.5 px-4 text-center min-w-[110px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-on-surface font-medium">
                    @forelse($employees as $emp)
                    @php 
                        $s = $emp->payrollSetting; 
                        $hasSalary = $s && $s->basic_salary > 0;
                    @endphp
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <!-- Karyawan -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-bold text-xs uppercase shrink-0">
                                    {{ substr($emp->name, 0, 2) }}
                                </span>
                                <div class="min-w-0">
                                    <span class="font-bold text-on-surface block text-xs truncate max-w-[180px]">{{ $emp->name }}</span>
                                    <span class="text-[10px] text-on-surface-variant font-mono">{{ $emp->employee_number ?: 'EMP-' . $emp->id }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- Toko & Posisi -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="text-xs font-semibold text-on-surface block truncate max-w-[160px]">{{ $emp->stores->first()?->name ?? 'Semua Toko' }}</span>
                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-surface-container text-on-surface-variant">
                                {{ $emp->position ?: 'Staff' }}
                            </span>
                        </td>

                        <!-- Gaji Pokok -->
                        <td class="py-3.5 px-4 text-right font-mono whitespace-nowrap">
                            @if($hasSalary)
                                <span class="font-bold text-on-surface text-xs">Rp {{ number_format($s->basic_salary, 0, ',', '.') }}</span>
                            @else
                                <span class="text-on-surface-variant/40 text-xs font-bold">—</span>
                            @endif
                        </td>

                        <!-- Uang Makan / Hari -->
                        <td class="py-3.5 px-4 text-right font-mono whitespace-nowrap">
                            @if($s && $s->daily_allowance > 0)
                                <span class="text-on-surface text-xs">Rp {{ number_format($s->daily_allowance, 0, ',', '.') }}</span>
                            @else
                                <span class="text-on-surface-variant/40 text-xs font-bold">—</span>
                            @endif
                        </td>

                        <!-- Lembur Reguler / Jam -->
                        <td class="py-3.5 px-4 text-right font-mono whitespace-nowrap">
                            @if($s && $s->overtime_rate_per_hour > 0)
                                <span class="text-on-surface text-xs">Rp {{ number_format($s->overtime_rate_per_hour, 0, ',', '.') }}</span>
                            @else
                                <span class="text-on-surface-variant/40 text-xs font-bold">—</span>
                            @endif
                        </td>

                        <!-- Lembur Libur (OFF) / Jam -->
                        <td class="py-3.5 px-4 text-right font-mono whitespace-nowrap">
                            @if($s && $s->holiday_overtime_rate_per_hour > 0)
                                <span class="font-bold text-indigo-700 dark:text-indigo-400 text-xs">Rp {{ number_format($s->holiday_overtime_rate_per_hour, 0, ',', '.') }}</span>
                            @else
                                <span class="text-on-surface-variant/40 text-xs font-bold">—</span>
                            @endif
                        </td>

                        <!-- Pot. Telat / Hari -->
                        <td class="py-3.5 px-4 text-right font-mono whitespace-nowrap">
                            @if($s && $s->late_deduction_per_day > 0)
                                <div class="font-bold text-rose-700 text-xs">Rp {{ number_format($s->late_deduction_per_day, 0, ',', '.') }}</div>
                                <span class="text-[9px] text-on-surface-variant block font-sans">Tol: {{ $s->late_tolerance_minutes ?? 15 }}m</span>
                            @else
                                <span class="text-on-surface-variant/40 text-xs font-bold">—</span>
                            @endif
                        </td>

                        <!-- Rekening Bank -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($s && $s->bank_name && $s->bank_account_number)
                                <div class="flex items-center gap-1.5">
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-primary/10 text-primary border border-primary/20">{{ $s->bank_name }}</span>
                                    <span class="font-mono font-bold text-xs text-on-surface">{{ $s->bank_account_number }}</span>
                                </div>
                                <span class="text-[10px] text-on-surface-variant block mt-0.5 truncate max-w-[170px]">a.n {{ $s->bank_account_holder }}</span>
                            @else
                                <span class="text-on-surface-variant/40 italic text-[11px]">Belum diisi</span>
                            @endif
                        </td>

                        <!-- Aksi -->
                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                            <button 
                                type="button" 
                                onclick="openSettingModal({{ $emp->id }}, '{{ addslashes($emp->name) }}', {{ (float)($s?->basic_salary ?? 0) }}, {{ (float)($s?->daily_allowance ?? 0) }}, {{ (float)($s?->overtime_rate_per_hour ?? 0) }}, {{ (float)($s?->holiday_overtime_rate_per_hour ?? 0) }}, {{ (float)($s?->late_deduction_per_day ?? 0) }}, {{ (int)($s?->late_tolerance_minutes ?? 15) }}, '{{ $s?->payment_type ?? 'monthly' }}', '{{ addslashes($s?->bank_name ?? '') }}', '{{ addslashes($s?->bank_account_number ?? '') }}', '{{ addslashes($s?->bank_account_holder ?? '') }}', '{{ addslashes($s?->notes ?? '') }}')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary/10 text-primary hover:bg-primary hover:text-white font-bold text-xs transition-all cursor-pointer shadow-xs active:scale-95"
                            >
                                <span class="material-symbols-outlined text-[15px]">edit</span>
                                <span>Atur Gaji</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-on-surface-variant">
                            Tidak ada data karyawan ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())
        <div class="p-4 border-t border-outline-variant/60">
            {{ $employees->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Edit Komponen Gaji Karyawan -->
<div id="setting-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs hidden overflow-y-auto">
    <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-3xl w-full max-w-lg shadow-2xl p-6 space-y-4 my-8">
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/60">
            <div>
                <h3 class="font-bold text-sm text-on-surface">Atur Komponen Gaji Karyawan</h3>
                <p id="modal-emp-name" class="text-xs font-bold text-primary"></p>
            </div>
            <button type="button" onclick="closeSettingModal()" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('commercial.payrolls.settings.update') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="user_id" id="modal-user-id">

            <!-- 1. Gaji Pokok & Tipe Pembayaran -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Gaji Pokok (Rp) <span class="text-rose-600">*</span></label>
                    <input type="number" step="any" name="basic_salary" id="modal-basic-salary" required min="0" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono text-on-surface focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Siklus Pembayaran <span class="text-rose-600">*</span></label>
                    <select name="payment_type" id="modal-payment-type" required class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                        <option value="monthly">Bulanan (Monthly)</option>
                        <option value="daily">Harian (Daily)</option>
                    </select>
                </div>
            </div>

            <!-- 2. Uang Makan & Potongan Keterlambatan Flat Per Hari -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Uang Makan / Hadir per Hari (Rp)</label>
                    <input type="number" step="any" name="daily_allowance" id="modal-daily-allowance" min="0" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono text-on-surface focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Potongan Telat per Hari (Rp)</label>
                    <input type="number" step="any" name="late_deduction_per_day" id="modal-late-deduction-day" min="0" placeholder="e.g. 20000" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono text-on-surface focus:outline-none focus:border-primary">
                    <span class="text-[10px] text-on-surface-variant block mt-0.5">Denda flat jika clock in > batas toleransi</span>
                </div>
            </div>

            <!-- Batas Toleransi Keterlambatan -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Batas Toleransi Keterlambatan (Menit)</label>
                <input type="number" name="late_tolerance_minutes" id="modal-late-tolerance" min="0" max="180" placeholder="15" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono text-on-surface focus:outline-none focus:border-primary">
                <span class="text-[10px] text-on-surface-variant block mt-0.5">Contoh: 15 menit (Shift 09:00, jika clock in 09:16 langsung dipotong per hari)</span>
            </div>

            <!-- 3. Tarif Lembur Reguler vs Lembur Libur (OFF) -->
            <div class="p-3 bg-surface-container-low/80 rounded-2xl border border-outline-variant/60 space-y-3">
                <span class="text-[11px] font-extrabold uppercase text-secondary tracking-wider block">Tarif Lembur (Overtime Rate):</span>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface mb-1">Lembur Reguler / Jam (Rp)</label>
                        <input type="number" step="any" name="overtime_rate_per_hour" id="modal-overtime-rate" min="0" placeholder="e.g. 20000" class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs font-mono text-on-surface focus:outline-none focus:border-primary">
                        <span class="text-[10px] text-on-surface-variant block mt-0.5">Kelebihan jam kerja normal</span>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-indigo-700 dark:text-indigo-400 mb-1">Lembur Hari Libur / Jam (Rp)</label>
                        <input type="number" step="any" name="holiday_overtime_rate_per_hour" id="modal-holiday-overtime-rate" min="0" placeholder="e.g. 35000" class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-xs font-mono text-on-surface focus:outline-none focus:border-primary">
                        <span class="text-[10px] text-indigo-600/80 block mt-0.5">Jam kerja saat jadwal OFF</span>
                    </div>
                </div>
            </div>

            <!-- 4. Data Rekening Bank Transfer -->
            <div class="space-y-2">
                <span class="text-[11px] font-extrabold uppercase text-secondary tracking-wider block">Rekening Pembayaran Gaji:</span>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Bank</label>
                        <input type="text" name="bank_name" id="modal-bank-name" placeholder="BCA / Mandiri / BRI" class="w-full px-2.5 py-1.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Nomor Rekening</label>
                        <input type="text" name="bank_account_number" id="modal-bank-number" placeholder="1234567890" class="w-full px-2.5 py-1.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono text-on-surface focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Atas Nama</label>
                        <input type="text" name="bank_account_holder" id="modal-bank-holder" placeholder="Nama pemilik" class="w-full px-2.5 py-1.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
                    </div>
                </div>
            </div>

            <!-- 5. Catatan -->
            <div>
                <label class="block text-xs font-bold text-on-surface mb-1">Catatan Tambahan</label>
                <input type="text" name="notes" id="modal-notes" placeholder="Opsional: syarat tunjangan atau catatan kontrak..." class="w-full px-3 py-1.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs text-on-surface focus:outline-none focus:border-primary">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-outline-variant/60">
                <button type="button" onclick="closeSettingModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-on-surface hover:bg-surface-container border border-outline-variant">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-primary text-white hover:bg-[#00553a] shadow-xs cursor-pointer">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('settings_store_id')) {
            let isReady = false;
            new TomSelect('#settings_store_id', {
                create: false,
                allowEmptyOption: true,
                placeholder: 'Semua Cabang Toko',
                maxOptions: 100,
                onChange: function() {
                    if (isReady) {
                        document.getElementById('payrollSettingsFilterForm').submit();
                    }
                }
            });
            setTimeout(() => { isReady = true; }, 150);
        }
    });

    function openSettingModal(userId, name, basic, allowance, otRate, holidayOtRate, lateDeductionDay, lateTolerance, paymentType, bank, bankNumber, bankHolder, notes) {
        document.getElementById('modal-user-id').value = userId;
        document.getElementById('modal-emp-name').textContent = name;
        document.getElementById('modal-basic-salary').value = basic || 0;
        document.getElementById('modal-daily-allowance').value = allowance || 0;
        document.getElementById('modal-overtime-rate').value = otRate || 0;
        document.getElementById('modal-holiday-overtime-rate').value = holidayOtRate || 0;
        document.getElementById('modal-late-deduction-day').value = lateDeductionDay || 0;
        document.getElementById('modal-late-tolerance').value = lateTolerance !== undefined ? lateTolerance : 15;
        document.getElementById('modal-payment-type').value = paymentType || 'monthly';
        document.getElementById('modal-bank-name').value = bank || '';
        document.getElementById('modal-bank-number').value = bankNumber || '';
        document.getElementById('modal-bank-holder').value = bankHolder || '';
        document.getElementById('modal-notes').value = notes || '';

        document.getElementById('setting-modal').classList.remove('hidden');
    }

    function closeSettingModal() {
        document.getElementById('setting-modal').classList.add('hidden');
    }
</script>
@endpush
@endsection
