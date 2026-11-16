<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            // Master Data: Pengaturan Dropdown
            [
                'name' => 'Setting List',
                'category' => 'Master Data',
                'group_name' => 'PENGATURAN DROPDOWN',
                'description' => 'Kemampuan melihat daftar opsi dropdown pengaturan master data',
            ],
            [
                'name' => 'Setting Create',
                'category' => 'Master Data',
                'group_name' => 'PENGATURAN DROPDOWN',
                'description' => 'Kemampuan menambah opsi dropdown master data baru',
            ],
            [
                'name' => 'Setting Destroy',
                'category' => 'Master Data',
                'group_name' => 'PENGATURAN DROPDOWN',
                'description' => 'Kemampuan menghapus opsi dropdown master data',
            ],

            // Commercial: Kehadiran
            [
                'name' => 'Attendance List',
                'category' => 'Kehadiran',
                'group_name' => 'KEHADIRAN & ABSENSI',
                'description' => 'Kemampuan melihat rekap data kehadiran dan absensi karyawan',
            ],
            [
                'name' => 'Attendance Export',
                'category' => 'Kehadiran',
                'group_name' => 'KEHADIRAN & ABSENSI',
                'description' => 'Kemampuan mengunduh data absensi staf ke file spreadsheet',
            ],
            [
                'name' => 'Attendance Import',
                'category' => 'Kehadiran',
                'group_name' => 'KEHADIRAN & ABSENSI',
                'description' => 'Kemampuan mengunggah/mengimpor data absensi karyawan',
            ],

            // Master Data Shift: Shift
            [
                'name' => 'Shift List',
                'category' => 'Master Data Shift',
                'group_name' => 'MASTER DATA SHIFT',
                'description' => 'Kemampuan melihat daftar master jam kerja shift',
            ],
            [
                'name' => 'Shift Create',
                'category' => 'Master Data Shift',
                'group_name' => 'MASTER DATA SHIFT',
                'description' => 'Kemampuan menambahkan shift jam kerja baru',
            ],
            [
                'name' => 'Shift Edit',
                'category' => 'Master Data Shift',
                'group_name' => 'MASTER DATA SHIFT',
                'description' => 'Kemampuan mengubah jam kerja shift dan mengaktifkan/menonaktifkan shift',
            ],
            [
                'name' => 'Shift Destroy',
                'category' => 'Master Data Shift',
                'group_name' => 'MASTER DATA SHIFT',
                'description' => 'Kemampuan menghapus data master shift kerja',
            ],

            // Master Data Shift: Status Kehadiran
            [
                'name' => 'Attendance Status List',
                'category' => 'Master Data Shift',
                'group_name' => 'STATUS KEHADIRAN',
                'description' => 'Kemampuan melihat daftar kode dan label status kehadiran',
            ],
            [
                'name' => 'Attendance Status Create',
                'category' => 'Master Data Shift',
                'group_name' => 'STATUS KEHADIRAN',
                'description' => 'Kemampuan menambahkan kode status kehadiran baru',
            ],
            [
                'name' => 'Attendance Status Edit',
                'category' => 'Master Data Shift',
                'group_name' => 'STATUS KEHADIRAN',
                'description' => 'Kemampuan mengubah kode status, warna badge, dan status aktif',
            ],
            [
                'name' => 'Attendance Status Destroy',
                'category' => 'Master Data Shift',
                'group_name' => 'STATUS KEHADIRAN',
                'description' => 'Kemampuan menghapus kode status kehadiran kustom',
            ],

            // Master Data Shift: User Schedule
            [
                'name' => 'User Schedule List',
                'category' => 'Master Data Shift',
                'group_name' => 'JADWAL KERJA (ROSTER)',
                'description' => 'Kemampuan melihat matriks jadwal kerja bulanan karyawan',
            ],
            [
                'name' => 'User Schedule Manage',
                'category' => 'Master Data Shift',
                'group_name' => 'JADWAL KERJA (ROSTER)',
                'description' => 'Kemampuan menetapkan shift harian, generate pola otomatis, dan reset jadwal',
            ],

            // Master Data Shift: Shift Pattern
            [
                'name' => 'Shift Pattern List',
                'category' => 'Master Data Shift',
                'group_name' => 'POLA SHIFT (PATTERN)',
                'description' => 'Kemampuan melihat daftar template pola rotasi shift kerja',
            ],
            [
                'name' => 'Shift Pattern Create',
                'category' => 'Master Data Shift',
                'group_name' => 'POLA SHIFT (PATTERN)',
                'description' => 'Kemampuan membuat template pola siklus rotasi shift baru',
            ],
            [
                'name' => 'Shift Pattern Edit',
                'category' => 'Master Data Shift',
                'group_name' => 'POLA SHIFT (PATTERN)',
                'description' => 'Kemampuan mengubah rincian hari kerja dan off pada pola shift',
            ],
            [
                'name' => 'Shift Pattern Destroy',
                'category' => 'Master Data Shift',
                'group_name' => 'POLA SHIFT (PATTERN)',
                'description' => 'Kemampuan menghapus template pola rotasi shift',
            ],

            // Z1 App: Portal Karyawan
            [
                'name' => 'Z1 App Access',
                'category' => 'Z1 App',
                'group_name' => 'Z1 APP PORTAL',
                'description' => 'Kemampuan mengakses portal mobile karyawan Z1 App',
            ],
            [
                'name' => 'Z1 App Attendance',
                'category' => 'Z1 App',
                'group_name' => 'Z1 APP PORTAL',
                'description' => 'Kemampuan melakukan Clock In dan Clock Out kehadiran pada Z1 App',
            ],
            [
                'name' => 'Z1 App Purchase Request',
                'category' => 'Z1 App',
                'group_name' => 'Z1 APP PORTAL',
                'description' => 'Kemampuan mengajukan dan mengelola pesanan barang purchase request di Z1 App',
            ],
            [
                'name' => 'Z1 App Sales Report',
                'category' => 'Z1 App',
                'group_name' => 'Z1 APP PORTAL',
                'description' => 'Kemampuan melihat laporan dan performa penjualan staf sendiri di Z1 App',
            ],
        ];

        $superAdmin = Role::where('name', 'Super Admin')->first();
        $manager = Role::where('name', 'Manager')->first();
        $staff = Role::where('name', 'Staff')->first();

        $staffPermissionNames = [
            'Z1 App Access',
            'Z1 App Attendance',
            'Z1 App Purchase Request',
            'Z1 App Sales Report',
        ];

        foreach ($permissions as $pData) {
            $permission = Permission::firstOrCreate(
                ['name' => $pData['name']],
                $pData
            );

            // Give all to Super Admin
            if ($superAdmin && !$superAdmin->permissions->contains('id', $permission->id)) {
                $superAdmin->permissions()->attach($permission->id);
            }

            // Give all to Manager
            if ($manager && !$manager->permissions->contains('id', $permission->id)) {
                $manager->permissions()->attach($permission->id);
            }

            // Give Z1 App to Staff
            if ($staff && in_array($permission->name, $staffPermissionNames)) {
                if (!$staff->permissions->contains('id', $permission->id)) {
                    $staff->permissions()->attach($permission->id);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', [
            'Setting List',
            'Setting Create',
            'Setting Destroy',
            'Attendance List',
            'Attendance Export',
            'Attendance Import',
            'Shift List',
            'Shift Create',
            'Shift Edit',
            'Shift Destroy',
            'Attendance Status List',
            'Attendance Status Create',
            'Attendance Status Edit',
            'Attendance Status Destroy',
            'User Schedule List',
            'User Schedule Manage',
            'Shift Pattern List',
            'Shift Pattern Create',
            'Shift Pattern Edit',
            'Shift Pattern Destroy',
            'Z1 App Access',
            'Z1 App Attendance',
            'Z1 App Purchase Request',
            'Z1 App Sales Report',
        ])->delete();
    }
};
