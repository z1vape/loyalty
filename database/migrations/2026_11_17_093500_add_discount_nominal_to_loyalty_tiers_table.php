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
        if (Schema::hasTable('loyalty_tiers')) {
            Schema::table('loyalty_tiers', function (Blueprint $table) {
                if (!Schema::hasColumn('loyalty_tiers', 'discount_nominal')) {
                    $table->decimal('discount_nominal', 15, 2)->default(0)->after('point_multiplier');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('loyalty_tiers')) {
            Schema::table('loyalty_tiers', function (Blueprint $table) {
                if (Schema::hasColumn('loyalty_tiers', 'discount_nominal')) {
                    $table->dropColumn('discount_nominal');
                }
            });
        }
    }
};
