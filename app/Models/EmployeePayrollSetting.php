<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePayrollSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'basic_salary',
        'daily_allowance',
        'overtime_rate_per_hour',
        'holiday_overtime_rate_per_hour',
        'late_deduction_per_day',
        'late_tolerance_minutes',
        'late_deduction_per_minute',
        'payment_type',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'notes',
    ];

    protected $casts = [
        'basic_salary'                   => 'decimal:2',
        'daily_allowance'                => 'decimal:2',
        'overtime_rate_per_hour'         => 'decimal:2',
        'holiday_overtime_rate_per_hour' => 'decimal:2',
        'late_deduction_per_day'         => 'decimal:2',
        'late_tolerance_minutes'         => 'integer',
        'late_deduction_per_minute'      => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
