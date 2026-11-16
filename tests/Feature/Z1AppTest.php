<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Permission;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Z1AppTest extends TestCase
{
    use RefreshDatabase;

    protected Role $staffRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffRole = Role::firstOrCreate(['name' => 'Staff', 'description' => 'Staff Toko']);
        $z1Permissions = Permission::whereIn('name', [
            'Z1 App Access',
            'Z1 App Attendance',
            'Z1 App Purchase Request',
            'Z1 App Sales Report',
        ])->get();

        $this->staffRole->permissions()->sync($z1Permissions->pluck('id'));
    }

    protected function createZ1User(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach($this->staffRole->id);
        return $user;
    }

    public function test_user_without_permission_cannot_access_z1_app(): void
    {
        $user = User::factory()->create();
        $store = Store::create([
            'name' => 'Toko Utama',
            'address' => 'Jl. Z1',
            'phone' => '08123456789'
        ]);
        $user->stores()->attach($store->id);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('z1-app.index'));

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_access_z1_app(): void
    {
        $user = $this->createZ1User();
        $store = Store::create([
            'name' => 'Toko Utama',
            'address' => 'Jl. Z1',
            'phone' => '08123456789'
        ]);
        $user->stores()->attach($store->id);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('z1-app.index'));

        $response->assertStatus(200);
        $response->assertSee('Z1 App');
        $response->assertSee('Absensi');
        $response->assertSee('Order Barang');
        $response->assertSee('Laporan Penjualan');
    }

    public function test_user_can_clock_in_and_clock_out(): void
    {
        $user = $this->createZ1User();
        $store = Store::create([
            'name' => 'Toko Cabang',
            'address' => 'Jl. Z1',
            'phone' => '08123456789'
        ]);
        $user->stores()->attach($store->id);

        $shift = Shift::create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_active' => true,
        ]);

        // 1. Clock In without photo fails validation
        $failResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('z1-app.clock-in'), [
                'shift_id' => $shift->id,
            ]);
        $failResponse->assertSessionHasErrors('photo');

        // 2. Clock In with selfie photo succeeds
        $samplePhoto = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $inResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('z1-app.clock-in'), [
                'shift_id' => $shift->id,
                'photo' => $samplePhoto,
                'note' => 'Buka toko tepat waktu',
            ]);

        $inResponse->assertRedirect(route('z1-app.index', ['tab' => 'attendance']));
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'note' => 'Buka toko tepat waktu',
        ]);
        $this->assertNotNull(Attendance::where('user_id', $user->id)->first()->clock_in_photo);

        // 2. Clock Out
        $outResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('z1-app.clock-out'), [
                'note' => 'Shift selesai dengan baik',
            ]);

        $outResponse->assertRedirect(route('z1-app.index', ['tab' => 'attendance']));
        $attendance = Attendance::where('user_id', $user->id)->first();
        $this->assertNotNull($attendance->clock_out);
    }

    public function test_user_can_submit_purchase_request(): void
    {
        $user = $this->createZ1User();
        $store = Store::create([
            'name' => 'Toko Cabang',
            'address' => 'Jl. Z1',
            'phone' => '08123456789'
        ]);
        $user->stores()->attach($store->id);

        $product = Product::create([
            'name' => 'Liquid Freebase 60ml',
            'sku' => 'LQ-001',
            'store_id' => $store->id,
            'stock' => 5,
            'purchase_price' => 70000,
            'selling_price' => 120000,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('z1-app.purchase-request.store'), [
                'request_date' => date('Y-m-d'),
                'priority' => 'urgent',
                'notes' => 'Stok etalase hampir habis',
            ]);

        $pr = PurchaseRequest::where('store_id', $store->id)->first();
        $this->assertNotNull($pr);
        $response->assertRedirect(route('z1-app.purchase-request.show', $pr->id));

        $this->assertDatabaseHas('purchase_requests', [
            'id' => $pr->id,
            'store_id' => $store->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'priority' => 'urgent',
        ]);
        $this->assertEquals(date('Y-m-d'), Carbon::parse($pr->request_date)->toDateString());
    }

    public function test_user_can_cancel_purchase_request(): void
    {
        $user = $this->createZ1User();
        $store = Store::create([
            'name' => 'Toko Cabang',
            'address' => 'Jl. Z1',
            'phone' => '08123456789'
        ]);
        $user->stores()->attach($store->id);

        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-20261026-9999',
            'store_id' => $store->id,
            'user_id' => $user->id,
            'request_date' => date('Y-m-d'),
            'status' => 'pending',
            'priority' => 'normal',
        ]);

        $deleteResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->delete(route('z1-app.purchase-request.destroy', $pr->id));

        $deleteResponse->assertRedirect(route('z1-app.index', ['tab' => 'purchase_request']));
        $this->assertDatabaseMissing('purchase_requests', ['id' => $pr->id]);
    }

    public function test_user_can_view_purchase_request_detail_page(): void
    {
        $user = $this->createZ1User();
        $store = Store::create([
            'name' => 'Toko Cabang',
            'address' => 'Jl. Z1',
            'phone' => '08123456789'
        ]);
        $user->stores()->attach($store->id);

        $product = Product::create([
            'name' => 'Foom Tangy Milk 30mg',
            'sku' => 'IND-302-HB',
            'store_id' => $store->id,
            'stock' => 15,
            'purchase_price' => 83333,
            'selling_price' => 110000,
        ]);

        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-20261026-1001',
            'store_id' => $store->id,
            'user_id' => $user->id,
            'request_date' => date('Y-m-d'),
            'status' => 'pending',
            'priority' => 'normal',
            'notes' => 'Restock reguler minggu ini'
        ]);

        $item = $pr->items()->create([
            'product_id' => $product->id,
            'quantity_requested' => 5,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('z1-app.purchase-request.show', $pr->id));

        $response->assertStatus(200);
        $response->assertSee('PR-20261026-1001');
        $response->assertSee('Foom Tangy Milk 30mg');
        $response->assertSee('IND-302-HB');
        $response->assertSee('ORDER BARANG');
        $response->assertSee('83.333');
        $response->assertDontSee('Harga Jual');
    }

    public function test_purchase_request_tab_renders_without_error_for_staff(): void
    {
        $user = $this->createZ1User();
        $store = Store::create([
            'name' => 'Toko Cabang Test',
            'address' => 'Jl. Z1 Test',
            'phone' => '08123456789'
        ]);
        $user->stores()->attach($store->id);

        PurchaseRequest::create([
            'pr_number' => 'PR-20261026-9999',
            'store_id' => $store->id,
            'user_id' => $user->id,
            'request_date' => now()->toDateString(),
            'status' => 'pending',
            'priority' => 'normal',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('z1-app.index', ['tab' => 'purchase_request']));

        $response->assertStatus(200);
        $response->assertSee('PR-20261026-9999');
    }

    public function test_user_can_add_and_delete_item_in_purchase_request(): void
    {
        $user = $this->createZ1User();
        $store = Store::create([
            'name' => 'Toko Cabang',
            'address' => 'Jl. Z1',
            'phone' => '08123456789'
        ]);
        $user->stores()->attach($store->id);

        $product = Product::create([
            'name' => 'Oxva Xlim Pod Cartridge',
            'sku' => 'OXV-001',
            'store_id' => $store->id,
            'stock' => 20,
            'purchase_price' => 35000,
            'selling_price' => 50000,
        ]);

        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-20261026-1002',
            'store_id' => $store->id,
            'user_id' => $user->id,
            'request_date' => date('Y-m-d'),
            'status' => 'pending',
            'priority' => 'normal',
        ]);

        // Add item
        $addResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('z1-app.purchase-request.add-item', $pr->id), [
                'product_id' => $product->id,
                'quantity_requested' => 3,
            ]);

        $addResponse->assertRedirect(route('z1-app.purchase-request.show', $pr->id));
        $this->assertDatabaseHas('purchase_request_items', [
            'purchase_request_id' => $pr->id,
            'product_id' => $product->id,
            'quantity_requested' => 3,
        ]);

        $item = $pr->items()->first();

        // Delete item
        $delResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->delete(route('z1-app.purchase-request.destroy-item', $item->id));

        $delResponse->assertRedirect(route('z1-app.purchase-request.show', $pr->id));
        $this->assertDatabaseMissing('purchase_request_items', ['id' => $item->id]);
    }

    public function test_user_can_add_manual_non_inventory_item_to_purchase_request(): void
    {
        $user = $this->createZ1User();
        $store = Store::create([
            'name' => 'Toko Cabang',
            'address' => 'Jl. Z1',
            'phone' => '08123456789'
        ]);
        $user->stores()->attach($store->id);

        $initialProductCount = Product::count();

        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-20261026-1003',
            'store_id' => $store->id,
            'user_id' => $user->id,
            'request_date' => date('Y-m-d'),
            'status' => 'pending',
            'priority' => 'normal',
        ]);

        // Add item manually without selecting from inventory
        $addResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('z1-app.purchase-request.add-item', $pr->id), [
                'product_id' => '',
                'custom_item_name' => 'Koil RBA Custom 0.8 ohm',
                'estimated_price' => 45000,
                'quantity_requested' => 4,
                'notes' => 'Tolong tanyakan ke supplier distributor',
            ]);

        $addResponse->assertRedirect(route('z1-app.purchase-request.show', $pr->id));

        // Assert item was created in purchase_request_items with product_id null
        $this->assertDatabaseHas('purchase_request_items', [
            'purchase_request_id' => $pr->id,
            'product_id' => null,
            'custom_item_name' => 'Koil RBA Custom 0.8 ohm',
            'estimated_price' => 45000,
            'quantity_requested' => 4,
        ]);

        // Assert products table was NOT touched (tidak menambahkan otomatis pada inventory)
        $this->assertEquals($initialProductCount, Product::count());

        // Check show page displays manual item
        $showResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('z1-app.purchase-request.show', $pr->id));

        $showResponse->assertStatus(200);
        $showResponse->assertSee('Koil RBA Custom 0.8 ohm');
        $showResponse->assertSee('Manual (Non-Inventory)');
        $showResponse->assertSee('45.000');
        $showResponse->assertSee('180.000'); // 45000 * 4 = 180000
    }

    public function test_clock_in_succeeds_when_within_store_radius(): void
    {
        $user = $this->createZ1User();
        $store = Store::create([
            'name' => 'Toko Geofence',
            'address' => 'Jl. Braga No. 10',
            'phone' => '08123456789',
            'latitude' => -6.917464,
            'longitude' => 107.619123,
            'radius_meters' => 100,
        ]);
        $user->stores()->attach($store->id);

        $shift = Shift::create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_active' => true,
        ]);

        $samplePhoto = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        // Nearby coordinates (~15 meters away)
        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('z1-app.clock-in'), [
                'shift_id' => $shift->id,
                'photo' => $samplePhoto,
                'lat' => -6.917500,
                'lng' => 107.619150,
                'note' => 'Hadir di toko',
            ]);

        $response->assertRedirect(route('z1-app.index', ['tab' => 'attendance']));
        $response->assertSessionHas('success');

        $attendance = Attendance::where('user_id', $user->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals(-6.917500, (float) $attendance->clock_in_lat);
        $this->assertEquals(107.619150, (float) $attendance->clock_in_lng);
        $this->assertNotNull($attendance->clock_in_distance_meters);
        $this->assertLessThanOrEqual(100, $attendance->clock_in_distance_meters);
    }

    public function test_clock_in_fails_when_outside_store_radius(): void
    {
        $user = $this->createZ1User();
        $store = Store::create([
            'name' => 'Toko Geofence',
            'address' => 'Jl. Braga No. 10',
            'phone' => '08123456789',
            'latitude' => -6.917464,
            'longitude' => 107.619123,
            'radius_meters' => 50,
        ]);
        $user->stores()->attach($store->id);

        $shift = Shift::create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_active' => true,
        ]);

        $samplePhoto = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        // Distant coordinates (~1.5 km away)
        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('z1-app.clock-in'), [
                'shift_id' => $shift->id,
                'photo' => $samplePhoto,
                'lat' => -6.930000,
                'lng' => 107.630000,
                'note' => 'Mencoba clock in dari jauh',
            ]);

        $response->assertRedirect(route('z1-app.index', ['tab' => 'attendance']));
        $response->assertSessionHas('error');
        $errorMessage = session('error');
        $this->assertStringContainsString('di luar radius toko', $errorMessage);

        // No attendance record should be created
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    public function test_clock_in_requires_gps_when_store_has_geofence(): void
    {
        $user = $this->createZ1User();
        $store = Store::create([
            'name' => 'Toko Geofence',
            'address' => 'Jl. Braga No. 10',
            'phone' => '08123456789',
            'latitude' => -6.917464,
            'longitude' => 107.619123,
            'radius_meters' => 50,
        ]);
        $user->stores()->attach($store->id);

        $shift = Shift::create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_active' => true,
        ]);

        $samplePhoto = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        // Clock In without lat & lng
        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('z1-app.clock-in'), [
                'shift_id' => $shift->id,
                'photo' => $samplePhoto,
            ]);

        $response->assertSessionHasErrors(['lat', 'lng']);
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    public function test_clock_out_records_location_and_distance(): void
    {
        $user = $this->createZ1User();
        $store = Store::create([
            'name' => 'Toko Geofence',
            'address' => 'Jl. Braga No. 10',
            'phone' => '08123456789',
            'latitude' => -6.917464,
            'longitude' => 107.619123,
            'radius_meters' => 100,
        ]);
        $user->stores()->attach($store->id);

        $shift = Shift::create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_active' => true,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'shift_id' => $shift->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->subHours(8),
            'clock_in_photo' => 'attendances/sample.jpg',
            'clock_in_lat' => -6.917464,
            'clock_in_lng' => 107.619123,
            'clock_in_distance_meters' => 0,
            'status' => 'present',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('z1-app.clock-out'), [
                'lat' => -6.917480,
                'lng' => 107.619130,
                'note' => 'Pulang kerja',
            ]);

        $response->assertRedirect(route('z1-app.index', ['tab' => 'attendance']));

        $attendance->refresh();
        $this->assertNotNull($attendance->clock_out);
        $this->assertEquals(-6.917480, (float) $attendance->clock_out_lat);
        $this->assertEquals(107.619130, (float) $attendance->clock_out_lng);
        $this->assertNotNull($attendance->clock_out_distance_meters);
        $this->assertLessThanOrEqual(100, $attendance->clock_out_distance_meters);
    }

    public function test_overnight_shift_allows_clock_out_after_midnight(): void
    {
        $user = $this->createZ1User();
        $store = Store::create([
            'name' => 'Z1 VAPE STORE VOL 2',
            'address' => 'Jl. Z1',
            'phone' => '08123456789'
        ]);
        $user->stores()->attach($store->id);

        $shift = Shift::create([
            'name' => 'Shift Malam (Vol 2)',
            'start_time' => '16:00:00',
            'end_time' => '00:00:00',
            'is_overnight' => true,
            'is_active' => true,
        ]);

        // Employee clocked in on Sunday, 1 November 2026 at 15:52 WIB
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'shift_id' => $shift->id,
            'date' => '2026-11-01',
            'clock_in' => Carbon::parse('2026-11-01 15:52:00'),
            'status' => 'present',
            'note' => 'Shift Malam',
        ]);

        // It is now Monday, 2 November 2026 at 00:15 WIB (past midnight)
        Carbon::setTestNow('2026-11-02 00:15:00');

        // 1. Employee opens the app on Monday
        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('z1-app.index'));

        $response->assertStatus(200);
        $response->assertSee('Sedang Bekerja');
        $response->assertSee('Clock Out Sekarang');
        $response->assertDontSee('Clock In Sekarang');

        // 2. Employee submits Clock Out
        $clockOutResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('z1-app.clock-out'), [
                'note' => 'Closing toko beres',
            ]);

        $clockOutResponse->assertRedirect(route('z1-app.index', ['tab' => 'attendance']));

        // 3. Verify attendance on 2026-11-01 is updated with clock_out
        $attendance->refresh();
        $this->assertNotNull($attendance->clock_out);
        $this->assertEquals('2026-11-02 00:15:00', $attendance->clock_out->toDateTimeString());
        $this->assertEquals(503, $attendance->work_minutes); // 15:52 to 00:15 = 503 minutes (~8 hours 23 mins)

        // 4. Employee refreshes the app: now ready for Monday's shift (Belum Clock In)
        $nextResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('z1-app.index'));

        $nextResponse->assertStatus(200);
        $nextResponse->assertSee('Belum Clock In');

        Carbon::setTestNow();
    }
}

