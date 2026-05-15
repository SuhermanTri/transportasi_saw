<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            ['id_role' => 1, 'nama_role' => 'user'],
            ['id_role' => 2, 'nama_role' => 'dispatcher'],
            ['id_role' => 3, 'nama_role' => 'driver'],
        ]);
    }
}
