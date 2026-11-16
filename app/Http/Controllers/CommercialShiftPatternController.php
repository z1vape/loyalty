<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftPattern;
use App\Models\ShiftPatternItem;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CommercialShiftPatternController extends Controller
{
    /**
     * Display a listing of shift patterns.
     */
    public function index(Request $request)
    {
        Gate::authorize('Shift Pattern List');

        $search = $request->query('search');
        $storeId = $request->query('store_id');

        $patterns = ShiftPattern::with(['store', 'items.shift'])
            ->when(!empty($search), function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            })
            ->when(!empty($storeId), function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->paginate(10)
            ->withQueryString();

        $shifts = Shift::where('is_active', true)->orderBy('start_time')->get();
        $stores = Store::orderBy('name')->get();

        return view('commercial.shift_patterns.index', compact(
            'patterns',
            'shifts',
            'stores',
            'search',
            'storeId'
        ));
    }

    /**
     * Store a newly created shift pattern.
     */
    public function store(Request $request)
    {
        Gate::authorize('Shift Pattern Create');

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'store_id'    => 'nullable|exists:stores,id',
            'cycle_days'  => 'required|integer|min:2|max:31',
            'days'        => 'required|array|min:2',
            'days.*.shift_id' => 'nullable',
            'days.*.is_off'   => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $pattern = ShiftPattern::create([
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
                'store_id'    => $validated['store_id'] ?? null,
                'cycle_days'  => $validated['cycle_days'],
                'is_active'   => true,
            ]);

            for ($dayNum = 1; $dayNum <= $validated['cycle_days']; $dayNum++) {
                $dayData = $validated['days'][$dayNum] ?? null;
                $isOff = !empty($dayData['is_off']) || empty($dayData['shift_id']);
                $shiftId = $isOff ? null : (int) $dayData['shift_id'];

                ShiftPatternItem::create([
                    'shift_pattern_id' => $pattern->id,
                    'day_number'       => $dayNum,
                    'shift_id'         => $shiftId,
                    'is_off'           => $isOff,
                ]);
            }

            DB::commit();

            return redirect()->route('commercial.shift-patterns.index')
                ->with('success', "Pola Shift \"{$pattern->name}\" ({$pattern->cycle_days} hari) berhasil disimpan.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan pola shift: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update the specified shift pattern.
     */
    public function update(Request $request, ShiftPattern $shiftPattern)
    {
        Gate::authorize('Shift Pattern Edit');

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'store_id'    => 'nullable|exists:stores,id',
            'cycle_days'  => 'required|integer|min:2|max:31',
            'days'        => 'required|array|min:2',
            'days.*.shift_id' => 'nullable',
            'days.*.is_off'   => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $shiftPattern->update([
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
                'store_id'    => $validated['store_id'] ?? null,
                'cycle_days'  => $validated['cycle_days'],
            ]);

            // Recreate items for the cycle
            $shiftPattern->items()->delete();

            for ($dayNum = 1; $dayNum <= $validated['cycle_days']; $dayNum++) {
                $dayData = $validated['days'][$dayNum] ?? null;
                $isOff = !empty($dayData['is_off']) || empty($dayData['shift_id']);
                $shiftId = $isOff ? null : (int) $dayData['shift_id'];

                ShiftPatternItem::create([
                    'shift_pattern_id' => $shiftPattern->id,
                    'day_number'       => $dayNum,
                    'shift_id'         => $shiftId,
                    'is_off'           => $isOff,
                ]);
            }

            DB::commit();

            return redirect()->route('commercial.shift-patterns.index')
                ->with('success', "Pola Shift \"{$shiftPattern->name}\" berhasil diperbarui.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui pola shift: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified shift pattern.
     */
    public function destroy(ShiftPattern $shiftPattern)
    {
        Gate::authorize('Shift Pattern Destroy');

        $name = $shiftPattern->name;
        $shiftPattern->items()->delete();
        $shiftPattern->delete();

        return redirect()->route('commercial.shift-patterns.index')
            ->with('success', "Pola Shift \"{$name}\" berhasil dihapus.");
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(ShiftPattern $shiftPattern)
    {
        Gate::authorize('Shift Pattern Edit');

        $shiftPattern->update(['is_active' => !$shiftPattern->is_active]);

        $statusText = $shiftPattern->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Pola Shift \"{$shiftPattern->name}\" berhasil {$statusText}.");
    }
}
