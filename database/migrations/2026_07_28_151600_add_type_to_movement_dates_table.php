<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movement_dates', function (Blueprint $table) {
            $table->string('type')->default('in')->after('date');
            $table->dropUnique(['date', 'store_id']);
            $table->unique(['date', 'type', 'store_id']);
        });

        // Duplicate/create 'out' MovementDate for existing records that contain 'out' stock movements
        $existingDates = DB::table('movement_dates')->get();
        foreach ($existingDates as $md) {
            $hasOut = DB::table('stock_movements')
                ->where('movement_date', $md->date)
                ->where('type', 'out')
                ->exists();

            if ($hasOut) {
                DB::table('movement_dates')->updateOrInsert(
                    [
                        'date' => $md->date,
                        'type' => 'out',
                        'store_id' => $md->store_id,
                    ],
                    [
                        'created_by' => $md->created_by,
                        'notes' => $md->notes,
                        'created_at' => $md->created_at,
                        'updated_at' => $md->updated_at,
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movement_dates', function (Blueprint $table) {
            $table->dropUnique(['date', 'type', 'store_id']);
            $table->dropColumn('type');
            $table->unique(['date', 'store_id']);
        });
    }
};
