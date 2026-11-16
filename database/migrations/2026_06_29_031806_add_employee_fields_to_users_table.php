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
        Schema::table('users', function (Blueprint $table) {
            // Personal Info
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->text('address')->nullable();
            $table->string('nationality')->nullable();
            $table->string('religion')->nullable();
            $table->string('marital_status')->nullable();

            // Identity
            $table->string('id_number')->nullable();
            $table->string('tax_file_number')->nullable();
            $table->string('tax_registered_name')->nullable();
            $table->string('sim_name')->nullable();
            $table->string('sim_number')->nullable();
            $table->string('sim_type')->nullable();

            // Work
            $table->string('employee_number')->nullable();
            $table->string('status')->nullable();
            $table->date('join_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('position')->nullable();

            // Account
            $table->string('username')->nullable()->unique();
            $table->string('mobile_phone')->nullable();
        });

        // Migrate existing users (yang lama disesuaikan)
        // Using DB query to prevent model booting side-effects during migration
        $users = \Illuminate\Support\Facades\DB::table('users')->get();
        foreach ($users as $user) {
            $parts = explode(' ', $user->name);
            $firstName = $parts[0] ?? $user->name;
            $lastName = count($parts) > 1 ? end($parts) : '';
            $middleName = '';
            if (count($parts) > 2) {
                $middleName = implode(' ', array_slice($parts, 1, -1));
            }

            // Username generator
            $emailPrefix = preg_replace('/[^a-zA-Z0-9]/', '', strstr($user->email, '@', true));
            $username = $emailPrefix ?: strtolower($firstName);
            $originalUsername = $username;
            $counter = 1;
            while (\Illuminate\Support\Facades\DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $originalUsername . $counter;
                $counter++;
            }

            // Try to find a role for the user
            $roleUser = \Illuminate\Support\Facades\DB::table('role_user')->where('user_id', $user->id)->first();
            $roleName = 'Staff';
            if ($roleUser) {
                $role = \Illuminate\Support\Facades\DB::table('roles')->where('id', $roleUser->role_id)->first();
                if ($role) {
                    $roleName = $role->name;
                }
            }

            \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update([
                'first_name' => $firstName,
                'middle_name' => $middleName ?: null,
                'last_name' => $lastName ?: null,
                'birth_place' => 'Bandung',
                'birth_date' => '1995-01-01',
                'gender' => 'Male',
                'address' => 'Jl. Raya No. 1',
                'nationality' => 'WNI',
                'religion' => 'Islam',
                'marital_status' => 'Single',
                'id_number' => '32' . str_pad($user->id, 14, '0', STR_PAD_LEFT),
                'employee_number' => 'EMP-' . str_pad($user->id, 5, '0', STR_PAD_LEFT),
                'status' => 'Permanent',
                'join_date' => $user->created_at ? date('Y-m-d', strtotime($user->created_at)) : date('Y-m-d'),
                'position' => $roleName,
                'username' => $username,
                'mobile_phone' => '0812345678' . str_pad($user->id, 2, '0', STR_PAD_LEFT),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'middle_name', 'last_name',
                'birth_place', 'birth_date', 'gender', 'address', 'nationality', 'religion', 'marital_status',
                'id_number', 'tax_file_number', 'tax_registered_name', 'sim_name', 'sim_number', 'sim_type',
                'employee_number', 'status', 'join_date', 'end_date', 'position',
                'username', 'mobile_phone'
            ]);
        });
    }
};
