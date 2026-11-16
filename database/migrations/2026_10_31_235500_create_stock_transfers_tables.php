<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();
            $table->foreignId('from_store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('to_store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('transfer_date');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('status', 30)->default('draft'); // draft, in_transit, received, cancelled
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['from_store_id', 'status']);
            $table->index(['to_store_id', 'status']);
            $table->index('transfer_date');
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('source_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('destination_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->integer('quantity_requested')->default(0);
            $table->integer('quantity_sent')->default(0);
            $table->integer('quantity_received')->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('subtotal_cost', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
