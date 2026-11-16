<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shifts')) {
            Schema::table('shifts', function (Blueprint $table) {
                if (!Schema::hasColumn('shifts', 'early_clock_in_minutes')) {
                    $table->integer('early_clock_in_minutes')->default(120)->after('late_tolerance_minutes');
                }
                if (!Schema::hasColumn('shifts', 'max_clock_out_delay_minutes')) {
                    $table->integer('max_clock_out_delay_minutes')->default(240)->after('early_clock_in_minutes');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shifts')) {
            Schema::table('shifts', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('shifts', 'early_clock_in_minutes')) {
                    $columns[] = 'early_clock_in_minutes';
                }
                if (Schema::hasColumn('shifts', 'max_clock_out_delay_minutes')) {
                    $columns[] = 'max_clock_out_delay_minutes';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};

