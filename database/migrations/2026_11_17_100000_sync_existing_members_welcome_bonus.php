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
        // 1. Update welcome bonus setting to 500 if it was 50
        $current = DB::table('settings')->where('type', 'loyalty_welcome_bonus')->first();
        if ($current && (int)$current->value === 50) {
            DB::table('settings')->where('type', 'loyalty_welcome_bonus')->update(['value' => '500']);
        }

        // 2. Update existing customers whose initial points were 50 to 500
        DB::table('customers')->where('points', 50)->update(['points' => 500]);
        DB::table('loyalty_logs')->where('points_change', 50)->where('points_balance', 50)->update([
            'points_change' => 500,
            'points_balance' => 500,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
