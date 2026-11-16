<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('Setting List');
        
        $type = $request->get('type', 'category');
        if (!in_array($type, ['category', 'variant', 'position', 'status', 'supplier'])) {
            $type = 'category';
        }

        $settings = Setting::where('type', $type)->orderBy('value')->get();

        $counts = Setting::selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
        
        return view('settings.index', compact('settings', 'type', 'counts'));
    }

    public function store(Request $request)
    {
        Gate::authorize('Setting Create');
        
        $request->validate([
            'type' => 'required|string|in:category,variant,position,status,supplier',
            'value' => 'required|string|max:255',
        ], [
            'value.required' => 'Nama opsi wajib diisi.',
            'value.max' => 'Nama opsi tidak boleh lebih dari 255 karakter.',
        ]);

        // Check if exists
        $exists = Setting::where('type', $request->type)->where('value', $request->value)->first();
        if ($exists) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'is_existing' => true,
                    'setting' => $exists,
                    'message' => 'Opsi dropdown ini sudah terdaftar.',
                ]);
            }
            return back()->withErrors(['value' => 'Opsi dropdown ini sudah terdaftar.']);
        }

        $setting = Setting::create([
            'type' => $request->type,
            'value' => $request->value,
        ]);

        $typeLabels = [
            'category' => 'Kategori',
            'variant' => 'Varian',
            'position' => 'Jabatan',
            'status' => 'Status Karyawan',
            'supplier' => 'Supplier / Distributor',
        ];
        $label = $typeLabels[$request->type] ?? ucfirst($request->type);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'setting' => $setting,
                'message' => "Opsi {$label} \"{$request->value}\" berhasil ditambahkan.",
            ]);
        }

        return back()->with('success', "Opsi {$label} \"{$request->value}\" berhasil ditambahkan.");
    }

    public function update(Request $request, Setting $setting)
    {
        Gate::authorize('Setting Create');
        
        $request->validate([
            'value' => 'required|string|max:255',
        ], [
            'value.required' => 'Nama opsi wajib diisi.',
            'value.max' => 'Nama opsi tidak boleh lebih dari 255 karakter.',
        ]);

        // Check duplicate
        $exists = Setting::where('type', $setting->type)
            ->where('value', $request->value)
            ->where('id', '!=', $setting->id)
            ->first();

        if ($exists) {
            return back()->withErrors(['value' => 'Opsi dropdown dengan nama tersebut sudah ada.']);
        }

        $oldValue = $setting->value;
        $setting->update([
            'value' => $request->value,
        ]);

        return back()->with('success', "Opsi \"{$oldValue}\" berhasil diperbarui menjadi \"{$request->value}\".");
    }

    public function destroy(Setting $setting)
    {
        Gate::authorize('Setting Destroy');
        
        $typeLabels = [
            'category' => 'Kategori',
            'variant' => 'Varian',
            'position' => 'Jabatan',
            'status' => 'Status Karyawan',
            'supplier' => 'Supplier / Distributor',
        ];
        $label = $typeLabels[$setting->type] ?? ucfirst($setting->type);
        $deletedValue = $setting->value;

        $setting->delete();

        return back()->with('success', "Opsi {$label} \"{$deletedValue}\" berhasil dihapus.");
    }
}

