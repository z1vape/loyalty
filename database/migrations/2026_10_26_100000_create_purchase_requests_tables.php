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
        if (!Schema::hasTable('purchase_requests')) {
            Schema::create('purchase_requests', function (Blueprint $table) {
                $table->id();
                $table->string('pr_number')->unique();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('request_date');
                $table->string('status')->default('pending'); // pending, approved, rejected, completed
                $table->string('priority')->default('normal'); // normal, urgent
                $table->text('notes')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('purchase_request_items')) {
            Schema::create('purchase_request_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('quantity_requested');
                $table->integer('quantity_approved')->nullable();
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
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
    }
};
