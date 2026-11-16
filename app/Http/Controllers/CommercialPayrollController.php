<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\EmployeePayrollSetting;
use App\Models\Payroll;
use App\Models\Shift;
use App\Models\Store;
use App\Models\User;
use App\Models\UserSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CommercialPayrollController extends Controller
{
    /**
     * Display payroll list & monthly summaries.
     */
    public function index(Request $request)
    {
        Gate::authorize('Payroll List');

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $storeId = $request->query('store_id');
        $status = $request->query('status');
        $search = $request->query('search');

        $query = Payroll::with(['user.stores', 'store', 'creator'])
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->when(!empty($storeId), function ($q) use ($storeId) {
                $q->where(function ($sub) use ($storeId) {
                    $sub->where('store_id', $storeId)
                        ->orWhereHas('user.stores', fn($sq) => $sq->where('stores.id', $storeId));
                });
            })
            ->when(!empty($status) && $status !== 'all', fn($q) => $q->where('status', $status))
            ->when(!empty($search), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('payroll_code', 'like', "%{$search}%")
                        ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('created_at', 'desc');

        $payrolls = $query->paginate(15)->withQueryString();

        // Monthly Summary
        $allPayrolls = Payroll::where('period_month', $month)
            ->where('period_year', $year)
            ->when(!empty($storeId), function ($q) use ($storeId) {
                $q->where(function ($sub) use ($storeId) {
                    $sub->where('store_id', $storeId)
                        ->orWhereHas('user.stores', fn($sq) => $sq->where('stores.id', $storeId));
                });
            })
            ->get();

        $summary = [
            'total_net_salary'        => $allPayrolls->sum('net_salary'),
            'total_regular_overtime'  => $allPayrolls->sum('regular_overtime_pay'),
            'total_holiday_overtime'  => $allPayrolls->sum('holiday_overtime_pay'),
            'total_employees'         => $allPayrolls->count(),
            'total_paid'              => $allPayrolls->where('status', 'paid')->count(),
            'total_approved'          => $allPayrolls->where('status', 'approved')->count(),
            'total_draft'             => $allPayrolls->where('status', 'draft')->count(),
        ];

        // Store options
        $stores = Store::orderBy('name')->get();
        $months = $this->getMonthOptions();
        $years = [now()->year - 1, now()->year, now()->year + 1];
        if (!in_array($year, $years)) {
            $years[] = $year;
            sort($years);
        }

        // Eligible employees for quick generation
        $employees = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'Super Admin'))
            ->when(!empty($storeId), fn($q) => $q->whereHas('stores', fn($sq) => $sq->where('stores.id', $storeId)))
            ->orderBy('name')
            ->get();

        return view('commercial.payrolls.index', compact(
            'payrolls',
            'summary',
            'stores',
            'months',
            'years',
            'month',
            'year',
            'storeId',
            'status',
            'search',
            'employees'
        ));
    }

    /**
     * Display salary settings per employee.
     */
    public function settings(Request $request)
    {
        Gate::authorize('Payroll Setting');

        $search = $request->query('search');
        $storeId = $request->query('store_id');

        $employees = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'Super Admin'))
            ->with(['stores', 'payrollSetting'])
            ->when(!empty($storeId), fn($q) => $q->whereHas('stores', fn($sq) => $sq->where('stores.id', $storeId)))
            ->when(!empty($search), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $stores = Store::orderBy('name')->get();

        return view('commercial.payrolls.settings', compact('employees', 'stores', 'search', 'storeId'));
    }

    /**
     * Store or update salary settings for a user.
     */
    public function updateSettings(Request $request)
    {
        Gate::authorize('Payroll Setting');

        $validated = $request->validate([
            'user_id'                         => 'required|exists:users,id',
            'basic_salary'                    => 'required|numeric|min:0',
            'daily_allowance'                 => 'nullable|numeric|min:0',
            'overtime_rate_per_hour'          => 'nullable|numeric|min:0',
            'holiday_overtime_rate_per_hour'  => 'nullable|numeric|min:0',
            'late_deduction_per_day'          => 'nullable|numeric|min:0',
            'late_tolerance_minutes'          => 'nullable|integer|min:0|max:180',
            'late_deduction_per_minute'       => 'nullable|numeric|min:0',
            'payment_type'                    => 'required|in:monthly,daily',
            'bank_name'                       => 'nullable|string|max:100',
            'bank_account_number'             => 'nullable|string|max:50',
            'bank_account_holder'             => 'nullable|string|max:100',
            'notes'                           => 'nullable|string|max:500',
        ]);

        EmployeePayrollSetting::updateOrCreate(
            ['user_id' => $validated['user_id']],
            $validated
        );

        return redirect()->back()->with('success', 'Pengaturan gaji karyawan berhasil disimpan.');
    }

    /**
     * Bulk generate or refresh payroll calculation for selected period.
     */
    public function generate(Request $request)
    {
        Gate::authorize('Payroll Manage');

        $validated = $request->validate([
            'period_month' => 'required|integer|between:1,12',
            'period_year'  => 'required|integer|min:2020|max:2035',
            'user_ids'     => 'required|array',
            'user_ids.*'   => 'exists:users,id',
            'store_id'     => 'nullable|exists:stores,id',
        ]);

        $month = (int) $validated['period_month'];
        $year = (int) $validated['period_year'];
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $generatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($validated['user_ids'] as $userId) {
                $user = User::with(['stores', 'payrollSetting'])->find($userId);
                if (!$user) continue;

                $setting = $user->payrollSetting;
                $basicSalary = (float) ($setting?->basic_salary ?? 0);
                $dailyRate = (float) ($setting?->daily_allowance ?? 0);
                $overtimeHourlyRate = (float) ($setting?->overtime_rate_per_hour ?? 0);
                $holidayOvertimeHourlyRate = (float) ($setting?->holiday_overtime_rate_per_hour ?? 0);
                $lateDeductionPerDay = (float) ($setting?->late_deduction_per_day ?? 0);
                $toleranceMinutes = (int) ($setting?->late_tolerance_minutes ?? 15);
                $lateMinuteRate = (float) ($setting?->late_deduction_per_minute ?? 0);

                // Fetch all attendances for the period safely across datetime boundaries
                $attendances = Attendance::with('shift')
                    ->where('user_id', $user->id)
                    ->whereDate('date', '>=', $startDate->toDateString())
                    ->whereDate('date', '<=', $endDate->toDateString())
                    ->whereNotNull('clock_in')
                    ->orderBy('date')
                    ->get();

                // Fetch all schedules for this period
                $schedules = UserSchedule::with('shift')
                    ->where('user_id', $user->id)
                    ->whereDate('date', '>=', $startDate->toDateString())
                    ->whereDate('date', '<=', $endDate->toDateString())
                    ->get()
                    ->keyBy(fn($s) => Carbon::parse($s->date)->toDateString());

                $defaultShift = Shift::where('is_active', true)->first();

                // Unique days worked (clocked in)
                $uniqueWorkDates = $attendances->pluck('date')->map(fn($d) => Carbon::parse($d)->toDateString())->unique();
                $totalDaysWorked = $uniqueWorkDates->count();

                $totalLateMinutes = 0;
                $lateDaysCount = 0;
                $regularOvertimeMinutes = 0;
                $holidayOvertimeMinutes = 0;

                foreach ($attendances as $att) {
                    $attDateStr = Carbon::parse($att->date)->toDateString();
                    $sched = $schedules->get($attDateStr);
                    $shiftObj = $att->shift ?? $sched?->shift ?? $defaultShift;
                    
                    // Cek apakah hari libur (OFF)
                    $isOffDay = ($sched && $sched->is_off) 
                        || $att->status === 'off_ovt' 
                        || str_contains(strtolower($att->note ?? ''), 'off') 
                        || str_contains(strtolower($att->note ?? ''), 'libur');

                    $otMinutes = (int) ($att->overtime_minutes > 0 ? $att->overtime_minutes : 0);

                    if ($isOffDay) {
                        // 1. Lembur Hari Libur (Jadwal OFF)
                        $holMins = 0;
                        if ($att->clock_in && $att->clock_out) {
                            $holMins = Carbon::parse($att->clock_in)->diffInMinutes(Carbon::parse($att->clock_out));
                        } elseif ($att->work_minutes > 0) {
                            $holMins = (int) $att->work_minutes;
                        }
                        $holidayOvertimeMinutes += max($holMins, $otMinutes);
                    } else {
                        // 2. Hari Kerja Normal (Shift Regular)
                        // A. Periksa Keterlambatan (Late)
                        if ($shiftObj && $att->clock_in) {
                            $shiftStart = Carbon::parse($attDateStr . ' ' . $shiftObj->start_time);
                            $clockIn = Carbon::parse($att->clock_in);
                            $tol = $toleranceMinutes > 0 ? $toleranceMinutes : (int) ($shiftObj->late_tolerance_minutes ?? 15);
                            $deadline = $shiftStart->copy()->addMinutes($tol);

                            $isLate = false;
                            $lateMins = 0;

                            if ($clockIn->gt($deadline)) {
                                $isLate = true;
                                $lateMins = $shiftStart->diffInMinutes($clockIn);
                            } elseif ($att->status === 'late' || str_contains(strtolower($att->status ?? ''), 'late')) {
                                $isLate = true;
                                $lateMins = (int) ($att->late_minutes > 0 ? $att->late_minutes : $tol);
                            }

                            if ($isLate) {
                                $lateDaysCount++;
                                $totalLateMinutes += max($lateMins, 1);
                            }
                        }

                        // B. Periksa Lembur Reguler (Kelebihan Jam Kerja Shift)
                        if ($shiftObj && $att->clock_out) {
                            $shiftStart = Carbon::parse($attDateStr . ' ' . $shiftObj->start_time);
                            $shiftEnd = Carbon::parse($attDateStr . ' ' . $shiftObj->end_time);
                            // Penanganan shift lintas hari/malam (e.g., 16:00 - 00:00 atau 22:00 - 06:00)
                            if ($shiftEnd->lt($shiftStart)) {
                                $shiftEnd->addDay();
                            }

                            $clockIn = Carbon::parse($att->clock_in);
                            $clockOut = Carbon::parse($att->clock_out);
                            if ($clockOut->lt($clockIn)) {
                                $clockOut->addDay();
                            }

                            $calcRegOt = 0;
                            if ($clockOut->gt($shiftEnd)) {
                                $diffMins = $shiftEnd->diffInMinutes($clockOut);
                                // Lembur reguler hanya diakui jika kelebihan waktu minimal 1 jam (60 menit)
                                // Dihitung kelipatan 1 jam penuh (contoh: selisih 75 menit tetap dihitung 1 jam / 60 menit)
                                if ($diffMins >= 60) {
                                    $calcRegOt = intdiv($diffMins, 60) * 60;
                                }
                            }
                            $validOtMinutes = $otMinutes >= 60 ? (intdiv($otMinutes, 60) * 60) : 0;
                            $regularOvertimeMinutes += max($calcRegOt, $validOtMinutes);
                        } elseif ($otMinutes >= 60) {
                            $regularOvertimeMinutes += intdiv($otMinutes, 60) * 60;
                        }
                    }
                }

                // Kalkulasi Finansial
                $paymentType = $setting?->payment_type ?? 'monthly';
                $effectiveBasicSalary = $paymentType === 'daily' 
                    ? round($totalDaysWorked * $basicSalary, 2) 
                    : $basicSalary;

                $totalDailyAllowance = round($totalDaysWorked * $dailyRate, 2);
                $regularOvertimePay = round(($regularOvertimeMinutes / 60) * $overtimeHourlyRate, 2);
                $holidayOvertimePay = round(($holidayOvertimeMinutes / 60) * $holidayOvertimeHourlyRate, 2);

                // Potongan Keterlambatan: Flat Per Hari atau Per Menit
                if ($lateDeductionPerDay > 0) {
                    $lateDeduction = round($lateDaysCount * $lateDeductionPerDay, 2);
                } elseif ($lateMinuteRate > 0) {
                    $lateDeduction = round($totalLateMinutes * $lateMinuteRate, 2);
                } else {
                    $lateDeduction = 0;
                }

                // Pertahankan Bonus dan Potongan Lain jika sebelumnya sudah pernah disesuaikan
                $existingPayroll = Payroll::where('user_id', $user->id)
                    ->where('period_month', $month)
                    ->where('period_year', $year)
                    ->first();
                $bonus = $existingPayroll ? (float) $existingPayroll->bonus : 0;
                $otherDeductions = $existingPayroll ? (float) $existingPayroll->other_deductions : 0;
                $existingNote = $existingPayroll ? $existingPayroll->note : null;

                $grossSalary = $effectiveBasicSalary + $totalDailyAllowance + $regularOvertimePay + $holidayOvertimePay + $bonus;
                $totalDeductions = $lateDeduction + $otherDeductions;
                $netSalary = max(0, round($grossSalary - $totalDeductions, 2));

                $assignedStoreId = $validated['store_id'] ?? $user->stores->first()?->id ?? null;
                $payrollCode = 'PAY-' . $year . str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string)$user->id, 3, '0', STR_PAD_LEFT);

                Payroll::updateOrCreate(
                    [
                        'user_id'      => $user->id,
                        'period_month' => $month,
                        'period_year'  => $year,
                    ],
                    [
                        'payroll_code'             => $payrollCode,
                        'store_id'                 => $assignedStoreId,
                        'start_date'               => $startDate->toDateString(),
                        'end_date'                 => $endDate->toDateString(),
                        'payment_type'             => $paymentType,
                        'basic_salary'             => $effectiveBasicSalary,
                        'total_days_worked'        => $totalDaysWorked,
                        'daily_allowance_rate'     => $dailyRate,
                        'total_daily_allowance'    => $totalDailyAllowance,
                        'regular_overtime_minutes' => $regularOvertimeMinutes,
                        'regular_overtime_rate'    => $overtimeHourlyRate,
                        'regular_overtime_pay'     => $regularOvertimePay,
                        'holiday_overtime_minutes' => $holidayOvertimeMinutes,
                        'holiday_overtime_rate'    => $holidayOvertimeHourlyRate,
                        'holiday_overtime_pay'     => $holidayOvertimePay,
                        'late_days'                => $lateDaysCount,
                        'late_deduction_rate'      => $lateDeductionPerDay,
                        'late_minutes'             => $totalLateMinutes,
                        'late_deduction'           => $lateDeduction,
                        'bonus'                    => $bonus,
                        'other_deductions'         => $otherDeductions,
                        'net_salary'               => $netSalary,
                        'note'                     => $existingNote,
                        'created_by'               => auth()->id(),
                    ]
                );

                $generatedCount++;
            }

            DB::commit();
            return redirect()->route('commercial.payrolls.index', ['month' => $month, 'year' => $year])
                ->with('success', "Berhasil menghitung dan generate {$generatedCount} slip gaji karyawan.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal generate payroll: ' . $e->getMessage());
        }
    }

    /**
     * Display printable pay slip for an employee.
     */
    public function show(Payroll $payroll)
    {
        Gate::authorize('Payroll List');

        $payroll->load(['user.payrollSetting', 'store', 'creator']);

        return view('commercial.payrolls.show', compact('payroll'));
    }

    /**
     * Update adjustments (bonus, other deductions, note) on a payroll slip.
     */
    public function update(Request $request, Payroll $payroll)
    {
        Gate::authorize('Payroll Manage');

        $validated = $request->validate([
            'bonus'            => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
            'note'             => 'nullable|string|max:500',
        ]);

        $bonus = (float) ($validated['bonus'] ?? 0);
        $otherDeductions = (float) ($validated['other_deductions'] ?? 0);

        $gross = $payroll->basic_salary 
            + $payroll->total_daily_allowance 
            + $payroll->regular_overtime_pay 
            + $payroll->holiday_overtime_pay 
            + $bonus;

        $deductions = $payroll->late_deduction + $otherDeductions;
        $netSalary = max(0, round($gross - $deductions, 2));

        $payroll->update([
            'bonus'            => $bonus,
            'other_deductions' => $otherDeductions,
            'note'             => $validated['note'] ?? null,
            'net_salary'       => $netSalary,
        ]);

        return redirect()->back()->with('success', "Slip gaji [{$payroll->payroll_code}] berhasil disesuaikan.");
    }

    /**
     * Update payroll status (Draft -> Approved -> Paid).
     */
    public function updateStatus(Request $request, Payroll $payroll)
    {
        Gate::authorize('Payroll Manage');

        $validated = $request->validate([
            'status' => 'required|in:draft,approved,paid',
        ]);

        $payroll->update([
            'status'  => $validated['status'],
            'paid_at' => $validated['status'] === 'paid' ? now() : null,
        ]);

        return redirect()->back()->with('success', "Status payroll [{$payroll->payroll_code}] diubah ke " . strtoupper($validated['status']) . ".");
    }

    /**
     * Delete a draft payroll record.
     */
    public function destroy(Payroll $payroll)
    {
        Gate::authorize('Payroll Manage');

        if ($payroll->status === 'paid') {
            return redirect()->back()->with('error', 'Gaji yang sudah berstatus PAID tidak dapat dihapus.');
        }

        $code = $payroll->payroll_code;
        $payroll->delete();

        return redirect()->back()->with('success', "Slip gaji [{$code}] berhasil dihapus.");
    }

    /**
     * Indonesian month names.
     */
    private function getMonthOptions(): array
    {
        return [
            1  => 'Januari',
            2  => 'Februari',
            3  => 'Maret',
            4  => 'April',
            5  => 'Mei',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'Agustus',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }
}
