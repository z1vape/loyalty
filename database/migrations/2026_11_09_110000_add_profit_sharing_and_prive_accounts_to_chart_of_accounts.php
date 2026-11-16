<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Store;
use App\Models\Account;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $stores = Store::all();

        $accountsToAdd = [
            [
                'code' => '3102',
                'name' => 'Prive & Bagi Hasil Pemilik / Investor',
                'type' => 'equity',
                'category' => 'Modal',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Penarikan dividen keuntungan atau transfer bagi hasil (profit sharing) kepada pemilik atau investor toko (tidak memotong laba bersih usaha)',
            ],
            [
                'code' => '6201',
                'name' => 'Beban Bagi Hasil Mitra & Pengelola',
                'type' => 'expense',
                'category' => 'Beban Operasional',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Biaya bagi hasil atau komisi profit sharing kepada mitra pengelola / pihak ketiga (memotong laba bersih operasional toko)',
            ],
        ];

        foreach ($stores as $store) {
            foreach ($accountsToAdd as $accData) {
                Account::withoutGlobalScopes()->firstOrCreate(
                    [
                        'store_id' => $store->id,
                        'code' => $accData['code'],
                    ],
                    array_merge($accData, [
                        'store_id' => $store->id,
                        'is_active' => true,
                    ])
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Account::withoutGlobalScopes()->whereIn('code', ['3102', '6201'])->delete();
    }
};

