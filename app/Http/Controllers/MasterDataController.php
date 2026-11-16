<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\User;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\AttendanceStatus;
use App\Models\UserSchedule;
use App\Models\ShiftPattern;
use App\Models\ApprovalWorkflow;

class MasterDataController extends Controller
{
    public function index()
    {
        $currentUser = auth()->user();

        $hasAccess = $currentUser->hasRole('Super Admin') || $currentUser->hasAnyPermission([
            'Store List', 'User List', 'Role List', 'Setting List',
            'Shift List', 'Attendance Status List', 'User Schedule List', 'Shift Pattern List'
        ]);

        if (!$hasAccess) {
            abort(403, 'THIS ACTION IS UNAUTHORIZED.');
        }

        $counts = [
            'stores' => Store::count(),
            'users' => User::count(),
            'roles' => Role::count(),
            'settings' => Setting::count(),
            'shifts' => Shift::count(),
            'attendance_statuses' => AttendanceStatus::count(),
            'shift_patterns' => ShiftPattern::count(),
            'workflows' => ApprovalWorkflow::count(),
        ];

        return view('master_data.index', compact('counts'));
    }
}

