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
        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('auto_cash_to_bank')->default(false)->after('bank_account_name');
        });

        // Auto-enable for any store that already has bank information configured
        DB::table('stores')
            ->whereNotNull('bank_name')
            ->where('bank_name', '!=', '')
            ->update(['auto_cash_to_bank' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('auto_cash_to_bank');
        });
    }
};
