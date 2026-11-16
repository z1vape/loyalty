<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('sales') && !Schema::hasColumn('sales', 'payment_splits')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->json('payment_splits')->nullable()->after('payment_method');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'payment_splits')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('payment_splits');
            });
        }
    }
};
