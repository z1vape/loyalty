<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stock_opname_payroll_allocations')) {
            Schema::create('stock_opname_payroll_allocations', function (Blueprint $table) {
                $table->id();
                $table->date('opname_date');
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();
                $table->decimal('amount', 14, 2)->default(0);
                $table->timestamps();

                $table->unique(['opname_date', 'user_id']);
                $table->index(['opname_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_payroll_allocations');
    }
};

