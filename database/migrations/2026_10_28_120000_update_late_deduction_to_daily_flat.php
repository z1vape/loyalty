<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update employee_payroll_settings
        Schema::table('employee_payroll_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_payroll_settings', 'late_deduction_per_day')) {
                $table->decimal('late_deduction_per_day', 14, 2)->default(0)->after('holiday_overtime_rate_per_hour');
            }
            if (!Schema::hasColumn('employee_payroll_settings', 'late_tolerance_minutes')) {
                $table->integer('late_tolerance_minutes')->default(15)->after('late_deduction_per_day');
            }
        });

        // 2. Update payrolls table
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'late_days')) {
                $table->integer('late_days')->default(0)->after('holiday_overtime_pay');
            }
            if (!Schema::hasColumn('payrolls', 'late_deduction_rate')) {
                $table->decimal('late_deduction_rate', 14, 2)->default(0)->after('late_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_payroll_settings', function (Blueprint $table) {
            $table->dropColumn(['late_deduction_per_day', 'late_tolerance_minutes']);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['late_days', 'late_deduction_rate']);
        });
    }
};
