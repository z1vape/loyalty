<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\MovementDate;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 0. Seed Permissions (9 Core Modules: Commercial, Operation, Purchasing, Finance, Accounting, Reports, Master Data, Z1 APP, Z1 Admin)
        $permissions = [
            // 1. Commercial (6 Permissions)
            ['name' => 'Attendance List', 'category' => 'Commercial', 'group_name' => 'KEHADIRAN & ABSENSI', 'description' => 'Ability to view employee attendance records'],
            ['name' => 'Attendance Export', 'category' => 'Commercial', 'group_name' => 'KEHADIRAN & ABSENSI', 'description' => 'Ability to export attendance records to excel'],
            ['name' => 'Attendance Import', 'category' => 'Commercial', 'group_name' => 'KEHADIRAN & ABSENSI', 'description' => 'Ability to import attendance records'],
            ['name' => 'Payroll List', 'category' => 'Commercial', 'group_name' => 'PAYROLL EMPLOYEE', 'description' => 'Ability to view employee payroll records and slips'],
            ['name' => 'Payroll Manage', 'category' => 'Commercial', 'group_name' => 'PAYROLL EMPLOYEE', 'description' => 'Ability to generate, recalculate, and adjust employee payrolls'],
            ['name' => 'Payroll Setting', 'category' => 'Commercial', 'group_name' => 'PAYROLL EMPLOYEE', 'description' => 'Ability to configure employee base salary, hourly rates, and bank accounts'],

            // 2. Operation (22 Permissions)
            ['name' => 'Inventory List', 'category' => 'Operation', 'group_name' => 'INVENTORY (PRODUCT)', 'description' => 'Ability to view list of products'],
            ['name' => 'Inventory Create', 'category' => 'Operation', 'group_name' => 'INVENTORY (PRODUCT)', 'description' => 'Ability to view create product form'],
            ['name' => 'Inventory Show', 'category' => 'Operation', 'group_name' => 'INVENTORY (PRODUCT)', 'description' => 'Ability to view product details'],
            ['name' => 'Inventory Edit', 'category' => 'Operation', 'group_name' => 'INVENTORY (PRODUCT)', 'description' => 'Ability to view edit product form'],
            ['name' => 'Inventory Store', 'category' => 'Operation', 'group_name' => 'INVENTORY (PRODUCT)', 'description' => 'Ability to store new product in database'],
            ['name' => 'Inventory Update', 'category' => 'Operation', 'group_name' => 'INVENTORY (PRODUCT)', 'description' => 'Ability to update product details in database'],
            ['name' => 'Inventory Destroy', 'category' => 'Operation', 'group_name' => 'INVENTORY (PRODUCT)', 'description' => 'Ability to delete a product'],
            ['name' => 'Stock In List', 'category' => 'Operation', 'group_name' => 'STOCK IN', 'description' => 'Ability to view stock in records'],
            ['name' => 'Stock In Create', 'category' => 'Operation', 'group_name' => 'STOCK IN', 'description' => 'Ability to view stock in form'],
            ['name' => 'Stock In Store', 'category' => 'Operation', 'group_name' => 'STOCK IN', 'description' => 'Ability to log stock in movements'],
            ['name' => 'Stock In Destroy', 'category' => 'Operation', 'group_name' => 'STOCK IN', 'description' => 'Ability to delete stock in records'],
            ['name' => 'Stock Out List', 'category' => 'Operation', 'group_name' => 'STOCK OUT', 'description' => 'Ability to view stock out records'],
            ['name' => 'Stock Out Create', 'category' => 'Operation', 'group_name' => 'STOCK OUT', 'description' => 'Ability to view stock out form'],
            ['name' => 'Stock Out Store', 'category' => 'Operation', 'group_name' => 'STOCK OUT', 'description' => 'Ability to log stock out movements'],
            ['name' => 'Stock Out Destroy', 'category' => 'Operation', 'group_name' => 'STOCK OUT', 'description' => 'Ability to delete stock out records'],
            ['name' => 'Sales List', 'category' => 'Operation', 'group_name' => 'SALES RECORDS', 'description' => 'Ability to view sales history'],
            ['name' => 'Sales Create', 'category' => 'Operation', 'group_name' => 'SALES RECORDS', 'description' => 'Ability to create new sales'],
            ['name' => 'Sales Show', 'category' => 'Operation', 'group_name' => 'SALES RECORDS', 'description' => 'Ability to view details of specific sales'],
            ['name' => 'Sales Destroy', 'category' => 'Operation', 'group_name' => 'SALES RECORDS', 'description' => 'Ability to delete sales records'],
            ['name' => 'Movement List', 'category' => 'Operation', 'group_name' => 'SALES & MOVEMENT RECORDS', 'description' => 'Ability to view stock movement history'],
            ['name' => 'Movement Show', 'category' => 'Operation', 'group_name' => 'SALES & MOVEMENT RECORDS', 'description' => 'Ability to view details of specific movements'],
            ['name' => 'Movement Destroy', 'category' => 'Operation', 'group_name' => 'SALES & MOVEMENT RECORDS', 'description' => 'Ability to delete movement records'],

            // 3. Purchasing (1 Permission)
            ['name' => 'Accounting Payable Manage', 'category' => 'Purchasing', 'group_name' => 'HUTANG PEMBELIAN SUPPLIER', 'description' => 'Ability to view and record repayments for supplier debts'],

            // 4. Finance (8 Permissions)
            ['name' => 'Income List', 'category' => 'Finance', 'group_name' => 'PEMASUKAN TOKO', 'description' => 'Ability to view store incomes list'],
            ['name' => 'Income Create', 'category' => 'Finance', 'group_name' => 'PEMASUKAN TOKO', 'description' => 'Ability to add store income'],
            ['name' => 'Income Edit', 'category' => 'Finance', 'group_name' => 'PEMASUKAN TOKO', 'description' => 'Ability to edit store income'],
            ['name' => 'Income Destroy', 'category' => 'Finance', 'group_name' => 'PEMASUKAN TOKO', 'description' => 'Ability to delete store income'],
            ['name' => 'Expense List', 'category' => 'Finance', 'group_name' => 'PENGELUARAN TOKO', 'description' => 'Ability to view store expenses list'],
            ['name' => 'Expense Create', 'category' => 'Finance', 'group_name' => 'PENGELUARAN TOKO', 'description' => 'Ability to add store expense'],
            ['name' => 'Expense Edit', 'category' => 'Finance', 'group_name' => 'PENGELUARAN TOKO', 'description' => 'Ability to edit store expense'],
            ['name' => 'Expense Destroy', 'category' => 'Finance', 'group_name' => 'PENGELUARAN TOKO', 'description' => 'Ability to delete store expense'],

            // 5. Accounting (10 Permissions)
            ['name' => 'Accounting CoA List', 'category' => 'Accounting', 'group_name' => 'BAGAN AKUN (COA)', 'description' => 'Ability to view Chart of Accounts list'],
            ['name' => 'Accounting CoA Create', 'category' => 'Accounting', 'group_name' => 'BAGAN AKUN (COA)', 'description' => 'Ability to create new custom account'],
            ['name' => 'Accounting CoA Edit', 'category' => 'Accounting', 'group_name' => 'BAGAN AKUN (COA)', 'description' => 'Ability to edit custom account details'],
            ['name' => 'Accounting CoA Delete', 'category' => 'Accounting', 'group_name' => 'BAGAN AKUN (COA)', 'description' => 'Ability to delete non-system account'],
            ['name' => 'Accounting Journal List', 'category' => 'Accounting', 'group_name' => 'JURNAL TRANSAKSI', 'description' => 'Ability to view journal entries'],
            ['name' => 'Accounting Journal Create', 'category' => 'Accounting', 'group_name' => 'JURNAL TRANSAKSI', 'description' => 'Ability to create manual journal entry'],
            ['name' => 'Accounting Ledger View', 'category' => 'Accounting', 'group_name' => 'BUKU BESAR', 'description' => 'Ability to view General Ledger'],
            ['name' => 'Accounting Report View', 'category' => 'Accounting', 'group_name' => 'LAPORAN KEUANGAN', 'description' => 'Ability to view Profit & Loss, Balance Sheet, and Trial Balance'],
            ['name' => 'Accounting Transfer Manage', 'category' => 'Accounting', 'group_name' => 'KAS & BANK TRANSFER', 'description' => 'Ability to record internal fund transfers between cash and bank'],
            ['name' => 'Accounting Period Manage', 'category' => 'Accounting', 'group_name' => 'TUTUP BUKU / PERIODE', 'description' => 'Ability to close and reopen accounting periods'],

            // 6. Reports (4 Permissions)
            ['name' => 'Report List', 'category' => 'Reports', 'group_name' => 'LAPORAN & ANALITIK', 'description' => 'Ability to access reports section'],
            ['name' => 'Report SKU', 'category' => 'Reports', 'group_name' => 'LAPORAN & ANALITIK', 'description' => 'Ability to view sales report by SKU'],
            ['name' => 'Report Category', 'category' => 'Reports', 'group_name' => 'LAPORAN & ANALITIK', 'description' => 'Ability to view sales report by category'],
            ['name' => 'Report Staff', 'category' => 'Reports', 'group_name' => 'LAPORAN & ANALITIK', 'description' => 'Ability to view sales and movement performance report by staff'],

            // 7. Master Data (24 Permissions)
            ['name' => 'Store List', 'category' => 'Master Data', 'group_name' => 'CABANG TOKO', 'description' => 'Ability to view list of stores'],
            ['name' => 'Store Create', 'category' => 'Master Data', 'group_name' => 'CABANG TOKO', 'description' => 'Ability to view add store form'],
            ['name' => 'Store Show', 'category' => 'Master Data', 'group_name' => 'CABANG TOKO', 'description' => 'Ability to view store details'],
            ['name' => 'Store Edit', 'category' => 'Master Data', 'group_name' => 'CABANG TOKO', 'description' => 'Ability to view edit store form'],
            ['name' => 'Store Store', 'category' => 'Master Data', 'group_name' => 'CABANG TOKO', 'description' => 'Ability to store new store'],
            ['name' => 'Store Update', 'category' => 'Master Data', 'group_name' => 'CABANG TOKO', 'description' => 'Ability to update store details'],
            ['name' => 'Store Destroy', 'category' => 'Master Data', 'group_name' => 'CABANG TOKO', 'description' => 'Ability to delete a store'],
            ['name' => 'Shift List', 'category' => 'Master Data', 'group_name' => 'MASTER DATA SHIFT', 'description' => 'Ability to view work shifts'],
            ['name' => 'Shift Create', 'category' => 'Master Data', 'group_name' => 'MASTER DATA SHIFT', 'description' => 'Ability to create new work shift'],
            ['name' => 'Shift Edit', 'category' => 'Master Data', 'group_name' => 'MASTER DATA SHIFT', 'description' => 'Ability to edit work shift and toggle status'],
            ['name' => 'Shift Destroy', 'category' => 'Master Data', 'group_name' => 'MASTER DATA SHIFT', 'description' => 'Ability to delete work shift'],
            ['name' => 'Attendance Status List', 'category' => 'Master Data', 'group_name' => 'STATUS KEHADIRAN', 'description' => 'Ability to view attendance status codes'],
            ['name' => 'Attendance Status Create', 'category' => 'Master Data', 'group_name' => 'STATUS KEHADIRAN', 'description' => 'Ability to create attendance status code'],
            ['name' => 'Attendance Status Edit', 'category' => 'Master Data', 'group_name' => 'STATUS KEHADIRAN', 'description' => 'Ability to edit attendance status code and badge color'],
            ['name' => 'Attendance Status Destroy', 'category' => 'Master Data', 'group_name' => 'STATUS KEHADIRAN', 'description' => 'Ability to delete custom attendance status'],
            ['name' => 'User Schedule List', 'category' => 'Master Data', 'group_name' => 'JADWAL KERJA (ROSTER)', 'description' => 'Ability to view employee monthly roster matrix'],
            ['name' => 'User Schedule Manage', 'category' => 'Master Data', 'group_name' => 'JADWAL KERJA (ROSTER)', 'description' => 'Ability to assign shifts, generate patterns, and reset roster'],
            ['name' => 'Shift Pattern List', 'category' => 'Master Data', 'group_name' => 'POLA SHIFT (PATTERN)', 'description' => 'Ability to view shift rotation pattern templates'],
            ['name' => 'Shift Pattern Create', 'category' => 'Master Data', 'group_name' => 'POLA SHIFT (PATTERN)', 'description' => 'Ability to create shift rotation pattern template'],
            ['name' => 'Shift Pattern Edit', 'category' => 'Master Data', 'group_name' => 'POLA SHIFT (PATTERN)', 'description' => 'Ability to edit shift rotation pattern cycle days'],
            ['name' => 'Shift Pattern Destroy', 'category' => 'Master Data', 'group_name' => 'POLA SHIFT (PATTERN)', 'description' => 'Ability to delete shift rotation pattern template'],
            ['name' => 'Setting List', 'category' => 'Master Data', 'group_name' => 'PENGATURAN DROPDOWN', 'description' => 'Ability to view dropdown options'],
            ['name' => 'Setting Create', 'category' => 'Master Data', 'group_name' => 'PENGATURAN DROPDOWN', 'description' => 'Ability to add new dropdown option'],
            ['name' => 'Setting Destroy', 'category' => 'Master Data', 'group_name' => 'PENGATURAN DROPDOWN', 'description' => 'Ability to delete dropdown option'],

            // 8. Z1 APP (4 Permissions)
            ['name' => 'Z1 App Access', 'category' => 'Z1 APP', 'group_name' => 'Z1 APP PORTAL', 'description' => 'Ability to access Z1 App mobile employee portal'],
            ['name' => 'Z1 App Attendance', 'category' => 'Z1 APP', 'group_name' => 'Z1 APP PORTAL', 'description' => 'Ability to Clock In and Clock Out attendance in Z1 App'],
            ['name' => 'Z1 App Purchase Request', 'category' => 'Z1 APP', 'group_name' => 'Z1 APP PORTAL', 'description' => 'Ability to submit and manage purchase requests in Z1 App'],
            ['name' => 'Z1 App Sales Report', 'category' => 'Z1 APP', 'group_name' => 'Z1 APP PORTAL', 'description' => 'Ability to view own staff sales performance report in Z1 App'],

            // 9. Z1 Admin (18 Permissions)
            ['name' => 'Dashboard List', 'category' => 'Z1 Admin', 'group_name' => 'DASHBOARD', 'description' => 'Ability to view dashboard and analytics charts'],
            ['name' => 'User List', 'category' => 'Z1 Admin', 'group_name' => 'USER MANAGEMENT', 'description' => 'Ability to view list of system users'],
            ['name' => 'User Create', 'category' => 'Z1 Admin', 'group_name' => 'USER MANAGEMENT', 'description' => 'Ability to view add user form'],
            ['name' => 'User Show', 'category' => 'Z1 Admin', 'group_name' => 'USER MANAGEMENT', 'description' => 'Ability to view user details'],
            ['name' => 'User Edit', 'category' => 'Z1 Admin', 'group_name' => 'USER MANAGEMENT', 'description' => 'Ability to view edit user form'],
            ['name' => 'User Store', 'category' => 'Z1 Admin', 'group_name' => 'USER MANAGEMENT', 'description' => 'Ability to store new user'],
            ['name' => 'User Update', 'category' => 'Z1 Admin', 'group_name' => 'USER MANAGEMENT', 'description' => 'Ability to update user details'],
            ['name' => 'User Destroy', 'category' => 'Z1 Admin', 'group_name' => 'USER MANAGEMENT', 'description' => 'Ability to delete a user account'],
            ['name' => 'Role List', 'category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION', 'description' => 'Ability to view list of roles'],
            ['name' => 'Role Create', 'category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION', 'description' => 'Ability to create new roles'],
            ['name' => 'Role Show', 'category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION', 'description' => 'Ability to view role details'],
            ['name' => 'Role Edit', 'category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION', 'description' => 'Ability to edit role details'],
            ['name' => 'Role Store', 'category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION', 'description' => 'Ability to store new role'],
            ['name' => 'Role Update', 'category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION', 'description' => 'Ability to assign permissions to a role'],
            ['name' => 'Role Destroy', 'category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION', 'description' => 'Ability to delete custom roles'],
            ['name' => 'Permission List', 'category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION', 'description' => 'Ability to list system permission keys'],
            ['name' => 'Permission Create', 'category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION', 'description' => 'Ability to add custom permissions'],
            ['name' => 'Permission Destroy', 'category' => 'Z1 Admin', 'group_name' => 'ROLE & PERMISSION', 'description' => 'Ability to delete custom permission keys'],
        ];

        $permissionInstances = [];
        foreach ($permissions as $pData) {
            $permissionInstances[$pData['name']] = \App\Models\Permission::firstOrCreate(['name' => $pData['name']], $pData);
        }

        // Seed Roles
        $superAdminRole = \App\Models\Role::firstOrCreate(
            ['name' => 'Super Admin'],
            ['description' => 'Full access to all modules and configurations']
        );
        $managerRole = \App\Models\Role::firstOrCreate(
            ['name' => 'Manager'],
            ['description' => 'Manage inventory list, view movements, attendance, shifts, and log stock in/out']
        );
        $staffRole = \App\Models\Role::firstOrCreate(
            ['name' => 'Staff'],
            ['description' => 'Log stock in/out, view records, and access Z1 App employee portal']
        );

        // Map Permissions to Roles
        $superAdminRole->permissions()->sync(array_values(array_map(fn($p) => $p->id, $permissionInstances)));
        
        $managerPermNames = [
            'Dashboard List',
            'Inventory List', 'Inventory Create', 'Inventory Show', 'Inventory Edit', 'Inventory Store', 'Inventory Update', 'Inventory Destroy',
            'Stock In List', 'Stock In Create', 'Stock In Store', 'Stock In Destroy',
            'Stock Out List', 'Stock Out Create', 'Stock Out Store', 'Stock Out Destroy',
            'Movement List', 'Movement Show', 'Movement Destroy',
            'Sales List', 'Sales Create', 'Sales Show', 'Sales Destroy',
            'Report List', 'Report SKU', 'Report Category', 'Report Staff',
            'Expense List', 'Expense Create', 'Expense Edit', 'Expense Destroy',
            'Income List', 'Income Create', 'Income Edit', 'Income Destroy',
            'Setting List', 'Setting Create', 'Setting Destroy',
            'Accounting CoA List', 'Accounting CoA Create', 'Accounting CoA Edit', 'Accounting CoA Delete',
            'Accounting Journal List', 'Accounting Journal Create', 'Accounting Ledger View',
            'Accounting Report View', 'Accounting Transfer Manage', 'Accounting Payable Manage', 'Accounting Period Manage',
            'Attendance List', 'Attendance Export', 'Attendance Import',
            'Payroll List', 'Payroll Manage', 'Payroll Setting',
            'Shift List', 'Shift Create', 'Shift Edit', 'Shift Destroy',
            'Attendance Status List', 'Attendance Status Create', 'Attendance Status Edit', 'Attendance Status Destroy',
            'User Schedule List', 'User Schedule Manage',
            'Shift Pattern List', 'Shift Pattern Create', 'Shift Pattern Edit', 'Shift Pattern Destroy',
            'Z1 App Access', 'Z1 App Attendance', 'Z1 App Purchase Request', 'Z1 App Sales Report',
        ];

        $managerPermIds = [];
        foreach ($managerPermNames as $mpName) {
            if (isset($permissionInstances[$mpName])) {
                $managerPermIds[] = $permissionInstances[$mpName]->id;
            }
        }
        $managerRole->permissions()->sync($managerPermIds);
        
        $staffPermNames = [
            'Dashboard List',
            'Stock In List', 'Stock In Create', 'Stock In Store',
            'Stock Out List', 'Stock Out Create', 'Stock Out Store',
            'Movement List', 'Movement Show',
            'Sales List', 'Sales Create', 'Sales Show',
            'Z1 App Access', 'Z1 App Attendance', 'Z1 App Purchase Request', 'Z1 App Sales Report',
        ];

        $staffPermIds = [];
        foreach ($staffPermNames as $spName) {
            if (isset($permissionInstances[$spName])) {
                $staffPermIds[] = $permissionInstances[$spName]->id;
            }
        }
        $staffRole->permissions()->sync($staffPermIds);

        // Seed Stores
        $storeBandung = \App\Models\Store::firstOrCreate(
            ['name' => 'Toko Bandung'],
            [
                'address' => 'Jl. Merdeka No. 10, Bandung',
                'phone' => '022-1234567',
            ]
        );

        $storeJakarta = \App\Models\Store::firstOrCreate(
            ['name' => 'Toko Jakarta'],
            [
                'address' => 'Jl. Sudirman No. 20, Jakarta',
                'phone' => '021-7654321',
            ]
        );

        // Seed Users
        $superAdminUser = User::firstOrCreate(
            ['email' => 'admin@inventrack.com'],
            [
                'name' => 'Super Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
            ]
        );
        $superAdminUser->roles()->sync([$superAdminRole->id]);

        $managerUser = User::firstOrCreate(
            ['email' => 'manager@inventrack.com'],
            [
                'name' => 'Manager Reynaldi',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
            ]
        );
        $managerUser->roles()->sync([$managerRole->id]);

        $staffUser = User::firstOrCreate(
            ['email' => 'staff@inventrack.com'],
            [
                'name' => 'Staff Warehouse',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
            ]
        );
        $staffUser->roles()->sync([$staffRole->id]);

        // Link Users to Stores
        $superAdminUser->stores()->sync([$storeBandung->id, $storeJakarta->id]);
        $managerUser->stores()->sync([$storeBandung->id]);
        $staffUser->stores()->sync([$storeJakarta->id]);

        // 1. Seed Products
        $products = [
            [
                'sku' => 'IND-901-HB',
                'name' => 'Precision Hex Bolt M8',
                'description' => 'High durability precision hex bolts for industrial assemblies.',
                'purchase_price' => 2000,
                'selling_price' => 3500,
                'stock' => 1500,
                'min_stock' => 200,
                'store_id' => $storeBandung->id,
            ],
            [
                'sku' => 'IND-112-FL',
                'name' => 'Industrial Filter S3',
                'description' => 'Replacement cartridge for heavy-duty fluid filter housing.',
                'purchase_price' => 45000,
                'selling_price' => 60000,
                'stock' => 120,
                'min_stock' => 15,
                'store_id' => $storeBandung->id,
            ],
            [
                'sku' => 'CAS-443-NY',
                'name' => 'Nylon Casing - Alpha',
                'description' => 'Lightweight protective outer casing made of premium nylon.',
                'purchase_price' => 25000,
                'selling_price' => 40000,
                'stock' => 1200,
                'min_stock' => 100,
                'store_id' => $storeBandung->id,
            ],
            [
                'sku' => 'ELE-778-LM',
                'name' => 'LED Control Module',
                'description' => 'Electronic control unit for LED lighting array assemblies.',
                'purchase_price' => 125000,
                'selling_price' => 150000,
                'stock' => 8,
                'min_stock' => 20,
                'store_id' => $storeJakarta->id,
            ],
            [
                'sku' => 'CHM-120-TP',
                'name' => 'Thermal Paste (Bulk)',
                'description' => 'High thermal conductivity paste for heat sink applications.',
                'purchase_price' => 35000,
                'selling_price' => 50000,
                'stock' => 50,
                'min_stock' => 10,
                'store_id' => $storeJakarta->id,
            ],
            [
                'sku' => 'IND-302-HB',
                'name' => 'Heavy Duty Bracket',
                'description' => 'Steel support brackets for mounting heavy instrumentation.',
                'purchase_price' => 15000,
                'selling_price' => 25000,
                'stock' => 5,
                'min_stock' => 10,
                'store_id' => $storeJakarta->id,
            ],
            [
                'sku' => 'CHM-889-IL',
                'name' => 'Industrial Lubricant',
                'description' => 'Synthetic gear and bearing oil for extreme load profiles.',
                'purchase_price' => 50000,
                'selling_price' => 75000,
                'stock' => 3,
                'min_stock' => 10,
                'store_id' => $storeJakarta->id,
            ]
        ];

        $insertedProducts = [];
        foreach ($products as $prodData) {
            $insertedProducts[$prodData['sku']] = Product::firstOrCreate(['sku' => $prodData['sku']], $prodData);
        }

        // 2. Seed Stock Movements (Removed dummy data per user request)

        // 3. Seed Shift and Attendance Statuses
        $this->call(ShiftAndAttendanceStatusSeeder::class);
    }
}
