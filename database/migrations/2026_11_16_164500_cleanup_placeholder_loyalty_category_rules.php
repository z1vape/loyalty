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
        // 1. Remove placeholder/dummy categories seeded previously
        $placeholderCategories = [
            'Accessories',
            'Box Mod',
            'Coil & Cartridge',
            'E-Liquid Freebase',
            'E-Liquid SaltNic',
            'Pod System',
        ];

        DB::table('loyalty_category_rules')
            ->whereIn('category_name', $placeholderCategories)
            ->delete();

        // 2. Ensure official Z1 Vape master categories exist in rules
        $officialCategories = [
            'Aksesoris' => 10000,
            'Atomizer' => 10000,
            'Device' => 10000,
            'Freebase' => 10000,
            'OCC/Coil' => 10000,
            'Saltnic' => 10000,
        ];

        foreach ($officialCategories as $catName => $spendPerPoint) {
            $exists = DB::table('loyalty_category_rules')
                ->where('category_name', $catName)
                ->exists();

            if (!$exists) {
                DB::table('loyalty_category_rules')->insert([
                    'category_name' => $catName,
                    'spend_per_point' => $spendPerPoint,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to restore placeholder categories
    }
};
