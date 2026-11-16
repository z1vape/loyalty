<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\LoyaltyLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoyaltyCardController extends Controller
{
    /**
     * Cashier & Admin Portal: Main Loyalty Dashboard
     */
    public function index(Request $request)
    {
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
        }

        $customers = $customersQuery->latest()->paginate(15)->withQueryString();

        // Statistics
        $stats = [
            'total_members' => Customer::count(),
            'total_points' => Customer::sum('points'),
            'total_stamps' => Customer::sum('liquid_stamp_count'),
            'rewards_available' => Customer::sum('liquid_stamp_rewards_available'),
        ];

        return view('loyalty.index', compact('customers', 'stats', 'search'));
    }

    /**
     * Public page: Display digital loyalty card for customer
     */
    public function show(string $cardToken)
    {
        $customer = Customer::with(['loyaltyLogs' => function ($q) {
            $q->latest()->take(10);
        }])->where('card_token', $cardToken)->firstOrFail();

        // Calculate next tier target
        $nextTier = null;
        $amountToNextTier = 0;
        $tierProgressPercent = 100;

        if ($customer->tier === 'Rookie') {
            $nextTier = 'Cloud Chaser';
            $target = 1500000;
            $amountToNextTier = max(0, $target - (float) $customer->total_spent);
            $tierProgressPercent = min(100, round(($customer->total_spent / $target) * 100));
        } elseif ($customer->tier === 'Cloud Chaser') {
            $nextTier = 'Master';
            $target = 3500000;
            $amountToNextTier = max(0, $target - (float) $customer->total_spent);
            $tierProgressPercent = min(100, round(($customer->total_spent / $target) * 100));
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
            if ($customer->redeemPoints($points, null, "Penukaran Poin Diskon di Kasir")) {
                return back()->with('success', "Berhasil menukar {$points} poin (Diskon Rp " . number_format($points * 100, 0, ',', '.') . ")!");
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

        return response()->json([
            'found' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'member_code' => $customer->member_code,
                'tier' => $customer->tier,
                'points' => $customer->points,
                'points_value_rp' => $customer->points * 100,
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
            'favorite_flavor' => 'nullable|string|max:100',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        }

        $existing = Customer::where('phone', $phone)->first();
        if ($existing) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pelanggan dengan nomor ini sudah terdaftar.',
                    'customer' => $existing,
                    'whatsapp_url' => $existing->whatsapp_share_url,
                ]);
            }
            return back()->with('info', "Pelanggan sudah terdaftar sebelumnya: {$existing->name}");
        }

        $customer = Customer::create([
            'name' => $request->name,
            'phone' => $phone,
            'favorite_flavor' => $request->favorite_flavor,
            'tier' => 'Rookie',
            'points' => 50, // Welcome bonus 50 poin!
        ]);

        // Catat welcome bonus
        LoyaltyLog::create([
            'customer_id' => $customer->id,
            'type' => 'earn_points',
            'points_change' => 50,
            'points_balance' => 50,
            'description' => 'Welcome Bonus Member Baru 🎉',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Member baru berhasil didaftarkan!',
                'customer' => $customer,
                'whatsapp_url' => $customer->whatsapp_share_url,
            ]);
        }

        return back()->with('success', "Member {$customer->name} berhasil didaftarkan dengan bonus 50 Poin!")
                     ->with('new_member_whatsapp', $customer->whatsapp_share_url);
    }
}
