<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
                $table->enum('type', ['permission', 'sick', 'annual_leave', 'other']);
                $table->date('start_date');
                $table->date('end_date');
                $table->integer('total_days')->default(1);
                $table->text('reason');
                $table->string('attachment')->nullable();   // path to uploaded document
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->text('rejection_note')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'start_date']);
                $table->index(['store_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
