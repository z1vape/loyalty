<?php

namespace App\Http\Controllers;

use App\Models\CctvCamera;
use App\Models\Store;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalController extends Controller
{
    /**
     * Display the Z1 VapeOS Workspace Portal (App Launcher).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Ensure active store is set in session so background store operations are ready
        $userStores = $user->hasRole('Super Admin') 
            ? Store::orderBy('name')->get() 
            : $user->stores()->orderBy('name')->get();

        $activeStoreId = session('active_store_id');
        $activeStore = null;

        if ($activeStoreId) {
            $activeStore = Store::find($activeStoreId);
        }

        if (!$activeStore && $userStores->isNotEmpty()) {
            $activeStore = $userStores->first();
            session([
                'active_store_id' => $activeStore->id,
                'active_store_name' => $activeStore->name,
            ]);
        }

        // Summary Statistics for Quick Glance
        $employeeCount = User::count();
        $cameraCount = CctvCamera::count();
        
        $todayAttendanceCount = 0;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('attendances')) {
                $todayAttendanceCount = Attendance::whereDate('date', today())->count();
            }
        } catch (\Throwable $e) {
            $todayAttendanceCount = 0;
        }

        return view('portal.index', compact(
            'user',
            'activeStore',
            'userStores',
            'employeeCount',
            'cameraCount',
            'todayAttendanceCount'
        ));
    }
}

