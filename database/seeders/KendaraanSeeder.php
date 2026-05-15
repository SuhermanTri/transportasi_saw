<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KendaraanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('kendaraan')->insert([
            [
                'nama_kendaraan' => 'Toyota Innova',
                'no_polisi'      => 'B2487BXY',
                'nama_driver'    => 'Anton',
                'no_hp_driver'   => '08123456789',
                'kapasitas'      => 7,
                'status'         => 'tersedia',
                'tahun'          => 2020,
                'warna'          => 'Hitam',
            ],
            [
                'nama_kendaraan' => 'Toyota Avanza',
                'no_polisi'      => 'B3456CD',
                'nama_driver'    => 'Zainal',
                'no_hp_driver'   => '08123456780',
                'kapasitas'      => 5,
                'status'         => 'tersedia',
                'tahun'          => 2019,
                'warna'          => 'Silver',
            ],
            [
                'nama_kendaraan' => 'Mitsubishi Pajero',
                'no_polisi'      => 'B1234EF',
                'nama_driver'    => 'Rudi',
                'no_hp_driver'   => '08129876543',
                'kapasitas'      => 7,
                'status'         => 'tersedia',
                'tahun'          => 2021,
                'warna'          => 'Putih',
            ],
        ]);
    }
}
