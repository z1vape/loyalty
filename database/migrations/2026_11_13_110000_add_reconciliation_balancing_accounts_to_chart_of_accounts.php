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
                'code' => '3103',
                'name' => 'Penyesuaian Saldo Awal & Koreksi Saldo Toko',
                'type' => 'equity',
                'category' => 'Modal',
                'normal_balance' => 'credit',
                'is_system' => false,
                'description' => 'Penyesuaian selisih saldo awal atau koreksi ekuitas historis toko agar tidak mempengaruhi laba-rugi berjalan',
            ],
            [
                'code' => '4205',
                'name' => 'Pendapatan Selisih Kas & Rekonsiliasi Bank',
                'type' => 'revenue',
                'category' => 'Pendapatan Non-Usaha',
                'normal_balance' => 'credit',
                'is_system' => false,
                'description' => 'Pendapatan selisih lebih (cash overage) hasil pencocokan saldo kas/bank fisik dengan sistem',
            ],
            [
                'code' => '6111',
                'name' => 'Beban Selisih Kas & Rekonsiliasi Bank',
                'type' => 'expense',
                'category' => 'Beban Operasional',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Beban selisih kurang (cash shortage) hasil pencocokan saldo kas/bank fisik dengan sistem',
            ],
            [
                'code' => '6112',
                'name' => 'Beban Administrasi Bank & Biaya Layanan',
                'type' => 'expense',
                'category' => 'Beban Operasional',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Biaya potongan administrasi bulanan bank, biaya transfer, dan fee layanan perbankan',
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
        Account::withoutGlobalScopes()->whereIn('code', ['3103', '4205', '6111', '6112'])->delete();
    }
};

