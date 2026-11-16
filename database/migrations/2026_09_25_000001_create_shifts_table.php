<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shifts')) {
            Schema::create('shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
                $table->string('name');                          // e.g. "Pagi", "Siang", "Malam"
                $table->time('start_time');                     // jam masuk
                $table->time('end_time');                       // jam pulang
                $table->integer('late_tolerance_minutes')->default(15); // toleransi terlambat
                $table->boolean('is_overnight')->default(false); // shift lintas tengah malam
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
