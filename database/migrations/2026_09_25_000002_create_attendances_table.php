<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->date('date');
                $table->dateTime('clock_in')->nullable();
                $table->dateTime('clock_out')->nullable();
                $table->string('clock_in_photo')->nullable();    // path to selfie
                $table->string('clock_out_photo')->nullable();
                $table->decimal('clock_in_lat', 10, 8)->nullable();
                $table->decimal('clock_in_lng', 11, 8)->nullable();
                $table->decimal('clock_out_lat', 10, 8)->nullable();
                $table->decimal('clock_out_lng', 11, 8)->nullable();
                $table->string('clock_in_address')->nullable();  // reverse geocoded address
                $table->string('clock_out_address')->nullable();
                $table->enum('status', ['present', 'absent', 'late', 'permission', 'sick', 'annual_leave', 'holiday'])->default('absent');
                $table->integer('late_minutes')->nullable();
                $table->integer('work_minutes')->nullable();     // total jam kerja
                $table->integer('overtime_minutes')->nullable();
                $table->boolean('face_verified')->default(false);
                $table->decimal('face_confidence', 5, 2)->nullable(); // % confidence dari face API
                $table->text('note')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'date']); // 1 record per karyawan per hari
                $table->index(['store_id', 'date']);
                $table->index(['user_id', 'date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
