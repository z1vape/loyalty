<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('sales') && !Schema::hasColumn('sales', 'payment_method')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('payment_method', 50)->nullable()->default('cash')->after('notes');
            });

            // Backfill existing sales payment_method from notes or related stock_movements
            DB::table('sales')->whereNull('payment_method')->orWhere('payment_method', 'cash')->chunkById(100, function ($sales) {
                foreach ($sales as $sale) {
                    $method = 'cash';
                    if (!empty($sale->notes) && preg_match('/Bayar:\s*([A-Za-z]+)/i', $sale->notes, $matches)) {
                        $method = strtolower($matches[1]);
                    } else {
                        $smMethod = DB::table('stock_movements')
                            ->where('reference_number', $sale->reference_number)
                            ->whereNotNull('payment_method')
                            ->value('payment_method');
                        if (!empty($smMethod)) {
                            $method = strtolower($smMethod);
                        }
                    }

                    DB::table('sales')
                        ->where('id', $sale->id)
                        ->update(['payment_method' => $method]);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'payment_method')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('payment_method');
            });
        }
    }
};
