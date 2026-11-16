<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'action_key',
        'module_name',
        'status_from',
        'status_to',
        'label',
        'description',
        'role_ids',
        'is_active',
    ];

    protected $casts = [
        'role_ids' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Check if a user is authorized to perform approval for a specific module and target status.
     * Super Admin is always authorized by default as the root authority.
     */
    public static function canUserApprove(?User $user, string $module, string $statusTo = 'approved'): bool
    {
        if (!$user) {
            return false;
        }

        // Super Admin always has full bypass authority
        $isSuperAdmin = $user->id === 1
            || $user->hasRole('Super Admin')
            || $user->roles->contains(fn($r) => strtolower($r->name) === 'super admin');

        if ($isSuperAdmin) {
            return true;
        }

        $workflow = self::where('module', $module)
            ->where('status_to', $statusTo)
            ->where('is_active', true)
            ->first();

        // If workflow rule doesn't exist, fallback to Super Admin check
        if (!$workflow) {
            return false;
        }

        $allowedRoleIds = $workflow->role_ids ?? [];
        if (empty($allowedRoleIds)) {
            return false;
        }

        $userRoleIds = $user->roles->pluck('id')->map(fn($id) => (int) $id)->toArray();

        return !empty(array_intersect($allowedRoleIds, $userRoleIds));
    }

    /**
     * Get authorized roles models.
     */
    public function getAuthorizedRoles()
    {
        if (empty($this->role_ids)) {
            return collect();
        }

        return Role::whereIn('id', $this->role_ids)->get();
    }
}

