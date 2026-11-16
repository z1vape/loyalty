<?php
namespace App\Policies;
use App\Models\{Product,User};
class ProductPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermission('Inventory List') || $user->hasRole('Super Admin'); }
    public function view(User $user, Product $product): bool { return $this->sameStore($user, $product) && $this->viewAny($user); }
    public function create(User $user): bool { return $user->hasPermission('Inventory Create') || $user->hasRole('Super Admin'); }
    public function update(User $user, Product $product): bool { return $this->sameStore($user,$product) && ($user->hasPermission('Inventory Update') || $user->hasPermission('Inventory Edit') || $user->hasRole('Super Admin')); }
    public function delete(User $user, Product $product): bool { return $this->sameStore($user,$product) && ($user->hasPermission('Inventory Destroy') || $user->hasRole('Super Admin')); }
    private function sameStore(User $user, Product $product): bool { return (int)$product->store_id === (int)session('active_store_id') && ($user->stores()->whereKey($product->store_id)->exists() || $user->hasRole('Super Admin')); }
}
