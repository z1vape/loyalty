<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    /**
     * Show the store selection screen.
     */
    public function showSelectStore()
    {
        $user = Auth::user();

        // Super Admin gets access to all stores, others only get access to their assigned stores
        if ($user->hasRole('Super Admin')) {
            $stores = Store::all();
        } else {
            $stores = $user->stores;
        }

        // If user only has access to 1 store, set it as active and redirect directly to dashboard
        if ($stores->count() === 1) {
            $store = $stores->first();
            session(['active_store_id' => $store->id]);
            return redirect()->intended('/dashboard')->with('success', 'Selamat datang di ' . $store->name);
        }

        return view('auth.select-store', compact('stores'));
    }

    /**
     * Set the selected store as active in session.
     */
    public function selectStore(Request $request)
    {
        $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
        ]);

        $user = Auth::user();
        $storeId = $request->store_id;

        // Verify access (unless Super Admin)
        if (!$user->hasRole('Super Admin') && !$user->stores->contains('id', $storeId)) {
            return back()->withErrors(['store_id' => 'Anda tidak memiliki akses ke toko ini.']);
        }

        $store = Store::find($storeId);
        session(['active_store_id' => $store->id]);

        return redirect()->intended('/dashboard')->with('success', 'Membuka toko: ' . $store->name);
    }

    /**
     * Switch the active store.
     */
    public function switchStore(Request $request)
    {
        $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
        ]);

        $user = Auth::user();
        $storeId = $request->store_id;

        if (!$user->hasRole('Super Admin') && !$user->stores->contains('id', $storeId)) {
            return back()->with('error', 'Anda tidak memiliki akses ke toko tersebut.');
        }

        $store = Store::find($storeId);
        session(['active_store_id' => $store->id]);

        // Tetap di halaman sebelumnya (tidak dilempar ke dashboard)
        $referer = $request->headers->get('referer');
        if ($referer) {
            $parsedUrl = parse_url($referer);
            $path = $parsedUrl['path'] ?? '/';
            $queryParams = [];
            if (!empty($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
            }
            // Hapus filter store_id jika ada agar halaman langsung membaca session active_store_id yang baru
            unset($queryParams['store_id']);
            $newQuery = http_build_query($queryParams);
            $redirectUrl = $path . ($newQuery ? '?' . $newQuery : '');

            return redirect($redirectUrl)->with('success', 'Berhasil beralih ke toko: ' . $store->name);
        }

        return redirect()->back()->with('success', 'Berhasil beralih ke toko: ' . $store->name);
    }
}
