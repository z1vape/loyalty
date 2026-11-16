<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\Store;
use App\Models\UserSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class Z1AppController extends Controller
{
    /**
     * Display the Z1 App employee portal.
     */
    public function index(Request $request)
    {
        Gate::authorize('Z1 App Access');

        $user = auth()->user();
        $activeStoreId = session('active_store_id');
        $activeStore = $activeStoreId ? Store::find($activeStoreId) : ($user->stores->first() ?? Store::first());

        if ($activeStore && session('active_store_id') != $activeStore->id) {
            session(['active_store_id' => $activeStore->id]);
        }

        $activeTab = $request->query('tab');
        if (!$activeTab || ($activeTab === 'attendance' && !$user->can('Z1 App Attendance')) || ($activeTab === 'purchase_request' && !$user->can('Z1 App Purchase Request')) || ($activeTab === 'sales_report' && !$user->can('Z1 App Sales Report'))) {
            if ($user->can('Z1 App Attendance')) {
                $activeTab = 'attendance';
            } elseif ($user->can('Z1 App Purchase Request')) {
                $activeTab = 'purchase_request';
            } elseif ($user->can('Z1 App Sales Report')) {
                $activeTab = 'sales_report';
            } else {
                $activeTab = 'attendance';
            }
        }

        // ─── 1. Modul Absensi ────────────────────────────────────────────
        $today = Carbon::today();
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->with(['shift', 'store'])
            ->first();

        // Cross-midnight / overnight shift check:
        // Jika hari ini belum clock in atau belum ada record, periksa apakah karyawan masih memiliki sesi kerja aktif dari kemarin yang belum clock out.
        if (!$todayAttendance || !$todayAttendance->clock_in) {
            $openYesterdayAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today->copy()->subDay())
                ->whereNotNull('clock_in')
                ->whereNull('clock_out')
                ->with(['shift', 'store'])
                ->latest('clock_in')
                ->first();

            if ($openYesterdayAttendance) {
                $yShift = $openYesterdayAttendance->shift;
                $now = Carbon::now();
                $isWithinMaxDelay = true;
                if ($yShift) {
                    $isWithinMaxDelay = $yShift->isWithinClockOutWindow($now, $openYesterdayAttendance->date);
                } else {
                    $isWithinMaxDelay = $now->lt(Carbon::today()->setTime(6, 0));
                }

                if ($isWithinMaxDelay) {
                    $todayAttendance = $openYesterdayAttendance;
                }
            }
        }

        $effectiveDate = $todayAttendance ? Carbon::parse($todayAttendance->date) : $today;

        $todaySchedule = UserSchedule::where('user_id', $user->id)
            ->whereDate('date', $effectiveDate)
            ->with('shift')
            ->first();

        $shifts = Shift::where('is_active', true)
            ->when($activeStore, fn($q) => $q->where(function($sq) use ($activeStore) {
                $sq->where('store_id', $activeStore->id)->orWhereNull('store_id');
            }))
            ->orderBy('start_time')
            ->get();

        $attendanceMonth = $request->query('att_month', $today->format('Y-m'));
        $monthDate = Carbon::createFromFormat('Y-m', $attendanceMonth);

        $attendanceHistory = Attendance::where('user_id', $user->id)
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month)
            ->with(['shift', 'store', 'user'])
            ->orderByDesc('date')
            ->get();

        $attSummary = [
            'present' => $attendanceHistory->whereIn('status', ['present', 'on_time'])->count(),
            'late'    => $attendanceHistory->where('status', 'late')->count(),
            'other'   => $attendanceHistory->whereIn('status', ['sick', 'permission', 'annual_leave'])->count(),
            'alpha'   => $attendanceHistory->where('status', 'absent')->count(),
        ];

        // Rekap 30 hari terakhir (untuk tab "Rekap 30 Hari Terakhir")
        $attendanceLast30 = Attendance::where('user_id', $user->id)
            ->whereDate('date', '>=', $today->copy()->subDays(29))
            ->whereDate('date', '<=', $today)
            ->with(['shift'])
            ->orderByDesc('date')
            ->get();

        // ─── 2. Modul Purchase Request ──────────────────────────────────
        $prDate = $request->query('pr_date');
        $prStatus = $request->query('pr_status');

        $userStoreIds = $user->stores->pluck('id')->toArray();
        $isSuperAdmin = $user->hasRole('Super Admin') || $user->id === 1;

        $prQuery = PurchaseRequest::withoutGlobalScopes()
            ->with(['items.product' => fn($q) => $q->withoutGlobalScopes(), 'approvedByUser', 'user', 'store'])
            ->orderByDesc('created_at');

        // Karyawan yang di-assign di toko tersebut bisa melihat seluruh order barang tokonya
        if ($activeStore) {
            $prQuery->where('store_id', $activeStore->id);
        } elseif (!$isSuperAdmin) {
            if (!empty($userStoreIds)) {
                $prQuery->whereIn('store_id', $userStoreIds);
            } else {
                $prQuery->where('user_id', $user->id);
            }
        }

        if ($prDate) {
            $prQuery->whereDate('request_date', $prDate);
        }
        if ($prStatus && $prStatus !== 'all') {
            $prQuery->where('status', $prStatus);
        }

        $purchaseRequests = $prQuery->get();

        $totalPrOrders = $purchaseRequests->count();
        $totalPrUnits = $purchaseRequests->sum(fn($pr) => $pr->items->sum('quantity_requested'));
        $totalPrAmount = $purchaseRequests->sum(fn($pr) => $pr->items->sum(fn($i) => ($i->product?->purchase_price ?? $i->product?->price ?? 0) * $i->quantity_requested));

        $storeProducts = $activeStore 
            ? Product::withoutGlobalScopes()->where('store_id', $activeStore->id)->orderBy('name')->get() 
            : Product::withoutGlobalScopes()->orderBy('name')->get();

        // ─── 3. Modul Report Penjualan Staff ────────────────────────────
        $period = $request->query('period', 'today'); // 'today' | 'this_month' | 'last_month' | 'custom'
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $now = Carbon::now();
        if ($period === 'today') {
            $startDate = $now->toDateString();
            $endDate = $now->toDateString();
        } elseif ($period === 'this_month') {
            $startDate = $now->copy()->startOfMonth()->toDateString();
            $endDate = $now->toDateString();
        } elseif ($period === 'last_month') {
            $startDate = $now->copy()->subMonth()->startOfMonth()->toDateString();
            $endDate = $now->copy()->subMonth()->endOfMonth()->toDateString();
        } else {
            $startDate = $dateFrom ?: $now->toDateString();
            $endDate = $dateTo ?: $now->toDateString();
        }

        $salesQuery = Sale::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            });

        if ($activeStore) {
            $salesQuery->where('store_id', $activeStore->id);
        }

        $staffSales = (clone $salesQuery)->with(['items.product'])->orderByDesc('created_at')->get();

        $totalSalesAmount = (float) $staffSales->sum('total_amount');
        $totalTransactions = $staffSales->count();
        $totalItemsSold = 0;
        $productStats = [];

        foreach ($staffSales as $sale) {
            foreach ($sale->items as $item) {
                $totalItemsSold += (int) $item->quantity;
                $pId = $item->product_id;
                $pName = $item->product?->name ?? 'Produk Dihapus';
                $pSku = $item->product?->sku ?? '-';

                if (!isset($productStats[$pId])) {
                    $productStats[$pId] = [
                        'name' => $pName,
                        'sku' => $pSku,
                        'qty' => 0,
                        'total_amount' => 0,
                    ];
                }
                $productStats[$pId]['qty'] += (int) $item->quantity;
                $productStats[$pId]['total_amount'] += (float) ($item->total_price ?? ($item->quantity * $item->unit_price));
            }
        }

        $topProducts = collect(array_values($productStats))->sortByDesc('qty')->take(10)->values();
        $avgTransaction = $totalTransactions > 0 ? round($totalSalesAmount / $totalTransactions, 2) : 0;

        return view('z1_app.index', compact(
            'user',
            'isSuperAdmin',
            'activeStore',
            'activeTab',
            // Absensi
            'todayAttendance',
            'todaySchedule',
            'shifts',
            'attendanceHistory',
            'attSummary',
            'attendanceLast30',
            'attendanceMonth',
            // Purchase Request
            'purchaseRequests',
            'storeProducts',
            'totalPrOrders',
            'totalPrUnits',
            'totalPrAmount',
            'prDate',
            'prStatus',
            // Report Staff
            'period',
            'startDate',
            'endDate',
            'totalSalesAmount',
            'totalTransactions',
            'totalItemsSold',
            'avgTransaction',
            'topProducts',
            'staffSales'
        ));
    }

    /**
     * Clock in attendance.
     */
    public function clockIn(Request $request)
    {
        Gate::authorize('Z1 App Attendance');

        $user = auth()->user();
        $activeStoreId = session('active_store_id') ?? $user->stores->first()?->id;

        $today = Carbon::today();

        // Cegah Clock In ganda jika karyawan masih memiliki sesi kerja aktif dari shift sebelumnya yang belum di-Clock Out
        $openYesterday = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today->copy()->subDay())
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if ($openYesterday) {
            $yShift = $openYesterday->shift;
            $now = Carbon::now();
            $isWithinMaxDelay = true;
            if ($yShift) {
                $isWithinMaxDelay = $yShift->isWithinClockOutWindow($now, $openYesterday->date);
            } else {
                $isWithinMaxDelay = $now->lt(Carbon::today()->setTime(6, 0));
            }

            if ($isWithinMaxDelay) {
                return redirect()->route('z1-app.index', ['tab' => 'attendance'])
                    ->with('error', 'Anda masih memiliki sesi kerja aktif dari shift kemarin yang belum di-Clock Out. Harap lakukan Clock Out terlebih dahulu.');
            }
        }

        $todaySchedule = UserSchedule::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->with('shift')
            ->first();
        $isOffSchedule = $todaySchedule && $todaySchedule->is_off;

        $request->validate([
            'shift_id' => $isOffSchedule ? 'nullable|exists:shifts,id' : 'required|exists:shifts,id',
            'photo'    => 'required|string',
            'note'     => 'nullable|string|max:500',
        ], [
            'photo.required' => 'Foto selfie kehadiran wajib diambil sebelum melakukan Clock In.',
        ]);

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($attendance && $attendance->clock_in) {
            return redirect()->route('z1-app.index', ['tab' => 'attendance'])
                ->with('error', 'Anda sudah melakukan clock in hari ini.');
        }

        $photoPath = $this->saveSelfie($request->input('photo'), (int) $user->id, 'in');
        if (!$photoPath) {
            return redirect()->route('z1-app.index', ['tab' => 'attendance'])
                ->with('error', 'Gagal memproses foto selfie. Silakan ambil foto ulang.');
        }

        $activeStore = $activeStoreId ? Store::find($activeStoreId) : null;

        $distance = null;
        if ($activeStore && $activeStore->hasGeofence()) {
            $request->validate([
                'lat' => 'required|numeric|between:-90,90',
                'lng' => 'required|numeric|between:-180,180',
            ], [
                'lat.required' => 'Koordinat lokasi GPS wajib terdeteksi untuk memverifikasi kehadiran di toko.',
                'lng.required' => 'Koordinat lokasi GPS wajib terdeteksi untuk memverifikasi kehadiran di toko.',
                'lat.between'  => 'Koordinat Latitude tidak valid.',
                'lng.between'  => 'Koordinat Longitude tidak valid.',
            ]);

            $userLat = (float) $request->input('lat');
            $userLng = (float) $request->input('lng');
            $distance = $this->calculateDistance($userLat, $userLng, (float) $activeStore->latitude, (float) $activeStore->longitude);

            $radius = (int) ($activeStore->radius_meters ?: 100);

            if ($distance > $radius) {
                return redirect()->route('z1-app.index', ['tab' => 'attendance'])
                    ->with('error', "Gagal Clock In: Anda berada di luar radius toko {$activeStore->name}. Jarak Anda saat ini {$distance} meter dari toko (Batas radius maksimal: {$radius} meter).");
            }
        }

        $shift = $request->filled('shift_id') ? Shift::find($request->shift_id) : null;
        $now = Carbon::now();
        $lateMinutes = 0;
        $status = 'present';

        if ($shift && !$isOffSchedule) {
            $earliest = $shift->getEarliestClockInTime($today);
            if ($now->lt($earliest)) {
                return redirect()->route('z1-app.index', ['tab' => 'attendance'])
                    ->with('error', "Clock In untuk shift {$shift->name} belum dibuka. Anda baru dapat melakukan Clock In mulai pukul {$earliest->format('H:i')} WIB ({$shift->early_clock_in_minutes} menit sebelum jadwal shift).");
            }

            $shiftStart = Carbon::parse($today->toDateString() . ' ' . $shift->start_time);
            $tolerance = (int) ($user->payrollSetting?->late_tolerance_minutes ?? $shift->late_tolerance_minutes ?? 15);
            $deadline = $shiftStart->copy()->addMinutes($tolerance);

            if ($now->gt($deadline)) {
                $lateMinutes = $shiftStart->diffInMinutes($now);
                $status = 'late';
            } else {
                $status = 'present';
                $lateMinutes = 0;
            }
        }

        if (!$attendance) {
            $attendance = new Attendance();
            $attendance->user_id = $user->id;
            $attendance->store_id = $activeStoreId;
            $attendance->date = $today->toDateString();
        }

        $attendance->shift_id = $shift?->id;
        $attendance->clock_in = $now;
        $attendance->clock_in_photo = $photoPath;
        if ($request->filled('lat') && $request->filled('lng')) {
            $attendance->clock_in_lat = $request->input('lat');
            $attendance->clock_in_lng = $request->input('lng');
            $attendance->clock_in_distance_meters = $distance ?? ($activeStore && $activeStore->hasGeofence() ? $this->calculateDistance((float)$request->input('lat'), (float)$request->input('lng'), (float)$activeStore->latitude, (float)$activeStore->longitude) : null);
        }
        $attendance->status = $status;
        $attendance->late_minutes = $lateMinutes;
        $attendance->note = $request->note ?: ($isOffSchedule ? 'Lembur Hari Libur (OFF)' : null);
        $attendance->save();

        $distanceInfo = $distance !== null ? " (Jarak {$distance}m dari toko)" : '';
        return redirect()->route('z1-app.index', ['tab' => 'attendance'])
            ->with('success', 'Clock In berhasil pada ' . $now->format('H:i') . ($status === 'late' ? " (Terlambat {$lateMinutes} menit)" : ' (Tepat waktu)') . $distanceInfo);
    }

    /**
     * Save base64 selfie image to public storage.
     */
    private function saveSelfie(?string $base64, int $userId, string $type): ?string
    {
        if (empty($base64)) {
            return null;
        }

        $image = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $image = base64_decode($image);

        if (!$image) {
            return null;
        }

        $dir = "attendances/{$userId}";
        $filename = "{$type}_" . now()->format('Ymd_His') . "_" . bin2hex(random_bytes(4)) . ".jpg";
        $path = "{$dir}/{$filename}";

        Storage::disk('public')->put($path, $image);

        return $path;
    }

    /**
     * Calculate geodesic distance between two coordinate points in meters using Haversine formula.
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadius = 6371000; // in meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round($earthRadius * $c);
    }

    /**
     * Clock out attendance.
     */
    public function clockOut(Request $request)
    {
        Gate::authorize('Z1 App Attendance');

        $user = auth()->user();
        $today = Carbon::today();

        $attendanceId = $request->input('attendance_id');
        $attendance = null;

        if ($attendanceId) {
            $attendance = Attendance::where('id', $attendanceId)
                ->where('user_id', $user->id)
                ->first();
        }

        if (!$attendance) {
            // Cek rekaman absensi hari ini terlebih dahulu
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();
        }

        // Jika rekaman hari ini belum ada / belum clock in / sudah clock out,
        // cek apakah ada sesi kerja aktif lintas malam dari kemarin yang belum clock out
        if (!$attendance || !$attendance->clock_in || $attendance->clock_out) {
            $openYesterday = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today->copy()->subDay())
                ->whereNotNull('clock_in')
                ->whereNull('clock_out')
                ->latest('clock_in')
                ->first();

            if ($openYesterday) {
                $yShift = $openYesterday->shift;
                $isWithinMaxDelay = true;
                if ($yShift) {
                    $isWithinMaxDelay = $yShift->isWithinClockOutWindow(Carbon::now(), $openYesterday->date);
                } else {
                    $isWithinMaxDelay = Carbon::now()->lt(Carbon::today()->setTime(6, 0));
                }

                if ($isWithinMaxDelay) {
                    $attendance = $openYesterday;
                }
            }
        }

        if (!$attendance || !$attendance->clock_in) {
            return redirect()->route('z1-app.index', ['tab' => 'attendance'])
                ->with('error', 'Anda belum melakukan clock in hari ini.');
        }

        if ($attendance->clock_out) {
            return redirect()->route('z1-app.index', ['tab' => 'attendance'])
                ->with('error', 'Anda sudah melakukan clock out untuk sesi kerja ini.');
        }

        $now = Carbon::now();
        $attShift = $attendance->shift;
        if ($attShift && !$attShift->isWithinClockOutWindow($now, $attendance->date)) {
            $latestOut = $attShift->getLatestClockOutTime($attendance->date);
            return redirect()->route('z1-app.index', ['tab' => 'attendance'])
                ->with('error', "Batas waktu clock out untuk shift {$attShift->name} telah berakhir pada pukul {$latestOut->format('H:i')} WIB (+{$attShift->max_clock_out_delay_minutes} menit setelah shift). Silakan hubungi Admin Toko untuk koreksi manual.");
        }
        $workMinutes = Carbon::parse($attendance->clock_in)->diffInMinutes($now);

        $attendance->clock_out = $now;
        $attendance->work_minutes = $workMinutes;

        if ($request->filled('photo')) {
            $attendance->clock_out_photo = $this->saveSelfie($request->input('photo'), (int) $user->id, 'out');
        }

        if ($request->filled('lat') && $request->filled('lng')) {
            $attendance->clock_out_lat = $request->input('lat');
            $attendance->clock_out_lng = $request->input('lng');
            $activeStore = $attendance->store;
            if ($activeStore && $activeStore->hasGeofence()) {
                $attendance->clock_out_distance_meters = $this->calculateDistance(
                    (float) $request->input('lat'),
                    (float) $request->input('lng'),
                    (float) $activeStore->latitude,
                    (float) $activeStore->longitude
                );
            }
        }

        if ($request->filled('note')) {
            $attendance->note = ($attendance->note ? $attendance->note . ' | ' : '') . $request->note;
        }

        // Hitung Lembur (Hari Libur OFF vs Shift Normal)
        $attDate = Carbon::parse($attendance->date);
        $schedule = UserSchedule::where('user_id', $user->id)
            ->whereDate('date', $attDate)
            ->first();
        $isOffSchedule = ($schedule && $schedule->is_off) || empty($attendance->shift_id);

        if ($isOffSchedule) {
            // Hari Libur (OFF): Seluruh durasi jam kerja dihitung sebagai lembur
            $attendance->overtime_minutes = $workMinutes;
        } else {
            // Shift Normal: Kelebihan jam kerja di luar shift dihitung sebagai lembur reguler
            $shift = $attendance->shift ?? ($attendance->shift_id ? Shift::find($attendance->shift_id) : null);
            if ($shift) {
                $attDateStr = $attDate->toDateString();
                $shiftStart = Carbon::parse($attDateStr . ' ' . $shift->start_time);
                $shiftEnd = Carbon::parse($attDateStr . ' ' . $shift->end_time);
                if ($shift->is_overnight || $shiftEnd->lte($shiftStart)) {
                    $shiftEnd->addDay();
                }

                if ($now->gt($shiftEnd)) {
                    $diffMins = $shiftEnd->diffInMinutes($now);
                    // Lembur reguler hanya diakui jika clock out melebihi jam selesai shift minimal 1 jam (60 menit)
                    // Dihitung kelipatan 1 jam penuh (contoh: selisih 75 menit tetap dihitung 1 jam / 60 menit)
                    $attendance->overtime_minutes = $diffMins >= 60 ? (intdiv($diffMins, 60) * 60) : 0;
                }
            }
        }

        $attendance->save();

        $otInfo = '';
        if ($attendance->overtime_minutes > 0) {
            $otHours = intdiv($attendance->overtime_minutes, 60);
            $otMins = $attendance->overtime_minutes % 60;
            $otType = $isOffSchedule ? 'Lembur Hari Libur' : 'Lembur Reguler';
            $otInfo = ($otMins > 0) ? " &bull; {$otType}: {$otHours}j {$otMins}m" : " &bull; {$otType}: {$otHours} Jam";
        }

        return redirect()->route('z1-app.index', ['tab' => 'attendance'])
            ->with('success', 'Clock Out berhasil pada ' . $now->format('H:i') . ". Total jam kerja: " . intdiv($workMinutes, 60) . 'j ' . ($workMinutes % 60) . 'm' . $otInfo);
    }

    /**
     * Store new purchase request.
     */
    public function storePurchaseRequest(Request $request)
    {
        Gate::authorize('Z1 App Purchase Request');

        $user = auth()->user();
        $activeStoreId = session('active_store_id') ?? $user->stores->first()?->id;

        $validated = $request->validate([
            'request_date' => 'required|date',
            'priority' => 'nullable|string|in:normal,urgent',
            'notes' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.custom_item_name' => 'nullable|string|max:255',
            'items.*.estimated_price' => 'nullable|numeric|min:0',
            'items.*.quantity_requested' => 'nullable|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $datePrefix = date('Ymd');
            $latestPr = PurchaseRequest::withoutGlobalScopes()
                ->where('pr_number', 'like', "PR-{$datePrefix}-%")
                ->orderByDesc('id')
                ->first();

            $seq = 1;
            if ($latestPr && preg_match('/PR-\d+-(\d+)/', $latestPr->pr_number, $matches)) {
                $seq = intval($matches[1]) + 1;
            }
            $prNumber = sprintf('PR-%s-%04d', $datePrefix, $seq);

            $pr = PurchaseRequest::create([
                'pr_number' => $prNumber,
                'store_id' => $activeStoreId,
                'user_id' => $user->id,
                'request_date' => $validated['request_date'] ?? Carbon::now()->toDateString(),
                'status' => 'pending',
                'priority' => $validated['priority'] ?? 'normal',
                'notes' => $validated['notes'] ?? null,
            ]);

            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $itemData) {
                    if (empty($itemData['product_id']) && empty($itemData['custom_item_name'])) {
                        continue;
                    }
                    PurchaseRequestItem::create([
                        'purchase_request_id' => $pr->id,
                        'product_id' => !empty($itemData['product_id']) ? $itemData['product_id'] : null,
                        'custom_item_name' => !empty($itemData['custom_item_name']) ? trim($itemData['custom_item_name']) : null,
                        'estimated_price' => $itemData['estimated_price'] ?? 0,
                        'quantity_requested' => $itemData['quantity_requested'] ?? 1,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('z1-app.purchase-request.show', $pr->id)
                ->with('success', "Order barang #{$prNumber} berhasil dibuat. Silakan tambahkan barang yang ingin dipesan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('z1-app.index', ['tab' => 'purchase_request'])
                ->with('error', 'Gagal membuat purchase request: ' . $e->getMessage());
        }
    }

    /**
     * Delete / Cancel purchase request.
     */
    public function destroyPurchaseRequest($id)
    {
        Gate::authorize('Z1 App Purchase Request');

        $user = auth()->user();
        $pr = PurchaseRequest::withoutGlobalScopes()->findOrFail($id);

        $isSuperAdmin = $user->hasRole('Super Admin') || $user->id === 1;
        $userStoreIds = $user->stores->pluck('id')->toArray();
        $isAssignedToStore = in_array($pr->store_id, $userStoreIds);
        $isPendingAndEmpty = $pr->status === 'pending' && $pr->items()->count() === 0;

        if (!$isPendingAndEmpty && !$isSuperAdmin && $pr->user_id !== $user->id && !$isAssignedToStore) {
            abort(403, 'Akses ditolak. Anda tidak ditugaskan di cabang toko order ini.');
        }

        if (in_array($pr->status, ['approved', 'completed']) && !$isPendingAndEmpty) {
            return redirect()->route('z1-app.index', ['tab' => 'purchase_request'])
                ->with('error', 'Order yang sudah disetujui tidak dapat dihapus.');
        }

        $prNumber = $pr->pr_number;
        $pr->items()->delete();
        $pr->delete();

        return redirect()->route('z1-app.index', ['tab' => 'purchase_request'])
            ->with('success', "Order barang #{$prNumber} berhasil dihapus.");
    }

    /**
     * Show detail page for a specific purchase request (similar to date_show).
     */
    public function showPurchaseRequest($id)
    {
        Gate::authorize('Z1 App Purchase Request');

        $user = auth()->user();
        $pr = PurchaseRequest::withoutGlobalScopes()
            ->with(['items.product' => fn($q) => $q->withoutGlobalScopes(), 'store', 'user', 'approvedByUser'])
            ->findOrFail($id);

        $isSuperAdmin = $user->hasRole('Super Admin') || $user->id === 1;
        $userStoreIds = $user->stores->pluck('id')->toArray();
        $isAssignedToStore = in_array($pr->store_id, $userStoreIds);

        if (!$isSuperAdmin && $pr->user_id !== $user->id && !$isAssignedToStore) {
            abort(403, 'Akses ditolak. Anda tidak ditugaskan di cabang toko order ini.');
        }

        $activeStoreId = session('active_store_id') ?? $pr->store_id;
        $activeStore = $pr->store ?? Store::find($activeStoreId);

        $totalItemsCount = $pr->items->count();
        $totalUnits = $pr->items->sum('quantity_requested');
        $totalAmount = $pr->items->sum(fn($i) => $i->item_price * $i->quantity_requested);

        $storeProducts = Product::withoutGlobalScopes()->where('store_id', $pr->store_id)->orderBy('name')->get();

        $activeTab = 'purchase_request';

        return view('z1_app.purchase_request_show', compact(
            'pr',
            'user',
            'isSuperAdmin',
            'activeStore',
            'activeTab',
            'totalItemsCount',
            'totalUnits',
            'totalAmount',
            'storeProducts'
        ));
    }

    /**
     * Add a product item to an existing purchase request.
     */
    public function addPurchaseRequestItem(Request $request, $id)
    {
        Gate::authorize('Z1 App Purchase Request');

        $user = auth()->user();
        $pr = PurchaseRequest::withoutGlobalScopes()->findOrFail($id);

        $isSuperAdmin = $user->hasRole('Super Admin') || $user->id === 1;
        $userStoreIds = $user->stores->pluck('id')->toArray();
        $isAssignedToStore = in_array($pr->store_id, $userStoreIds);

        if (!$isSuperAdmin && $pr->user_id !== $user->id && !$isAssignedToStore) {
            abort(403, 'Akses ditolak. Anda tidak ditugaskan di cabang toko order ini.');
        }

        if ($pr->status === 'completed' || $pr->status === 'approved') {
            return redirect()->route('z1-app.purchase-request.show', $pr->id)
                ->with('error', 'Order yang sudah disetujui atau selesai tidak dapat diubah.');
        }

        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'custom_item_name' => 'nullable|string|max:255',
            'estimated_price' => 'nullable|numeric|min:0',
            'quantity_requested' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        if (empty($validated['product_id']) && empty($validated['custom_item_name'])) {
            return redirect()->route('z1-app.purchase-request.show', $pr->id)
                ->with('error', 'Pilih produk toko atau ketik nama barang manual.');
        }

        $prItem = null;
        if (!empty($validated['product_id'])) {
            $existing = PurchaseRequestItem::where('purchase_request_id', $pr->id)
                ->where('product_id', $validated['product_id'])
                ->first();

            if ($existing) {
                $existing->increment('quantity_requested', $validated['quantity_requested']);
                if (!empty($validated['notes'])) {
                    $existing->update(['notes' => $validated['notes']]);
                }
                $prItem = $existing->fresh();
            } else {
                $product = Product::withoutGlobalScopes()->findOrFail($validated['product_id']);
                $prItem = PurchaseRequestItem::create([
                    'purchase_request_id' => $pr->id,
                    'product_id' => $validated['product_id'],
                    'custom_item_name' => null,
                    'estimated_price' => $product->purchase_price ?? $product->price ?? 0,
                    'quantity_requested' => $validated['quantity_requested'],
                    'notes' => $validated['notes'] ?? null,
                ]);
            }
        } else {
            $existing = PurchaseRequestItem::where('purchase_request_id', $pr->id)
                ->where('custom_item_name', trim($validated['custom_item_name']))
                ->first();

            if ($existing) {
                $existing->increment('quantity_requested', $validated['quantity_requested']);
                $prItem = $existing->fresh();
            } else {
                // Manual non-inventory item (TIDAK menambahkan ke master products / inventory)
                $prItem = PurchaseRequestItem::create([
                    'purchase_request_id' => $pr->id,
                    'product_id' => null,
                    'custom_item_name' => trim($validated['custom_item_name']),
                    'estimated_price' => $validated['estimated_price'] ?? 0,
                    'quantity_requested' => $validated['quantity_requested'],
                    'notes' => $validated['notes'] ?? null,
                ]);
            }
        }

        // Sinkronisasi otomatis ke Purchase Order (PO Supplier) yang aktif jika sudah dibuat dari PR ini
        if ($prItem) {
            $activePos = PurchaseOrder::withoutGlobalScopes()
                ->where('purchase_request_id', $pr->id)
                ->where('status', '!=', 'received')
                ->get();

            foreach ($activePos as $po) {
                $existingPoItem = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->when($prItem->product_id, fn($q) => $q->where('product_id', $prItem->product_id))
                    ->when(!$prItem->product_id, fn($q) => $q->where('custom_item_name', $prItem->custom_item_name))
                    ->first();

                $unitPrice = $prItem->product?->purchase_price ?? $prItem->product?->price ?? $prItem->estimated_price ?? 0;

                if ($existingPoItem) {
                    $existingPoItem->increment('quantity_ordered', $validated['quantity_requested']);
                    $existingPoItem->update([
                        'subtotal' => $existingPoItem->quantity_ordered * $existingPoItem->unit_price
                    ]);
                } else {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'purchase_request_item_id' => $prItem->id,
                        'product_id' => $prItem->product_id,
                        'custom_item_name' => $prItem->custom_item_name,
                        'quantity_ordered' => $validated['quantity_requested'],
                        'quantity_received' => 0,
                        'unit_price' => $unitPrice,
                        'subtotal' => $validated['quantity_requested'] * $unitPrice,
                        'notes' => $prItem->notes,
                    ]);
                }
                $po->update(['total_amount' => $po->items()->sum('subtotal')]);
            }
        }

        return redirect()->route('z1-app.purchase-request.show', $pr->id)
            ->with('success', 'Barang berhasil ditambahkan ke order #' . $pr->pr_number);
    }

    /**
     * Delete an item from an existing purchase request.
     */
    public function destroyPurchaseRequestItem($id)
    {
        Gate::authorize('Z1 App Purchase Request');

        $user = auth()->user();
        $item = PurchaseRequestItem::with('purchaseRequest')->findOrFail($id);
        $pr = $item->purchaseRequest;

        $isSuperAdmin = $user->hasRole('Super Admin') || $user->id === 1;
        $userStoreIds = $user->stores->pluck('id')->toArray();
        $isAssignedToStore = in_array($pr->store_id, $userStoreIds);

        if (!$isSuperAdmin && $pr->user_id !== $user->id && !$isAssignedToStore) {
            abort(403, 'Akses ditolak. Anda tidak ditugaskan di cabang toko order ini.');
        }

        if (in_array($pr->status, ['approved', 'completed'])) {
            return redirect()->route('z1-app.purchase-request.show', $pr->id)
                ->with('error', 'Order yang sudah disetujui tidak dapat diubah.');
        }

        $item->delete();

        return redirect()->route('z1-app.purchase-request.show', $pr->id)
            ->with('success', 'Barang berhasil dihapus dari order #' . $pr->pr_number);
    }
}
