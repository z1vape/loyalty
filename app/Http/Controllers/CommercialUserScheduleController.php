<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftPattern;
use App\Models\Store;
use App\Models\User;
use App\Models\UserSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CommercialUserScheduleController extends Controller
{
    /**
     * Display the employee roster matrix grid for the selected month and store.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): \Illuminate\View\View
    {
        Gate::authorize('User Schedule List');

        $search = $request->filled('search') ? (string) $request->query('search') : null;
        $storeId = $request->filled('store_id') ? (string) $request->query('store_id') : null;
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        $monthCarbon = Carbon::create($year, $month, 1);
        $startDate = $monthCarbon->copy()->startOfMonth();
        $endDate = $monthCarbon->copy()->endOfMonth();

        /** @var \Illuminate\Support\Collection<int, User> $users */
        $users = $this->getEmployeesForSchedule($search, $storeId);
        $dates = $this->buildMonthDates($startDate, $endDate);
        /** @var \Illuminate\Support\Collection<string, UserSchedule> $schedules */
        $schedules = $this->getMonthSchedules($users->pluck('id')->all(), $startDate, $endDate);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Shift> $shifts */
        $shifts = Shift::where('is_active', true)->orderBy('start_time')->get();
        /** @var \Illuminate\Database\Eloquent\Collection<int, ShiftPattern> $patterns */
        $patterns = ShiftPattern::where('is_active', true)->with('items.shift')->get();
        /** @var \Illuminate\Database\Eloquent\Collection<int, Store> $stores */
        $stores = Store::orderBy('name')->get();

        $months = $this->getMonthOptions();
        $years = [now()->year - 1, now()->year, now()->year + 1];

        return view('commercial.schedules.index', compact(
            'users',
            'dates',
            'schedules',
            'shifts',
            'patterns',
            'stores',
            'search',
            'storeId',
            'month',
            'year',
            'startDate',
            'endDate',
            'months',
            'years'
        ));
    }

    /**
     * Build dates array for the active month.
     */
    private function buildMonthDates(Carbon $startDate, Carbon $endDate): array
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = [];
        foreach ($period as $dt) {
            $dates[] = [
                'date_str'       => $dt->toDateString(),
                'day_num'        => $dt->day,
                'day_name'       => $dt->locale('id')->isoFormat('ddd'),
                'formatted_date' => $dt->locale('id')->isoFormat('dddd, D MMMM YYYY'),
                'is_weekend'     => ($dt->dayOfWeek === 0),
                'is_today'       => $dt->isToday(),
            ];
        }
        return $dates;
    }

    /**
     * Query eligible employees excluding Super Admin.
     */
    private function getEmployeesForSchedule(?string $search, ?string $storeId): \Illuminate\Support\Collection
    {
        return User::query()
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'Super Admin'))
            ->with(['stores'])
            ->when(!empty($search), function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            })
            ->when(!empty($storeId), function ($q) use ($storeId) {
                $q->whereHas('stores', fn($sq) => $sq->where('stores.id', $storeId));
            })
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Fetch user schedules indexed by user_id and date.
     */
    private function getMonthSchedules(array $userIds, Carbon $startDate, Carbon $endDate): \Illuminate\Support\Collection
    {
        return UserSchedule::with('shift')
            ->whereIn('user_id', $userIds)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy(fn($item) => $item->user_id . '_' . Carbon::parse($item->date)->toDateString());
    }

    /**
     * Get Indonesian month names.
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

    /**
     * Store or update a single day schedule for a user.
     */
    public function store(Request $request)
    {
        Gate::authorize('User Schedule Manage');

        // Normalize if user selected radio "OFF"
        $rawShiftId = $request->input('shift_id');
        if ($rawShiftId === 'OFF' || $rawShiftId === 'off') {
            $request->merge([
                'shift_id' => null,
                'is_off'   => true,
            ]);
        } elseif ($request->boolean('is_off')) {
            $request->merge([
                'shift_id' => null,
            ]);
        }

        $validated = $request->validate([
            'user_id'  => 'required|exists:users,id',
            'date'     => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'is_off'   => 'nullable|boolean',
            'note'     => 'nullable|string|max:255',
            'store_id' => 'nullable|exists:stores,id',
        ]);

        $isOff = $request->boolean('is_off');
        $shiftId = $isOff ? null : ($validated['shift_id'] ?? null);

        // If neither shift nor is_off is set, delete the schedule to reset
        if (!$isOff && empty($shiftId)) {
            UserSchedule::where('user_id', $validated['user_id'])
                ->where('date', $validated['date'])
                ->delete();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'action' => 'reset']);
            }
            return redirect()->back()->with('success', 'Jadwal direset.');
        }

        $user = User::find($validated['user_id']);
        $storeId = $validated['store_id'] ?? $user?->stores->first()?->id ?? null;
        $targetDate = Carbon::parse($validated['date'])->toDateString();

        $schedule = UserSchedule::where('user_id', $validated['user_id'])
            ->where('date', $targetDate)
            ->first();

        if ($schedule) {
            $schedule->update([
                'store_id'   => $storeId,
                'shift_id'   => $shiftId,
                'is_off'     => $isOff,
                'note'       => $validated['note'] ?? null,
                'created_by' => auth()->id(),
            ]);
        } else {
            $schedule = UserSchedule::create([
                'user_id'    => $validated['user_id'],
                'date'       => $targetDate,
                'store_id'   => $storeId,
                'shift_id'   => $shiftId,
                'is_off'     => $isOff,
                'note'       => $validated['note'] ?? null,
                'created_by' => auth()->id(),
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'schedule' => $schedule->load('shift')
            ]);
        }

        return redirect()->back()->with('success', 'Jadwal kerja berhasil diperbarui.');
    }

    /**
     * Bulk generate user schedules based on a ShiftPattern.
     */
    public function bulkGenerate(Request $request)
    {
        Gate::authorize('User Schedule Manage');

        $validated = $request->validate([
            'user_ids'         => 'required|array|min:1',
            'user_ids.*'       => 'exists:users,id',
            'shift_pattern_id' => 'required|exists:shift_patterns,id',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
            'start_day_number' => 'nullable|integer|min:1',
        ]);

        $pattern = ShiftPattern::with('items')->findOrFail($validated['shift_pattern_id']);
        $itemsByDay = $pattern->items->keyBy('day_number');
        $cycleDays = max(1, $pattern->cycle_days);
        $startDayNum = (int) ($validated['start_day_number'] ?? 1);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $period = CarbonPeriod::create($start, $end);

        $users = User::whereIn('id', $validated['user_ids'])->with('stores')->get();
        $generatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($users as $user) {
                $primaryStoreId = $user->stores->first()?->id ?? $pattern->store_id;
                $dayIndex = $startDayNum - 1;

                foreach ($period as $dateObj) {
                    $dayModulo = ($dayIndex % $cycleDays) + 1;
                    $item = $itemsByDay->get($dayModulo);

                    $isOff = $item ? $item->is_off : false;
                    $shiftId = ($item && !$isOff) ? $item->shift_id : null;

                    $targetDate = $dateObj->toDateString();
                    $existing = UserSchedule::where('user_id', $user->id)
                        ->where('date', $targetDate)
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'store_id'   => $primaryStoreId,
                            'shift_id'   => $shiftId,
                            'is_off'     => $isOff,
                            'note'       => "Auto: {$pattern->name} (Hari #{$dayModulo})",
                            'created_by' => auth()->id(),
                        ]);
                    } else {
                        UserSchedule::create([
                            'user_id'    => $user->id,
                            'date'       => $targetDate,
                            'store_id'   => $primaryStoreId,
                            'shift_id'   => $shiftId,
                            'is_off'     => $isOff,
                            'note'       => "Auto: {$pattern->name} (Hari #{$dayModulo})",
                            'created_by' => auth()->id(),
                        ]);
                    }

                    $generatedCount++;
                    $dayIndex++;
                }
            }

            DB::commit();

            return redirect()->route('commercial.schedules.index', [
                'month'    => $start->month,
                'year'     => $start->year,
                'store_id' => $request->query('store_id'),
            ])->with('success', "Berhasil menjadwalkan {$generatedCount} jadwal kerja untuk " . count($users) . " karyawan menggunakan pola \"{$pattern->name}\".");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menerapkan pola jadwal: ' . $e->getMessage());
        }
    }

    /**
     * Delete a schedule for a specific user and date.
     */
    public function destroy(Request $request)
    {
        Gate::authorize('User Schedule Manage');

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date'    => 'required|date',
        ]);

        UserSchedule::where('user_id', $validated['user_id'])
            ->where('date', $validated['date'])
            ->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Jadwal berhasil dihapus.');
    }
}
