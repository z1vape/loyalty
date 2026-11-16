<?php
namespace App\Policies;
use App\Models\{Sale,User};
class SalePolicy
{
    public function viewAny(User $user): bool { return $user->hasPermission('Sales List') || $user->hasRole('Super Admin'); }
    public function view(User $user, Sale $sale): bool { return $this->sameStore($sale) && ($user->hasPermission('Sales Show') || $this->viewAny($user)); }
    public function create(User $user): bool { return $user->hasPermission('Sales Create') || $user->hasRole('Super Admin'); }
    public function update(User $user, Sale $sale): bool { return $this->sameStore($sale) && ($user->hasPermission('Sales Edit') || $user->hasRole('Super Admin')); }
    public function delete(User $user, Sale $sale): bool { return $this->sameStore($sale) && ($user->hasPermission('Sales Destroy') || $user->hasRole('Super Admin')); }
    private function sameStore(Sale $sale): bool { return (int)$sale->store_id === (int)session('active_store_id'); }
}
