<?php

namespace Tests\Feature;

use App\Models\AttendanceStatus;
use App\Models\Role;
use App\Models\Shift;
use App\Models\ShiftPattern;
use App\Models\Store;
use App\Models\User;
use App\Models\UserSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialShiftAndScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $role = Role::create(['name' => 'Super Admin', 'description' => 'Super Administrator']);
        $this->admin->roles()->attach($role->id);

        $this->store = Store::create([
            'name' => 'Diton Central Store',
            'address' => 'Jl. Merdeka No. 10',
            'phone' => '08123456789'
        ]);
        $this->admin->stores()->attach($this->store->id);
    }

    public function test_admin_can_crud_shifts(): void
    {
        // 1. Index
        $response = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('commercial.shifts.index'));
        $response->assertStatus(200);
        $response->assertSee('Master Data Shift');

        // 2. Store Shift
        $storeResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('commercial.shifts.store'), [
                'name' => 'Shift Pagi Khusus',
                'code' => 'SPK',
                'start_time' => '07:30',
                'end_time' => '16:30',
                'late_tolerance_minutes' => 15,
                'color' => '#10b981',
                'description' => 'Shift operasional pagi',
                'is_active' => 1,
            ]);

        $storeResponse->assertRedirect(route('commercial.shifts.index'));
        $this->assertDatabaseHas('shifts', [
            'name' => 'Shift Pagi Khusus',
            'code' => 'SPK',
            'color' => '#10b981',
        ]);

        $shift = Shift::where('code', 'SPK')->first();

        // 3. Update Shift
        $updateResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->put(route('commercial.shifts.update', $shift), [
                'name' => 'Shift Pagi Regular',
                'code' => 'SPR',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'late_tolerance_minutes' => 10,
                'color' => '#059669',
                'description' => 'Updated shift',
                'is_active' => 1,
            ]);

        $updateResponse->assertRedirect(route('commercial.shifts.index'));
        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'name' => 'Shift Pagi Regular',
            'code' => 'SPR',
        ]);

        // 4. Toggle Active
        $toggleResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('commercial.shifts.toggle', $shift));

        $toggleResponse->assertRedirect();
        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_crud_attendance_statuses(): void
    {
        // 1. Index
        $response = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('commercial.attendance-statuses.index'));
        $response->assertStatus(200);
        $response->assertSee('Attendance Status');

        // 2. Store Status
        $storeResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('commercial.attendance-statuses.store'), [
                'code' => 'TRN',
                'name' => 'Training Luar Kota',
                'type' => 'leave',
                'badge_color' => 'purple',
                'counts_as_workday' => 1,
                'requires_attachment' => 1,
                'is_active' => 1,
            ]);

        $storeResponse->assertRedirect(route('commercial.attendance-statuses.index'));
        $this->assertDatabaseHas('attendance_statuses', [
            'code' => 'TRN',
            'name' => 'Training Luar Kota',
            'counts_as_workday' => 1,
        ]);

        $status = AttendanceStatus::where('code', 'TRN')->first();

        // 3. Update Status
        $updateResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->put(route('commercial.attendance-statuses.update', $status), [
                'code' => 'TRN',
                'name' => 'Dinas Pelatihan',
                'type' => 'leave',
                'badge_color' => 'indigo',
                'counts_as_workday' => 1,
                'requires_attachment' => 0,
                'is_active' => 1,
            ]);

        $updateResponse->assertRedirect(route('commercial.attendance-statuses.index'));
        $this->assertDatabaseHas('attendance_statuses', [
            'id' => $status->id,
            'name' => 'Dinas Pelatihan',
        ]);

        // 4. Cannot delete system-protected status
        $systemStatus = AttendanceStatus::create([
            'code' => 'SYS',
            'name' => 'System Status',
            'type' => 'present',
            'badge_color' => 'slate',
            'is_system' => true,
        ]);

        $deleteResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->delete(route('commercial.attendance-statuses.destroy', $systemStatus));

        $deleteResponse->assertSessionHas('error');
        $this->assertDatabaseHas('attendance_statuses', ['id' => $systemStatus->id]);
    }

    public function test_admin_can_create_shift_pattern_with_cycles(): void
    {
        $shiftPagi = Shift::create([
            'name' => 'Pagi',
            'code' => 'PG',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'color' => '#3b82f6',
            'is_active' => true,
        ]);

        // Create Pattern 5 Kerja 2 Libur
        $response = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('commercial.shift-patterns.store'), [
                'name' => 'Pola 5-2 Office',
                'cycle_days' => 7,
                'description' => 'Senin - Jumat kerja, Sabtu - Minggu libur',
                'days' => [
                    1 => ['shift_id' => $shiftPagi->id, 'is_off' => 0],
                    2 => ['shift_id' => $shiftPagi->id, 'is_off' => 0],
                    3 => ['shift_id' => $shiftPagi->id, 'is_off' => 0],
                    4 => ['shift_id' => $shiftPagi->id, 'is_off' => 0],
                    5 => ['shift_id' => $shiftPagi->id, 'is_off' => 0],
                    6 => ['shift_id' => null, 'is_off' => 1],
                    7 => ['shift_id' => null, 'is_off' => 1],
                ],
            ]);

        $response->assertRedirect(route('commercial.shift-patterns.index'));
        $this->assertDatabaseHas('shift_patterns', [
            'name' => 'Pola 5-2 Office',
            'cycle_days' => 7,
        ]);

        $pattern = ShiftPattern::where('name', 'Pola 5-2 Office')->first();
        $this->assertCount(7, $pattern->items);
        $this->assertTrue((bool)$pattern->items->where('day_number', 6)->first()->is_off);
        $this->assertEquals($shiftPagi->id, $pattern->items->where('day_number', 1)->first()->shift_id);
    }

    public function test_admin_can_view_schedule_matrix_and_update_cells(): void
    {
        $employee = User::factory()->create([
            'name' => 'Budi Santoso',
            'employee_number' => 'EMP001'
        ]);
        $employee->stores()->attach($this->store->id);

        $shift = Shift::create([
            'name' => 'Shift Siang',
            'code' => 'SS',
            'start_time' => '13:00',
            'end_time' => '21:00',
            'color' => '#f59e0b',
            'is_active' => true,
        ]);

        // 1. View Matrix Index
        $response = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('commercial.schedules.index', [
                'month' => 10,
                'year' => 2026,
            ]));

        $response->assertStatus(200);
        $response->assertSee('User Schedule');
        $response->assertSee('Budi Santoso');

        // 2. Quick update single cell
        $cellResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('commercial.schedules.store'), [
                'user_id' => $employee->id,
                'date' => '2026-10-15',
                'shift_id' => $shift->id,
                'is_off' => 0,
                'note' => 'Tukar shift',
            ]);

        $cellResponse->assertRedirect();
        $this->assertDatabaseHas('user_schedules', [
            'user_id' => $employee->id,
            'date' => '2026-10-15',
            'shift_id' => $shift->id,
            'note' => 'Tukar shift',
        ]);

        // 3. Reset cell to OFF via is_off parameter
        $offResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('commercial.schedules.store'), [
                'user_id' => $employee->id,
                'date' => '2026-10-15',
                'is_off' => 1,
            ]);

        $offResponse->assertRedirect();
        $this->assertDatabaseHas('user_schedules', [
            'user_id' => $employee->id,
            'date' => '2026-10-15',
            'shift_id' => null,
            'is_off' => 1,
        ]);

        // 4. Update cell to OFF via shift_id="OFF" radio button input
        $radioOffResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('commercial.schedules.store'), [
                'user_id' => $employee->id,
                'date' => '2026-10-16',
                'shift_id' => 'OFF',
                'note' => 'Libur jadwal OFF mingguan',
            ]);

        $radioOffResponse->assertRedirect();
        $this->assertDatabaseHas('user_schedules', [
            'user_id' => $employee->id,
            'date' => '2026-10-16',
            'shift_id' => null,
            'is_off' => 1,
            'note' => 'Libur jadwal OFF mingguan',
        ]);
    }

    public function test_bulk_schedule_generator_applies_pattern_to_users(): void
    {
        $employee1 = User::factory()->create(['name' => 'Emp 1']);
        $employee2 = User::factory()->create(['name' => 'Emp 2']);
        $employee1->stores()->attach($this->store->id);
        $employee2->stores()->attach($this->store->id);

        $shift = Shift::create([
            'name' => 'Pagi',
            'code' => 'PG',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'color' => '#3b82f6',
            'is_active' => true,
        ]);

        $pattern = ShiftPattern::create([
            'name' => 'Pola 2 Hari',
            'cycle_days' => 2,
            'is_active' => true,
        ]);

        $pattern->items()->create(['day_number' => 1, 'shift_id' => $shift->id, 'is_off' => false]);
        $pattern->items()->create(['day_number' => 2, 'shift_id' => null, 'is_off' => true]);

        // Bulk generate for 4 days: 2026-10-01 to 2026-10-04
        $bulkResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('commercial.schedules.generate'), [
                'user_ids' => [$employee1->id, $employee2->id],
                'shift_pattern_id' => $pattern->id,
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-04',
            ]);

        $bulkResponse->assertRedirect();
        $bulkResponse->assertSessionHas('success');

        // 2 users * 4 days = 8 records
        $this->assertDatabaseCount('user_schedules', 8);

        // Day 1 (Oct 1) -> shift
        $this->assertDatabaseHas('user_schedules', [
            'user_id' => $employee1->id,
            'date' => '2026-10-01',
            'shift_id' => $shift->id,
            'is_off' => 0,
        ]);

        // Day 2 (Oct 2) -> OFF
        $this->assertDatabaseHas('user_schedules', [
            'user_id' => $employee1->id,
            'date' => '2026-10-02',
            'shift_id' => null,
            'is_off' => 1,
        ]);

        // Day 3 (Oct 3) -> shift
        $this->assertDatabaseHas('user_schedules', [
            'user_id' => $employee2->id,
            'date' => '2026-10-03',
            'shift_id' => $shift->id,
            'is_off' => 0,
        ]);

        // Day 4 (Oct 4) -> OFF
        $this->assertDatabaseHas('user_schedules', [
            'user_id' => $employee2->id,
            'date' => '2026-10-04',
            'shift_id' => null,
            'is_off' => 1,
        ]);
    }

    public function test_z1_app_portal_shows_today_scheduled_shift(): void
    {
        $employee = User::factory()->create(['name' => 'Siti Nurhaliza']);
        $employee->stores()->attach($this->store->id);

        $staffRole = Role::firstOrCreate(['name' => 'Staff', 'description' => 'Staff Toko']);
        $z1Perms = \App\Models\Permission::whereIn('name', ['Z1 App Access', 'Z1 App Attendance'])->get();
        $staffRole->permissions()->syncWithoutDetaching($z1Perms->pluck('id'));
        $employee->roles()->attach($staffRole->id);

        $shift = Shift::create([
            'name' => 'Shift Pagi Diton',
            'code' => 'SPD',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'color' => '#10b981',
            'is_active' => true,
        ]);

        $today = Carbon::today();
        UserSchedule::create([
            'user_id' => $employee->id,
            'date' => $today->toDateString(),
            'shift_id' => $shift->id,
            'is_off' => false,
        ]);

        $response = $this->actingAs($employee)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('z1-app.index', ['tab' => 'attendance']));

        $response->assertStatus(200);
        $response->assertSee('Shift Kerja:');
        $response->assertSee('Shift Pagi Diton');
    }

    public function test_admin_can_view_user_attendance_detail_ledger(): void
    {
        $employee = User::factory()->create(['name' => 'Budi Santoso']);
        $employee->stores()->attach($this->store->id);

        $shift = Shift::create([
            'name' => 'Shift Reguler',
            'code' => 'REG',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'color' => '#3b82f6',
            'is_active' => true,
        ]);

        UserSchedule::create([
            'user_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'shift_id' => $shift->id,
            'is_off' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('commercial.attendances.show', $employee));

        $response->assertStatus(200);
        $response->assertSee('Budi Santoso');
        $response->assertSee('Shift Reguler');
    }

    public function test_employee_scheduled_off_can_clock_in_without_shift_and_count_overtime(): void
    {
        $employee = User::factory()->create(['name' => 'Doni Libur']);
        $employee->stores()->attach($this->store->id);

        $staffRole = Role::firstOrCreate(['name' => 'Staff', 'description' => 'Staff Toko']);
        $z1Perms = \App\Models\Permission::whereIn('name', ['Z1 App Access', 'Z1 App Attendance'])->get();
        $staffRole->permissions()->syncWithoutDetaching($z1Perms->pluck('id'));
        $employee->roles()->attach($staffRole->id);

        $today = Carbon::today();
        UserSchedule::create([
            'user_id' => $employee->id,
            'date' => $today->toDateString(),
            'shift_id' => null,
            'is_off' => true,
        ]);

        // 1. Portal shows OFF mode without shift selector
        $portalResponse = $this->actingAs($employee)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('z1-app.index', ['tab' => 'attendance']));

        $portalResponse->assertStatus(200);
        $portalResponse->assertSee('Jadwal Anda: Libur (OFF)');
        $portalResponse->assertSee('Mode Lembur Hari Libur');

        // 2. Employee clocks in with empty shift_id
        $dummyImage = 'data:image/png;base64,' . base64_encode('fake-image-binary-data-for-testing-clockin-selfie-1234567890');
        $clockInResponse = $this->actingAs($employee)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('z1-app.clock-in'), [
                'shift_id' => '',
                'photo' => $dummyImage,
                'note' => 'Masuk bantu shift',
            ]);

        $clockInResponse->assertRedirect();
        $attendance = \App\Models\Attendance::where('user_id', $employee->id)->first();
        $this->assertNotNull($attendance);
        $this->assertNull($attendance->shift_id);
        $this->assertEquals('present', $attendance->status);
        $this->assertEquals(0, $attendance->late_minutes);
    }

    public function test_shift_stores_and_updates_early_clockin_and_max_clockout_windows(): void
    {
        // 1. Create shift with custom window
        $response = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('commercial.shifts.store'), [
                'name'                        => 'Shift Khusus Malam',
                'code'                        => 'SKM',
                'start_time'                  => '16:00',
                'end_time'                    => '00:00',
                'late_tolerance_minutes'      => 15,
                'early_clock_in_minutes'      => 90,
                'max_clock_out_delay_minutes' => 300,
                'is_overnight'                => 1,
            ]);

        $response->assertRedirect(route('commercial.shifts.index'));
        $shift = Shift::where('code', 'SKM')->first();
        $this->assertNotNull($shift);
        $this->assertEquals(90, $shift->early_clock_in_minutes);
        $this->assertEquals(300, $shift->max_clock_out_delay_minutes);

        // 2. Update shift windows
        $updateResp = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->put(route('commercial.shifts.update', $shift), [
                'name'                        => 'Shift Khusus Malam Updated',
                'code'                        => 'SKM2',
                'start_time'                  => '17:00',
                'end_time'                    => '01:00',
                'late_tolerance_minutes'      => 20,
                'early_clock_in_minutes'      => 60,
                'max_clock_out_delay_minutes' => 180,
                'is_overnight'                => 1,
            ]);

        $updateResp->assertRedirect(route('commercial.shifts.index'));
        $shift->refresh();
        $this->assertEquals(60, $shift->early_clock_in_minutes);
        $this->assertEquals(180, $shift->max_clock_out_delay_minutes);
    }

    public function test_z1_app_enforces_early_clock_in_and_max_clock_out_delay_windows(): void
    {
        $employee = User::factory()->create(['name' => 'Bambang Jendela']);
        $employee->stores()->attach($this->store->id);

        $staffRole = Role::firstOrCreate(['name' => 'Staff', 'description' => 'Staff Toko']);
        $z1Perms = \App\Models\Permission::whereIn('name', ['Z1 App Access', 'Z1 App Attendance'])->get();
        $staffRole->permissions()->syncWithoutDetaching($z1Perms->pluck('id'));
        $employee->roles()->attach($staffRole->id);

        // Shift 09:00 - 17:00, early_clock_in = 120 (bisa masuk mulai 07:00)
        $shift = Shift::create([
            'name'                        => 'Shift Pagi Kantor',
            'code'                        => 'SPK',
            'start_time'                  => '09:00:00',
            'end_time'                    => '17:00:00',
            'late_tolerance_minutes'      => 15,
            'early_clock_in_minutes'      => 120,
            'max_clock_out_delay_minutes' => 240, // max clock out sampai 21:00
            'is_active'                   => true,
        ]);

        $today = Carbon::today();
        UserSchedule::create([
            'user_id'  => $employee->id,
            'date'     => $today->toDateString(),
            'shift_id' => $shift->id,
            'is_off'   => false,
        ]);

        // A. Jam 06:30 (terlalu awal, sebelum 07:00) -> Clock in ditolak
        Carbon::setTestNow($today->copy()->setTime(6, 30));
        $dummyImage = 'data:image/png;base64,' . base64_encode('fake-image-selfie-12345');

        $tooEarlyResp = $this->actingAs($employee)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('z1-app.clock-in'), [
                'shift_id' => $shift->id,
                'photo'    => $dummyImage,
            ]);

        $tooEarlyResp->assertRedirect();
        $tooEarlyResp->assertSessionHas('error');
        $this->assertDatabaseMissing('attendances', ['user_id' => $employee->id]);

        // B. Jam 07:15 (dalam jendela 07:00 - 09:00) -> Clock in berhasil
        Carbon::setTestNow($today->copy()->setTime(7, 15));
        $okClockInResp = $this->actingAs($employee)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('z1-app.clock-in'), [
                'shift_id' => $shift->id,
                'photo'    => $dummyImage,
            ]);

        $okClockInResp->assertRedirect();
        $okClockInResp->assertSessionHas('success');
        $this->assertDatabaseHas('attendances', ['user_id' => $employee->id]);

        // C. Jam 22:00 (melewati batas max clock out 21:00) -> Clock out ditolak
        Carbon::setTestNow($today->copy()->setTime(22, 00));
        $tooLateOutResp = $this->actingAs($employee)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('z1-app.clock-out'));

        $tooLateOutResp->assertRedirect();
        $tooLateOutResp->assertSessionHas('error');

        Carbon::setTestNow(); // reset test clock
    }
}
