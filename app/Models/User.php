<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'password',
    'photo', 'gender', 'address',
    'employee_number', 'status', 'join_date', 'end_date', 'position',
    'username', 'mobile_phone'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The roles that belong to the user.
     */
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * The stores that belong to the user.
     */
    public function stores(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Store::class);
    }

    /**
     * The attendances that belong to the user.
     */
    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * The work schedules that belong to the user.
     */
    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserSchedule::class);
    }

    /**
     * The payroll setting for this user.
     */
    public function payrollSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployeePayrollSetting::class);
    }

    /**
     * The payroll records that belong to the user.
     */
    public function payrolls(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    /**
     * Check if user has a specific permission (via roles).
     */
    public function hasPermission(string $permission): bool
    {
        return $this->roles->flatMap->permissions->contains('name', $permission);
    }

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) {
            return null;
        }

        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            return $this->photo;
        }

        return url('storage/' . $this->photo);
    }
}
