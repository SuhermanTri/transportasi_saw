<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KriteriaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('kriteria')->insert([
            ['nama_kriteria' => 'Prioritas',          'jenis' => 'benefit', 'bobot' => null],
            ['nama_kriteria' => 'Jabatan',             'jenis' => 'benefit', 'bobot' => null],
            ['nama_kriteria' => 'Jumlah_penumpang',   'jenis' => 'benefit', 'bobot' => null],
            ['nama_kriteria' => 'Jarak',              'jenis' => 'cost',    'bobot' => null],
        ]);
    }
}
