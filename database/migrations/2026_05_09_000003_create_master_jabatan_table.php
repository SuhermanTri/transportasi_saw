<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_jabatan', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama_jabatan', 100);
            $table->unsignedTinyInteger('nilai_saw')->comment('Nilai SAW 1-5, semakin tinggi semakin prioritas');
            $table->string('keterangan', 255)->nullable();
        });

        // Seed data awal
        DB::table('master_jabatan')->insert([
            ['nama_jabatan' => 'Direktur',        'nilai_saw' => 5, 'keterangan' => 'Level eksekutif tertinggi'],
            ['nama_jabatan' => 'General Manager', 'nilai_saw' => 5, 'keterangan' => 'Level GM'],
            ['nama_jabatan' => 'Manager',         'nilai_saw' => 4, 'keterangan' => 'Level manajer departemen'],
            ['nama_jabatan' => 'Supervisor',      'nilai_saw' => 3, 'keterangan' => 'Level supervisor'],
            ['nama_jabatan' => 'Staff',           'nilai_saw' => 2, 'keterangan' => 'Level staf'],
            ['nama_jabatan' => 'Lainnya',         'nilai_saw' => 1, 'keterangan' => 'Jabatan lain'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('master_jabatan');
    }
};
