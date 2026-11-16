<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Store;
use App\Models\User;
use App\Models\UserSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialHrDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_hr_dashboard(): void
    {
        $role = Role::create(['name' => 'Super Admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($role->id);

        $store = Store::create(['name' => 'Z1 VAPE STORE VOL 1']);
        $admin->stores()->attach($store->id);

        $employee = User::factory()->create(['name' => 'Budi Santoso']);
        $employee->stores()->attach($store->id);

        $shift = Shift::create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_active' => true,
        ]);

        Attendance::create([
            'user_id' => $employee->id,
            'store_id' => $store->id,
            'shift_id' => $shift->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->subHours(2),
            'status' => 'present',
            'late_minutes' => 15,
        ]);

        Payroll::create([
            'payroll_code' => 'PAY-202611-001',
            'user_id' => $employee->id,
            'store_id' => $store->id,
            'period_month' => now()->month,
            'period_year' => now()->year,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'basic_salary' => 3000000,
            'net_salary' => 3200000,
            'status' => 'Draft',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('commercial.hr.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Human Resources');
        $response->assertSee('Total Karyawan');
        $response->assertSee('Hadir Hari Ini');
        $response->assertSee('Terlambat Hari Ini');
        $response->assertSee('Kesiapan Staf Toko Hari Ini');
        $response->assertSee('Z1 VAPE STORE VOL 1');
        $response->assertSee('Tren Kehadiran (7 Hari)');
        $response->assertSee('Aktivitas Presensi Terkini');
        $response->assertSee('Budi Santoso');
        $response->assertSee('Estimasi Komponen Payroll');
        $response->assertSee('Rp 3.000.000');
    }

    public function test_user_with_attendance_list_permission_can_access_hr_dashboard(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'Attendance List'],
            ['category' => 'Commercial', 'group_name' => 'KEHADIRAN & ABSENSI']
        );
        $role = Role::create(['name' => 'HR Manager']);
        $role->permissions()->attach($permission->id);

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $store = Store::create(['name' => 'Store Cabang']);
        $user->stores()->attach($store->id);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('commercial.hr.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Human Resources');
    }

    public function test_unauthorized_user_cannot_access_hr_dashboard(): void
    {
        $role = Role::create(['name' => 'Cashier']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $store = Store::create(['name' => 'Store Cabang']);
        $user->stores()->attach($store->id);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('commercial.hr.dashboard'));

        $response->assertStatus(403);
    }

    public function test_hr_dashboard_store_filter(): void
    {
        $role = Role::create(['name' => 'Super Admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($role->id);

        $store1 = Store::create(['name' => 'Toko 1']);
        $store2 = Store::create(['name' => 'Toko 2']);

        $response = $this->actingAs($admin)
            ->get(route('commercial.hr.dashboard', ['store_id' => $store1->id]));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Human Resources');
    }
}
