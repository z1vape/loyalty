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
        // 1. Add code and color to shifts table if not present
        if (Schema::hasTable('shifts')) {
            Schema::table('shifts', function (Blueprint $table) {
                if (!Schema::hasColumn('shifts', 'code')) {
                    $table->string('code', 20)->nullable()->after('store_id');
                }
                if (!Schema::hasColumn('shifts', 'color')) {
                    $table->string('color', 20)->nullable()->default('#006c49')->after('is_active');
                }
                if (!Schema::hasColumn('shifts', 'description')) {
                    $table->string('description')->nullable()->after('name');
                }
            });
        }

        // 2. Create attendance_statuses table
        if (!Schema::hasTable('attendance_statuses')) {
            Schema::create('attendance_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique();
                $table->string('name', 100);
                $table->string('type', 30)->default('present'); // present, late, leave, sick, holiday, absent, overtime
                $table->string('badge_color', 30)->default('emerald'); // emerald, amber, sky, orange, purple, slate, rose
                $table->boolean('counts_as_workday')->default(true);
                $table->boolean('requires_attachment')->default(false);
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Create shift_patterns table
        if (!Schema::hasTable('shift_patterns')) {
            Schema::create('shift_patterns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->integer('cycle_days')->default(7); // e.g. 7 days cycle
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. Create shift_pattern_items table
        if (!Schema::hasTable('shift_pattern_items')) {
            Schema::create('shift_pattern_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shift_pattern_id')->constrained('shift_patterns')->cascadeOnDelete();
                $table->integer('day_number'); // 1, 2, ..., cycle_days
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->boolean('is_off')->default(false);
                $table->timestamps();
            });
        }

        // 5. Create user_schedules table
        if (!Schema::hasTable('user_schedules')) {
            Schema::create('user_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
                $table->date('date');
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->boolean('is_off')->default(false);
                $table->string('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'date']);
                $table->index(['store_id', 'date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_schedules');
        Schema::dropIfExists('shift_pattern_items');
        Schema::dropIfExists('shift_patterns');
        Schema::dropIfExists('attendance_statuses');

        if (Schema::hasTable('shifts')) {
            Schema::table('shifts', function (Blueprint $table) {
                if (Schema::hasColumn('shifts', 'code')) {
                    $table->dropColumn('code');
                }
                if (Schema::hasColumn('shifts', 'color')) {
                    $table->dropColumn('color');
                }
                if (Schema::hasColumn('shifts', 'description')) {
                    $table->dropColumn('description');
                }
            });
        }
    }
};
