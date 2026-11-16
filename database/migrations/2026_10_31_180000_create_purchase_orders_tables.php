<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->string('po_number')->unique();
                $table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests')->nullOnDelete();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->string('supplier_name');
                $table->date('po_date');
                $table->date('expected_date')->nullable();
                $table->string('payment_method')->default('tempo'); // cash, transfer, tempo
                $table->integer('payment_term_days')->default(0);
                $table->string('status')->default('draft'); // draft, sent, partial, received, cancelled
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('received_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
                $table->foreignId('purchase_request_item_id')->nullable()->constrained('purchase_request_items')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('custom_item_name')->nullable();
                $table->integer('quantity_ordered');
                $table->integer('quantity_received')->default(0);
                $table->decimal('unit_price', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};

