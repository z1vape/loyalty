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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::table('movement_dates', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unique(['date', 'store_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });

        Schema::table('movement_dates', function (Blueprint $table) {
            $table->dropUnique(['date', 'store_id']);
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });
    }
};
