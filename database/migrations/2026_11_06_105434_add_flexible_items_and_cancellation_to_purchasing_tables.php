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
        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_order_items', 'is_cancelled')) {
                    $table->boolean('is_cancelled')->default(false)->after('quantity_received');
                }
                if (!Schema::hasColumn('purchase_order_items', 'selling_price')) {
                    $table->decimal('selling_price', 15, 2)->nullable()->after('unit_price');
                }
                if (!Schema::hasColumn('purchase_order_items', 'custom_sku')) {
                    $table->string('custom_sku', 100)->nullable()->after('custom_item_name');
                }
            });
        }

        if (Schema::hasTable('purchase_request_items')) {
            Schema::table('purchase_request_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_request_items', 'is_cancelled')) {
                    $table->boolean('is_cancelled')->default(false)->after('quantity_approved');
                }
                if (!Schema::hasColumn('purchase_request_items', 'selling_price')) {
                    $table->decimal('selling_price', 15, 2)->nullable()->after('estimated_price');
                }
                if (!Schema::hasColumn('purchase_request_items', 'custom_sku')) {
                    $table->string('custom_sku', 100)->nullable()->after('custom_item_name');
                }
                if (!Schema::hasColumn('purchase_request_items', 'added_from_po')) {
                    $table->boolean('added_from_po')->default(false)->after('is_cancelled');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $columns = ['is_cancelled', 'selling_price', 'custom_sku'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('purchase_order_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('purchase_request_items')) {
            Schema::table('purchase_request_items', function (Blueprint $table) {
                $columns = ['is_cancelled', 'selling_price', 'custom_sku', 'added_from_po'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('purchase_request_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
