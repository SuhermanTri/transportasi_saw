<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('hasil_saw')->truncate();
        DB::table('perbandingan_kriteria')->truncate();
        DB::table('alternatif_penilaian')->truncate();
        DB::table('kriteria')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Seed 3 kriteria baru sesuai proposal
        DB::table('kriteria')->insert([
            ['nama_kriteria' => 'Jabatan',         'jenis' => 'benefit', 'bobot' => null],
            ['nama_kriteria' => 'Urgensi',          'jenis' => 'benefit', 'bobot' => null],
            ['nama_kriteria' => 'Gage Kendaraan',   'jenis' => 'benefit', 'bobot' => null],
        ]);
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('kriteria')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
