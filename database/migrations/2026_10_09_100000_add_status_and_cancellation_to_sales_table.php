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
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (!Schema::hasColumn('sales', 'status')) {
                    $table->string('status', 50)->default('completed')->after('payment_method');
                }
                if (!Schema::hasColumn('sales', 'cancellation_reason')) {
                    $table->text('cancellation_reason')->nullable()->after('status');
                }
                if (!Schema::hasColumn('sales', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
                }
                if (!Schema::hasColumn('sales', 'cancelled_by')) {
                    $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                $columnsToDrop = [];
                if (Schema::hasColumn('sales', 'cancelled_by')) {
                    $columnsToDrop[] = 'cancelled_by';
                }
                if (Schema::hasColumn('sales', 'cancelled_at')) {
                    $columnsToDrop[] = 'cancelled_at';
                }
                if (Schema::hasColumn('sales', 'cancellation_reason')) {
                    $columnsToDrop[] = 'cancellation_reason';
                }
                if (Schema::hasColumn('sales', 'status')) {
                    $columnsToDrop[] = 'status';
                }
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }
};
