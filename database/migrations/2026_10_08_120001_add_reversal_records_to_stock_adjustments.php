<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->dropForeign(['store_id']);
            $table->foreign('store_id')->references('id')->on('stores')->restrictOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->unique()->constrained('stock_adjustments')->restrictOnDelete();
            $table->index(['store_id', 'user_id', 'created_at']);
            $table->index(['store_id', 'reason', 'created_at']);
        });
        // Legacy cancellations already changed stock. Add their missing audit events
        // without changing stock again; the historical reversal balance is unknown.
        DB::table('stock_adjustments')->whereNotNull('reversed_at')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                DB::table('stock_adjustments')->insert([
                    'store_id' => $row->store_id, 'product_id' => $row->product_id,
                    'user_id' => $row->reversed_by, 'quantity_before' => $row->quantity_after,
                    'quantity_change' => -$row->quantity_change, 'quantity_after' => $row->quantity_before,
                    'reason' => 'reversal', 'notes' => 'Legacy reversal: balance reconstructed from original snapshot.',
                    'reversal_of_id' => $row->id, 'created_at' => $row->reversed_at, 'updated_at' => $row->reversed_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('stock_adjustments')->whereNotNull('reversal_of_id')->delete();
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropForeign(['reversal_of_id']);
            $table->dropUnique(['reversal_of_id']);
            $table->dropColumn('reversal_of_id');
            $table->dropIndex(['store_id', 'user_id', 'created_at']);
            $table->dropIndex(['store_id', 'reason', 'created_at']);
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->dropForeign(['store_id']);
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
        });
    }
};
