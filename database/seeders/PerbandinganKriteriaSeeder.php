<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerbandinganKriteriaSeeder extends Seeder
{
    public function run(): void
    {
        // 3 perbandingan berpasangan untuk 3 kriteria: Jabatan(1), Urgensi(2), Gage(3)
        // Jabatan > Urgensi (3x), Jabatan >> Gage (5x), Urgensi > Gage (3x)
        DB::table('perbandingan_kriteria')->insert([
            ['kriteria1' => 1, 'kriteria2' => 2, 'nilai' => 3],
            ['kriteria1' => 1, 'kriteria2' => 3, 'nilai' => 5],
            ['kriteria1' => 2, 'kriteria2' => 3, 'nilai' => 3],
        ]);
    }
}
