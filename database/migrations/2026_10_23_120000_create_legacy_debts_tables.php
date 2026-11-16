<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Store;
use App\Services\AccountingService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('legacy_debts')) {
            Schema::create('legacy_debts', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('supplier_name', 150);
                $table->string('location', 150)->nullable();
                $table->decimal('initial_amount', 15, 2);
                $table->decimal('paid_amount', 15, 2)->default(0);
                $table->decimal('remaining_amount', 15, 2);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('legacy_debt_payments')) {
            Schema::create('legacy_debt_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('legacy_debt_id')->constrained('legacy_debts')->cascadeOnDelete();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('payment_date');
                $table->decimal('amount', 15, 2);
                $table->foreignId('payment_account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->string('payment_method', 50)->default('transfer');
                $table->string('reference_number', 100)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['legacy_debt_id', 'store_id']);
            });
        }

        // Ensure 2105 account is created for all stores
        $accountingService = app(AccountingService::class);
        foreach (Store::withoutGlobalScopes()->get() as $store) {
            $accountingService->ensureStoreAccounts($store->id);
        }

        // Seed the 3 legacy debts if not exists
        $initialDebts = [
            [
                'code' => 'LEG-VB',
                'supplier_name' => 'Vape Break (VB)',
                'location' => 'Tanggerang',
                'initial_amount' => 133595000,
                'paid_amount' => 0,
                'remaining_amount' => 133595000,
                'notes' => 'Utang warisan lama manajemen masa lalu (ditanggung bersama Vol 1, Vol 2, Belitung).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'LEG-VZ',
                'supplier_name' => 'Vapezoo',
                'location' => 'Jakarta',
                'initial_amount' => 19000000,
                'paid_amount' => 0,
                'remaining_amount' => 19000000,
                'notes' => 'Utang warisan lama manajemen masa lalu (ditanggung bersama Vol 1, Vol 2, Belitung).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'LEG-VO',
                'supplier_name' => 'Vape On',
                'location' => 'Surabaya',
                'initial_amount' => 377925656,
                'paid_amount' => 0,
                'remaining_amount' => 377925656,
                'notes' => 'Utang warisan lama manajemen masa lalu (ditanggung bersama Vol 1, Vol 2, Belitung).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($initialDebts as $debtData) {
            DB::table('legacy_debts')->updateOrInsert(
                ['code' => $debtData['code']],
                $debtData
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legacy_debt_payments');
        Schema::dropIfExists('legacy_debts');
    }
};
