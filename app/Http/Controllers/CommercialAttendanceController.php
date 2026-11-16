<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Shift;
use App\Models\Store;
use App\Models\User;
use App\Models\UserSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommercialAttendanceController extends Controller
{
    /**
     * Display a listing of employees with their attendance recap.
     */
    public function index(Request $request)
    {
        Gate::authorize('Attendance List');

        $search = $request->query('search');
        $storeId = $request->query('store_id');
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $perPage = (int) $request->query('per_page', 10);
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 10;
        }

        // Determine date range: 1 full month of selected month/year (or custom selected dates)
        $monthCarbon = Carbon::create($year, $month, 1);
        $defaultStart = $monthCarbon->copy()->startOfMonth()->toDateString();
        $defaultEnd = $monthCarbon->copy()->endOfMonth()->toDateString();

        $startDate = $request->query('start_date', $defaultStart);
        $endDate = $request->query('end_date', $defaultEnd);
        $absensiBolong = $request->boolean('absensi_bolong');

        $usersQuery = User::query()
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Super Admin');
            })
            ->with(['stores', 'roles'])
            ->withCount([
                'attendances as total_attendances_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate])
                      ->whereNotNull('clock_in');
                },
                'attendances as late_attendances_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate])
                      ->where('status', 'late');
                }
            ]);

        // Filter: Search name / employee_number / email
        if (!empty($search)) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        // Filter: Store location
        if (!empty($storeId)) {
            $usersQuery->whereHas('stores', function ($q) use ($storeId) {
                $q->where('stores.id', $storeId);
            });
        }

        // Filter: Absensi Bolong (karyawan dengan kehadiran kurang dari total hari kerja di periode)
        if ($absensiBolong) {
            $periodDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            $estimatedWorkDays = max(1, (int) round($periodDays * 0.75)); // approximate workdays without weekend
            $usersQuery->whereHas('attendances', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate])
                  ->where(function ($sq) {
                      $sq->whereNull('clock_out')
                        ->orWhereIn('status', ['absent', 'late']);
                  });
            })->orHaving('total_attendances_count', '<', $estimatedWorkDays);
        }

        $users = $usersQuery->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        $stores = Store::orderBy('name')->get();
        $totalEmployees = $users->total();

        $months = [
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

        $years = [now()->year - 1, now()->year, now()->year + 1];

        return view('commercial.attendances.index', compact(
            'users',
            'stores',
            'totalEmployees',
            'search',
            'storeId',
            'month',
            'year',
            'startDate',
            'endDate',
            'absensiBolong',
            'perPage',
            'months',
            'years'
        ));
    }

    /**
     * Display detailed attendance history for a specific employee.
     */
    public function show(Request $request, User $user)
    {
        Gate::authorize('Attendance List');

        if ($user->hasRole('Super Admin')) {
            abort(404, 'Karyawan dengan role Super Admin tidak memiliki data absensi.');
        }

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $storeId = $request->query('store_id');

        // Determine date range: 1 full month of selected month/year (or custom selected dates)
        $monthCarbon = Carbon::create($year, $month, 1);
        $defaultStart = $monthCarbon->copy()->startOfMonth()->toDateString();
        $defaultEnd = $monthCarbon->copy()->endOfMonth()->toDateString();

        $startDate = $request->query('start_date', $defaultStart);
        $endDate = $request->query('end_date', $defaultEnd);

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->gt($end)) {
            $temp = $start;
            $start = $end;
            $end = $temp;
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        }

        // Fetch actual attendance records indexed by date string (Y-m-d)
        $attendances = Attendance::where('user_id', $user->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->with(['shift', 'store'])
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->date)->toDateString());

        $defaultShift = Shift::where('is_active', true)->first();
        $shifts = Shift::where('is_active', true)->orderBy('start_time')->get();
        $stores = Store::orderBy('name')->get();
        $primaryStore = $user->stores->first() ?? Store::first();

        // Fetch user schedules for the period
        $schedules = UserSchedule::where('user_id', $user->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->with('shift')
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->date)->toDateString());

        // Build complete date ledger row-by-row for the period
        $period = CarbonPeriod::create($start, $end);
        $records = [];

        foreach ($period as $dateObj) {
            $dateStr = $dateObj->toDateString();
            $att = $attendances->get($dateStr);
            $sched = $schedules->get($dateStr);
            $records[] = $this->buildAttendanceRecord($dateObj, $att, $sched, $defaultShift, $user, $primaryStore);
        }

        // Sort records descending by date for modern display
        usort($records, fn($a, $b) => strcmp($b['date_obj']->toDateString(), $a['date_obj']->toDateString()));

        $totalRecords = count($records);
        $totalPresent = collect($records)->filter(fn($r) => !empty($r['clock_in_time']))->count();
        $totalLate = collect($records)->filter(fn($r) => collect($r['badges'])->contains('code', 'LATE'))->count();
        $totalOff = collect($records)->filter(fn($r) => $r['is_off'])->count();

        $months = [
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
        $years = [now()->year - 1, now()->year, now()->year + 1];

        return view('commercial.attendances.show', compact(
            'user',
            'records',
            'totalRecords',
            'totalPresent',
            'totalLate',
            'totalOff',
            'startDate',
            'endDate',
            'month',
            'year',
            'storeId',
            'stores',
            'shifts',
            'months',
            'years'
        ));
    }

    /**
     * Export attendance report to CSV for Excel.
     */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('Attendance Export');

        $userId = $request->query('user_id');
        $storeId = $request->query('store_id');
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $monthCarbon = Carbon::create($year, $month, 1);
        $defaultStart = $monthCarbon->copy()->startOfMonth()->toDateString();
        $defaultEnd = $monthCarbon->copy()->endOfMonth()->toDateString();

        $startDate = $request->query('start_date', $defaultStart);
        $endDate = $request->query('end_date', $defaultEnd);

        $query = Attendance::with(['user', 'shift', 'store'])
            ->whereHas('user', function ($q) {
                $q->whereDoesntHave('roles', function ($rq) {
                    $rq->where('name', 'Super Admin');
                });
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->orderBy('date', 'desc');

        $attendances = $query->get();
        $fileName = 'rekap_kehadiran_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($attendances) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Microsoft Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($handle, [
                'ID Karyawan',
                'Nama Karyawan',
                'Email',
                'Jabatan',
                'Toko / Cabang',
                'Tanggal',
                'Shift',
                'Clock In',
                'Jarak Masuk (Meter)',
                'Clock Out',
                'Jarak Keluar (Meter)',
                'Total Jam Kerja',
                'Status',
                'Catatan'
            ]);

            foreach ($attendances as $att) {
                fputcsv($handle, [
                    $att->user?->employee_number ?? ('DPP.' . str_pad($att->user_id, 4, '0', STR_PAD_LEFT)),
                    $att->user?->name ?? '—',
                    $att->user?->email ?? '—',
                    $att->user?->position ?? '—',
                    $att->store?->name ?? '—',
                    Carbon::parse($att->date)->format('Y-m-d'),
                    $att->shift?->name ?? '—',
                    $att->clock_in ? Carbon::parse($att->clock_in)->format('H:i:s') : '—',
                    $att->clock_in_distance_meters !== null ? $att->clock_in_distance_meters . 'm' : '—',
                    $att->clock_out ? Carbon::parse($att->clock_out)->format('H:i:s') : '—',
                    $att->clock_out_distance_meters !== null ? $att->clock_out_distance_meters . 'm' : '—',
                    $att->work_hours ?? '—',
                    $att->status_label ?? ucfirst($att->status),
                    $att->note ?? '—'
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Import attendance records from CSV.
     */
    public function import(Request $request)
    {
        Gate::authorize('Attendance Import');

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ], [
            'file.required' => 'File CSV absensi wajib diunggah.',
            'file.mimes'    => 'Format file wajib berupa CSV (.csv).',
            'file.max'      => 'Ukuran file maksimal 5MB.',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $importedCount = 0;
        $rowNumber = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 2048, ',')) !== false) {
                $rowNumber++;
                if ($rowNumber === 1) {
                    // Skip header row
                    continue;
                }

                // Expected format: employee_number/name, date (Y-m-d), clock_in (H:i), clock_out (H:i), note
                $empIdentifier = trim($row[0] ?? '');
                $dateStr = trim($row[1] ?? '');
                $clockInTime = trim($row[2] ?? '');
                $clockOutTime = trim($row[3] ?? '');
                $note = trim($row[4] ?? '');

                if (empty($empIdentifier) || empty($dateStr)) {
                    continue;
                }

                $user = User::where('employee_number', $empIdentifier)
                    ->orWhere('name', 'like', "%{$empIdentifier}%")
                    ->orWhere('id', (int) $empIdentifier)
                    ->first();

                if (!$user || $user->hasRole('Super Admin')) {
                    continue;
                }

                $parsedDate = Carbon::parse($dateStr);
                $clockIn = !empty($clockInTime) ? Carbon::parse($dateStr . ' ' . $clockInTime) : null;
                $clockOut = !empty($clockOutTime) ? Carbon::parse($dateStr . ' ' . $clockOutTime) : null;
                $workMinutes = ($clockIn && $clockOut) ? $clockIn->diffInMinutes($clockOut) : 0;

                Attendance::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'date'    => $parsedDate->toDateString(),
                    ],
                    [
                        'store_id'     => $user->stores->first()?->id ?? session('active_store_id'),
                        'clock_in'     => $clockIn,
                        'clock_out'    => $clockOut,
                        'work_minutes' => $workMinutes,
                        'status'       => $clockIn ? 'present' : 'absent',
                        'note'         => $note ?: 'Impor Data Fingerprint',
                    ]
                );

                $importedCount++;
            }

            fclose($handle);
            DB::commit();

            return redirect()->back()->with('success', "Berhasil mengimpor {$importedCount} data absensi karyawan.");
        } catch (\Throwable $e) {
            DB::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }
            return redirect()->back()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    /**
     * Build attendance day record for show view.
     *
     * @return array<string, mixed>
     */
    private function buildAttendanceRecord(
        Carbon $dateObj,
        ?Attendance $att,
        ?UserSchedule $schedule,
        ?Shift $defaultShift,
        User $user,
        ?Store $primaryStore
    ): array {
        $dateStr = $dateObj->toDateString();
        $isWeekend = ($dateObj->dayOfWeek === 0);
        $isScheduledOff = $schedule ? $schedule->is_off : false;
        $scheduledShift = $schedule?->shift;

        if ($att) {
            $shiftObj = $att->shift ?? $scheduledShift ?? $defaultShift;
            $shiftLabel = $isScheduledOff 
                ? 'Lembur Hari Libur' 
                : ($shiftObj ? Carbon::parse($shiftObj->start_time)->format('H:i') . ' - ' . Carbon::parse($shiftObj->end_time)->format('H:i') : '08:00 - 17:00');

            // Determine badge tags (e.g. OFF OVT, PRS LATE, PRS, ABS)
            $badges = [];
            $lateMinutes = (int) ($att->late_minutes ?? 0);
            $lateDuration = $att->late_duration;
            $lateDurationLong = $att->late_duration_long;

            if ($att->clock_in) {
                if ($isScheduledOff) {
                    $badges[] = ['code' => 'OFF', 'label' => 'Jadwal Libur', 'color' => 'slate'];
                    $badges[] = ['code' => 'OVT', 'label' => 'Lembur Hari Libur', 'color' => 'indigo'];
                } else {
                    $badges[] = ['code' => 'PRS', 'label' => 'Hadir', 'color' => 'emerald'];

                    if ($shiftObj) {
                        $shiftStartTime = Carbon::parse($dateStr . ' ' . $shiftObj->start_time);
                        $clockInTime = Carbon::parse($att->clock_in);
                        $tolerance = (int) ($user->payrollSetting?->late_tolerance_minutes ?? $shiftObj->late_tolerance_minutes ?? 15);
                        $deadline = $shiftStartTime->copy()->addMinutes($tolerance);
                        
                        if ($clockInTime->lt($shiftStartTime->copy()->subMinutes(5))) {
                            $badges[] = ['code' => 'EAI', 'label' => 'Early In', 'color' => 'sky'];
                        } elseif ($clockInTime->gt($deadline) || $att->status === 'late' || $lateMinutes > 0) {
                            if (!$lateMinutes && $clockInTime->gt($shiftStartTime)) {
                                $lateMinutes = $shiftStartTime->diffInMinutes($clockInTime);
                            }
                            if (!$lateDuration && $lateMinutes > 0) {
                                $lh = intdiv($lateMinutes, 60);
                                $lm = $lateMinutes % 60;
                                $lateDuration = ($lh > 0 && $lm > 0) ? "{$lh}j {$lm}m" : ($lh > 0 ? "{$lh} Jam" : "{$lm} Menit");
                                $lateDurationLong = ($lh > 0 && $lm > 0) ? "{$lh} jam {$lm} menit" : ($lh > 0 ? "{$lh} jam" : "{$lm} menit");
                            }
                            $lateLabel = $lateDuration ? "Terlambat ({$lateDuration})" : 'Terlambat';
                            $badges[] = ['code' => 'LATE', 'label' => $lateLabel, 'color' => 'amber'];
                        }
                    } elseif ($att->status === 'late' || $lateMinutes > 0) {
                        $lateLabel = $lateDuration ? "Terlambat ({$lateDuration})" : 'Terlambat';
                        $badges[] = ['code' => 'LATE', 'label' => $lateLabel, 'color' => 'amber'];
                    }

                    // Lembur Reguler (OVT): Hanya diberikan jika selisih clock out melebihi end shift minimal 1 jam (60 menit)
                    if ($att->overtime_minutes >= 60) {
                        $ovtHours = intdiv($att->overtime_minutes, 60);
                        $badges[] = ['code' => 'OVT', 'label' => "Lembur ({$ovtHours} Jam)", 'color' => 'indigo'];
                    }
                }
            } else {
                $badges[] = ['code' => 'ABS', 'label' => 'Tidak Hadir', 'color' => 'rose'];
            }

            return [
                'date_obj'         => $dateObj,
                'date_formatted'   => $dateObj->isoFormat('D MMM Y'),
                'day_name'         => $dateObj->isoFormat('dddd'),
                'is_off'           => false,
                'shift_name'       => $isScheduledOff ? 'Lembur Hari Libur (OFF)' : ($shiftObj?->name ?? 'Shift Regular'),
                'shift_time'       => $shiftLabel,
                'clock_in_time'    => $att->clock_in ? Carbon::parse($att->clock_in)->format('H:i:s') : null,
                'clock_out_time'   => $att->clock_out ? Carbon::parse($att->clock_out)->format('H:i:s') : null,
                'store_name'       => $att->store?->name ?? $primaryStore?->name ?? 'Z1 Store',
                'badges'           => $badges,
                'note'             => $att->note,
                'attendance'       => $att,
                'has_attendance'   => true,
                'edit_data'        => [
                    'date'               => $dateStr,
                    'date_formatted'     => $dateObj->isoFormat('dddd, D MMMM Y'),
                    'shift_id'           => $shiftObj?->id,
                    'clock_in'           => $att->clock_in ? Carbon::parse($att->clock_in)->format('H:i') : '',
                    'clock_out'          => $att->clock_out ? Carbon::parse($att->clock_out)->format('H:i') : '',
                    'clock_out_next_day' => ($att->clock_in && $att->clock_out && Carbon::parse($att->clock_out)->toDateString() > Carbon::parse($att->clock_in)->toDateString()),
                    'status'             => $att->status ?? ($isScheduledOff ? 'holiday' : 'present'),
                    'note'               => $att->note ?? '',
                    'user_name'          => $user->name,
                    'store_id'           => $att->store_id ?? $primaryStore?->id,
                ],
                'detail_data'      => [
                    'date'               => $dateObj->isoFormat('dddd, D MMMM Y'),
                    'date_short'         => $dateObj->isoFormat('D MMM Y'),
                    'shift'              => $shiftObj?->name ?? 'Shift Regular',
                    'shift_time'         => $shiftLabel . ' WIB',
                    'clock_in'           => $att->clock_in ? Carbon::parse($att->clock_in)->format('H:i') . ' WIB' : '—',
                    'clock_out'          => $att->clock_out ? Carbon::parse($att->clock_out)->format('H:i') . ' WIB' : '—',
                    'clock_in_photo'     => $att->clock_in_photo ? asset('storage/' . $att->clock_in_photo) : null,
                    'clock_out_photo'    => $att->clock_out_photo ? asset('storage/' . $att->clock_out_photo) : null,
                    'clock_in_distance'  => $att->clock_in_distance_meters,
                    'clock_out_distance' => $att->clock_out_distance_meters,
                    'clock_in_lat'       => $att->clock_in_lat,
                    'clock_in_lng'       => $att->clock_in_lng,
                    'status_label'       => ($att->status === 'late' || $lateMinutes > 0) ? ($lateDuration ? "Terlambat ({$lateDuration})" : ($att->status_label ?? 'Terlambat')) : $att->status_label,
                    'status'             => ($lateMinutes > 0) ? 'late' : $att->status,
                    'late_minutes'       => $lateMinutes,
                    'late_duration'      => $lateDuration,
                    'late_duration_long' => $lateDurationLong,
                    'work_hours'         => $att->work_hours,
                    'overtime_minutes'   => (int) ($att->overtime_minutes ?? 0),
                    'overtime_hours'     => ($att->overtime_minutes >= 60) ? intdiv($att->overtime_minutes, 60) : 0,
                    'note'               => $att->note,
                    'user_name'          => $user->name,
                    'user_photo'         => $user->photo ? asset('storage/' . $user->photo) : null,
                    'store_name'         => $att->store?->name ?? $primaryStore?->name ?? 'Toko',
                    'edit_data'          => [
                        'date'               => $dateStr,
                        'date_formatted'     => $dateObj->isoFormat('dddd, D MMMM Y'),
                        'shift_id'           => $shiftObj?->id,
                        'clock_in'           => $att->clock_in ? Carbon::parse($att->clock_in)->format('H:i') : '',
                        'clock_out'          => $att->clock_out ? Carbon::parse($att->clock_out)->format('H:i') : '',
                        'clock_out_next_day' => ($att->clock_in && $att->clock_out && Carbon::parse($att->clock_out)->toDateString() > Carbon::parse($att->clock_in)->toDateString()),
                        'status'             => $att->status ?? ($isScheduledOff ? 'holiday' : 'present'),
                        'note'               => $att->note ?? '',
                        'user_name'          => $user->name,
                        'store_id'           => $att->store_id ?? $primaryStore?->id,
                    ],
                ]
            ];
        }

        // No attendance record for this date
        $isOffDay = $schedule ? $schedule->is_off : $isWeekend;
        $shiftObj = $scheduledShift ?? $defaultShift;
        $shiftLabel = $isOffDay ? 'OFF' : ($shiftObj ? Carbon::parse($shiftObj->start_time)->format('H:i') . ' - ' . Carbon::parse($shiftObj->end_time)->format('H:i') : '08:00 - 17:00');

        $emptyEditData = [
            'date'               => $dateStr,
            'date_formatted'     => $dateObj->isoFormat('dddd, D MMMM Y'),
            'shift_id'           => $shiftObj?->id,
            'clock_in'           => '',
            'clock_out'          => '',
            'clock_out_next_day' => false,
            'status'             => $isOffDay ? 'holiday' : 'present',
            'note'               => '',
            'user_name'          => $user->name,
            'store_id'           => $primaryStore?->id,
        ];

        return [
            'date_obj'         => $dateObj,
            'date_formatted'   => $dateObj->isoFormat('D MMM Y'),
            'day_name'         => $dateObj->isoFormat('dddd'),
            'is_off'           => $isOffDay,
            'shift_name'       => $isOffDay ? 'OFF' : ($shiftObj?->name ?? 'Shift Regular'),
            'shift_time'       => $shiftLabel,
            'clock_in_time'    => null,
            'clock_out_time'   => null,
            'store_name'       => $primaryStore?->name ?? 'Z1 Store',
            'badges'           => $isOffDay 
                ? [['code' => 'OFF', 'label' => 'Libur', 'color' => 'slate']]
                : [['code' => 'ABS', 'label' => 'Alpha', 'color' => 'rose']],
            'note'             => null,
            'attendance'       => null,
            'has_attendance'   => false,
            'edit_data'        => $emptyEditData,
            'detail_data'      => [
                'date'               => $dateObj->isoFormat('dddd, D MMMM Y'),
                'date_short'         => $dateObj->isoFormat('D MMM Y'),
                'shift'              => $isOffDay ? 'Hari Libur (OFF)' : ($defaultShift?->name ?? 'Shift Regular'),
                'shift_time'         => $isOffDay ? 'Libur' : '08:00 - 17:00 WIB',
                'clock_in'           => '—',
                'clock_out'          => '—',
                'clock_in_photo'     => null,
                'clock_out_photo'    => null,
                'clock_in_distance'  => null,
                'clock_out_distance' => null,
                'clock_in_lat'       => null,
                'clock_in_lng'       => null,
                'status_label'       => $isOffDay ? 'Libur (OFF)' : 'Tidak Hadir',
                'status'             => $isOffDay ? 'holiday' : 'absent',
                'work_hours'         => '—',
                'note'               => $isOffDay ? 'Jadwal Libur Rutin' : 'Tidak ada rekaman absensi',
                'user_name'          => $user->name,
                'user_photo'         => $user->photo ? asset('storage/' . $user->photo) : null,
                'store_name'         => $primaryStore?->name ?? 'Toko',
                'edit_data'          => $emptyEditData,
            ]
        ];
    }

    /**
     * Save/Update individual employee attendance record (Admin manual correction).
     */
    public function saveRecord(Request $request, User $user)
    {
        $authUser = $request->user();
        $canEdit = $authUser && (
            $authUser->hasRole('Super Admin')
            || $authUser->hasRole('Manager')
            || Gate::allows('Attendance List')
            || Gate::allows('Attendance Edit')
        );

        if (!$canEdit) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk mengubah data absensi.');
        }

        try {
            $request->validate([
                'date'               => 'required|date',
                'shift_id'           => 'nullable|exists:shifts,id',
                'clock_in_time'      => 'nullable|string',
                'clock_out_time'     => 'nullable|string',
                'clock_out_next_day' => 'nullable|boolean',
                'status'             => 'required|string',
                'note'               => 'nullable|string|max:500',
                'store_id'           => 'nullable|exists:stores,id',
            ]);

            $dateStr = Carbon::parse($request->input('date'))->toDateString();
            $clockInTime = trim($request->input('clock_in_time') ?? '');
            $clockOutTime = trim($request->input('clock_out_time') ?? '');
            $shiftId = $request->input('shift_id');
            $shift = $shiftId ? Shift::find($shiftId) : null;
            $status = $request->input('status', 'present');

            $clockIn = null;
            if (!empty($clockInTime)) {
                $clockIn = Carbon::parse($dateStr . ' ' . $clockInTime);
            }

            $clockOut = null;
            if (!empty($clockOutTime)) {
                $clockOut = Carbon::parse($dateStr . ' ' . $clockOutTime);
                $isOvernight = $request->boolean('clock_out_next_day')
                    || ($clockIn && $clockOut->lte($clockIn))
                    || ($shift && $shift->is_overnight);

                if ($isOvernight) {
                    $clockOut->addDay();
                }
            }

            $workMinutes = 0;
            if ($clockIn && $clockOut) {
                $workMinutes = $clockIn->diffInMinutes($clockOut);
            }

            $lateMinutes = 0;
            if ($clockIn && $shift) {
                $shiftStartTime = Carbon::parse($dateStr . ' ' . $shift->start_time);
                $tolerance = (int) ($user->payrollSetting?->late_tolerance_minutes ?? $shift->late_tolerance_minutes ?? 15);
                $deadline = $shiftStartTime->copy()->addMinutes($tolerance);

                if ($clockIn->gt($deadline)) {
                    $lateMinutes = $shiftStartTime->diffInMinutes($clockIn);
                    if ($status === 'present') {
                        $status = 'late';
                    }
                }
            }

            $overtimeMinutes = 0;
            if ($clockIn && $clockOut && $shift) {
                $shiftStartTime = Carbon::parse($dateStr . ' ' . $shift->start_time);
                $shiftEndTime = Carbon::parse($dateStr . ' ' . $shift->end_time);
                if ($shift->is_overnight || $shiftEndTime->lte($shiftStartTime)) {
                    $shiftEndTime->addDay();
                }

                $schedule = UserSchedule::where('user_id', $user->id)->whereDate('date', $dateStr)->first();
                $isOffSchedule = ($schedule && $schedule->is_off);

                if ($isOffSchedule) {
                    $overtimeMinutes = $workMinutes;
                } elseif ($clockOut->gt($shiftEndTime)) {
                    $diffMins = $shiftEndTime->diffInMinutes($clockOut);
                    // Lembur reguler: minimal selisih 60 menit, dihitung kelipatan 1 jam penuh (contoh: selisih 75 menit -> 1 jam / 60 menit)
                    $overtimeMinutes = $diffMins >= 60 ? (intdiv($diffMins, 60) * 60) : 0;
                }
            }

            $storeId = $request->input('store_id');
            if (empty($storeId)) {
                $storeId = $user->stores->first()?->id ?? session('active_store_id') ?? Store::first()?->id;
            }

            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $dateStr)
                ->first();

            if (!$attendance) {
                $attendance = new Attendance();
                $attendance->user_id = $user->id;
                $attendance->date = $dateStr;
            }

            $attendance->store_id         = $storeId;
            $attendance->shift_id         = $shift?->id;
            $attendance->clock_in         = $clockIn;
            $attendance->clock_out        = $clockOut;
            $attendance->work_minutes     = $workMinutes;
            $attendance->late_minutes     = $lateMinutes;
            $attendance->overtime_minutes = $overtimeMinutes;
            $attendance->status           = $status;
            $attendance->note             = $request->input('note');
            $attendance->save();

            $formattedDate = Carbon::parse($dateStr)->isoFormat('D MMMM Y');
            return redirect()->back()->with('success', "Data absensi {$user->name} untuk tanggal {$formattedDate} berhasil disimpan.");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to save attendance record for user {$user->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Gagal menyimpan absensi: ' . $e->getMessage());
        }
    }
}
