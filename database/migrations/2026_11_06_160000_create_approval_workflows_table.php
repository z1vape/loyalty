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
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('module')->index(); // e.g. purchase_request, purchase_order
            $table->string('action_key')->unique(); // e.g. pr_approved, po_sent
            $table->string('module_name'); // e.g. Purchase Request, Purchase Order
            $table->string('status_from')->default('pending'); // e.g. pending, draft
            $table->string('status_to')->default('approved'); // e.g. approved, sent
            $table->string('label'); // e.g. Create PR -> Approved
            $table->text('description')->nullable();
            $table->json('role_ids')->nullable(); // Array of allowed role IDs
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default initial workflows
        $now = now();
        DB::table('approval_workflows')->insert([
            [
                'module' => 'purchase_request',
                'action_key' => 'purchase_request_approved',
                'module_name' => 'Purchase Request (Order Barang Cabang)',
                'status_from' => 'pending',
                'status_to' => 'approved',
                'label' => 'Create PR -> Approved (Persetujuan Permintaan)',
                'description' => 'Role yang berhak menyetujui (approve) atau menolak order restock barang dari cabang.',
                'role_ids' => json_encode([1, 2]), // Super Admin (1) & Manager (2)
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module' => 'purchase_order',
                'action_key' => 'purchase_order_sent',
                'module_name' => 'Purchase Order (PO Supplier)',
                'status_from' => 'draft',
                'status_to' => 'sent',
                'label' => 'Draft PO -> Sent (Kirim ke Supplier)',
                'description' => 'Role yang berhak menerbitkan dan mengirim Purchase Order resmi ke supplier luar.',
                'role_ids' => json_encode([1, 2]), // Super Admin (1) & Manager (2)
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_workflows');
    }
};

