<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop unique index to allow multiple items in a sale/transaction to share the same reference number
        DB::statement('DROP INDEX IF EXISTS stock_movements_reference_number_unique');

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('reference_number', 'stock_movements_reference_number_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_reference_number_index');
        });
    }
};
