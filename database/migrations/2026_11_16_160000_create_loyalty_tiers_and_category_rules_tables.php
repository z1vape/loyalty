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
        // 1. Table Loyalty Tiers (Customizable CRUD Tiers)
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('level')->default(1);
            $table->decimal('min_spend', 15, 2)->default(0);
            $table->decimal('point_multiplier', 5, 2)->default(1.00);
            $table->decimal('discount_percent', 5, 2)->default(0.00);
            $table->integer('point_value_rp')->default(100);
            $table->string('badge_color')->default('emerald');
            $table->text('perks')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 2. Table Loyalty Category Rules (Points per Category)
        Schema::create('loyalty_category_rules', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
            $table->integer('spend_per_point')->default(10000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Seed Default 4 Tiers: Essential, Silver, Gold, Prestige
        DB::table('loyalty_tiers')->insert([
            [
                'name' => 'Essential',
                'level' => 1,
                'min_spend' => 0,
                'point_multiplier' => 1.00,
                'discount_percent' => 0.00,
                'point_value_rp' => 100,
                'badge_color' => 'emerald',
                'perks' => 'Akses Member, Stempel Liquid & Tukar Poin Diskon',
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Silver',
                'level' => 2,
                'min_spend' => 1000000,
                'point_multiplier' => 1.25,
                'discount_percent' => 2.00,
                'point_value_rp' => 120,
                'badge_color' => 'slate',
                'perks' => 'Diskon Belanja 2%, Multiplier Poin 1.25x',
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gold',
                'level' => 3,
                'min_spend' => 3000000,
                'point_multiplier' => 1.50,
                'discount_percent' => 5.00,
                'point_value_rp' => 150,
                'badge_color' => 'amber',
                'perks' => 'Diskon Belanja 5%, Multiplier Poin 1.5x, Promo Bulanan',
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Prestige',
                'level' => 4,
                'min_spend' => 5000000,
                'point_multiplier' => 2.00,
                'discount_percent' => 10.00,
                'point_value_rp' => 200,
                'badge_color' => 'purple',
                'perks' => 'Diskon Belanja 10%, Multiplier Poin 2.0x, Hadiah Ulang Tahun & Prioritas',
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 4. Seed Default Category Point Rules
        $defaultCategories = [
            ['category_name' => 'E-Liquid SaltNic', 'spend_per_point' => 10000, 'is_active' => true],
            ['category_name' => 'E-Liquid Freebase', 'spend_per_point' => 10000, 'is_active' => true],
            ['category_name' => 'Coil & Cartridge', 'spend_per_point' => 15000, 'is_active' => true],
            ['category_name' => 'Pod System', 'spend_per_point' => 50000, 'is_active' => true],
            ['category_name' => 'Box Mod', 'spend_per_point' => 50000, 'is_active' => true],
            ['category_name' => 'Accessories', 'spend_per_point' => 20000, 'is_active' => true],
        ];

        foreach ($defaultCategories as $cat) {
            DB::table('loyalty_category_rules')->insert([
                'category_name' => $cat['category_name'],
                'spend_per_point' => $cat['spend_per_point'],
                'is_active' => $cat['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 5. Migrate Existing Customers to New Tiers
        DB::table('customers')->where('tier', 'Rookie')->update(['tier' => 'Essential']);
        DB::table('customers')->where('tier', 'Cloud Chaser')->update(['tier' => 'Silver']);
        DB::table('customers')->where('tier', 'Master')->update(['tier' => 'Gold']);
        DB::table('customers')->whereNull('tier')->update(['tier' => 'Essential']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_category_rules');
        Schema::dropIfExists('loyalty_tiers');
    }
};
