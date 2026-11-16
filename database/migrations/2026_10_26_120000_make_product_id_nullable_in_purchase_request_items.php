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
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
            $table->string('custom_item_name')->nullable()->after('product_id');
            $table->decimal('estimated_price', 15, 2)->default(0)->after('custom_item_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropColumn(['custom_item_name', 'estimated_price']);
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};
