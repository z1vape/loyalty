<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_commercial_attendances_index(): void
    {
        $admin = User::factory()->create();
        $role = Role::create(['name' => 'Super Admin', 'description' => 'Super Administrator']);
        $admin->roles()->attach($role->id);

        $store = Store::create([
            'name' => 'Store 1',
            'address' => 'Jl. Test',
            'phone' => '08123456789'
        ]);
        $admin->stores()->attach($store->id);

        $employee = User::factory()->create([
            'name' => 'Abdul Rozaq',
            'username' => 'rozaq01',
            'employee_number' => 'DPP.0917.052',
            'position' => 'Staff'
        ]);
        $employee->stores()->attach($store->id);

        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('commercial.attendances.index'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Kehadiran');
        $response->assertSee('Lokasi Kerja');
        $response->assertSee('Posisi');
        $response->assertSee('Peran');
        $response->assertSee('ID Username');
        $response->assertSee('ABDUL ROZAQ');
        $response->assertSee('rozaq01');
        $response->assertDontSee('Impor Fingerprint');
        $response->assertDontSee('Daftar kehadiran seluruh karyawan');
    }

    public function test_admin_can_view_employee_attendance_detail_ledger(): void
    {
        $admin = User::factory()->create();
        $role = Role::create(['name' => 'Super Admin', 'description' => 'Super Administrator']);
        $admin->roles()->attach($role->id);

        $store = Store::create([
            'name' => 'Diton Pitstop Palmerah',
            'address' => 'Palmerah',
            'phone' => '08123456789'
        ]);
        $admin->stores()->attach($store->id);

        $employee = User::factory()->create([
            'name' => 'Abdul Rozaq',
            'employee_number' => 'DPP.0917.052',
            'position' => 'Staff Operasional'
        ]);
        $employee->stores()->attach($store->id);

        $shift = Shift::create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_active' => true,
        ]);

        Attendance::create([
            'user_id' => $employee->id,
            'store_id' => $store->id,
            'shift_id' => $shift->id,
            'date' => '2026-10-15',
            'clock_in' => Carbon::parse('2026-10-15 07:52:11'),
            'clock_out' => Carbon::parse('2026-10-15 20:16:32'),
            'status' => 'present',
            'work_minutes' => 744,
            'note' => 'Hadir tepat waktu',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('commercial.attendances.show', [
                'user' => $employee->id,
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-25',
                'month' => 10,
                'year' => 2026
            ]));

        $response->assertStatus(200);
        $response->assertSee('ABDUL ROZAQ');
        $response->assertSee('DPP.0917.052');
        $response->assertSee('CLOCK IN');
        $response->assertSee('CLOCK OUT');
        $response->assertSee('07:52:11');
        $response->assertSee('20:16:32');
        $response->assertSee('Diton Pitstop Palmerah');
    }

    public function test_admin_can_export_attendances_csv(): void
    {
        $admin = User::factory()->create();
        $role = Role::create(['name' => 'Super Admin', 'description' => 'Super Administrator']);
        $admin->roles()->attach($role->id);

        $store = Store::create([
            'name' => 'Store 1',
            'address' => 'Jl. Test',
            'phone' => '08123456789'
        ]);
        $admin->stores()->attach($store->id);

        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('commercial.attendances.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_commercial_attendance_defaults_to_full_month_period(): void
    {
        $admin = User::factory()->create();
        $role = Role::create(['name' => 'Super Admin', 'description' => 'Super Administrator']);
        $admin->roles()->attach($role->id);

        $store = Store::create([
            'name' => 'Store 1',
            'address' => 'Jl. Test',
            'phone' => '08123456789'
        ]);
        $admin->stores()->attach($store->id);

        // Access October 2026 without start_date / end_date
        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('commercial.attendances.index', [
                'month' => 10,
                'year' => 2026
            ]));

        $response->assertStatus(200);
        $response->assertSee('1 Oktober 2026 — 31 Oktober 2026');
        $response->assertSee('value="2026-10-01"', false);
        $response->assertSee('value="2026-10-31"', false);
    }

    public function test_super_admin_is_excluded_from_attendance(): void
    {
        $admin = User::factory()->create([
            'name' => 'Boss Super Admin',
            'username' => 'superboss01',
        ]);
        $role = Role::create(['name' => 'Super Admin', 'description' => 'Super Administrator']);
        $admin->roles()->attach($role->id);

        $store = Store::create([
            'name' => 'Store 1',
            'address' => 'Jl. Test',
            'phone' => '08123456789'
        ]);
        $admin->stores()->attach($store->id);

        $staff = User::factory()->create([
            'name' => 'Staff Biasa',
            'username' => 'staff01',
        ]);
        $staff->stores()->attach($store->id);

        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('commercial.attendances.index'));

        $response->assertStatus(200);
        $response->assertViewHas('users', function ($users) use ($admin, $staff) {
            return !$users->contains('id', $admin->id) && $users->contains('id', $staff->id);
        });

        // Detail show page for super admin should return 404
        $showResponse = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('commercial.attendances.show', $admin));

        $showResponse->assertStatus(404);
    }

    public function test_late_attendance_displays_late_duration(): void
    {
        $admin = User::factory()->create();
        $role = Role::create(['name' => 'Super Admin', 'description' => 'Super Administrator']);
        $admin->roles()->attach($role->id);

        $store = Store::create([
            'name' => 'Z1 VAPE STORE VOL 2',
            'address' => 'Jl. Test',
            'phone' => '08123456789'
        ]);
        $admin->stores()->attach($store->id);

        $employee = User::factory()->create([
            'name' => 'Jovanus Putra Junior',
            'employee_number' => 'DPP.0917.099',
            'position' => 'Staff Toko'
        ]);
        $employee->stores()->attach($store->id);

        $shift = Shift::create([
            'name' => 'Shift Pagi (Vol 2)',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
            'is_active' => true,
        ]);

        // Clock in at 13:24:00 (4 hours 24 mins late from 09:00)
        Attendance::create([
            'user_id' => $employee->id,
            'store_id' => $store->id,
            'shift_id' => $shift->id,
            'date' => '2026-10-30',
            'clock_in' => Carbon::parse('2026-10-30 13:24:00'),
            'status' => 'late',
            'late_minutes' => 264,
            'note' => 'hadir',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('commercial.attendances.show', [
                'user' => $employee->id,
                'start_date' => '2026-10-30',
                'end_date' => '2026-10-30',
                'month' => 10,
                'year' => 2026
            ]));

        $response->assertStatus(200);
        $response->assertSee('Jovanus Putra Junior');
        $response->assertSee('LATE (4j 24m)');
        $response->assertSee('Terlambat (4j 24m)');
    }

    public function test_admin_can_manually_record_or_edit_employee_attendance(): void
    {
        $admin = User::factory()->create();
        $role = Role::create(['name' => 'Super Admin', 'description' => 'Super Administrator']);
        $admin->roles()->attach($role->id);

        $store = Store::create([
            'name' => 'Z1 VAPE STORE VOL 2',
            'address' => 'Jl. Test',
            'phone' => '08123456789'
        ]);
        $admin->stores()->attach($store->id);

        $employee = User::factory()->create([
            'name' => 'Muhammad Nashata Pratama Putra',
            'employee_number' => 'DPP.0917.100',
            'position' => 'Staff Toko'
        ]);
        $employee->stores()->attach($store->id);

        $shift = Shift::create([
            'name' => 'Shift Malam (Vol 2)',
            'start_time' => '16:00:00',
            'end_time' => '00:00:00',
            'late_tolerance_minutes' => 15,
            'is_overnight' => true,
            'is_active' => true,
        ]);

        // POST save-record for cross midnight shift (16:00 to 01:30 next day)
        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('commercial.attendances.save-record', $employee), [
                'date'               => '2026-11-01',
                'shift_id'           => $shift->id,
                'clock_in_time'      => '16:00',
                'clock_out_time'     => '01:30',
                'clock_out_next_day' => 1,
                'status'             => 'present',
                'note'               => 'Koreksi admin karena karyawan lupa clock out',
                'store_id'           => $store->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $att = Attendance::where('user_id', $employee->id)->first();
        $this->assertNotNull($att);
        $this->assertEquals($store->id, $att->store_id);
        $this->assertEquals($shift->id, $att->shift_id);
        $this->assertEquals('2026-11-01', Carbon::parse($att->date)->toDateString());
        $this->assertEquals('2026-11-01 16:00:00', Carbon::parse($att->clock_in)->toDateTimeString());
        $this->assertEquals('2026-11-02 01:30:00', Carbon::parse($att->clock_out)->toDateTimeString());
        $this->assertEquals(570, $att->work_minutes);
        $this->assertEquals(0, $att->late_minutes);
        // Selisih 90 menit dari jam 00:00 tetap dihitung kelipatan 1 jam penuh (60 menit)
        $this->assertEquals(60, $att->overtime_minutes);
        $this->assertEquals('present', $att->status);
        $this->assertEquals('Koreksi admin karena karyawan lupa clock out', $att->note);

        // Test kasus selisih 75 menit (clock out 01:15 dari shift selesai 00:00) -> tetap 1 jam (60 menit)
        $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('commercial.attendances.save-record', $employee), [
                'date'               => '2026-11-01',
                'shift_id'           => $shift->id,
                'clock_in_time'      => '16:00',
                'clock_out_time'     => '01:15',
                'clock_out_next_day' => 1,
                'status'             => 'present',
                'note'               => 'Pulang 01:15',
            ]);

        $att->refresh();
        $this->assertEquals(555, $att->work_minutes);
        $this->assertEquals(60, $att->overtime_minutes); // 75 mins diff -> tetap 1 jam (60 menit) lembur
    }

    public function test_user_can_access_human_resources_hub_with_six_submenus(): void
    {
        $admin = User::factory()->create();
        $role = Role::create(['name' => 'Super Admin', 'description' => 'Super Administrator']);
        $admin->roles()->attach($role->id);

        $store = Store::create([
            'name' => 'Store 1',
            'address' => 'Jl. Test',
            'phone' => '08123456789'
        ]);
        $admin->stores()->attach($store->id);

        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('commercial.hr'));

        $response->assertRedirect(route('commercial.hr.dashboard'));

        $pageResponse = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('commercial.attendances.index'));

        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Human Resources');
        $pageResponse->assertSee('Dashboard HR');
        $pageResponse->assertSee('Daftar Kehadiran');
        $pageResponse->assertSee('Payroll Employee');
        $pageResponse->assertSee('Shift Kerja');
        $pageResponse->assertSee('Status Kehadiran');
        $pageResponse->assertSee('Jadwal Kerja (Roster)');
        $pageResponse->assertSee('Shift Pattern');

        // Verify HR page displays left sidebar navigation without Kembali ke Portal
        $pageResponse->assertDontSee('Kembali ke Portal');
        $pageResponse->assertSee('Presensi & Penggajian', false);
        $pageResponse->assertSee('Manajemen Shift & Roster', false);
        // Verify Store Switcher & Notification Bell are hidden in HR
        $pageResponse->assertDontSee('notifications');
        // Verify My Profile dropdown in HR only has My Profile (no Z1 App, Master Data, Reports)
        $pageResponse->assertSee('My Profile');
        $pageResponse->assertDontSee('Z1 App');
        $pageResponse->assertDontSee('Master Data');
        $pageResponse->assertDontSee('Reports');
        $pageResponse->assertDontSee('title="Buka Workspace Portal (Human Resource, Security CCTV, Store Management)"', false);

        // Verify portal card 1 links to HR dashboard
        $portalResponse = $this->actingAs($admin)->get(route('portal'));
        $portalResponse->assertStatus(200);
        $portalResponse->assertSee(route('commercial.hr.dashboard'));

        // Verify Store Management dashboard does NOT render the old Commercial dropdown
        $dashResponse = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('dashboard'));

        $dashResponse->assertStatus(200);
        $dashResponse->assertDontSee('data-dropdown-trigger="dropdown-commercial"', false);
        $dashResponse->assertSee('Z1 VapeOS');
        $dashResponse->assertDontSee('title="Buka Workspace Portal (Human Resource, Security CCTV, Store Management)"', false);
    }
}


