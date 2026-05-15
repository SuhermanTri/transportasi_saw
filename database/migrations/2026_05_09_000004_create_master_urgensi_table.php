<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_urgensi', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama_urgensi', 100);
            $table->unsignedTinyInteger('nilai_saw')->comment('Nilai SAW 1-5, semakin tinggi semakin urgen');
            $table->string('keterangan', 255)->nullable();
        });

        DB::table('master_urgensi')->insert([
            ['nama_urgensi' => 'Darurat / Emergency',  'nilai_saw' => 5, 'keterangan' => 'Kebutuhan mendesak, tidak bisa ditunda'],
            ['nama_urgensi' => 'Rapat Pimpinan',       'nilai_saw' => 4, 'keterangan' => 'Rapat tingkat direksi atau manajemen'],
            ['nama_urgensi' => 'Dinas Luar Kota',      'nilai_saw' => 4, 'keterangan' => 'Perjalanan dinas resmi ke luar kota'],
            ['nama_urgensi' => 'Kunjungan Klien',      'nilai_saw' => 3, 'keterangan' => 'Kunjungan ke klien atau mitra bisnis'],
            ['nama_urgensi' => 'Rapat Rutin',          'nilai_saw' => 3, 'keterangan' => 'Rapat koordinasi rutin'],
            ['nama_urgensi' => 'Dinas Dalam Kota',     'nilai_saw' => 2, 'keterangan' => 'Perjalanan dinas biasa dalam kota'],
            ['nama_urgensi' => 'Lainnya',              'nilai_saw' => 1, 'keterangan' => 'Keperluan lain'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('master_urgensi');
    }
};
