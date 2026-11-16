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
        // 1. Table Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->string('card_token', 64)->unique(); // Secret token for public digital card link
            $table->string('member_code', 32)->unique(); // Code for barcode/QR scan, e.g. VP-10023
            $table->string('tier')->default('Rookie'); // Rookie, Cloud Chaser, Master
            $table->integer('points')->default(0);
            $table->integer('liquid_stamp_count')->default(0); // 0 to 5 stamps
            $table->integer('liquid_stamp_rewards_available')->default(0); // Available free liquid/coils to claim
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->date('birth_date')->nullable();
            $table->string('favorite_flavor')->nullable(); // e.g. "Fruity dingin", "Creamy 6mg"
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Table Loyalty Logs
        Schema::create('loyalty_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Kasir/staff
            $table->string('type'); // earn_points, redeem_points, earn_stamp, redeem_stamp, tier_upgrade, manual_adjustment
            $table->integer('points_change')->default(0);
            $table->integer('stamps_change')->default(0);
            $table->integer('points_balance')->default(0);
            $table->integer('stamps_balance')->default(0);
            $table->string('description');
            $table->timestamps();
        });

        // 3. Add customer & loyalty columns to Sales
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('customer_name')->constrained('customers')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales', 'points_earned')) {
                $table->integer('points_earned')->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('sales', 'points_redeemed')) {
                $table->integer('points_redeemed')->default(0)->after('points_earned');
            }
            if (!Schema::hasColumn('sales', 'points_discount_amount')) {
                $table->decimal('points_discount_amount', 15, 2)->default(0)->after('points_redeemed');
            }
            if (!Schema::hasColumn('sales', 'liquid_stamps_earned')) {
                $table->integer('liquid_stamps_earned')->default(0)->after('points_discount_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn(['customer_id', 'points_earned', 'points_redeemed', 'points_discount_amount', 'liquid_stamps_earned']);
            }
        });

        Schema::dropIfExists('loyalty_logs');
        Schema::dropIfExists('customers');
    }
};

