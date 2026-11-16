<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\EmployeePayrollSetting;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Store;
use App\Models\User;
use App\Models\UserSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialPayrollTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['name' => 'Super Admin User']);
        $role = Role::create(['name' => 'Super Admin', 'description' => 'Super Administrator']);
        $this->admin->roles()->attach($role->id);

        $this->store = Store::create([
            'name' => 'Z1 Central Store',
            'address' => 'Jl. Boulevard No. 1',
            'phone' => '08123456789'
        ]);
        $this->admin->stores()->attach($this->store->id);
    }

    public function test_admin_can_access_payroll_index_and_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('commercial.payrolls.index'));

        $response->assertStatus(200);
        $response->assertSee('Payroll Employee');
        $response->assertSee('Generate Gaji');

        $settingsResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('commercial.payrolls.settings'));

        $settingsResponse->assertStatus(200);
        $settingsResponse->assertSee('Komponen Gaji Karyawan');
    }

    public function test_admin_can_update_employee_payroll_settings(): void
    {
        $employee = User::factory()->create(['name' => 'Rina Staff']);
        $employee->stores()->attach($this->store->id);

        $response = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('commercial.payrolls.settings.update'), [
                'user_id' => $employee->id,
                'basic_salary' => 3500000,
                'daily_allowance' => 25000,
                'overtime_rate_per_hour' => 20000,
                'holiday_overtime_rate_per_hour' => 35000,
                'late_deduction_per_minute' => 1000,
                'payment_type' => 'monthly',
                'bank_name' => 'BCA',
                'bank_account_number' => '1234567890',
                'bank_account_holder' => 'Rina Staff',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employee_payroll_settings', [
            'user_id' => $employee->id,
            'basic_salary' => 3500000,
            'holiday_overtime_rate_per_hour' => 35000,
            'bank_name' => 'BCA',
        ]);
    }

    public function test_admin_can_generate_payroll_calculating_holiday_overtime(): void
    {
        $employee = User::factory()->create(['name' => 'Anton Kasir']);
        $employee->stores()->attach($this->store->id);

        EmployeePayrollSetting::create([
            'user_id' => $employee->id,
            'basic_salary' => 3000000,
            'daily_allowance' => 20000,
            'overtime_rate_per_hour' => 15000,
            'holiday_overtime_rate_per_hour' => 30000,
            'late_deduction_per_minute' => 500,
            'payment_type' => 'monthly',
        ]);

        $periodMonth = 10;
        $periodYear = 2026;

        // Day 1: Regular shift, worked 8 hours (on time)
        $shift = Shift::create([
            'name' => 'Pagi',
            'code' => 'PAGI',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        Attendance::create([
            'user_id' => $employee->id,
            'store_id' => $this->store->id,
            'date' => '2026-10-05',
            'shift_id' => $shift->id,
            'clock_in' => '2026-10-05 08:00:00',
            'clock_out' => '2026-10-05 17:00:00',
            'work_minutes' => 540,
            'late_minutes' => 0,
            'overtime_minutes' => 0,
            'status' => 'present',
        ]);

        // Day 2: Scheduled OFF, but employee worked 4 hours (240 minutes)
        UserSchedule::create([
            'user_id' => $employee->id,
            'date' => '2026-10-10',
            'is_off' => true,
        ]);

        Attendance::create([
            'user_id' => $employee->id,
            'store_id' => $this->store->id,
            'date' => '2026-10-10',
            'shift_id' => null,
            'clock_in' => '2026-10-10 10:00:00',
            'clock_out' => '2026-10-10 14:00:00',
            'work_minutes' => 240,
            'late_minutes' => 0,
            'overtime_minutes' => 240,
            'status' => 'present',
        ]);

        // Generate Payroll
        $generateResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('commercial.payrolls.generate'), [
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'user_ids' => [$employee->id],
                'store_id' => $this->store->id,
            ]);

        $generateResponse->assertRedirect();

        // Check generated payroll:
        // Basic: 3,000,000
        // Days worked: 2 days * 20,000 = 40,000
        // Holiday Overtime: 4 hours * 30,000 = 120,000
        // Net: 3,000,000 + 40,000 + 120,000 = 3,160,000
        $payroll = Payroll::where('user_id', $employee->id)
            ->where('period_month', $periodMonth)
            ->first();

        $this->assertNotNull($payroll);
        $this->assertEquals(2, $payroll->total_days_worked);
        $this->assertEquals(40000, $payroll->total_daily_allowance);
        $this->assertEquals(240, $payroll->holiday_overtime_minutes);
        $this->assertEquals(120000, $payroll->holiday_overtime_pay);
        $this->assertEquals(3160000, $payroll->net_salary);

        // Check printable show view
        $showResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('commercial.payrolls.show', $payroll));

        $showResponse->assertStatus(200);
        $showResponse->assertSee('SLIP GAJI KARYAWAN');
        $showResponse->assertSee('Anton Kasir');
        $showResponse->assertSee('3.160.000');
    }

    public function test_daily_flat_late_deduction_applied_when_clock_in_exceeds_tolerance(): void
    {
        $employee = User::factory()->create(['name' => 'Budi Staff']);
        $employee->stores()->attach($this->store->id);

        EmployeePayrollSetting::create([
            'user_id' => $employee->id,
            'basic_salary' => 2000000,
            'daily_allowance' => 0,
            'late_deduction_per_day' => 20000,
            'late_tolerance_minutes' => 15,
            'payment_type' => 'monthly',
        ]);

        $shift = Shift::create([
            'name' => 'Pagi 9',
            'code' => 'PAGI9',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'late_tolerance_minutes' => 15,
            'is_active' => true,
        ]);

        // Day 1: Clock in at 09:14 (Within 15 mins tolerance) -> NO deduction
        Attendance::create([
            'user_id' => $employee->id,
            'store_id' => $this->store->id,
            'date' => '2026-10-01',
            'shift_id' => $shift->id,
            'clock_in' => '2026-10-01 09:14:00',
            'clock_out' => '2026-10-01 18:00:00',
            'work_minutes' => 526,
            'late_minutes' => 0,
            'status' => 'present',
        ]);

        // Day 2: Clock in at 09:16 (Exceeds 15 mins tolerance) -> Kena potong 20.000 flat di hari itu
        Attendance::create([
            'user_id' => $employee->id,
            'store_id' => $this->store->id,
            'date' => '2026-10-02',
            'shift_id' => $shift->id,
            'clock_in' => '2026-10-02 09:16:00',
            'clock_out' => '2026-10-02 18:00:00',
            'work_minutes' => 524,
            'late_minutes' => 16,
            'status' => 'late',
        ]);

        // Generate Payroll
        $response = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('commercial.payrolls.generate'), [
                'period_month' => 10,
                'period_year' => 2026,
                'user_ids' => [$employee->id],
                'store_id' => $this->store->id,
            ]);

        $response->assertRedirect();

        $payroll = Payroll::where('user_id', $employee->id)->where('period_month', 10)->first();
        $this->assertNotNull($payroll);
        $this->assertEquals(1, $payroll->late_days);
        $this->assertEquals(20000, $payroll->late_deduction);
        $this->assertEquals(1980000, $payroll->net_salary); // 2,000,000 - 20,000
    }

    public function test_admin_can_filter_payroll_by_month_year_store_and_status(): void
    {
        $employeeA = User::factory()->create(['name' => 'Karyawan Toko A']);
        $employeeA->stores()->attach($this->store->id);

        $storeB = Store::create([
            'name' => 'Z1 Branch Store B',
            'address' => 'Jl. Cabang No. 2',
            'phone' => '08987654321',
        ]);
        $employeeB = User::factory()->create(['name' => 'Karyawan Toko B']);
        $employeeB->stores()->attach($storeB->id);

        // Payroll A: Store A, Month 10, Year 2026, Status 'draft'
        Payroll::create([
            'payroll_code' => 'PAY-202610-001',
            'user_id' => $employeeA->id,
            'store_id' => $this->store->id,
            'period_month' => 10,
            'period_year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_type' => 'monthly',
            'basic_salary' => 3000000,
            'net_salary' => 3000000,
            'status' => 'draft',
        ]);

        // Payroll B: Store B, Month 10, Year 2026, Status 'paid'
        Payroll::create([
            'payroll_code' => 'PAY-202610-002',
            'user_id' => $employeeB->id,
            'store_id' => $storeB->id,
            'period_month' => 10,
            'period_year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'payment_type' => 'monthly',
            'basic_salary' => 3500000,
            'net_salary' => 3500000,
            'status' => 'paid',
        ]);

        // Filter by status = 'draft'
        $responseDraft = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('commercial.payrolls.index', ['month' => 10, 'year' => 2026, 'status' => 'draft']));

        $responseDraft->assertStatus(200);
        $responseDraft->assertSee('PAY-202610-001');
        $responseDraft->assertDontSee('PAY-202610-002');

        // Filter by store = storeB
        $responseStoreB = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('commercial.payrolls.index', ['month' => 10, 'year' => 2026, 'store_id' => $storeB->id]));

        $responseStoreB->assertStatus(200);
        $responseStoreB->assertSee('PAY-202610-002');
        $responseStoreB->assertDontSee('PAY-202610-001');

        // Filter by different month = 11 (empty)
        $responseNov = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('commercial.payrolls.index', ['month' => 11, 'year' => 2026]));

        $responseNov->assertStatus(200);
        $responseNov->assertDontSee('PAY-202610-001');
        $responseNov->assertDontSee('PAY-202610-002');
    }
}
