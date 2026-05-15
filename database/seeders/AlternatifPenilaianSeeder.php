<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlternatifPenilaianSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // Kendaraan 1 (Toyota Innova)
            ['id_kendaraan' => 1, 'id_kriteria' => 1, 'nilai' => 4],
            ['id_kendaraan' => 1, 'id_kriteria' => 2, 'nilai' => 5],
            ['id_kendaraan' => 1, 'id_kriteria' => 3, 'nilai' => 7],
            ['id_kendaraan' => 1, 'id_kriteria' => 4, 'nilai' => 6],
            // Kendaraan 2 (Toyota Avanza)
            ['id_kendaraan' => 2, 'id_kriteria' => 1, 'nilai' => 3],
            ['id_kendaraan' => 2, 'id_kriteria' => 2, 'nilai' => 4],
            ['id_kendaraan' => 2, 'id_kriteria' => 3, 'nilai' => 5],
            ['id_kendaraan' => 2, 'id_kriteria' => 4, 'nilai' => 7],
            // Kendaraan 3 (Mitsubishi Pajero)
            ['id_kendaraan' => 3, 'id_kriteria' => 1, 'nilai' => 5],
            ['id_kendaraan' => 3, 'id_kriteria' => 2, 'nilai' => 5],
            ['id_kendaraan' => 3, 'id_kriteria' => 3, 'nilai' => 7],
            ['id_kendaraan' => 3, 'id_kriteria' => 4, 'nilai' => 5],
        ];

        DB::table('alternatif_penilaian')->insert($data);
    }
}
