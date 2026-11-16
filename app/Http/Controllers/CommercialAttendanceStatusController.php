<?php

namespace App\Http\Controllers;

use App\Models\AttendanceStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommercialAttendanceStatusController extends Controller
{
    /**
     * Display a listing of attendance statuses.
     */
    public function index(Request $request)
    {
        Gate::authorize('Attendance Status List');

        $search = $request->query('search');

        $statuses = AttendanceStatus::query()
            ->when(!empty($search), function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            })
            ->orderBy('is_system', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        $types = [
            'present'  => 'Hadir / Masuk Kerja',
            'late'     => 'Keterlambatan',
            'leave'    => 'Izin / Cuti',
            'sick'     => 'Sakit',
            'holiday'  => 'Hari Libur / OFF',
            'absent'   => 'Tidak Hadir / Alpha',
            'overtime' => 'Lembur',
        ];

        $colors = [
            'emerald' => 'Hijau Zamrud (Emerald)',
            'amber'   => 'Kuning / Oranye (Amber)',
            'sky'     => 'Biru Langit (Sky)',
            'indigo'  => 'Indigo / Biru Tua',
            'purple'  => 'Ungu (Purple)',
            'slate'   => 'Abu-abu (Slate)',
            'rose'    => 'Merah Muda / Rose',
        ];

        return view('commercial.attendance_statuses.index', compact('statuses', 'types', 'colors', 'search'));
    }

    /**
     * Store a newly created attendance status.
     */
    public function store(Request $request)
    {
        Gate::authorize('Attendance Status Create');

        $validated = $request->validate([
            'code'                => 'required|string|max:20|unique:attendance_statuses,code',
            'name'                => 'required|string|max:100',
            'type'                => 'required|in:present,late,leave,sick,holiday,absent,overtime',
            'badge_color'         => 'required|string|max:30',
            'counts_as_workday'   => 'nullable|boolean',
            'requires_attachment' => 'nullable|boolean',
            'is_active'           => 'nullable|boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['counts_as_workday'] = $request->boolean('counts_as_workday');
        $validated['requires_attachment'] = $request->boolean('requires_attachment');
        $validated['is_system'] = false;
        $validated['is_active'] = $request->boolean('is_active', true);

        AttendanceStatus::create($validated);

        return redirect()->route('commercial.attendance-statuses.index')
            ->with('success', "Status Kehadiran [{$validated['code']}] \"{$validated['name']}\" berhasil dibuat.");
    }

    /**
     * Update the specified attendance status.
     */
    public function update(Request $request, AttendanceStatus $attendanceStatus)
    {
        Gate::authorize('Attendance Status Edit');

        $validated = $request->validate([
            'code'                => 'required|string|max:20|unique:attendance_statuses,code,' . $attendanceStatus->id,
            'name'                => 'required|string|max:100',
            'type'                => 'required|in:present,late,leave,sick,holiday,absent,overtime',
            'badge_color'         => 'required|string|max:30',
            'counts_as_workday'   => 'nullable|boolean',
            'requires_attachment' => 'nullable|boolean',
            'is_active'           => 'nullable|boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['counts_as_workday'] = $request->boolean('counts_as_workday');
        $validated['requires_attachment'] = $request->boolean('requires_attachment');
        $validated['is_active'] = $request->boolean('is_active');

        // Prevent changing code of system status if system requires it
        if ($attendanceStatus->is_system && $attendanceStatus->code !== $validated['code']) {
            $validated['code'] = $attendanceStatus->code; // preserve system code
        }

        $attendanceStatus->update($validated);

        return redirect()->route('commercial.attendance-statuses.index')
            ->with('success', "Status Kehadiran [{$attendanceStatus->code}] berhasil diperbarui.");
    }

    /**
     * Remove the specified attendance status.
     */
    public function destroy(AttendanceStatus $attendanceStatus)
    {
        Gate::authorize('Attendance Status Destroy');

        if ($attendanceStatus->is_system) {
            return redirect()->back()->with('error', 'Status bawaan sistem tidak boleh dihapus.');
        }

        $code = $attendanceStatus->code;
        $attendanceStatus->delete();

        return redirect()->route('commercial.attendance-statuses.index')
            ->with('success', "Status Kehadiran [{$code}] berhasil dihapus.");
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(AttendanceStatus $attendanceStatus)
    {
        Gate::authorize('Attendance Status Edit');

        $attendanceStatus->update(['is_active' => !$attendanceStatus->is_active]);

        $statusText = $attendanceStatus->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Status [{$attendanceStatus->code}] berhasil {$statusText}.");
    }
}
