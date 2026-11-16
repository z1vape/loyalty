<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\Store;
use App\Models\User;
use App\Models\UserSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommercialHrDashboardController extends Controller
{
    /**
     * Display the comprehensive Human Resources Analytics & Operational Dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('Super Admin') && !$user->can('Attendance List') && !$user->can('Payroll List') && !$user->can('Shift List')) {
            abort(403, 'Akses ke Dashboard HR dibatasi.');
        }

        $stores = Store::orderBy('name')->get();
        $selectedStoreId = $request->query('store_id');
        $activeStore = $selectedStoreId ? Store::find($selectedStoreId) : null;

        $today = Carbon::today();
        $currentMonth = (int) $request->query('month', now()->month);
        $currentYear = (int) $request->query('year', now()->year);

        // 1. Total Active Employees
        $employeeQuery = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'Super Admin');
        });
        if ($selectedStoreId) {
            $employeeQuery->whereHas('stores', function ($q) use ($selectedStoreId) {
                $q->where('stores.id', $selectedStoreId);
            });
        }
        $totalEmployees = $employeeQuery->count();

        // 2. Today's Attendance Metrics
        $todayAttendancesQuery = Attendance::with(['user.roles', 'store', 'shift'])
            ->whereDate('date', $today);
        if ($selectedStoreId) {
            $todayAttendancesQuery->where('store_id', $selectedStoreId);
        }
        $todayAttendances = $todayAttendancesQuery->get();

        $presentCount = $todayAttendances->whereIn('status', ['Hadir', 'Hadir (OFF)', 'present'])->count();
        $onTimeCount = $todayAttendances->whereIn('status', ['Hadir', 'Hadir (OFF)', 'present'])->where('late_minutes', '<=', 0)->count();
        $lateCount = $todayAttendances->where('late_minutes', '>', 0)->count();
        $totalLateMinutesToday = $todayAttendances->sum('late_minutes');
        $leaveCount = $todayAttendances->whereIn('status', ['Izin', 'Sakit', 'Cuti', 'leave', 'sick', 'SKD'])->count();

        // 3. Today's Schedules & Unattended Staff
        $todaySchedulesQuery = UserSchedule::with(['user', 'store', 'shift'])
            ->whereDate('date', $today);
        if ($selectedStoreId) {
            $todaySchedulesQuery->where('store_id', $selectedStoreId);
        }
        $todaySchedules = $todaySchedulesQuery->get();

        $scheduledActiveSchedules = $todaySchedules->where('is_off', false);
        $clockedInUserIds = $todayAttendances->pluck('user_id')->unique();
        $unattendedSchedules = $scheduledActiveSchedules->whereNotIn('user_id', $clockedInUserIds);
        $unattendedCount = $unattendedSchedules->count();
        $offCount = $todaySchedules->where('is_off', true)->count();

        // 4. Store-by-Store Readiness Breakdown
        $storeReadiness = $stores->map(function ($st) use ($todayAttendances, $todaySchedules) {
            $stAttendances = $todayAttendances->where('store_id', $st->id);
            $stSchedules = $todaySchedules->where('store_id', $st->id)->where('is_off', false);

            $stPresent = $stAttendances->whereIn('status', ['Hadir', 'Hadir (OFF)', 'present'])->count();
            $stLate = $stAttendances->where('late_minutes', '>', 0)->count();
            $stScheduled = $stSchedules->count();
            $stUnattended = $stSchedules->whereNotIn('user_id', $stAttendances->pluck('user_id'))->count();

            $status = 'Siap Beroperasi';
            $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            if ($stScheduled > 0 && $stPresent === 0) {
                $status = 'Belum Ada Presensi';
                $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
            } elseif ($stUnattended > 0) {
                $status = 'Sebagian Hadir';
                $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
            } elseif ($stScheduled === 0 && $stPresent === 0) {
                $status = 'Tidak Ada Jadwal';
                $badgeClass = 'bg-slate-50 text-slate-600 border-slate-200';
            }

            return (object)[
                'id' => $st->id,
                'name' => $st->name,
                'present' => $stPresent,
                'late' => $stLate,
                'scheduled' => $stScheduled,
                'unattended' => $stUnattended,
                'status' => $status,
                'badge_class' => $badgeClass,
                'attendances' => $stAttendances->take(4),
            ];
        });

        // 5. Live Feed (Most recent attendances)
        $recentFeed = Attendance::with(['user.roles', 'store', 'shift'])
            ->when($selectedStoreId, fn($q) => $q->where('store_id', $selectedStoreId))
            ->latest('clock_in')
            ->latest('date')
            ->limit(6)
            ->get();

        // 6. 7-Day Trend
        $trendDays = collect();
        for ($i = 6; $i >= 0; $i--) {
            $targetDate = Carbon::today()->subDays($i);
            $dayAttendances = Attendance::whereDate('date', $targetDate)
                ->when($selectedStoreId, fn($q) => $q->where('store_id', $selectedStoreId))
                ->get();

            $dayPresent = $dayAttendances->whereIn('status', ['Hadir', 'Hadir (OFF)', 'present'])->count();
            $dayLate = $dayAttendances->where('late_minutes', '>', 0)->count();

            $trendDays->push((object)[
                'date' => $targetDate->format('Y-m-d'),
                'day_name' => $targetDate->locale('id')->isoFormat('ddd, D MMM'),
                'present' => $dayPresent,
                'late' => $dayLate,
                'percentage' => $totalEmployees > 0 ? min(100, round(($dayPresent / $totalEmployees) * 100)) : 0,
            ]);
        }

        // 7. Monthly Payroll & Labor Cost Estimate
        $payrolls = Payroll::where('period_month', $currentMonth)
            ->where('period_year', $currentYear)
            ->when($selectedStoreId, fn($q) => $q->where('store_id', $selectedStoreId))
            ->get();

        $totalEstimatedPayroll = $payrolls->sum('net_salary');
        $totalBasicSalary = $payrolls->sum('basic_salary');
        $totalAllowances = $payrolls->sum('total_daily_allowance');
        $totalOvertime = $payrolls->sum('regular_overtime_pay') + $payrolls->sum('holiday_overtime_pay');
        $totalLateDeductions = $payrolls->sum('late_deduction');
        $payrollCount = $payrolls->count();
        $payrollStatus = $payrolls->first()?->status ?? 'Draft';

        // 8. Top Late Employees This Month
        $startOfMonth = Carbon::create($currentYear, $currentMonth, 1)->startOfMonth();
        $endOfMonth = Carbon::create($currentYear, $currentMonth, 1)->endOfMonth();

        $topLateEmployees = Attendance::with(['user.stores', 'user.roles'])
            ->selectRaw('user_id, COUNT(*) as late_count, SUM(late_minutes) as total_late_minutes')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('late_minutes', '>', 0)
            ->when($selectedStoreId, fn($q) => $q->where('store_id', $selectedStoreId))
            ->groupBy('user_id')
            ->orderByDesc('late_count')
            ->orderByDesc('total_late_minutes')
            ->limit(5)
            ->get();

        return view('commercial.dashboard.index', compact(
            'stores',
            'selectedStoreId',
            'activeStore',
            'today',
            'currentMonth',
            'currentYear',
            'totalEmployees',
            'presentCount',
            'onTimeCount',
            'lateCount',
            'totalLateMinutesToday',
            'leaveCount',
            'unattendedCount',
            'offCount',
            'storeReadiness',
            'recentFeed',
            'trendDays',
            'totalEstimatedPayroll',
            'totalBasicSalary',
            'totalAllowances',
            'totalOvertime',
            'totalLateDeductions',
            'payrollCount',
            'payrollStatus',
            'topLateEmployees'
        ));
    }
}

