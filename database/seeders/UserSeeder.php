<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'nama'       => 'Admin Dispatcher',
                'username'   => 'admin',
                'password'   => Hash::make('123456'),
                'id_role'    => 2,
                'jabatan'    => 'Dispatcher',
                'created_at' => now(),
            ],
            [
                'nama'       => 'Budi Santoso',
                'username'   => 'budi',
                'password'   => Hash::make('123456'),
                'id_role'    => 1,
                'jabatan'    => 'Staff',
                'created_at' => now(),
            ],
            [
                'nama'       => 'Siti Aminah',
                'username'   => 'siti',
                'password'   => Hash::make('123456'),
                'id_role'    => 1,
                'jabatan'    => 'Supervisor',
                'created_at' => now(),
            ],
            [
                'nama'       => 'Anton Driver',
                'username'   => 'anton',
                'password'   => Hash::make('123456'),
                'id_role'    => 3,
                'jabatan'    => 'Driver',
                'created_at' => now(),
            ],
        ]);
    }
}
