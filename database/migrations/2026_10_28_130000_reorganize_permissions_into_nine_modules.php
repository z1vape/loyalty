<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $mapping = [
            // 1. Commercial
            'Attendance List'          => ['category' => 'Commercial', 'group_name' => 'KEHADIRAN & ABSENSI'],
            'Attendance Export'        => ['category' => 'Commercial', 'group_name' => 'KEHADIRAN & ABSENSI'],
            'Attendance Import'        => ['category' => 'Commercial', 'group_name' => 'KEHADIRAN & ABSENSI'],
            'Payroll List'             => ['category' => 'Commercial', 'group_name' => 'PAYROLL EMPLOYEE'],
            'Payroll Manage'           => ['category' => 'Commercial', 'group_name' => 'PAYROLL EMPLOYEE'],
            'Payroll Setting'          => ['category' => 'Commercial', 'group_name' => 'PAYROLL EMPLOYEE'],

            // 2. Operation
            'Inventory List'           => ['category' => 'Operation', 'group_name' => 'INVENTORY (PRODUCT)'],
            'Inventory Create'         => ['category' => 'Operation', 'group_name' => 'INVENTORY (PRODUCT)'],
            'Inventory Show'           => ['category' => 'Operation', 'group_name' => 'INVENTORY (PRODUCT)'],
            'Inventory Edit'           => ['category' => 'Operation', 'group_name' => 'INVENTORY (PRODUCT)'],
            'Inventory Store'          => ['category' => 'Operation', 'group_name' => 'INVENTORY (PRODUCT)'],
            'Inventory Update'         => ['category' => 'Operation', 'group_name' => 'INVENTORY (PRODUCT)'],
            'Inventory Destroy'        => ['category' => 'Operation', 'group_name' => 'INVENTORY (PRODUCT)'],
            'Stock In List'            => ['category' => 'Operation', 'group_name' => 'STOCK IN'],
            'Stock In Create'          => ['category' => 'Operation', 'group_name' => 'STOCK IN'],
            'Stock In Store'           => ['category' => 'Operation', 'group_name' => 'STOCK IN'],
            'Stock In Destroy'         => ['category' => 'Operation', 'group_name' => 'STOCK IN'],
            'Stock Out List'           => ['category' => 'Operation', 'group_name' => 'STOCK OUT'],
            'Stock Out Create'         => ['category' => 'Operation', 'group_name' => 'STOCK OUT'],
            'Stock Out Store'          => ['category' => 'Operation', 'group_name' => 'STOCK OUT'],
            'Stock Out Destroy'        => ['category' => 'Operation', 'group_name' => 'STOCK OUT'],
            'Sales List'               => ['category' => 'Operation', 'group_name' => 'SALES RECORDS'],
            'Sales Create'             => ['category' => 'Operation', 'group_name' => 'SALES RECORDS'],
            'Sales Show'               => ['category' => 'Operation', 'group_name' => 'SALES RECORDS'],
            'Sales Destroy'            => ['category' => 'Operation', 'group_name' => 'SALES RECORDS'],
            'Movement List'            => ['category' => 'Operation', 'group_name' => 'SALES & MOVEMENT RECORDS'],
            'Movement Show'            => ['category' => 'Operation', 'group_name' => 'SALES & MOVEMENT RECORDS'],
            'Movement Destroy'         => ['category' => 'Operation', 'group_name' => 'SALES & MOVEMENT RECORDS'],

            // 3. Purchasing
            'Accounting Payable Manage'=> ['category' => 'Purchasing', 'group_name' => 'HUTANG PEMBELIAN SUPPLIER'],

            // 4. Finance
            'Income List'              => ['category' => 'Finance', 'group_name' => 'PEMASUKAN TOKO'],
            'Income Create'            => ['category' => 'Finance', 'group_name' => 'PEMASUKAN TOKO'],
            'Income Edit'              => ['category' => 'Finance', 'group_name' => 'PEMASUKAN TOKO'],
            'Income Destroy'           => ['category' => 'Finance', 'group_name' => 'PEMASUKAN TOKO'],
            'Expense List'             => ['category' => 'Finance', 'group_name' => 'PENGELUARAN TOKO'],
            'Expense Create'           => ['category' => 'Finance', 'group_name' => 'PENGELUARAN TOKO'],
            'Expense Edit'             => ['category' => 'Finance', 'group_name' => 'PENGELUARAN TOKO'],
            'Expense Destroy'          => ['category' => 'Finance', 'group_name' => 'PENGELUARAN TOKO'],

            // 5. Accounting
            'Accounting CoA List'      => ['category' => 'Accounting', 'group_name' => 'BAGAN AKUN (COA)'],
            'Accounting CoA Create'    => ['category' => 'Accounting', 'group_name' => 'BAGAN AKUN (COA)'],
            'Accounting CoA Edit'      => ['category' => 'Accounting', 'group_name' => 'BAGAN AKUN (COA)'],
            'Accounting CoA Delete'    => ['category' => 'Accounting', 'group_name' => 'BAGAN AKUN (COA)'],
            'Accounting Journal List'  => ['category' => 'Accounting', 'group_name' => 'JURNAL TRANSAKSI'],
            'Accounting Journal Create'=> ['category' => 'Accounting', 'group_name' => 'JURNAL TRANSAKSI'],
            'Accounting Ledger View'   => ['category' => 'Accounting', 'group_name' => 'BUKU BESAR'],
            'Accounting Report View'   => ['category' => 'Accounting', 'group_name' => 'LAPORAN KEUANGAN'],
            'Accounting Transfer Manage'=>['category' => 'Accounting', 'group_name' => 'KAS & BANK TRANSFER'],
            'Accounting Period Manage' => ['category' => 'Accounting', 'group_name' => 'TUTUP BUKU / PERIODE'],

            // 6. Reports
            'Report List'              => ['category' => 'Reports', 'group_name' => 'LAPORAN & ANALITIK'],
            'Report SKU'               => ['category' => 'Reports', 'group_name' => 'LAPORAN & ANALITIK'],
            'Report Category'          => ['category' => 'Reports', 'group_name' => 'LAPORAN & ANALITIK'],
            'Report Staff'             => ['category' => 'Reports', 'group_name' => 'LAPORAN & ANALITIK'],

            // 7. Master Data
            'Store List'               => ['category' => 'Master Data', 'group_name' => 'CABANG TOKO'],
            'Store Create'             => ['category' => 'Master Data', 'group_name' => 'CABANG TOKO'],
            'Store Show'               => ['category' => 'Master Data', 'group_name' => 'CABANG TOKO'],
            'Store Edit'               => ['category' => 'Master Data', 'group_name' => 'CABANG TOKO'],
            'Store Store'              => ['category' => 'Master Data', 'group_name' => 'CABANG TOKO'],
            'Store Update'             => ['category' => 'Master Data', 'group_name' => 'CABANG TOKO'],
            'Store Destroy'            => ['category' => 'Master Data', 'group_name' => 'CABANG TOKO'],
            'Shift List'               => ['category' => 'Master Data', 'group_name' => 'MASTER DATA SHIFT'],
            'Shift Create'             => ['category' => 'Master Data', 'group_name' => 'MASTER DATA SHIFT'],
            'Shift Edit'               => ['category' => 'Master Data', 'group_name' => 'MASTER DATA SHIFT'],
            'Shift Destroy'            => ['category' => 'Master Data', 'group_name' => 'MASTER DATA SHIFT'],
            'Attendance Status List'   => ['category' => 'Master Data', 'group_name' => 'STATUS KEHADIRAN'],
            'Attendance Status Create' => ['category' => 'Master Data', 'group_name' => 'STATUS KEHADIRAN'],
            'Attendance Status Edit'   => ['category' => 'Master Data', 'group_name' => 'STATUS KEHADIRAN'],
            'Attendance Status Destroy'=> ['category' => 'Master Data', 'group_name' => 'STATUS KEHADIRAN'],
            'User Schedule List'       => ['category' => 'Master Data', 'group_name' => 'JADWAL KERJA (ROSTER)'],
            'User Schedule Manage'     => ['category' => 'Master Data', 'group_name' => 'JADWAL KERJA (ROSTER)'],
            'Shift Pattern List'       => ['category' => 'Master Data', 'group_name' => 'POLA SHIFT (PATTERN)'],
            'Shift Pattern Create'     => ['category' => 'Master Data', 'group_name' => 'POLA SHIFT (PATTERN)'],
            'Shift Pattern Edit'       => ['category' => 'Master Data', 'group_name' => 'POLA SHIFT (PATTERN)'],
            'Shift Pattern Destroy'    => ['category' => 'Master Data', 'group_name' => 'POLA SHIFT (PATTERN)'],
            'Setting List'             => ['category' => 'Master Data', 'group_name' => 'PENGATURAN DROPDOWN'],
            'Setting Create'           => ['category' => 'Master Data', 'group_name' => 'PENGATURAN DROPDOWN'],
            'Setting Destroy'          => ['category' => 'Master Data', 'group_name' => 'PENGATURAN DROPDOWN'],

            // 8. Z1 APP
            'Z1 App Access'            => ['category' => 'Z1 APP', 'group_name' => 'Z1 APP PORTAL'],
            'Z1 App Attendance'        => ['category' => 'Z1 APP', 'group_name' => 'Z1 APP PORTAL'],
            'Z1 App Purchase Request'  => ['category' => 'Z1 APP', 'group_name' => 'Z1 APP PORTAL'],
            'Z1 App Sales Report'      => ['category' => 'Z1 APP', 'group_name' => 'Z1 APP PORTAL'],

            // 9. Z1 Admin
            'Dashboard List'           => ['category' => 'Z1 Admin', 'group_name' => 'DASHBOARD'],
            'User List'                => ['category' => 'Z1 Admin', 'group_name' => 'USER MANAGEMENT'],
            'User Create'              => ['category' => 'Z1 Admin', 'group_name' => 'USER MANAGEMENT'],
            'User Show'                => ['category' => 'Z1 Admin', 'group_name' => 'USER MANAGEMENT'],
            'User Edit'                => ['category' => 'Z1 Admin', 'group_name' => 'USER MANAGEMENT'],
            'User Store'               => ['category' => 'Z1 Admin', 'group_name' => 'USER MANAGEMENT'],
            'User Update'              => ['category' => 'Z1 Admin', 'group_name' => 'USER MANAGEMENT'],
            'User Destroy'             => ['category' => 'Z1 Admin', 'group_name' => 'USER MANAGEMENT'],
            'Role List'                => ['category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION'],
            'Role Create'              => ['category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION'],
            'Role Show'                => ['category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION'],
            'Role Edit'                => ['category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION'],
            'Role Store'               => ['category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION'],
            'Role Update'              => ['category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION'],
            'Role Destroy'             => ['category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION'],
            'Permission List'          => ['category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION'],
            'Permission Create'        => ['category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION'],
            'Permission Destroy'       => ['category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION'],
        ];

        foreach ($mapping as $name => $data) {
            Permission::where('name', $name)->update($data);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversal logic optional
    }
};
