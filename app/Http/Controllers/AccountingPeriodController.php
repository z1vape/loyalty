<?php

namespace App\Http\Controllers;

use App\Models\AccountingPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class AccountingPeriodController extends Controller
{
    /**
     * Display a listing of accounting periods.
     */
    public function index()
    {
        Gate::authorize('Accounting Period Manage');

        $periods = AccountingPeriod::with('closedByUser')
            ->latest('start_date')
            ->latest('id')
            ->paginate(15);

        $activeClosedCount = AccountingPeriod::where('status', 'closed')->count();
        $suggestedName = 'Tutup Buku ' . Carbon::now()->locale('id')->isoFormat('MMMM Y');
        $suggestedStart = Carbon::now()->startOfMonth()->toDateString();
        $suggestedEnd = Carbon::now()->endOfMonth()->toDateString();

        return view('accounting.periods.index', compact(
            'periods',
            'activeClosedCount',
            'suggestedName',
            'suggestedStart',
            'suggestedEnd'
        ));
    }

    /**
     * Create and close a new accounting period (Tutup Buku).
     */
    public function store(Request $request)
    {
        Gate::authorize('Accounting Period Manage');

        $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'closing_notes' => 'nullable|string|max:1000',
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir periode harus sama atau setelah tanggal mulai.',
        ]);

        $storeId = session('active_store_id') ?? auth()->user()->stores()->first()?->id;
        if (!$storeId) {
            return back()->withInput()->with('error', 'Silakan pilih toko aktif terlebih dahulu.');
        }

        // Check for conflicting closed periods
        $conflict = AccountingPeriod::where('store_id', $storeId)
            ->where('status', 'closed')
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                    ->orWhere(function ($sub) use ($request) {
                        $sub->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date);
                    });
            })
            ->first();

        if ($conflict) {
            return back()->withInput()->with('error', "Rentang tanggal bertabrakan dengan periode tertutup: '{$conflict->name}' ({$conflict->start_date->format('d/m/Y')} - {$conflict->end_date->format('d/m/Y')}).");
        }

        $period = AccountingPeriod::create([
            'store_id' => $storeId,
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'closed',
            'closed_at' => Carbon::now(),
            'closed_by' => auth()->id(),
            'closing_notes' => $request->closing_notes,
        ]);

        return redirect()->route('accounting.periods.index')
            ->with('success', "Periode '{$period->name}' berhasil ditutup dan dikunci. Seluruh transaksi dalam rentang tanggal tersebut tidak dapat diubah atau dihapus.");
    }

    /**
     * Reopen a locked period.
     */
    public function reopen(AccountingPeriod $period)
    {
        Gate::authorize('Accounting Period Manage');

        $period->update([
            'status' => 'open',
            'closed_at' => null,
            'closed_by' => null,
            'closing_notes' => ($period->closing_notes ? $period->closing_notes . "\n" : '') . '[Dibuka kembali oleh ' . (auth()->user()->name ?? 'User') . ' pada ' . Carbon::now()->format('d/m/Y H:i') . ']',
        ]);

        return redirect()->route('accounting.periods.index')
            ->with('success', "Periode '{$period->name}' telah dibuka kembali (Unlocked).");
    }

    /**
     * Re-lock an opened period.
     */
    public function close(AccountingPeriod $period)
    {
        Gate::authorize('Accounting Period Manage');

        $period->update([
            'status' => 'closed',
            'closed_at' => Carbon::now(),
            'closed_by' => auth()->id(),
        ]);

        return redirect()->route('accounting.periods.index')
            ->with('success', "Periode '{$period->name}' telah dikunci kembali (Locked).");
    }

    /**
     * Delete an accounting period record.
     */
    public function destroy(AccountingPeriod $period)
    {
        Gate::authorize('Accounting Period Manage');

        $name = $period->name;
        $period->delete();

        return redirect()->route('accounting.periods.index')
            ->with('success', "Data periode '{$name}' berhasil dihapus.");
    }
}
