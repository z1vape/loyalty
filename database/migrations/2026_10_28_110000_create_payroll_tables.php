<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Employee Payroll Settings (Komponen Gaji Pokok, Tarif Lembur, dll per Karyawan)
        if (!Schema::hasTable('employee_payroll_settings')) {
            Schema::create('employee_payroll_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->decimal('basic_salary', 14, 2)->default(0); // Gaji Pokok
                $table->decimal('daily_allowance', 14, 2)->default(0); // Uang Makan / Kehadiran Harian
                $table->decimal('overtime_rate_per_hour', 14, 2)->default(0); // Tarif Lembur Reguler per Jam
                $table->decimal('holiday_overtime_rate_per_hour', 14, 2)->default(0); // Tarif Lembur Hari Libur (OFF) per Jam
                $table->decimal('late_deduction_per_minute', 14, 2)->default(0); // Potongan Terlambat per Menit
                $table->string('payment_type', 20)->default('monthly'); // monthly, daily, hourly
                $table->string('bank_name', 100)->nullable();
                $table->string('bank_account_number', 50)->nullable();
                $table->string('bank_account_holder', 100)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. Payrolls Records (Rekap Gaji & Slip Gaji Bulanan)
        if (!Schema::hasTable('payrolls')) {
            Schema::create('payrolls', function (Blueprint $table) {
                $table->id();
                $table->string('payroll_code', 30)->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
                $table->integer('period_month');
                $table->integer('period_year');
                $table->date('start_date');
                $table->date('end_date');
                $table->string('payment_type', 20)->default('monthly');
                
                // Pendapatan
                $table->decimal('basic_salary', 14, 2)->default(0);
                $table->integer('total_days_worked')->default(0);
                $table->decimal('daily_allowance_rate', 14, 2)->default(0);
                $table->decimal('total_daily_allowance', 14, 2)->default(0);
                
                // Lembur Reguler
                $table->integer('regular_overtime_minutes')->default(0);
                $table->decimal('regular_overtime_rate', 14, 2)->default(0);
                $table->decimal('regular_overtime_pay', 14, 2)->default(0);

                // Lembur Hari Libur (OFF)
                $table->integer('holiday_overtime_minutes')->default(0);
                $table->decimal('holiday_overtime_rate', 14, 2)->default(0);
                $table->decimal('holiday_overtime_pay', 14, 2)->default(0);

                // Potongan & Penyesuaian
                $table->integer('late_minutes')->default(0);
                $table->decimal('late_deduction', 14, 2)->default(0);
                $table->decimal('bonus', 14, 2)->default(0);
                $table->decimal('other_deductions', 14, 2)->default(0);

                // Total Bersih (Take Home Pay)
                $table->decimal('net_salary', 14, 2)->default(0);

                // Status & Metadata
                $table->string('status', 20)->default('draft'); // draft, approved, paid
                $table->timestamp('paid_at')->nullable();
                $table->text('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'period_month', 'period_year']);
                $table->index(['store_id', 'period_month', 'period_year']);
            });
        }

        // 3. Permissions untuk Payroll Employee
        $payrollPermissions = [
            [
                'name'        => 'Payroll List',
                'category'    => 'Commercial',
                'group_name'  => 'PAYROLL EMPLOYEE',
                'description' => 'Kemampuan melihat daftar slip gaji dan rekap payroll karyawan',
            ],
            [
                'name'        => 'Payroll Manage',
                'category'    => 'Commercial',
                'group_name'  => 'PAYROLL EMPLOYEE',
                'description' => 'Kemampuan generate, update status, dan mengelola penggajian karyawan',
            ],
            [
                'name'        => 'Payroll Setting',
                'category'    => 'Commercial',
                'group_name'  => 'PAYROLL EMPLOYEE',
                'description' => 'Kemampuan mengatur komponen gaji pokok, tarif lembur, dan tunjangan karyawan',
            ],
        ];

        foreach ($payrollPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }

        // Berikan izin ke Super Admin role
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $permIds = Permission::whereIn('name', ['Payroll List', 'Payroll Manage', 'Payroll Setting'])->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($permIds);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('employee_payroll_settings');
        Permission::whereIn('name', ['Payroll List', 'Payroll Manage', 'Payroll Setting'])->delete();
    }
};
