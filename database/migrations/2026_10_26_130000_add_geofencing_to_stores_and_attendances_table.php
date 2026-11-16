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
        // 1. Add geofence coordinates & radius to stores table
        if (Schema::hasTable('stores')) {
            Schema::table('stores', function (Blueprint $table) {
                if (!Schema::hasColumn('stores', 'latitude')) {
                    $table->decimal('latitude', 10, 8)->nullable()->after('address');
                }
                if (!Schema::hasColumn('stores', 'longitude')) {
                    $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
                }
                if (!Schema::hasColumn('stores', 'radius_meters')) {
                    $table->unsignedInteger('radius_meters')->default(100)->after('longitude');
                }
            });
        }

        // 2. Add distance tracking to attendances table
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('attendances', 'clock_in_distance_meters')) {
                    $table->unsignedInteger('clock_in_distance_meters')->nullable()->after('clock_in_lng');
                }
                if (!Schema::hasColumn('attendances', 'clock_out_distance_meters')) {
                    $table->unsignedInteger('clock_out_distance_meters')->nullable()->after('clock_out_lng');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stores')) {
            Schema::table('stores', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('stores', 'latitude')) $columns[] = 'latitude';
                if (Schema::hasColumn('stores', 'longitude')) $columns[] = 'longitude';
                if (Schema::hasColumn('stores', 'radius_meters')) $columns[] = 'radius_meters';
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('attendances', 'clock_in_distance_meters')) $columns[] = 'clock_in_distance_meters';
                if (Schema::hasColumn('attendances', 'clock_out_distance_meters')) $columns[] = 'clock_out_distance_meters';
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
