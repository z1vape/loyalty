<?php
namespace App\Policies;
use App\Models\{StockMovement,User};
class StockMovementPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermission('Movement List') || $user->hasRole('Super Admin'); }
    public function view(User $user, StockMovement $movement): bool { return (int)$movement->store_id === (int)session('active_store_id') && $this->viewAny($user); }
    public function create(User $user): bool { return $user->hasPermission('Stock In Store') || $user->hasPermission('Stock Out Store') || $user->hasRole('Super Admin'); }
    public function delete(User $user, StockMovement $movement): bool { return (int)$movement->store_id === (int)session('active_store_id') && ($user->hasPermission('Movement Destroy') || $user->hasPermission('Stock In Destroy') || $user->hasPermission('Stock Out Destroy') || $user->hasRole('Super Admin')); }
}
