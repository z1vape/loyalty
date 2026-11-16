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
        if (Schema::hasTable('legacy_debts')) {
            $debt = DB::table('legacy_debts')->where('code', 'LEG-VB')->first();
            if ($debt && (float)$debt->paid_amount <= 0) {
                DB::table('legacy_debts')->where('code', 'LEG-VB')->update([
                    'initial_amount' => 133595000,
                    'remaining_amount' => 133595000,
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
        if (Schema::hasTable('legacy_debts')) {
            $debt = DB::table('legacy_debts')->where('code', 'LEG-VB')->first();
            if ($debt && (float)$debt->paid_amount <= 0) {
                DB::table('legacy_debts')->where('code', 'LEG-VB')->update([
                    'initial_amount' => 130255000,
                    'remaining_amount' => 130255000,
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
