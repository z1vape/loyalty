<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membersihkan data lembur reguler yang di bawah 1 jam (1-59 menit) menjadi 0.
     */
    public function up(): void
    {
        if (Schema::hasTable('attendances')) {
            DB::table('attendances')
                ->where('overtime_minutes', '>', 0)
                ->where('overtime_minutes', '<', 60)
                ->update(['overtime_minutes' => 0]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op rollback
    }
};

