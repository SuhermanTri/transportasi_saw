<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KriteriaSeeder extends Seeder
{
    public function run(): void
    {
        // 3 kriteria SAW aktif: Jabatan (benefit), Urgensi (benefit), Gage Kendaraan (benefit)
        DB::table('kriteria')->insert([
            ['nama_kriteria' => 'Jabatan',        'jenis' => 'benefit', 'bobot' => null],
            ['nama_kriteria' => 'Urgensi',         'jenis' => 'benefit', 'bobot' => null],
            ['nama_kriteria' => 'Gage Kendaraan',  'jenis' => 'benefit', 'bobot' => null],
        ]);
    }
}
