<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\LoyaltyLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoyaltyCardController extends Controller
{
    /**
     * Get or initialize default tier configuration
     */
    public static function getTierConfig(): array
    {
        try {
            $setting = Setting::where('type', 'loyalty_tier_config')->first();
            if ($setting && !empty($setting->value)) {
                $decoded = json_decode($setting->value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        } catch (\Throwable $e) {
            // Fallback to default
        }

        return [
            'rookie' => [
                'name' => 'Rookie',
                'min_spend' => 0,
                'point_multiplier' => 1.0,
                'perks' => 'Akses Member, Stempel Liquid & Tukar Poin Diskon',
            ],
            'cloud_chaser' => [
                'name' => 'Cloud Chaser',
                'min_spend' => 1500000,
                'point_multiplier' => 1.25,
                'perks' => 'Bonus Perolehan Poin 1.25x, Diskon Promo Bulanan',
            ],
            'master' => [
                'name' => 'Master',
                'min_spend' => 3500000,
                'point_multiplier' => 1.5,
                'perks' => 'Bonus Perolehan Poin 1.5x, Antrean Kasir Prioritas & Merchandise',
            ],
            'rules' => [
                'spend_per_point' => 10000,
                'point_value_rp' => 100,
                'welcome_bonus' => 50,
            ]
        ];
    }

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

        $tierConfig = static::getTierConfig();

        // Tier Member Counts & Percentages
        $totalCustomers = max(1, Customer::count());
        $tierCounts = [
            'Rookie' => Customer::where('tier', 'Rookie')->count(),
            'Cloud Chaser' => Customer::where('tier', 'Cloud Chaser')->count(),
            'Master' => Customer::where('tier', 'Master')->count(),
        ];

        return view('loyalty.index', compact(
            'customers',
            'search',
            'activeTab',
            'tierConfig',
            'tierCounts',
            'totalCustomers'
        ));
    }

    /**
     * Update tier configuration and point rules
     */
    public function updateTiers(Request $request)
    {
        $tiers = [
            'rookie' => [
                'name' => 'Rookie',
                'min_spend' => (float) $request->input('tier_rookie_min_spend', 0),
                'point_multiplier' => (float) $request->input('tier_rookie_multiplier', 1.0),
                'perks' => $request->input('tier_rookie_perks', 'Akses Member, Stempel Liquid & Tukar Poin Diskon'),
            ],
            'cloud_chaser' => [
                'name' => 'Cloud Chaser',
                'min_spend' => (float) $request->input('tier_cloud_min_spend', 1500000),
                'point_multiplier' => (float) $request->input('tier_cloud_multiplier', 1.25),
                'perks' => $request->input('tier_cloud_perks', 'Bonus Perolehan Poin 1.25x, Diskon Promo Bulanan'),
            ],
            'master' => [
                'name' => 'Master',
                'min_spend' => (float) $request->input('tier_master_min_spend', 3500000),
                'point_multiplier' => (float) $request->input('tier_master_multiplier', 1.5),
                'perks' => $request->input('tier_master_perks', 'Bonus Perolehan Poin 1.5x, Antrean Kasir Prioritas & Merchandise'),
            ],
            'rules' => [
                'spend_per_point' => max(1000, (int) $request->input('spend_per_point', 10000)),
                'point_value_rp' => max(1, (int) $request->input('point_value_rp', 100)),
                'welcome_bonus' => max(0, (int) $request->input('welcome_bonus', 50)),
            ]
        ];

        Setting::updateOrCreate(
            ['type' => 'loyalty_tier_config'],
            ['value' => json_encode($tiers)]
        );

        return redirect()->route('loyalty.index', ['tab' => 'tiers'])
                         ->with('success', 'Pengaturan tier pembelanjaan dan perolehan poin berhasil diperbarui!');
    }

    /**
     * Public page: Display digital loyalty card for customer
     */
    public function show(string $cardToken)
    {
        $customer = Customer::with(['loyaltyLogs' => function ($q) {
            $q->latest()->take(10);
        }])->where('card_token', $cardToken)->firstOrFail();

        $tierConfig = static::getTierConfig();
        $targetCloud = $tierConfig['cloud_chaser']['min_spend'] ?? 1500000;
        $targetMaster = $tierConfig['master']['min_spend'] ?? 3500000;

        // Calculate next tier target
        $nextTier = null;
        $amountToNextTier = 0;
        $tierProgressPercent = 100;

        if ($customer->tier === 'Rookie') {
            $nextTier = 'Cloud Chaser';
            $amountToNextTier = max(0, $targetCloud - (float) $customer->total_spent);
            $tierProgressPercent = min(100, round(($customer->total_spent / max(1, $targetCloud)) * 100));
        } elseif ($customer->tier === 'Cloud Chaser') {
            $nextTier = 'Master';
            $amountToNextTier = max(0, $targetMaster - (float) $customer->total_spent);
            $tierProgressPercent = min(100, round(($customer->total_spent / max(1, $targetMaster)) * 100));
        }

        return view('loyalty.card', compact('customer', 'nextTier', 'amountToNextTier', 'tierProgressPercent'));
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
            $tierConfig = static::getTierConfig();
            $pointValueRp = $tierConfig['rules']['point_value_rp'] ?? 100;
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

        $tierConfig = static::getTierConfig();
        $pointValueRp = $tierConfig['rules']['point_value_rp'] ?? 100;

        return response()->json([
            'found' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'member_code' => $customer->member_code,
                'tier' => $customer->tier,
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
     * Fast registration for new customer at cashier (Without Rasa Favorit)
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

        $tierConfig = static::getTierConfig();
        $welcomeBonus = (int) ($tierConfig['rules']['welcome_bonus'] ?? 50);

        try {
            $customer = Customer::create([
                'name' => trim($request->name),
                'phone' => $phone,
                'tier' => 'Rookie',
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

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Member baru berhasil didaftarkan!',
                'customer' => $customer,
                'card_url' => $customer->card_url,
                'whatsapp_url' => $customer->whatsapp_share_url,
            ]);
        }

        return redirect()->route('loyalty.index', ['tab' => 'members'])
                         ->with('success', "Member {$customer->name} berhasil didaftarkan dengan bonus {$welcomeBonus} Poin!")
                         ->with('new_member_whatsapp', $customer->whatsapp_share_url);
    }
}
