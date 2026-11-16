<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveStore
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            // Exclude workspace portal, store selection, store switching, and logout from redirection loop
            if ($request->is('portal') || $request->is('select-store') || $request->is('switch-store') || $request->is('logout')) {
                return $next($request);
            }

            if (!session()->has('active_store_id')) {
                $user = Auth::user();
                $defaultStore = $user->hasRole('Super Admin') 
                    ? \App\Models\Store::first() 
                    : $user->stores()->first();

                if ($defaultStore) {
                    session([
                        'active_store_id' => $defaultStore->id,
                        'active_store_name' => $defaultStore->name,
                    ]);
                } else {
                    return redirect()->route('portal');
                }
            }

            $user = Auth::user();
            $storeId = session('active_store_id');

            // Super Admin can access all stores; other users are restricted to their assigned stores
            if (!$user->hasRole('Super Admin') && !$user->stores->contains('id', $storeId)) {
                $fallback = $user->stores()->first();
                if ($fallback) {
                    session([
                        'active_store_id' => $fallback->id,
                        'active_store_name' => $fallback->name,
                    ]);
                } else {
                    session()->forget('active_store_id');
                    return redirect()->route('portal')->with('error', 'Anda tidak memiliki akses ke toko yang dipilih.');
                }
            }
        }

        return $next($request);
    }
}
