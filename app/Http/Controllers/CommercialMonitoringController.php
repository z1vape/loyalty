<?php

namespace App\Http\Controllers;

use App\Models\CctvCamera;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommercialMonitoringController extends Controller
{
    /**
     * Display the CCTV Monitoring Dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Active Store Resolution
        $selectedStoreId = $request->query('store_id');
        $activeStoreId = session('active_store_id') ?? $user->stores()->first()?->id ?? 1;

        if ($selectedStoreId === 'all') {
            $currentStoreId = null;
        } elseif ($selectedStoreId) {
            $currentStoreId = (int) $selectedStoreId;
        } else {
            $currentStoreId = $activeStoreId;
        }

        $stores = Store::orderBy('id')->get();
        $activeStore = $currentStoreId ? Store::find($currentStoreId) : null;

        // Query CCTV Cameras
        $camerasQuery = CctvCamera::with('store')->orderBy('order_index')->orderBy('id');

        if ($currentStoreId) {
            $camerasQuery->where(function ($q) use ($currentStoreId) {
                $q->where('store_id', $currentStoreId)->orWhereNull('store_id');
            });
        }

        $cameras = $camerasQuery->get();

        // Group cameras by store for multi-branch monitoring
        $camerasByStore = $cameras->groupBy(fn($c) => $c->store?->name ?? 'Semua Cabang');

        // Layout mode: 'grid-1', 'grid-2', 'grid-4', 'portal'
        $viewMode = $request->query('view_mode', 'grid-2');

        return view('commercial.monitoring.index', compact(
            'stores',
            'activeStore',
            'currentStoreId',
            'cameras',
            'camerasByStore',
            'viewMode'
        ));
    }

    /**
     * Store a new CCTV camera.
     */
    public function storeCamera(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'store_id'     => 'nullable|exists:stores,id',
            'location_tag' => 'nullable|string|max:50',
            'stream_type'  => 'required|in:webrtc,hls,rtsp,web_portal',
            'stream_url'   => 'nullable|string|max:500',
            'ip_address'   => 'nullable|string|max:100',
            'port'         => 'nullable|integer|min:1|max:65535',
            'channel'      => 'nullable|string|max:20',
            'username'     => 'nullable|string|max:100',
            'password'     => 'nullable|string|max:100',
            'notes'        => 'nullable|string|max:500',
        ]);

        $validated['port'] = $validated['port'] ?? 554;
        $validated['channel'] = $validated['channel'] ?? 'ch0';
        $validated['is_active'] = $request->boolean('is_active', true);

        CctvCamera::create($validated);

        return redirect()->back()->with('success', "Kamera CCTV '{$validated['name']}' berhasil ditambahkan ke monitoring.");
    }

    /**
     * Update an existing CCTV camera.
     */
    public function updateCamera(Request $request, $id)
    {
        $cctvCamera = CctvCamera::findOrFail($id);

        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'store_id'     => 'nullable|exists:stores,id',
            'location_tag' => 'nullable|string|max:50',
            'stream_type'  => 'required|in:webrtc,hls,rtsp,web_portal',
            'stream_url'   => 'nullable|string|max:500',
            'ip_address'   => 'nullable|string|max:100',
            'port'         => 'nullable|integer|min:1|max:65535',
            'channel'      => 'nullable|string|max:20',
            'username'     => 'nullable|string|max:100',
            'password'     => 'nullable|string|max:100',
            'notes'        => 'nullable|string|max:500',
        ]);

        $validated['port'] = $validated['port'] ?? 554;
        $validated['channel'] = $validated['channel'] ?? 'ch0';
        $validated['is_active'] = $request->boolean('is_active', true);

        $cctvCamera->update($validated);

        return redirect()->back()->with('success', "Pengaturan CCTV '{$cctvCamera->name}' berhasil diperbarui.");
    }

    /**
     * Delete a CCTV camera.
     */
    public function destroyCamera($id)
    {
        $cctvCamera = CctvCamera::findOrFail($id);
        $name = $cctvCamera->name;
        $cctvCamera->delete();

        return redirect()->back()->with('success', "Kamera CCTV '{$name}' telah dihapus.");
    }
}
