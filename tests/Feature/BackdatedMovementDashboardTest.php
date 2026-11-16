<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\MovementDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class BackdatedMovementDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithStore(): array
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create([
            'name' => 'Store Utama',
            'initial_balance' => 1000000,
            'initial_balance_date' => '2026-10-01'
        ]);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        return [$user, $store];
    }

    public function test_backdated_movement_is_counted_on_its_movement_date_not_created_at(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-BACKDATE-01',
            'name' => 'Kopi Arabika Test',
            'purchase_price' => 10000,
            'selling_price' => 25000,
            'stock' => 100,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        // Simulasikan transaksi diinput pada jam sekarang (8 Oktober 2026),
        // tetapi tanggal operasionalnya (movement_date) adalah 7 Oktober 2026
        $movementDate = MovementDate::create([
            'date' => '2026-10-07',
            'type' => 'out',
            'created_by' => $user->id,
            'store_id' => $store->id,
        ]);

        $movOut = StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'TXN-OUT-OCT7',
            'movement_date' => '2026-10-07', // Tanggal transaksi yang diinginkan
            'type' => 'out',
            'quantity' => 4,
            'purchase_price' => 10000,
            'selling_price' => 25000,
            'discount' => 5000,
            'total_price' => 95000, // (4 * 25000) - 5000
            'payment_method' => 'transfer',
            'store_id' => $store->id,
        ]);

        // Pastikan created_at sengaja diset ke tanggal 8 Oktober (hari ini)
        $movOut->created_at = Carbon::parse('2026-10-08 10:00:00');
        $movOut->save();

        // 1. Dashboard filter ke tanggal 7 Oktober (Custom: 7 Okt - 7 Okt)
        $responseOct7 = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('dashboard', [
                'filter' => 'custom',
                'start_date' => '2026-10-07',
                'end_date' => '2026-10-07',
            ]));

        $responseOct7->assertStatus(200);
        // Omzet harus masuk ke 7 Oktober (Rp 95.000)
        $responseOct7->assertSee('95.000');

        // 2. Dashboard filter ke tanggal 8 Oktober (Custom: 8 Okt - 8 Okt)
        // Transaksi 7 Oktober TIDAK boleh masuk ke 8 Oktober meskipun diinput pada 8 Oktober
        $responseOct8 = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('dashboard', [
                'filter' => 'custom',
                'start_date' => '2026-10-08',
                'end_date' => '2026-10-08',
            ]));

        $responseOct8->assertStatus(200);
        $responseOct8->assertDontSee('95.000');
    }
}
