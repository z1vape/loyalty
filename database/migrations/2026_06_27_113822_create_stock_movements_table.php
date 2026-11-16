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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('reference_number')->unique();
            $table->date('movement_date');
            $table->string('type'); // 'in' or 'out'
            $table->integer('quantity');
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->decimal('selling_price', 15, 2)->nullable();
            $table->decimal('discount', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
