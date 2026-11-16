<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_code',
        'user_id',
        'store_id',
        'period_month',
        'period_year',
        'start_date',
        'end_date',
        'payment_type',
        'basic_salary',
        'total_days_worked',
        'daily_allowance_rate',
        'total_daily_allowance',
        'regular_overtime_minutes',
        'regular_overtime_rate',
        'regular_overtime_pay',
        'holiday_overtime_minutes',
        'holiday_overtime_rate',
        'holiday_overtime_pay',
        'late_days',
        'late_deduction_rate',
        'late_minutes',
        'late_deduction',
        'bonus',
        'other_deductions',
        'net_salary',
        'status',
        'paid_at',
        'note',
        'created_by',
    ];

    protected $casts = [
        'period_month'             => 'integer',
        'period_year'              => 'integer',
        'start_date'               => 'date',
        'end_date'                 => 'date',
        'basic_salary'             => 'decimal:2',
        'total_days_worked'        => 'integer',
        'daily_allowance_rate'     => 'decimal:2',
        'total_daily_allowance'    => 'decimal:2',
        'regular_overtime_minutes' => 'integer',
        'regular_overtime_rate'    => 'decimal:2',
        'regular_overtime_pay'     => 'decimal:2',
        'holiday_overtime_minutes' => 'integer',
        'holiday_overtime_rate'    => 'decimal:2',
        'holiday_overtime_pay'     => 'decimal:2',
        'late_days'                => 'integer',
        'late_deduction_rate'      => 'decimal:2',
        'late_minutes'             => 'integer',
        'late_deduction'           => 'decimal:2',
        'bonus'                    => 'decimal:2',
        'other_deductions'         => 'decimal:2',
        'net_salary'               => 'decimal:2',
        'paid_at'                  => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRegularOvertimeHoursAttribute(): float
    {
        return round($this->regular_overtime_minutes / 60, 2);
    }

    public function getHolidayOvertimeHoursAttribute(): float
    {
        return round($this->holiday_overtime_minutes / 60, 2);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'paid'     => ['label' => 'Terbayar', 'bg' => 'bg-emerald-500/10 text-emerald-800 border-emerald-500/30'],
            'approved' => ['label' => 'Disetujui', 'bg' => 'bg-sky-500/10 text-sky-800 border-sky-500/30'],
            default    => ['label' => 'Draft', 'bg' => 'bg-amber-500/10 text-amber-800 border-amber-500/30'],
        };
    }
}
