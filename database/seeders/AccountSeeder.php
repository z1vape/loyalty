<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Standard Chart of Accounts template for retail stores.
     */
    public static function defaultAccounts(): array
    {
        return [
            // 1. Assets (Aset / Aktiva)
            [
                'code' => '1101',
                'name' => 'Kas Toko',
                'type' => 'asset',
                'category' => 'Kas & Bank',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Kas tunai fisik pada laci kasir toko',
            ],
            [
                'code' => '1102',
                'name' => 'Bank Toko',
                'type' => 'asset',
                'category' => 'Kas & Bank',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Rekening bank operasional toko untuk transfer dan settlement',
            ],
            [
                'code' => '1103',
                'name' => 'Piutang QRIS & EDC',
                'type' => 'asset',
                'category' => 'Kas & Bank',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Dana pending settlement dari transaksi QRIS dan kartu debit/kredit',
            ],
            [
                'code' => '1201',
                'name' => 'Persediaan Barang Dagang',
                'type' => 'asset',
                'category' => 'Persediaan',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Nilai aset persediaan produk retail yang siap dijual',
            ],
            [
                'code' => '1104',
                'name' => 'Piutang Antar Cabang / Toko',
                'type' => 'asset',
                'category' => 'Piutang & Talangan',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Dana talangan pengeluaran atau operasional untuk cabang/toko lain',
            ],
            [
                'code' => '1105',
                'name' => 'Piutang Kasbon Karyawan',
                'type' => 'asset',
                'category' => 'Piutang & Talangan',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Pinjaman sementara atau kasbon staf toko yang akan dipotong saat penggajian',
            ],

            // 2. Liabilities (Kewajiban / Hutang)
            [
                'code' => '2101',
                'name' => 'Hutang Dagang / Supplier',
                'type' => 'liability',
                'category' => 'Kewajiban Lancar',
                'normal_balance' => 'credit',
                'is_system' => true,
                'description' => 'Kewajiban pembayaran pembelian stok tempo kepada supplier/distributor',
            ],
            [
                'code' => '2102',
                'name' => 'Hutang Antar Cabang / Toko',
                'type' => 'liability',
                'category' => 'Kewajiban Lancar',
                'normal_balance' => 'credit',
                'is_system' => false,
                'description' => 'Kewajiban pembayaran atas biaya/pengeluaran toko ini yang ditalangi cabang lain',
            ],
            [
                'code' => '2103',
                'name' => 'Hutang Talangan Pribadi / Reimbursement',
                'type' => 'liability',
                'category' => 'Kewajiban Lancar',
                'normal_balance' => 'credit',
                'is_system' => false,
                'description' => 'Kewajiban penggantian uang pribadi karyawan atau pemilik atas pengeluaran darurat toko',
            ],
            [
                'code' => '2105',
                'name' => 'Hutang Warisan Manajemen Lama',
                'type' => 'liability',
                'category' => 'Kewajiban Lancar',
                'normal_balance' => 'credit',
                'is_system' => false,
                'description' => 'Kewajiban utang masa lalu manajemen lama (VB, Vapezoo, Vape On) yang ditanggung bersama',
            ],

            // 3. Equity (Ekuitas / Modal)
            [
                'code' => '3101',
                'name' => 'Modal Pemilik',
                'type' => 'equity',
                'category' => 'Modal',
                'normal_balance' => 'credit',
                'is_system' => true,
                'description' => 'Setoran modal awal pemilik usaha untuk toko',
            ],
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
                'code' => '3103',
                'name' => 'Penyesuaian Saldo Awal & Koreksi Saldo Toko',
                'type' => 'equity',
                'category' => 'Modal',
                'normal_balance' => 'credit',
                'is_system' => false,
                'description' => 'Penyesuaian selisih saldo awal atau koreksi ekuitas historis toko agar tidak mempengaruhi laba-rugi berjalan',
            ],
            [
                'code' => '3201',
                'name' => 'Laba Ditahan',
                'type' => 'equity',
                'category' => 'Modal',
                'normal_balance' => 'credit',
                'is_system' => true,
                'description' => 'Akumulasi laba/rugi bersih dari periode-periode sebelumnya',
            ],

            // 4. Revenue (Pendapatan Usaha)
            [
                'code' => '4101',
                'name' => 'Pendapatan Penjualan',
                'type' => 'revenue',
                'category' => 'Pendapatan Usaha',
                'normal_balance' => 'credit',
                'is_system' => true,
                'description' => 'Pendapatan kotor dari hasil penjualan produk toko',
            ],
            [
                'code' => '4102',
                'name' => 'Diskon Penjualan',
                'type' => 'revenue',
                'category' => 'Potongan Penjualan',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Contra-revenue akun potongan harga / diskon penjualan kasir',
            ],
            [
                'code' => '4103',
                'name' => 'Pendapatan Jasa Coiling & Build',
                'type' => 'revenue',
                'category' => 'Pendapatan Usaha',
                'normal_balance' => 'credit',
                'is_system' => false,
                'description' => 'Pendapatan jasa pasang coil, wicking kapas, dan servis perangkat vape',
            ],
            [
                'code' => '4201',
                'name' => 'Pendapatan Lain-lain',
                'type' => 'revenue',
                'category' => 'Pendapatan Non-Usaha',
                'normal_balance' => 'credit',
                'is_system' => true,
                'description' => 'Pendapatan di luar penjualan utama (jasa, komisi, titipan)',
            ],
            [
                'code' => '4202',
                'name' => 'Bonus & Insentif Supplier',
                'type' => 'revenue',
                'category' => 'Pendapatan Non-Usaha',
                'normal_balance' => 'credit',
                'is_system' => false,
                'description' => 'Rebate, cashback, dan bonus reward dari distributor/supplier vape',
            ],
            [
                'code' => '4203',
                'name' => 'Pendapatan Komisi & Konsinyasi',
                'type' => 'revenue',
                'category' => 'Pendapatan Non-Usaha',
                'normal_balance' => 'credit',
                'is_system' => false,
                'description' => 'Fee komisi atau bagi hasil produk titip jual / konsinyasi pihak ketiga',
            ],
            [
                'code' => '4204',
                'name' => 'Pendapatan Bunga & Jasa Bank',
                'type' => 'revenue',
                'category' => 'Pendapatan Non-Usaha',
                'normal_balance' => 'credit',
                'is_system' => false,
                'description' => 'Bunga tabungan operasional, jasa giro, atau pendapatan finansial perbankan',
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

            // 5. Cost of Goods Sold (Beban Pokok Penjualan)
            [
                'code' => '5101',
                'name' => 'Beban Pokok Penjualan (HPP)',
                'type' => 'expense',
                'category' => 'HPP',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Biaya modal barang dagangan yang telah terjual berdasarkan moving average',
            ],

            // 6. Operating Expenses (Beban Operasional)
            [
                'code' => '6101',
                'name' => 'Beban Gaji Karyawan',
                'type' => 'expense',
                'category' => 'Beban Operasional',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Gaji, upah shift, dan tunjangan staf toko',
            ],
            [
                'code' => '6102',
                'name' => 'Beban Sewa Tempat',
                'type' => 'expense',
                'category' => 'Beban Operasional',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Biaya sewa ruko, kios, atau tempat operasional toko',
            ],
            [
                'code' => '6103',
                'name' => 'Beban Listrik, Air & Internet',
                'type' => 'expense',
                'category' => 'Beban Operasional',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Tagihan utilitas toko harian dan bulanan',
            ],
            [
                'code' => '6104',
                'name' => 'Beban Perlengkapan & Operasional',
                'type' => 'expense',
                'category' => 'Beban Operasional',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Plastik packing, struk thermal, kebersihan, dan consumable toko',
            ],
            [
                'code' => '6105',
                'name' => 'Beban Selisih Stok (Loss/Shrinkage)',
                'type' => 'expense',
                'category' => 'Beban Operasional',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Beban kerugian akibat barang rusak, bocor, hilang, atau selisih stock opname',
            ],
            [
                'code' => '6106',
                'name' => 'Beban Konsumsi & Pantry Toko',
                'type' => 'expense',
                'category' => 'Beban Operasional',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Konsumsi makan lembur, air galon, kopi, dan kebutuhan pantry karyawan',
            ],
            [
                'code' => '6107',
                'name' => 'Beban Pemasaran & Promosi',
                'type' => 'expense',
                'category' => 'Beban Operasional',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Biaya iklan medsos (IG/TikTok), cetak spanduk, banner, dan event promo',
            ],
            [
                'code' => '6108',
                'name' => 'Beban Transport & Pengiriman',
                'type' => 'expense',
                'category' => 'Beban Operasional',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Bensin operasional toko, parkir, dan ongkos kirim paket / kurir instan',
            ],
            [
                'code' => '6109',
                'name' => 'Beban Perawatan & Servis Toko',
                'type' => 'expense',
                'category' => 'Beban Operasional',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Perbaikan perlengkapan toko, servis AC, ganti lampu etalase, dan kebersihan',
            ],
            [
                'code' => '6110',
                'name' => 'Beban Operasional Lain-lain',
                'type' => 'expense',
                'category' => 'Beban Operasional',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Pengeluaran operasional umum lainnya yang tidak tercakup dalam kategori spesifik',
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
            [
                'code' => '6201',
                'name' => 'Beban Bagi Hasil Mitra & Pengelola',
                'type' => 'expense',
                'category' => 'Beban Operasional',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Biaya bagi hasil atau komisi profit sharing operasional kepada mitra pengelola toko (memotong laba bersih operasional toko)',
            ],
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::all();
        $defaultTemplate = self::defaultAccounts();

        foreach ($stores as $store) {
            foreach ($defaultTemplate as $accountData) {
                Account::withoutGlobalScopes()->firstOrCreate(
                    [
                        'store_id' => $store->id,
                        'code' => $accountData['code'],
                    ],
                    array_merge($accountData, ['store_id' => $store->id])
                );
            }
        }
    }
}
