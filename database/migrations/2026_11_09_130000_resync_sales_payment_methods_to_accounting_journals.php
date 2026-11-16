<?php

use App\Models\Sale;
use App\Services\AccountingService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $accountingService = app(AccountingService::class);

        // Re-sync all sales to accounting journals to reflect any updated payment methods
        $sales = Sale::withoutGlobalScopes()
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            })
            ->get();

        foreach ($sales as $sale) {
            try {
                $accountingService->recordSaleJournal($sale);
            } catch (\Throwable $e) {
                // Silently skip if journal fails on edge cases
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};

