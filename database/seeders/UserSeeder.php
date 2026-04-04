<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $kasirRole = Role::where('name', 'kasir')->first();

        // 1. Akun Admin Utama
        User::firstOrCreate(
            ['email' => 'admin@primadaya.com'],
            [
                'name' => 'Admin Primadaya',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );

        // 2. Akun Kasir Contoh
        User::firstOrCreate(
            ['email' => 'kasir@primadaya.com'],
            [
                'name' => 'Kasir 1',
                'password' => Hash::make('password'),
                'role_id' => $kasirRole->id,
                'is_active' => true,
            ]
        );
    }
}
