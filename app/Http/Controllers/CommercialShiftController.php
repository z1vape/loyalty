<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommercialShiftController extends Controller
{
    /**
     * Display a listing of master shifts.
     */
    public function index(Request $request)
    {
        Gate::authorize('Shift List');

        $search = $request->query('search');
        $storeId = $request->query('store_id');

        $query = Shift::with('store')
            ->when(!empty($search), function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                       ->orWhere('code', 'like', "%{$search}%")
                       ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(!empty($storeId), function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->orderBy('is_active', 'desc')
            ->orderBy('start_time', 'asc');

        $shifts = $query->paginate(15)->withQueryString();
        $stores = Store::orderBy('name')->get();
        $totalShifts = Shift::count();
        $activeShifts = Shift::where('is_active', true)->count();

        return view('commercial.shifts.index', compact(
            'shifts',
            'stores',
            'search',
            'storeId',
            'totalShifts',
            'activeShifts'
        ));
    }

    /**
     * Store a newly created shift in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('Shift Create');

        $validated = $request->validate([
            'name'                        => 'required|string|max:100',
            'code'                        => 'nullable|string|max:20',
            'store_id'                    => 'nullable|exists:stores,id',
            'start_time'                  => 'required|date_format:H:i',
            'end_time'                    => 'required|date_format:H:i',
            'late_tolerance_minutes'      => 'required|integer|min:0|max:180',
            'early_clock_in_minutes'      => 'nullable|integer|min:0|max:1440',
            'max_clock_out_delay_minutes' => 'nullable|integer|min:0|max:1440',
            'is_overnight'                => 'nullable|boolean',
            'color'                       => 'nullable|string|max:20',
            'description'                 => 'nullable|string|max:255',
            'is_active'                   => 'nullable|boolean',
        ]);

        $validated['early_clock_in_minutes'] = $request->filled('early_clock_in_minutes') ? (int) $request->input('early_clock_in_minutes') : 120;
        $validated['max_clock_out_delay_minutes'] = $request->filled('max_clock_out_delay_minutes') ? (int) $request->input('max_clock_out_delay_minutes') : 240;
        $validated['is_overnight'] = $request->boolean('is_overnight');
        $validated['is_active'] = $request->boolean('is_active', true);
        if (empty($validated['color'])) {
            $validated['color'] = '#006c49';
        }
        if (empty($validated['code'])) {
            $validated['code'] = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $validated['name']), 0, 5));
        }

        Shift::create($validated);

        return redirect()->route('commercial.shifts.index')
            ->with('success', 'Master Shift "' . $validated['name'] . '" berhasil ditambahkan.');
    }

    /**
     * Update the specified shift in storage.
     */
    public function update(Request $request, Shift $shift)
    {
        Gate::authorize('Shift Edit');

        $validated = $request->validate([
            'name'                        => 'required|string|max:100',
            'code'                        => 'nullable|string|max:20',
            'store_id'                    => 'nullable|exists:stores,id',
            'start_time'                  => 'required',
            'end_time'                    => 'required',
            'late_tolerance_minutes'      => 'required|integer|min:0|max:180',
            'early_clock_in_minutes'      => 'nullable|integer|min:0|max:1440',
            'max_clock_out_delay_minutes' => 'nullable|integer|min:0|max:1440',
            'is_overnight'                => 'nullable|boolean',
            'color'                       => 'nullable|string|max:20',
            'description'                 => 'nullable|string|max:255',
            'is_active'                   => 'nullable|boolean',
        ]);

        // Normalize time format to H:i:s or H:i
        $validated['start_time'] = substr($validated['start_time'], 0, 5);
        $validated['end_time'] = substr($validated['end_time'], 0, 5);
        $validated['early_clock_in_minutes'] = $request->filled('early_clock_in_minutes') ? (int) $request->input('early_clock_in_minutes') : 120;
        $validated['max_clock_out_delay_minutes'] = $request->filled('max_clock_out_delay_minutes') ? (int) $request->input('max_clock_out_delay_minutes') : 240;
        $validated['is_overnight'] = $request->boolean('is_overnight');
        $validated['is_active'] = $request->boolean('is_active');
        if (empty($validated['color'])) {
            $validated['color'] = $shift->color ?: '#006c49';
        }

        $shift->update($validated);

        return redirect()->route('commercial.shifts.index')
            ->with('success', 'Shift "' . $shift->name . '" berhasil diperbarui.');
    }

    /**
     * Remove or deactivate the specified shift.
     */
    public function destroy(Shift $shift)
    {
        Gate::authorize('Shift Destroy');

        $hasAttendances = $shift->attendances()->exists();
        $hasSchedules = $shift->schedules()->exists();

        if ($hasAttendances || $hasSchedules) {
            $shift->update(['is_active' => false]);
            return redirect()->route('commercial.shifts.index')
                ->with('info', "Shift \"{$shift->name}\" memiliki riwayat absensi/jadwal sehingga dinonaktifkan (arsip), bukan dihapus permanen.");
        }

        $name = $shift->name;
        $shift->delete();

        return redirect()->route('commercial.shifts.index')
            ->with('success', "Shift \"{$name}\" berhasil dihapus permanen.");
    }

    /**
     * Toggle shift active status.
     */
    public function toggleStatus(Shift $shift)
    {
        Gate::authorize('Shift Edit');

        $shift->update(['is_active' => !$shift->is_active]);

        $statusText = $shift->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Shift \"{$shift->name}\" berhasil {$statusText}.");
    }
}
