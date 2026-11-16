<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\Product;
use App\Models\MovementDate;
use App\Models\StockMovement;
use Illuminate\Http\UploadedFile;

class StockInImportTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithStore()
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create(['name' => 'Store Utama']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        return [$user, $store];
    }

    public function test_can_download_stock_in_import_template(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('movement-dates.import-template'));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('template_stock_in_', $response->headers->get('content-disposition'));
    }

    public function test_can_import_products_into_existing_stock_in_movement_date(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'PRD-001',
            'name' => 'Kopi Robusta',
            'purchase_price' => 10000,
            'selling_price' => 20000,
            'stock' => 10,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        $movementDate = MovementDate::create([
            'date' => '2026-10-06',
            'type' => 'in',
            'created_by' => $user->id,
            'store_id' => $store->id,
        ]);

        $csvContent = "sku,product_name,quantity,purchase_price,selling_price,notes\n";
        $csvContent .= "PRD-001,Kopi Robusta,20,12000,22000,Restock Masuk\n";

        $file = UploadedFile::fake()->createWithContent('stock_in.csv', $csvContent);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movement-dates.import', $movementDate->id), [
                'file' => $file,
                'user_id' => $user->id,
            ]);

        $response->assertRedirect(route('movement-dates.show', $movementDate->id));
        $response->assertSessionHas('success');

        $product->refresh();
        $this->assertEquals(10, $product->stock); // Stays 10 because it is created as DRAFT

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 20,
            'purchase_price' => 12000,
            'status' => 'draft',
            'notes' => 'Restock Masuk',
        ]);

        // When approved, stock increases to 30
        $movement = StockMovement::where('product_id', $product->id)->first();
        $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.approve', $movement->id));

        $product->refresh();
        $this->assertEquals(30, $product->stock);
        $this->assertEquals('approved', $movement->fresh()->status);
    }

    public function test_can_create_date_movement_and_import_excel_directly(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'PRD-002',
            'name' => 'Teh Melati',
            'purchase_price' => 5000,
            'selling_price' => 10000,
            'stock' => 5,
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        $csvContent = "sku,product_name,quantity,purchase_price,selling_price,notes\n";
        $csvContent .= "PRD-002,Teh Melati,15,5000,10000,Batch Awal\n";

        $file = UploadedFile::fake()->createWithContent('stock_in_batch.csv', $csvContent);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movement-dates.store'), [
                'date' => '2026-10-07',
                'type' => 'in',
                'notes' => 'Penerimaan Pabrik',
                'file' => $file,
            ]);

        $movementDate = MovementDate::where('date', '2026-10-07')->where('type', 'in')->first();
        $this->assertNotNull($movementDate);

        $response->assertRedirect(route('movement-dates.show', ['id' => $movementDate->id, 'type' => 'in']));
        $response->assertSessionHas('success');

        $product->refresh();
        $this->assertEquals(5, $product->stock); // Draft: stock not increased yet

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 15,
            'status' => 'draft',
        ]);
    }

    public function test_auto_creates_missing_product_if_name_is_provided_in_import(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $movementDate = MovementDate::create([
            'date' => '2026-10-08',
            'type' => 'in',
            'created_by' => $user->id,
            'store_id' => $store->id,
        ]);

        $csvContent = "sku,product_name,quantity,purchase_price,selling_price,notes\n";
        $csvContent .= "NEW-SKU-99,Produk Baru Otomatis,50,15000,25000,Import Produk Baru\n";

        $file = UploadedFile::fake()->createWithContent('new_products.csv', $csvContent);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movement-dates.import', $movementDate->id), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('movement-dates.show', $movementDate->id));

        $newProduct = Product::where('sku', 'NEW-SKU-99')->first();
        $this->assertNotNull($newProduct);
        $this->assertEquals('Produk Baru Otomatis', $newProduct->name);
        $this->assertEquals(0, $newProduct->stock); // 0 until approved
        $this->assertEquals(15000, $newProduct->purchase_price);

        // Approve and verify stock becomes 50
        $movement = StockMovement::where('product_id', $newProduct->id)->first();
        $this->assertEquals('draft', $movement->status);

        $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.approve', $movement->id));

        $newProduct->refresh();
        $this->assertEquals(50, $newProduct->stock);
    }

    public function test_can_import_products_with_variants_and_same_name_different_sku(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $movementDate = MovementDate::create([
            'date' => '2026-10-09',
            'type' => 'in',
            'created_by' => $user->id,
            'store_id' => $store->id,
        ]);

        // Two products with same name 'Kopi Susu Literan', different variants '250ml' and '500ml', different SKUs
        $csvContent = "sku,product_name,variant,quantity,purchase_price,selling_price,notes\n";
        $csvContent .= "SKU-KOPI-250,Kopi Susu Literan,250ml,40,15000,25000,Batch Pagi\n";
        $csvContent .= "SKU-KOPI-500,Kopi Susu Literan,500ml,25,28000,45000,Batch Pagi\n";
        $csvContent .= "SKU-ROTI-001,Roti Cokelat,,15,8000,12000,Tanpa Varian\n";

        $file = UploadedFile::fake()->createWithContent('variants_stock_in.csv', $csvContent);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movement-dates.import', $movementDate->id), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('movement-dates.show', $movementDate->id));

        $p250 = Product::where('sku', 'SKU-KOPI-250')->first();
        $this->assertNotNull($p250);
        $this->assertEquals('Kopi Susu Literan', $p250->name);
        $this->assertEquals('250ml', $p250->variant);
        $this->assertEquals(0, $p250->stock); // Draft

        $p500 = Product::where('sku', 'SKU-KOPI-500')->first();
        $this->assertNotNull($p500);
        $this->assertEquals('Kopi Susu Literan', $p500->name);
        $this->assertEquals('500ml', $p500->variant);
        $this->assertEquals(0, $p500->stock); // Draft

        $pRoti = Product::where('sku', 'SKU-ROTI-001')->first();
        $this->assertNotNull($pRoti);
        $this->assertEquals('Roti Cokelat', $pRoti->name);
        $this->assertNull($pRoti->variant);
        $this->assertEquals(0, $pRoti->stock); // Draft

        // Bulk approve all drafts for this date
        $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.bulk-approve'), [
                'movement_date_id' => $movementDate->id,
                'approve_all' => '1',
            ]);

        $p250->refresh();
        $this->assertEquals(40, $p250->stock);
        $p500->refresh();
        $this->assertEquals(25, $p500->stock);
        $pRoti->refresh();
        $this->assertEquals(15, $pRoti->stock);
    }

    public function test_can_unapprove_approved_stock_movements_reverting_stock_and_purging_journals(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'PRD-TEST-UNAPPROVE',
            'name' => 'Keripik Tempe',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'stock' => 10,
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        $movementDate = MovementDate::create([
            'date' => '2026-10-23',
            'type' => 'in',
            'created_by' => $user->id,
            'store_id' => $store->id,
        ]);

        $csvContent = "sku,product_name,quantity,purchase_price,selling_price,notes\n";
        $csvContent .= "PRD-TEST-UNAPPROVE,Keripik Tempe,20,12000,16000,Test Batch\n";

        $file = UploadedFile::fake()->createWithContent('unapprove_test.csv', $csvContent);

        $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movement-dates.import', $movementDate->id), [
                'file' => $file,
            ]);

        $movement = StockMovement::where('product_id', $product->id)->first();
        $this->assertEquals('draft', $movement->status);
        $this->assertEquals(10, $product->fresh()->stock);

        // 1. Approve
        $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.approve', $movement->id));

        $this->assertEquals('approved', $movement->fresh()->status);
        $this->assertEquals(30, $product->fresh()->stock);

        // Journal should exist
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'stock_movement',
            'source_id' => $movement->id,
        ]);

        // 2. Unapprove (Revert to Draft)
        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.unapprove', $movement->id));

        $response->assertSessionHas('success');
        $this->assertEquals('draft', $movement->fresh()->status);
        $this->assertEquals(10, $product->fresh()->stock); // Stock safely deducted back to 10

        // Journal must be purged!
        $this->assertDatabaseMissing('journal_entries', [
            'source_type' => 'stock_movement',
            'source_id' => $movement->id,
        ]);

        // 3. Bulk unapprove test
        // Approve again
        $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.approve', $movement->id));
        $this->assertEquals(30, $product->fresh()->stock);

        // Bulk unapprove all on date
        $bulkResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.bulk-unapprove'), [
                'movement_date_id' => $movementDate->id,
                'unapprove_all' => '1',
            ]);

        $bulkResponse->assertSessionHas('success');
        $this->assertEquals('draft', $movement->fresh()->status);
        $this->assertEquals(10, $product->fresh()->stock);
        $this->assertDatabaseMissing('journal_entries', [
            'source_type' => 'stock_movement',
            'source_id' => $movement->id,
        ]);
    }
}
