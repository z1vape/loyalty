<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'store_id', 'code', 'name', 'description', 'start_time', 'end_time',
        'late_tolerance_minutes', 'early_clock_in_minutes', 'max_clock_out_delay_minutes',
        'is_overnight', 'is_active', 'color',
    ];

    protected $casts = [
        'late_tolerance_minutes'      => 'integer',
        'early_clock_in_minutes'      => 'integer',
        'max_clock_out_delay_minutes' => 'integer',
        'is_overnight'                => 'boolean',
        'is_active'                   => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function schedules()
    {
        return $this->hasMany(UserSchedule::class);
    }

    public function patternItems()
    {
        return $this->hasMany(ShiftPatternItem::class);
    }

    /**
     * Formatted display label (e.g. "Shift Pagi (08:00 - 17:00)")
     */
    public function getLabelAttribute(): string
    {
        $start = Carbon::parse($this->start_time)->format('H:i');
        $end = Carbon::parse($this->end_time)->format('H:i');
        return "{$this->name} ({$start} - {$end})";
    }

    /**
     * Formatted time range (e.g. "08:00 - 17:00")
     */
    public function getTimeRangeAttribute(): string
    {
        $start = Carbon::parse($this->start_time)->format('H:i');
        $end = Carbon::parse($this->end_time)->format('H:i');
        return "{$start} - {$end}";
    }

    /**
     * Approximate work duration string (e.g. "9 jam")
     */
    public function getWorkDurationAttribute(): string
    {
        $start = Carbon::parse('2000-01-01 ' . $this->start_time);
        $end = Carbon::parse('2000-01-01 ' . $this->end_time);
        if ($this->is_overnight || $end->lte($start)) {
            $end->addDay();
        }
        $diffMinutes = $start->diffInMinutes($end);
        $h = intdiv($diffMinutes, 60);
        $m = $diffMinutes % 60;
        return $m > 0 ? "{$h}j {$m}m" : "{$h} Jam";
    }

    /**
     * Check if a given clock-in time is late.
     */
    public function isLate(Carbon $clockIn): bool
    {
        $shiftStart = Carbon::parse($clockIn->toDateString() . ' ' . $this->start_time);
        return $clockIn->gt($shiftStart->addMinutes($this->late_tolerance_minutes));
    }

    /**
     * Get late minutes for a given clock-in time.
     */
    public function lateMinutes(Carbon $clockIn): int
    {
        $shiftStart = Carbon::parse($clockIn->toDateString() . ' ' . $this->start_time);
        $deadline = $shiftStart->addMinutes($this->late_tolerance_minutes);
        return max(0, $clockIn->diffInMinutes($deadline, false) * -1);
    }

    /**
     * Get earliest allowed clock-in time for a given date.
     */
    public function getEarliestClockInTime(Carbon|string $date): Carbon
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $start = Carbon::parse($dateStr . ' ' . $this->start_time);
        return $start->subMinutes($this->early_clock_in_minutes ?? 120);
    }

    /**
     * Get latest allowed clock-out time for a given shift session date.
     */
    public function getLatestClockOutTime(Carbon|string $date): Carbon
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $start = Carbon::parse($dateStr . ' ' . $this->start_time);
        $end = Carbon::parse($dateStr . ' ' . $this->end_time);
        if ($this->is_overnight || $end->lte($start)) {
            $end->addDay();
        }
        return $end->addMinutes($this->max_clock_out_delay_minutes ?? 240);
    }

    /**
     * Check if a given time is allowed for clock-in (not too early).
     */
    public function isWithinClockInWindow(Carbon $time, Carbon|string $date): bool
    {
        return $time->gte($this->getEarliestClockInTime($date));
    }

    /**
     * Check if a given time is within allowed clock-out window (before max delay cutoff).
     */
    public function isWithinClockOutWindow(Carbon $time, Carbon|string $date): bool
    {
        return $time->lte($this->getLatestClockOutTime($date));
    }
}
