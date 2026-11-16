<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $store_id
 * @property int|null $shift_id
 * @property \Illuminate\Support\Carbon|string|null $date
 * @property \Illuminate\Support\Carbon|null $clock_in
 * @property \Illuminate\Support\Carbon|null $clock_out
 * @property string|null $clock_in_photo
 * @property string|null $clock_out_photo
 * @property float|null $clock_in_lat
 * @property float|null $clock_in_lng
 * @property float|null $clock_out_lat
 * @property float|null $clock_out_lng
 * @property string|null $clock_in_address
 * @property string|null $clock_out_address
 * @property string $status
 * @property int $late_minutes
 * @property int $work_minutes
 * @property int $overtime_minutes
 * @property bool $face_verified
 * @property float $face_confidence
 * @property string|null $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder whereDate(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 */
class Attendance extends Model
{
    protected $fillable = [
        'user_id', 'store_id', 'shift_id', 'date',
        'clock_in', 'clock_out',
        'clock_in_photo', 'clock_out_photo',
        'clock_in_lat', 'clock_in_lng', 'clock_in_distance_meters',
        'clock_out_lat', 'clock_out_lng', 'clock_out_distance_meters',
        'clock_in_address', 'clock_out_address',
        'status', 'late_minutes', 'work_minutes', 'overtime_minutes',
        'face_verified', 'face_confidence', 'note',
    ];

    protected $casts = [
        'date'             => 'date',
        'clock_in'         => 'datetime',
        'clock_out'        => 'datetime',
        'face_verified'    => 'boolean',
        'face_confidence'  => 'decimal:2',
        'clock_in_lat'     => 'decimal:8',
        'clock_in_lng'     => 'decimal:8',
        'clock_out_lat'    => 'decimal:8',
        'clock_out_lng'    => 'decimal:8',
        'clock_in_distance_meters'  => 'integer',
        'clock_out_distance_meters' => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    // ─── Scopes ──────────────────────────────────────────────
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    // ─── Helpers ─────────────────────────────────────────────
    public function getWorkHoursAttribute(): string
    {
        if (!$this->work_minutes) return '—';
        $h = intdiv($this->work_minutes, 60);
        $m = $this->work_minutes % 60;
        return "{$h}j {$m}m";
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'present'      => 'Hadir',
            'late'         => 'Terlambat',
            'absent'       => 'Absen',
            'permission'   => 'Izin',
            'sick'         => 'Sakit',
            'annual_leave' => 'Cuti',
            'holiday'      => 'Libur',
            default        => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'present'      => 'green',
            'late'         => 'yellow',
            'absent'       => 'red',
            'permission'   => 'blue',
            'sick'         => 'orange',
            'annual_leave' => 'purple',
            'holiday'      => 'gray',
            default        => 'gray',
        };
    }

    public function getClockInPhotoUrlAttribute(): ?string
    {
        if (!$this->clock_in_photo) return null;
        return asset('storage/' . $this->clock_in_photo);
    }

    public function getClockOutPhotoUrlAttribute(): ?string
    {
        if (!$this->clock_out_photo) return null;
        return asset('storage/' . $this->clock_out_photo);
    }

    public function getLateDurationAttribute(): ?string
    {
        $minutes = (int) ($this->late_minutes ?? 0);
        if ($minutes <= 0 && ($this->status === 'late' || $this->clock_in)) {
            $shift = $this->shift;
            if ($shift && $this->clock_in) {
                $shiftStart = Carbon::parse(Carbon::parse($this->date)->toDateString() . ' ' . $shift->start_time);
                $clockIn = Carbon::parse($this->clock_in);
                if ($clockIn->gt($shiftStart)) {
                    $minutes = $shiftStart->diffInMinutes($clockIn);
                }
            }
        }

        if ($minutes <= 0) {
            return null;
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($h > 0 && $m > 0) {
            return "{$h}j {$m}m";
        } elseif ($h > 0) {
            return "{$h} Jam";
        } else {
            return "{$m} Menit";
        }
    }

    public function getLateDurationLongAttribute(): ?string
    {
        $minutes = (int) ($this->late_minutes ?? 0);
        if ($minutes <= 0 && ($this->status === 'late' || $this->clock_in)) {
            $shift = $this->shift;
            if ($shift && $this->clock_in) {
                $shiftStart = Carbon::parse(Carbon::parse($this->date)->toDateString() . ' ' . $shift->start_time);
                $clockIn = Carbon::parse($this->clock_in);
                if ($clockIn->gt($shiftStart)) {
                    $minutes = $shiftStart->diffInMinutes($clockIn);
                }
            }
        }

        if ($minutes <= 0) {
            return null;
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($h > 0 && $m > 0) {
            return "{$h} jam {$m} menit";
        } elseif ($h > 0) {
            return "{$h} jam";
        } else {
            return "{$m} menit";
        }
    }

    protected $appends = ['work_hours', 'status_label', 'status_color', 'clock_in_photo_url', 'clock_out_photo_url', 'late_duration', 'late_duration_long'];
}
