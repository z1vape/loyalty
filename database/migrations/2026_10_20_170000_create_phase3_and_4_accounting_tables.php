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
        // 1. Add supplier and payable columns to stock_movements
        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_movements', 'supplier_name')) {
                    $table->string('supplier_name', 150)->nullable()->after('payment_method');
                }
                if (!Schema::hasColumn('stock_movements', 'payment_status')) {
                    $table->string('payment_status', 30)->default('paid')->after('supplier_name');
                }
                if (!Schema::hasColumn('stock_movements', 'due_date')) {
                    $table->date('due_date')->nullable()->after('payment_status');
                }
                if (!Schema::hasColumn('stock_movements', 'paid_amount')) {
                    $table->decimal('paid_amount', 15, 2)->default(0)->after('due_date');
                }
            });
        }

        // 2. Fund transfers (Cash to Bank, Bank to Cash, Inter-bank)
        if (!Schema::hasTable('fund_transfers')) {
            Schema::create('fund_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->string('transfer_number', 60);
                $table->date('transfer_date');
                $table->foreignId('from_account_id')->constrained('accounts')->restrictOnDelete();
                $table->foreignId('to_account_id')->constrained('accounts')->restrictOnDelete();
                $table->decimal('amount', 15, 2);
                $table->text('notes')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['store_id', 'transfer_number']);
                $table->index(['store_id', 'transfer_date']);
            });
        }

        // 3. Supplier debt repayments
        if (!Schema::hasTable('supplier_payments')) {
            Schema::create('supplier_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->foreignId('stock_movement_id')->constrained('stock_movements')->cascadeOnDelete();
                $table->date('payment_date');
                $table->foreignId('payment_account_id')->constrained('accounts')->restrictOnDelete();
                $table->decimal('amount', 15, 2);
                $table->string('reference_number', 100)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['store_id', 'payment_date']);
                $table->index('stock_movement_id');
            });
        }

        // 4. Accounting Periods (Tutup Buku)
        if (!Schema::hasTable('accounting_periods')) {
            Schema::create('accounting_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->string('name', 100);
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('status', ['open', 'closed'])->default('open');
                $table->timestamp('closed_at')->nullable();
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('closing_notes')->nullable();
                $table->timestamps();

                $table->index(['store_id', 'status']);
                $table->index(['store_id', 'start_date', 'end_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('fund_transfers');

        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                if (Schema::hasColumn('stock_movements', 'paid_amount')) {
                    $table->dropColumn('paid_amount');
                }
                if (Schema::hasColumn('stock_movements', 'due_date')) {
                    $table->dropColumn('due_date');
                }
                if (Schema::hasColumn('stock_movements', 'payment_status')) {
                    $table->dropColumn('payment_status');
                }
                if (Schema::hasColumn('stock_movements', 'supplier_name')) {
                    $table->dropColumn('supplier_name');
                }
            });
        }
    }
};
