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
        if (Schema::hasTable('loyalty_category_rules')) {
            Schema::table('loyalty_category_rules', function (Blueprint $table) {
                if (!Schema::hasColumn('loyalty_category_rules', 'points_per_item')) {
                    $table->integer('points_per_item')->default(1)->after('category_name');
                }
            });

            // Set specific points per item requested by user:
            // Saltnic: 3 Poin, Freebase: 2 Poin, Device: 5 Poin, etc.
            DB::table('loyalty_category_rules')->where('category_name', 'Saltnic')->update(['points_per_item' => 3]);
            DB::table('loyalty_category_rules')->where('category_name', 'Freebase')->update(['points_per_item' => 2]);
            DB::table('loyalty_category_rules')->where('category_name', 'Device')->update(['points_per_item' => 5]);
            DB::table('loyalty_category_rules')->where('category_name', 'Atomizer')->update(['points_per_item' => 2]);
            DB::table('loyalty_category_rules')->where('category_name', 'OCC/Coil')->update(['points_per_item' => 1]);
            DB::table('loyalty_category_rules')->where('category_name', 'Aksesoris')->update(['points_per_item' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('loyalty_category_rules')) {
            Schema::table('loyalty_category_rules', function (Blueprint $table) {
                if (Schema::hasColumn('loyalty_category_rules', 'points_per_item')) {
                    $table->dropColumn('points_per_item');
                }
            });
        }
    }
};
