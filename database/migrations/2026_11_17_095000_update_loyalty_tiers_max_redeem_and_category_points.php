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
        // 1. Add max_redeem_rp to loyalty_tiers
        if (Schema::hasTable('loyalty_tiers')) {
            Schema::table('loyalty_tiers', function (Blueprint $table) {
                if (!Schema::hasColumn('loyalty_tiers', 'max_redeem_rp')) {
                    $table->decimal('max_redeem_rp', 15, 2)->default(30000)->after('discount_nominal');
                }
            });

            // Set default max_redeem_rp based on tier levels
            DB::table('loyalty_tiers')->where('name', 'Essential')->update(['max_redeem_rp' => 30000]);
            DB::table('loyalty_tiers')->where('name', 'Silver')->update(['max_redeem_rp' => 50000]);
            DB::table('loyalty_tiers')->where('name', 'Gold')->update(['max_redeem_rp' => 75000]);
            DB::table('loyalty_tiers')->where('name', 'Prestige')->update(['max_redeem_rp' => 100000]);
        }

        // 2. Update default category points to nominal Rupiah
        if (Schema::hasTable('loyalty_category_rules')) {
            DB::table('loyalty_category_rules')->where('category_name', 'Device')->update(['points_per_item' => 2000]);
            DB::table('loyalty_category_rules')->where('category_name', 'Freebase')->update(['points_per_item' => 1000]);
            DB::table('loyalty_category_rules')->where('category_name', 'Saltnic')->update(['points_per_item' => 1000]);
            DB::table('loyalty_category_rules')->where('category_name', 'Atomizer')->update(['points_per_item' => 1000]);
            DB::table('loyalty_category_rules')->where('category_name', 'OCC/Coil')->update(['points_per_item' => 500]);
            DB::table('loyalty_category_rules')->where('category_name', 'Aksesoris')->update(['points_per_item' => 500]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('loyalty_tiers')) {
            Schema::table('loyalty_tiers', function (Blueprint $table) {
                if (Schema::hasColumn('loyalty_tiers', 'max_redeem_rp')) {
                    $table->dropColumn('max_redeem_rp');
                }
            });
        }
    }
};
