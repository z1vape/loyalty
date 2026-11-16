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
        Schema::table('movement_dates', function (Blueprint $table) {
            $table->string('supplier_name', 150)->nullable()->after('notes');
            $table->string('payment_method', 50)->nullable()->default('cash')->after('supplier_name');
            $table->date('due_date')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movement_dates', function (Blueprint $table) {
            $table->dropColumn(['supplier_name', 'payment_method', 'due_date']);
        });
    }
};
