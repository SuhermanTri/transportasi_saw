<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerbandinganKriteriaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('perbandingan_kriteria')->insert([
            ['kriteria1' => 1, 'kriteria2' => 2, 'nilai' => 3],
            ['kriteria1' => 1, 'kriteria2' => 3, 'nilai' => 4],
            ['kriteria1' => 1, 'kriteria2' => 4, 'nilai' => 5],
            ['kriteria1' => 2, 'kriteria2' => 3, 'nilai' => 2],
            ['kriteria1' => 2, 'kriteria2' => 4, 'nilai' => 3],
            ['kriteria1' => 3, 'kriteria2' => 4, 'nilai' => 2],
        ]);
    }
}
