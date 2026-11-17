<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\LoyaltyLog;
use App\Models\LoyaltyTier;
use App\Models\LoyaltyCategoryRule;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoyaltyCardController extends Controller
{
    /**
     * Cashier & Admin Portal: Main Loyalty Dashboard
     */
    public function index(Request $request)
    {
        $activeTab = $request->input('tab', session('active_tab', 'dashboard'));
        $search = trim($request->input('search', ''));

        $customersQuery = Customer::with(['loyaltyLogs' => function ($q) {
            $q->latest()->take(5);
        }]);

        if ($search) {
            $customersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%");
            });
            $activeTab = 'members';
        }

        $customers = $customersQuery->latest()->paginate(15)->withQueryString();

        // 1. Tiers with customer count
        $tiers = LoyaltyTier::withCount('customers')->orderBy('level')->get();

        // 2. Master Categories from Settings & Products
        $settingCategories = Setting::withoutGlobalScopes()
            ->where('type', 'category')
            ->pluck('value')
            ->map(fn($v) => trim($v))
            ->filter()
            ->values()
            ->toArray();

        $productCategories = Product::withoutGlobalScopes()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->map(fn($v) => trim($v))
            ->filter()
            ->values()
            ->toArray();

        $masterCategories = array_unique(array_merge($settingCategories, $productCategories));
        if (empty($masterCategories)) {
            $masterCategories = ['Aksesoris', 'Atomizer', 'Device', 'Freebase', 'OCC/Coil', 'Saltnic'];
        }

        // Clean up legacy placeholder categories that don't match actual store categories
        $placeholderCategories = [
            'Accessories',
            'Box Mod',
            'Coil & Cartridge',
            'E-Liquid Freebase',
            'E-Liquid SaltNic',
            'Pod System',
        ];
        LoyaltyCategoryRule::whereIn('category_name', $placeholderCategories)
            ->whereNotIn('category_name', $masterCategories)
            ->delete();

        // Ensure real master categories exist as rules
        foreach ($masterCategories as $catName) {
            LoyaltyCategoryRule::firstOrCreate(
                ['category_name' => $catName],
                [
                    'spend_per_point' => 10000,
                    'is_active' => true,
                ]
            );
        }

        $allCategories = array_values($masterCategories);
        sort($allCategories);

        $categoryRules = LoyaltyCategoryRule::whereIn('category_name', $allCategories)
            ->orderBy('category_name')
            ->get();

        // 4. Welcome bonus setting
        $welcomeBonusSetting = Setting::withoutGlobalScopes()->where('type', 'loyalty_welcome_bonus')->first();
        $welcomeBonus = $welcomeBonusSetting ? (int) $welcomeBonusSetting->value : 50;

        $totalCustomers = max(1, Customer::count());

        return view('loyalty.index', compact(
            'customers',
            'search',
            'activeTab',
            'tiers',
            'categoryRules',
            'allCategories',
            'welcomeBonus',
            'totalCustomers'
        ));
    }

    /**
     * Store a newly created Loyalty Tier
     */
    public function storeTier(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:loyalty_tiers,name',
            'level' => 'required|integer|min:1',
            'min_spend' => 'required|numeric|min:0',
            'point_multiplier' => 'required|numeric|min:0.1|max:10',
            'discount_nominal' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'point_value_rp' => 'required|integer|min:1',
            'badge_color' => 'nullable|string|max:20',
            'perks' => 'nullable|string|max:500',
        ]);

        $validated['discount_nominal'] = $request->input('discount_nominal', 0);
        $validated['discount_percent'] = $request->input('discount_percent', 0);
        $validated['badge_color'] = $validated['badge_color'] ?? 'slate';
        $validated['is_default'] = $request->has('is_default');

        if ($validated['is_default']) {
            LoyaltyTier::where('is_default', true)->update(['is_default' => false]);
        }

        LoyaltyTier::create($validated);

        return redirect()->route('loyalty.index', ['tab' => 'tiers'])
                         ->with('success', "Tier '{$validated['name']}' berhasil ditambahkan!");
    }

    /**
     * Update an existing Loyalty Tier
     */
    public function updateTier(Request $request, LoyaltyTier $tier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:loyalty_tiers,name,' . $tier->id,
            'level' => 'required|integer|min:1',
            'min_spend' => 'required|numeric|min:0',
            'point_multiplier' => 'required|numeric|min:0.1|max:10',
            'discount_nominal' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'point_value_rp' => 'required|integer|min:1',
            'badge_color' => 'nullable|string|max:20',
            'perks' => 'nullable|string|max:500',
        ]);

        $validated['discount_nominal'] = $request->input('discount_nominal', 0);
        $validated['discount_percent'] = $request->input('discount_percent', 0);
        $validated['badge_color'] = $validated['badge_color'] ?? 'slate';

        if ($request->has('is_default') && $request->input('is_default')) {
            LoyaltyTier::where('id', '!=', $tier->id)->update(['is_default' => false]);
            $validated['is_default'] = true;
        }

        $oldName = $tier->name;
        $tier->update($validated);

        // Sync customer records if tier name changed
        if ($oldName !== $validated['name']) {
            Customer::where('tier', $oldName)->update(['tier' => $validated['name']]);
        }

        return redirect()->route('loyalty.index', ['tab' => 'tiers'])
                         ->with('success', "Tier '{$validated['name']}' berhasil diperbarui!");
    }

    /**
     * Delete a Loyalty Tier
     */
    public function destroyTier(LoyaltyTier $tier)
    {
        if ($tier->is_default) {
            return back()->with('error', 'Tier default tidak dapat dihapus!');
        }

        $fallbackTier = LoyaltyTier::where('is_default', true)->first()
            ?? LoyaltyTier::where('id', '!=', $tier->id)->orderBy('level')->first();

        if ($fallbackTier) {
            Customer::where('tier', $tier->name)->update(['tier' => $fallbackTier->name]);
        }

        $name = $tier->name;
        $tier->delete();

        return redirect()->route('loyalty.index', ['tab' => 'tiers'])
                         ->with('success', "Tier '{$name}' berhasil dihapus! Member dialihkan ke {$fallbackTier?->name}.");
    }

    /**
     * Update Category Point Rules and Welcome Bonus
     */
    public function updateCategoryRules(Request $request)
    {
        $rules = $request->input('rules', []);
        foreach ($rules as $catName => $ruleData) {
            LoyaltyCategoryRule::updateOrCreate(
                ['category_name' => $catName],
                [
                    'points_per_item' => max(0, (int) ($ruleData['points_per_item'] ?? 1)),
                    'spend_per_point' => max(1000, (int) ($ruleData['spend_per_point'] ?? 10000)),
                    'is_active' => !empty($ruleData['is_active']),
                ]
            );
        }

        // Add new category rule if submitted
        if ($request->filled('new_category_name')) {
            LoyaltyCategoryRule::updateOrCreate(
                ['category_name' => trim($request->new_category_name)],
                [
                    'points_per_item' => max(0, (int) $request->input('new_points_per_item', 1)),
                    'spend_per_point' => max(1000, (int) $request->input('new_category_spend', 10000)),
                    'is_active' => true,
                ]
            );
        }

        // Update welcome bonus
        if ($request->has('welcome_bonus')) {
            Setting::updateOrCreate(
                ['type' => 'loyalty_welcome_bonus'],
                ['value' => (string) max(0, (int) $request->input('welcome_bonus', 50))]
            );
        }

        return redirect()->route('loyalty.index', ['tab' => 'tiers'])
                         ->with('success', 'Aturan perolehan poin per kategori barang berhasil diperbarui!');
    }

    /**
     * Public page: Display digital loyalty card for customer
     */
    public function show(string $cardToken)
    {
        $customer = Customer::with(['loyaltyLogs' => function ($q) {
            $q->latest()->take(10);
        }])->where('card_token', $cardToken)->firstOrFail();

        $tiers = LoyaltyTier::orderBy('level')->get();
        $currentTierModel = $customer->getTierModel();

        // Calculate next tier target
        $nextTierModel = null;
        if ($currentTierModel) {
            $nextTierModel = $tiers->firstWhere('level', '>', $currentTierModel->level);
        }

        $nextTier = $nextTierModel ? $nextTierModel->name : null;
        $amountToNextTier = 0;
        $tierProgressPercent = 100;

        if ($nextTierModel && (float) $nextTierModel->min_spend > 0) {
            $amountToNextTier = max(0, (float) $nextTierModel->min_spend - (float) $customer->total_spent);
            $tierProgressPercent = min(100, round(($customer->total_spent / (float) $nextTierModel->min_spend) * 100));
        }

        return view('loyalty.card', compact('customer', 'nextTier', 'nextTierModel', 'amountToNextTier', 'tierProgressPercent', 'currentTierModel'));
    }

    /**
     * Quick actions from Cashier (Add stamps, add points, claim reward)
     */
    public function quickAction(Request $request, Customer $customer)
    {
        $action = $request->input('action');

        if ($action === 'add_stamp') {
            $count = max(1, (int) $request->input('count', 1));
            $customer->addStamps($count, null, "Kasir: Tambah {$count}x Stempel Liquid");
            return back()->with('success', "Berhasil menambahkan {$count} stempel untuk {$customer->name}!");
        }

        if ($action === 'claim_reward') {
            if ($customer->liquid_stamp_rewards_available > 0) {
                $customer->decrement('liquid_stamp_rewards_available');
                LoyaltyLog::create([
                    'customer_id' => $customer->id,
                    'type' => 'redeem_stamp',
                    'description' => 'Klaim Hadiah 1x Free Liquid/Coil di Kasir 🎁',
                    'points_balance' => $customer->points,
                    'stamps_balance' => $customer->liquid_stamp_count,
                ]);
                return back()->with('success', "Hadiah stempel berhasil diklaim untuk {$customer->name}!");
            }
            return back()->with('error', "Belum ada hadiah stempel yang bisa diklaim.");
        }

        if ($action === 'add_points') {
            $points = (int) $request->input('points', 0);
            if ($points > 0) {
                $customer->addPoints($points, null, "Penambahan Manual Poin Kasir");
                return back()->with('success', "Berhasil menambahkan {$points} poin untuk {$customer->name}!");
            }
        }

        if ($action === 'redeem_points') {
            $points = (int) $request->input('points', 0);
            $tierModel = $customer->getTierModel();
            $pointValueRp = $tierModel ? $tierModel->point_value_rp : 100;

            if ($customer->redeemPoints($points, null, "Penukaran Poin Diskon di Kasir")) {
                $potongan = number_format($points * $pointValueRp, 0, ',', '.');
                return back()->with('success', "Berhasil menukar {$points} poin (Diskon Rp {$potongan})!");
            }
            return back()->with('error', "Poin tidak mencukupi untuk ditukar.");
        }

        return back()->with('error', 'Aksi tidak valid.');
    }

    /**
     * API for Cashier POS: Search customer by barcode scan, member_code, or phone
     */
    public function search(Request $request)
    {
        $query = trim($request->input('query', ''));

        if (empty($query)) {
            return response()->json(['found' => false, 'message' => 'Query kosong']);
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $query);

        $customer = Customer::where('member_code', $query)
            ->orWhere('card_token', $query)
            ->when($cleanPhone, function ($q) use ($cleanPhone, $query) {
                $q->orWhere('phone', $query)
                  ->orWhere('phone', 'like', "%{$cleanPhone}%");
            })
            ->orWhere('name', 'like', "%{$query}%")
            ->first();

        if (!$customer) {
            return response()->json(['found' => false, 'message' => 'Pelanggan tidak ditemukan']);
        }

        $tierModel = $customer->getTierModel();
        $pointValueRp = $tierModel ? $tierModel->point_value_rp : 100;
        $discountNominal = $tierModel ? (float) ($tierModel->discount_nominal ?? 0) : 0;
        $discountPercent = $tierModel ? (float) ($tierModel->discount_percent ?? 0) : 0;
        $pointMultiplier = $tierModel ? $tierModel->point_multiplier : 1.0;

        return response()->json([
            'found' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'member_code' => $customer->member_code,
                'tier' => $customer->tier,
                'tier_badge_color' => $tierModel?->badge_color ?? 'emerald',
                'tier_discount_nominal' => $discountNominal,
                'tier_discount_percent' => $discountPercent,
                'tier_multiplier' => $pointMultiplier,
                'points' => $customer->points,
                'points_value_rp' => $customer->points * $pointValueRp,
                'liquid_stamp_count' => $customer->liquid_stamp_count,
                'rewards_available' => $customer->liquid_stamp_rewards_available,
                'card_url' => $customer->card_url,
                'whatsapp_url' => $customer->whatsapp_share_url,
            ]
        ]);
    }

    /**
     * Fast registration for new customer at cashier
     */
    public function registerFast(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
        ]);

        $rawPhone = trim($request->phone, " \t\n\r\0\x0B'\"");
        $cleanDigits = preg_replace('/[^0-9]/', '', $rawPhone);
        if (str_starts_with($cleanDigits, '62')) {
            $phone = '0' . substr($cleanDigits, 2);
        } else {
            $phone = $cleanDigits;
        }

        $existing = Customer::where('phone', $phone)
            ->orWhere('phone', $rawPhone)
            ->when($cleanDigits, function ($q) use ($cleanDigits) {
                $q->orWhere('phone', 'like', "%{$cleanDigits}%");
            })
            ->first();

        if ($existing) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'already_exists' => true,
                    'message' => "Nomor WhatsApp {$phone} sudah terdaftar atas nama {$existing->name} ({$existing->member_code}).",
                    'customer' => $existing,
                    'card_url' => $existing->card_url,
                    'whatsapp_url' => $existing->whatsapp_share_url,
                ]);
            }
            return redirect()->route('loyalty.index', ['tab' => 'members'])
                             ->with('info', "Nomor WhatsApp {$phone} sudah terdaftar atas nama {$existing->name} ({$existing->member_code}).")
                             ->with('new_member_whatsapp', $existing->whatsapp_share_url);
        }

        $welcomeBonusSetting = Setting::withoutGlobalScopes()->where('type', 'loyalty_welcome_bonus')->first();
        $welcomeBonus = $welcomeBonusSetting ? (int) $welcomeBonusSetting->value : 50;

        $defaultTier = LoyaltyTier::where('is_default', true)->first();
        $initialTier = $defaultTier ? $defaultTier->name : 'Essential';

        try {
            $customer = Customer::create([
                'name' => trim($request->name),
                'phone' => $phone,
                'tier' => $initialTier,
                'points' => $welcomeBonus,
                'store_id' => session('active_store_id'),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $existing = Customer::where('phone', $phone)
                ->orWhere('phone', 'like', "%{$phone}%")
                ->first();
            if ($existing) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'already_exists' => true,
                        'message' => "Nomor WhatsApp {$phone} sudah terdaftar atas nama {$existing->name} ({$existing->member_code}).",
                        'customer' => $existing,
                        'card_url' => $existing->card_url,
                        'whatsapp_url' => $existing->whatsapp_share_url,
                    ]);
                }
                return redirect()->route('loyalty.index', ['tab' => 'members'])
                                 ->with('info', "Nomor WhatsApp {$phone} sudah terdaftar atas nama {$existing->name} ({$existing->member_code}).")
                                 ->with('new_member_whatsapp', $existing->whatsapp_share_url);
            }
            throw $e;
        }

        // Catat welcome bonus
        if ($welcomeBonus > 0) {
            LoyaltyLog::create([
                'customer_id' => $customer->id,
                'store_id' => session('active_store_id'),
                'type' => 'earn_points',
                'points_change' => $welcomeBonus,
                'points_balance' => $welcomeBonus,
                'description' => 'Welcome Bonus Member Baru 🎉',
            ]);
        }

        $tierModel = $customer->getTierModel();
        $customerData = array_merge($customer->toArray(), [
            'tier_badge_color' => $tierModel?->badge_color ?? 'emerald',
            'tier_discount_nominal' => (float) ($tierModel?->discount_nominal ?? 0),
            'tier_discount_percent' => (float) ($tierModel?->discount_percent ?? 0),
            'tier_multiplier' => (float) ($tierModel?->point_multiplier ?? 1.0),
            'points_value_rp' => $customer->points * ($tierModel?->point_value_rp ?? 100),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Member baru berhasil didaftarkan!',
                'customer' => $customerData,
                'card_url' => $customer->card_url,
                'whatsapp_url' => $customer->whatsapp_share_url,
            ]);
        }

        return redirect()->route('loyalty.index', ['tab' => 'members'])
                         ->with('success', "Member {$customer->name} berhasil didaftarkan dengan bonus {$welcomeBonus} Poin!")
                         ->with('new_member_whatsapp', $customer->whatsapp_share_url);
    }
}
